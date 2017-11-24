<?php

namespace Drupal\workspace\Replication;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\workspace\Changes\ChangesFactoryInterface;
use Drupal\workspace\Entity\ContentWorkspace;
use Drupal\workspace\Entity\ReplicationLog;
use Drupal\workspace\Entity\ReplicationLogInterface;
use Drupal\workspace\Entity\Workspace;
use Drupal\workspace\Index\SequenceIndexInterface;
use Drupal\workspace\UpstreamPluginInterface;
use Drupal\workspace\WorkspaceManager;
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
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

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
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(WorkspaceManagerInterface $workspace_manager, ChangesFactoryInterface $changes_factory, EntityTypeManagerInterface $entity_type_manager, SequenceIndexInterface $sequence_index, Connection $database) {
    $this->workspaceManager = $workspace_manager;
    $this->changesFactory = $changes_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->sequenceIndex = $sequence_index;
    $this->database = $database;
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
    $session_id = \md5((\microtime(TRUE) * 1000000));
    // @todo Figure out if we want to include more information in the
    // replication log ID.
    // @see http://docs.couchdb.org/en/2.0.0/replication/protocol.html#generate-replication-id
    $replication_id = hash('sha256', $source_workspace->id() . $target_workspace->id());

    // Load or create the Replication Log entity based on the replication ID.
    $replication_log = ReplicationLog::loadOrCreate($replication_id);

    // Get the current active workspace, so we can set it back as the active
    // after the replication has completed.
    $current_active = $this->workspaceManager->getActiveWorkspace(TRUE);

    // Set the source as the active workspace, so we can fetch all the entities
    // relative to the source workspace.
    $this->workspaceManager->setActiveWorkspace($source_workspace);

    // Get changes for the current workspace based on the sequence ID from the
    // last replication between this workspaces, if there is one.
    $changes = $this->changesFactory->get($source_workspace)->setLastSequenceId($this->getLastSequenceId($replication_log))->getChanges();

    // If there are no changes then there's no need to continue with the
    // replication.
    if (empty($changes)) {
      return $this->updateReplicationLog($replication_log, [
        'entity_write_failures' => 0,
        'entities_read' => 0,
        'entities_written' => 0,
        'end_last_sequence' => $this->sequenceIndex->useWorkspace($source_workspace->id())->getLastSequenceId(),
        'end_time' => (new \DateTime())->format('D, d M Y H:i:s e'),
        'recorded_sequence' => $this->sequenceIndex->useWorkspace($source_workspace->id())->getLastSequenceId(),
        'session_id' => $session_id,
        'start_last_sequence' => $this->getLastSequenceId($replication_log),
        'start_time' => $start_time->format('D, d M Y H:i:s e'),
      ]);
    }

    // Reduce the changes to an array of revision IDs, keyed by the entity type
    // ID.
    $changes = array_reduce($changes, function ($reduced, $change) {
      /** @var \Drupal\workspace\Changes\Change $change */
      $reduced[$change->getEntityTypeId()][] = $change->getRevisionId();
      return $reduced;
    });

    $content_workspace_ids = [];
    foreach ($changes as $entity_type_id => $revision_ids) {
      // Get all entity revision IDs for all entities which are in only one
      // of either the source or the target workspaces. We assume that this
      // means the revision is in the source, but not the target, and the
      // revision has not been replicated yet.
      $select = $this->database
        ->select('content_workspace_field_revision', 'cwfr')
        ->fields('cwfr', ['content_entity_revision_id']);
      $select->condition('content_entity_type_id', $entity_type_id);
      $select->condition('content_entity_revision_id', $revision_ids, 'IN');
      $select->condition('workspace', [$source_workspace->id(), $target_workspace->id()], 'IN');
      $select->groupBy('content_entity_revision_id');
      $select->having('count(workspace) < :workspaces', [':workspaces' => 2]);
      $revision_difference = $select->execute()->fetchCol();

      // Get the content workspace IDs for all of the entity revision IDs which
      // are not yet in the target workspace.
      $content_workspace_ids[$entity_type_id] = $this->entityTypeManager
        ->getStorage('content_workspace')
        ->getQuery()
        ->allRevisions()
        ->condition('content_entity_type_id', $entity_type_id)
        ->condition('content_entity_revision_id', $revision_difference, 'IN')
        ->condition('workspace', $source_workspace->id())
        ->execute();
    }

    $entities = [];
    foreach ($content_workspace_ids as $entity_type_id => $ids) {
      foreach ($ids as $revision_id => $entity_id) {
        // Get the content workspace entity for revision that is in the source
        // workspace.
        /** @var \Drupal\workspace\Entity\ContentWorkspaceInterface $content_workspace */
        $content_workspace = $this->entityTypeManager->getStorage('content_workspace')->loadRevision($revision_id);
        if (WorkspaceManager::DEFAULT_WORKSPACE === $target_workspace->id()) {
          // If the target workspace is the default workspace (generally 'live')
          // the revision needs to be set to the default revision.
          /** @var \Drupal\Core\Entity\ContentEntityInterface|\Drupal\Core\Entity\RevisionableInterface $entity */
          $entity = $this->entityTypeManager
            ->getStorage($content_workspace->content_entity_type_id->value)
            ->loadRevision($content_workspace->content_entity_revision_id->value);
          $entity->_isReplicating = TRUE;
          $entity->isDefaultRevision(TRUE);
          $entities[] = $entity;
        }
        else {
          // If the target workspace is not a default workspace the content
          // workspace link entity can simply be updated with the target
          // workspace.
          $content_workspace->setNewRevision(TRUE);
          $content_workspace->workspace->target_id = $target_workspace->id();
          // Use the updateOrCreateFormEntity() method to make sure the content
          // entity is not updated.
          // @todo: Look into if we need this method at all.
          ContentWorkspace::updateOrCreateFromEntity($content_workspace);
        }
      }
    }

    // Only switch to the target workspace and save entities if there are some
    // to save.
    if (!empty($entities)) {
      // Before saving set the active workspace to the target.
      $this->workspaceManager->setActiveWorkspace($target_workspace);
      // Save each revision on the target workspace.
      foreach ($entities as $entity) {
        $entity->save();
      }
    }

    // Switch back to the original active workspace, so that the user performing
    // the replication is back on the workspace they started on.
    $this->workspaceManager->setActiveWorkspace($current_active);

    // Update the replication log entity by adding the completed replication to
    // the history.
    return $this->updateReplicationLog($replication_log, [
      'entity_write_failures' => 0,
      'entities_read' => count($changes),
      'entities_written' => count($revision_difference),
      'end_last_sequence' => $this->sequenceIndex->useWorkspace($target_workspace->id())->getLastSequenceId(),
      'end_time' => (new \DateTime())->format('D, d M Y H:i:s e'),
      'recorded_sequence' => $this->sequenceIndex->useWorkspace($target_workspace->id())->getLastSequenceId(),
      'session_id' => $session_id,
      'start_last_sequence' => $this->getLastSequenceId($replication_log),
      'start_time' => $start_time->format('D, d M Y H:i:s e'),
    ]);
  }

  /**
   * Gets the last sequence ID from a replication log entity.
   *
   * @param \Drupal\workspace\Entity\ReplicationLogInterface $replication_log
   *   The replication log entity to get the last sequence ID from.
   *
   * @return int
   *   The last sequence ID for the replication log.
   */
  protected function getLastSequenceId(ReplicationLogInterface $replication_log) {
    $history = $replication_log->getHistory();
    return isset($history[0]['recorded_sequence']) ? $history[0]['recorded_sequence'] : 0;
  }

  /**
   * Updates the replication log entity with the given history.
   *
   * @param \Drupal\workspace\Entity\ReplicationLogInterface $replication_log
   *   The replication log entity to be updated.
   * @param array $history
   *   The new history items.
   *
   * @return \Drupal\workspace\Entity\ReplicationLogInterface
   *   The updated replication log entity.
   */
  protected function updateReplicationLog(ReplicationLogInterface $replication_log, array $history) {
    $replication_log->setHistory($history);
    $replication_log->save();
    return $replication_log;
  }

}
