<?php

/**
 * @file
 * Contains \Drupal\workspace\ReplicatorManager.
 */

namespace Drupal\workspace;
use Drupal\multiversion\Workspace\ConflictTrackerInterface;
use Drupal\replication\Entity\ReplicationLog;

/**
 * Provides the Replicator manager.
 */
class ReplicatorManager implements ReplicatorInterface {

  /** @var ReplicatorInterface[] */
  protected $replicators = [];

  /** @var  ConflictTrackerInterface */
  protected $conflictTracker;

  public function __construct(ConflictTrackerInterface $conflict_tracker) {
    $this->conflictTracker = $conflict_tracker;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(WorkspacePointerInterface $source, WorkspacePointerInterface $target) {
    return TRUE;
  }

  /**
   * Adds replication services.
   *
   * @param ReplicatorInterface $replicator
   */
  public function addReplicator(ReplicatorInterface $replicator) {
    $this->replicators[] = $replicator;
  }

  /**
   * {@inheritdoc}
   */
  public function replicate(WorkspacePointerInterface $source, WorkspacePointerInterface $target) {
    $initial_conflicts = $this->conflictTracker->getAll();
    $push = $this->doReplication($source, $target);
    return $push;
  }

  public function update(WorkspacePointerInterface $source, WorkspacePointerInterface $target) {
    return $this->doReplication($source, $target);
  }

  protected function doReplication(WorkspacePointerInterface $source, WorkspacePointerInterface $target) {
    foreach ($this->replicators as $replicator) {
      if ($replicator->applies($source, $target)) {
        return $replicator->replicate($source, $target);
      }
    }

    return $this->failedReplicationLog($source, $target);
  }

  protected function failedReplicationLog(WorkspacePointerInterface $source, WorkspacePointerInterface $target) {
    $time = new \DateTime();
    $history = [
      'start_time' => $time->format('D, d M Y H:i:s e'),
      'end_time' => $time->format('D, d M Y H:i:s e'),
      'session_id' => \md5((\microtime(true) * 1000000)),
    ];
    $replication_log_id = $source->generateReplicationId($target);
    /** @var \Drupal\replication\Entity\ReplicationLogInterface $replication_log */
    $replication_log = ReplicationLog::loadOrCreate($replication_log_id);
    $replication_log->set('ok', FALSE);
    $replication_log->setSessionId($history['session_id']);
    $replication_log->setHistory($history);
    $replication_log->save();
    return $replication_log;
  }
}
