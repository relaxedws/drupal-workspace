<?php

namespace Drupal\workspace;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\workspace\Changes\ChangesFactoryInterface;
use Drupal\workspace\Entity\ReplicationLog;
use Drupal\workspace\Entity\WorkspaceInterface;
use Drupal\workspace\Index\SequenceIndexInterface;

/**
 * Class DefaultReplicator
 */
class DefaultReplicator {

  /**
   * @var WorkspaceManagerInterface
   */
  protected $workspaceManager;

  /**
   * @var \Drupal\workspace\Changes\ChangesFactoryInterface
   */
  protected $changesFactory;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\workspace\Index\SequenceIndexInterface
   */
  protected $sequenceIndex;

  /**
   * DefaultReplication constructor.
   *
   * @param \Drupal\workspace\WorkspaceManagerInterface $workspace_manager
   * @param \Drupal\workspace\Changes\ChangesFactoryInterface $changes_factory
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct(WorkspaceManagerInterface $workspace_manager, ChangesFactoryInterface $changes_factory, EntityTypeManagerInterface $entity_type_manager, SequenceIndexInterface $sequence_index) {
    $this->workspaceManager = $workspace_manager;
    $this->changesFactory = $changes_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->sequenceIndex = $sequence_index;
  }

  /**
   * @param \Drupal\workspace\Entity\WorkspaceInterface $source
   * @param \Drupal\workspace\Entity\WorkspaceInterface $target
   *
   * @return \Drupal\workspace\Entity\ReplicationLogInterface
   */
  public function replication(WorkspaceInterface $source, WorkspaceInterface $target) {
    $replication_id = \md5($source->id() . $target->id());
    $start_time = new \DateTime();
    $sessionId = \md5((\microtime(true) * 1000000));
    $replication_log = ReplicationLog::loadOrCreate($replication_id);
    $current_active = $this->workspaceManager->getActiveWorkspace(TRUE);

    // Set the source as the active workspace.
    $this->workspaceManager->setActiveWorkspace($source);

    // Get changes for the current workspace.
    $history = $replication_log->getHistory();
    $last_seq = isset($history[0]['recorded_seq']) ? $history[0]['recorded_seq'] : 0;
    $changes = $this->changesFactory->get($source)->lastSeq($last_seq)->getNormal();
    $rev_diffs = [];
    foreach ($changes as $change) {
      foreach ($change['changes'] as $change_item) {
        $rev_diffs[$change['type']][] = $change_item['rev'];
      }
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
        ->condition('workspace', $target->id())
        ->execute();
    }
    foreach ($content_workspace_ids as $entity_type_id => $ids) {

    }

    $entities = []; 
    // Load each missing revision.
    foreach ($rev_diffs as $entity_type_id => $revs) {
      foreach ($revs as $rev) {
        /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
        $entity = $this->entityTypeManager
          ->getStorage($entity_type_id)
          ->loadRevision($rev);
        $entity->workspace->target_id = $target->id();
        $entity->isDefaultRevision(($target->id() == \Drupal::getContainer()->getParameter('workspace.default')));
        $entities[] = $entity;
      }
    }

    // Before saving set the active workspace to the target.
    $this->workspaceManager->setActiveWorkspace($target);

    // Save each revision on the target workspace
    foreach ($entities as $entity) {
      $entity->save();
    }

    // Log
    $this->workspaceManager->setActiveWorkspace($current_active);

    $replication_log->setHistory([
      'recorded_seq' => $this->sequenceIndex->useWorkspace($source->id())->getLastSequenceId(),
      'start_time' => $start_time->format('D, d M Y H:i:s e'),
      'session_id' => $sessionId,
    ]);
    $replication_log->save();
    return $replication_log;
  }

}