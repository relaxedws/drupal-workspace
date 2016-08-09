<?php

namespace Drupal\workspace;

use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\multiversion\Entity\WorkspaceInterface;
use Drupal\multiversion\Workspace\ConflictTrackerInterface;
use Drupal\replication\Entity\ReplicationLog;
use Drupal\replication\ReplicationTask\ReplicationTask;
use Drupal\replication\ReplicationTask\ReplicationTaskInterface;

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
  public function replicate(WorkspacePointerInterface $source, WorkspacePointerInterface $target, ReplicationTaskInterface $task = NULL) {
    // @todo use $initial_conflicts in a conflict management workflow
    $initial_conflicts = $this->conflictTracker->getAll();

    // Derive a replication task from the target Workspace for pulling.
    $pull_task = $this->getTask($source->getWorkspace(), 'pull_replication_settings');

    // Pull in changes from $target to $source to ensure a merge will complete.
    $this->update($target, $source, $pull_task);

    // @todo use $post_conflicts in a conflict management workflow
    $post_conflicts = $this->conflictTracker->getAll();

    // Push changes from $source to $target.
    $push_log = $this->doReplication($source, $target, $task);

    return $push_log;
  }

  /**
   * Derives a replication task from the workspace's replication settings.
   *
   * @param \Drupal\multiversion\Entity\WorkspaceInterface $workspace
   *   The workspace to derive the replication task from.
   * @param string $field_name
   *   The field name that references a ReplicationSettings config entity
   *   ('push_replication_settings', 'pull_replication_settings').
   *
   * @return \Drupal\replication\ReplicationTask\ReplicationTaskInterface
   *   A replication task that can be passed to a \Drupal\workspace\ReplicatorInterface.
   */
  public function getTask(WorkspaceInterface $workspace, $field_name) {
    $task = new ReplicationTask();
    $items = $workspace->get($field_name);
    if ($items instanceof EntityReferenceFieldItemListInterface) {
      $referenced_entities = $items->referencedEntities();
      if (count($referenced_entities) > 0) {
        $task->setFilter($referenced_entities[0]->getFilterId());
        $task->setParametersByArray($referenced_entities[0]->getParameters());
      }
    }
    return $task;
  }

  /**
   * Update the target using the source before doing a replication.
   *
   * @param \Drupal\workspace\WorkspacePointerInterface $target
   *   The workspace to replicate to.
   * @param \Drupal\workspace\WorkspacePointerInterface $source
   *   The workspace to replicate from.
   * @param \Drupal\replication\ReplicationTask\ReplicationTaskInterface $task
   *   Optional information that defines the replication task to perform.
   *
   * @return ReplicationLog
   *   The log entry for this replication.
   */
  public function update(WorkspacePointerInterface $target, WorkspacePointerInterface $source, ReplicationTaskInterface $task = NULL) {
    return $this->doReplication($target, $source, $task);
  }

  /**
   * Internal method to contain replication logic.
   *
   * @param \Drupal\workspace\WorkspacePointerInterface $source
   *   The workspace to replicate from.
   * @param \Drupal\workspace\WorkspacePointerInterface $target
   *   The workspace to replicate to.
   * @param \Drupal\replication\ReplicationTask\ReplicationTaskInterface $task
   *   Optional information that defines the replication task to perform.
   *
   * @return ReplicationLog
   *   The log entry for this replication.
   */
  protected function doReplication(WorkspacePointerInterface $source, WorkspacePointerInterface $target, ReplicationTaskInterface $task = NULL) {
    foreach ($this->replicators as $replicator) {
      if ($replicator->applies($source, $target)) {
        return $replicator->replicate($source, $target, $task);
      }
    }

    return $this->failedReplicationLog($source, $target, $task);
  }

  /**
   * Generate a failed replication log and return it.
   *
   * @param \Drupal\workspace\WorkspacePointerInterface $source
   *   The workspace to replicate from.
   * @param \Drupal\workspace\WorkspacePointerInterface $target
   *   The workspace to replicate to.
   * @param \Drupal\replication\ReplicationTask\ReplicationTaskInterface $task
   *   Optional information that defines the replication task to perform.
   *
   * @return ReplicationLog
   *   The log entry for this replication.
   */
  protected function failedReplicationLog(WorkspacePointerInterface $source, WorkspacePointerInterface $target, ReplicationTaskInterface $task = NULL) {
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
