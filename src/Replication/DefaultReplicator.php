<?php

namespace Drupal\workspace\Replication;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\workspace\Changes\ChangesFactoryInterface;
use Drupal\workspace\Entity\ReplicationLog;
use Drupal\workspace\Entity\Workspace;
use Drupal\workspace\Index\SequenceIndexInterface;
use Drupal\workspace\UpstreamPluginInterface;
use Drupal\workspace\WorkspaceManagerInterface;

/**
 * Defines the default replicator service.
 *
 * This replicator synchronizes entity revisions between workspaces on the same
 * site.
 */
class DefaultReplicator implements ReplicationInterface {

  /**
   * The workspace manager.
   *
   * @var \Drupal\workspace\WorkspaceManagerInterface
   */
  protected $workspaceManager;

  /**
   * The changes factory.
   *
   * @var \Drupal\workspace\Changes\ChangesFactoryInterface
   */
  protected $changesFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The sequence index.
   *
   * @var \Drupal\workspace\Index\SequenceIndexInterface
   */
  protected $sequenceIndex;

  /**
   * Constructs the default replicator service.
   *
   * @param \Drupal\workspace\WorkspaceManagerInterface $workspace_manager
   *   The workspace manager.
   * @param \Drupal\workspace\Changes\ChangesFactoryInterface $changes_factory
   *   The changes factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\workspace\Index\SequenceIndexInterface $sequence_index
   *   The sequence index.
   */
  public function __construct(WorkspaceManagerInterface $workspace_manager, ChangesFactoryInterface $changes_factory, EntityTypeManagerInterface $entity_type_manager, SequenceIndexInterface $sequence_index) {
    $this->workspaceManager = $workspace_manager;
    $this->changesFactory = $changes_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->sequenceIndex = $sequence_index;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(UpstreamPluginInterface $source, UpstreamPluginInterface $target) {
    // This replicator service is only used if the source and the target are
    // workspaces on the local site.
    // @see \Drupal\workspace\Plugin\Upstream\LocalWorkspaceUpstream
    if ($source->getBaseId() === 'local_workspace' && $target->getBaseId() === 'local_workspace') {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function replicate(UpstreamPluginInterface $source, UpstreamPluginInterface $target) {
    // Replicating content from one workspace to another on the same site
    // roughly follows the CouchDB replication protocol.
    // @see http://docs.couchdb.org/en/2.1.0/replication/protocol.html
    $source_workspace = Workspace::load($source->getDerivativeId());
    $target_workspace = Workspace::load($target->getDerivativeId());
    $start_time = new \DateTime();
    $sessionId = \md5((\microtime(TRUE) * 1000000));
    // @todo Figure out if we want to include more information in the
    // replication log ID.
    // @see http://docs.couchdb.org/en/2.0.0/replication/protocol.html#generate-replication-id
    $replication_id = hash('sha256', $source_workspace->id() . $target_workspace->id());
    $replication_log = ReplicationLog::loadOrCreate($replication_id);

    $current_active = $this->workspaceManager->getActiveWorkspace(TRUE);

    // Set the source as the active workspace.
    $this->workspaceManager->setActiveWorkspace($source_workspace);

    // Get changes for the current workspace.
    $history = $replication_log->getHistory();
    $last_sequence_id = isset($history[0]['recorded_sequence']) ? $history[0]['recorded_sequence'] : 0;
    $changes = $this->changesFactory->get($source_workspace)->setLastSequenceId($last_sequence_id)->getChanges();
    $rev_diffs = [];
    /** @var \Drupal\workspace\Changes\Change $change */
    foreach ($changes as $change) {
      $rev_diffs[$change->getEntityTypeId()][] = $change->getRevisionId();
    }

    // Get revision diff between source and target
    $content_workspace_ids = [];
    foreach ($rev_diffs as $entity_type_id => $revs) {
      $content_workspace_ids[$entity_type_id] = $this->entityTypeManager
        ->getStorage('content_workspace')
        ->getQuery()
        ->allRevisions()
        ->condition('content_entity_type_id', $entity_type_id)
        ->condition('content_entity_revision_id', $revs, 'IN')
        ->condition('workspace', $target_workspace->id())
        ->execute();
    }
    foreach ($content_workspace_ids as $entity_type_id => $ids) {
      foreach ($ids as $id) {
        $key = array_search($id, $rev_diffs[$entity_type_id]);
        if (isset($key)) {
          unset($rev_diffs[$entity_type_id][$key]);
        }
      }
    }

    $entities = [];
    // Load each missing revision.
    foreach ($rev_diffs as $entity_type_id => $revs) {
      foreach ($revs as $rev) {
        /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
        $entity = $this->entityTypeManager
          ->getStorage($entity_type_id)
          ->loadRevision($rev);
        $entity->isDefaultRevision(TRUE);
        $entities[] = $entity;
      }
    }

    // Before saving set the active workspace to the target.
    $this->workspaceManager->setActiveWorkspace($target_workspace);

    // Save each revision on the target workspace
    foreach ($entities as $entity) {
      $entity->save();
    }

    // Log
    $this->workspaceManager->setActiveWorkspace($current_active);

    $replication_log->setHistory([
      'recorded_sequence' => $this->sequenceIndex->useWorkspace($source_workspace->id())->getLastSequenceId(),
      'start_time' => $start_time->format('D, d M Y H:i:s e'),
      'session_id' => $sessionId,
    ]);
    $replication_log->save();
    return $replication_log;
  }

}
