<?php

namespace Drupal\workspace;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\multiversion\Workspace\ConflictTrackerInterface;
use Drupal\replication\Entity\ReplicationLog;
use Drupal\replication\ReplicationTask\ReplicationTask;
use Drupal\replication\ReplicationTask\ReplicationTaskInterface;
use Symfony\Component\Console\Exception\LogicException;

/**
 * Provides the Replicator manager.
 */
class ReplicatorManager implements ReplicatorInterface {

  /**
   * The services available to perform replication.
   *
   * @var ReplicatorInterface[]
   */
  protected $replicators = [];

  /**
   * The injected service to track conflicts during replication.
   *
   * @var ConflictTrackerInterface
   */
  protected $conflictTracker;

  /**
   * The injected service to track conflicts during replication.
   *
   * @param ConflictTrackerInterface $conflict_tracker
   *   The confict tracking service.
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
    $pull_task = $this->getTask($target->getWorkspace(), 'pull_replication_settings');

    // Pull in changes from $target to $source to ensure a merge will complete.
    $this->update($target, $source, $pull_task);

    // @todo use $post_conflicts in a conflict management workflow
    $post_conflicts = $this->conflictTracker->getAll();

    if ($task === NULL) {
      // Derive a replication task from the target Workspace for pushing.
      $task = $this->getTask($target->getWorkspace(), 'push_replication_settings');
    }

    // Push changes from $source to $target.
    $push_log = $this->doReplication($source, $target, $task);

    return $push_log;
  }

  /**
   * Derives a replication task from an entity with replication settings.
   *
   * This can be used with a Workspace using the 'push_replication_settings'
   * and 'pull_replication_settings' fields.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to derive the replication task from.
   * @param string $field_name
   *   The field name that references a ReplicationSettings config entity.
   *
   * @return \Drupal\replication\ReplicationTask\ReplicationTaskInterface
   *   A replication task that can be passed to a replicator.
   *
   * @throws \Symfony\Component\Console\Exception\LogicException
   *   The replication settings field does not exist on the entity.
   */
  public function getTask(EntityInterface $entity, $field_name) {
    $task = new ReplicationTask();
    $items = $entity->get($field_name);

    if (!$items instanceof EntityReferenceFieldItemListInterface) {
      throw new LogicException('Replication settings field does not exist.');
    }

    $referenced_entities = $items->referencedEntities();
    if (count($referenced_entities) > 0) {
      $task->setFilter($referenced_entities[0]->getFilterId());
      $task->setParameters($referenced_entities[0]->getParameters());
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
