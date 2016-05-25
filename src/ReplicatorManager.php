<?php

namespace Drupal\workspace;

use Drupal\multiversion\Workspace\ConflictTrackerInterface;
use Drupal\replication\Entity\ReplicationLog;

/**
 * Provides the Replicator manager.
 */
class ReplicatorManager implements ReplicatorInterface {

  /**
   * @var ReplicatorInterface[]
   *   The services available to perform replication.
   */
  protected $replicators = [];

  /**
   * @var ConflictTrackerInterface
   *   The injected service to track conflicts during replication.
   */
  protected $conflictTracker;

  /**
   * @param ConflictTrackerInterface $conflict_tracker
   *   The injected service to track conflicts during replication.
   */
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
   *   The service to make available for performing replication.
   */
  public function addReplicator(ReplicatorInterface $replicator) {
    $this->replicators[] = $replicator;
  }

  /**
   * {@inheritdoc}
   */
  public function replicate(WorkspacePointerInterface $source, WorkspacePointerInterface $target) {
    // @todo why is $initial_conflicts not used?
    $initial_conflicts = $this->conflictTracker->getAll();
    // @todo why is $pull unused?
    $pull = $this->update($target, $source);
    // @todo why is $post_conflicts unused?
    $post_conflicts = $this->conflictTracker->getAll();
    $push = $this->doReplication($source, $target);
    return $push;
  }

  /**
   * @todo document what this is for
   */
  public function update(WorkspacePointerInterface $source, WorkspacePointerInterface $target) {
    return $this->doReplication($source, $target);
  }

  /**
   * Internal method to contain replication logic.
   *
   * @param \Drupal\workspace\WorkspacePointerInterface $source
   *   The workspace to replicate from.
   * @param \Drupal\workspace\WorkspacePointerInterface $target
   *   The workspace to replicate to.
   *
   * @return ReplicationLog
   *   The log entry for this replication.
   */
  protected function doReplication(WorkspacePointerInterface $source, WorkspacePointerInterface $target) {
    foreach ($this->replicators as $replicator) {
      if ($replicator->applies($source, $target)) {
        return $replicator->replicate($source, $target);
      }
    }

    return $this->failedReplicationLog($source, $target);
  }

  /**
   * Generate a failed replication log and return it.
   *
   * @param \Drupal\workspace\WorkspacePointerInterface $source
   *   The workspace to replicate from.
   * @param \Drupal\workspace\WorkspacePointerInterface $target
   *   The workspace to replicate to.
   *
   * @return ReplicationLog
   *   The log entry for this replication.
   */
  protected function failedReplicationLog(WorkspacePointerInterface $source, WorkspacePointerInterface $target) {
    $time = new \DateTime();
    $history = [
      'start_time' => $time->format('D, d M Y H:i:s e'),
      'end_time' => $time->format('D, d M Y H:i:s e'),
      'session_id' => \md5((\microtime(TRUE) * 1000000)),
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
