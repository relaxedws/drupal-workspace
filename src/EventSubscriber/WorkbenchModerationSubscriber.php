<?php

namespace Drupal\workspace\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\multiversion\Entity\WorkspaceInterface;
use Drupal\replication\ReplicationTask\ReplicationTask;
use Drupal\workbench_moderation\Entity\ModerationState;
use Drupal\workbench_moderation\Event\WorkbenchModerationEvents;
use Drupal\workbench_moderation\Event\WorkbenchModerationTransitionEvent;
use Drupal\workspace\ReplicatorManager;
use Drupal\workspace\WorkspacePointerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscriber for workbench transitions.
 */
class WorkbenchModerationSubscriber implements EventSubscriberInterface {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\workspace\ReplicatorManager
   */
  protected $replicatorManager;

  public function __construct(EntityTypeManagerInterface $entity_type_manager, ReplicatorManager $replicator_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->replicatorManager = $replicator_manager;
  }

  /**
   * Listener for workbench moderation event transitions.
   *
   * @param \Drupal\workbench_moderation\Event\WorkbenchModerationTransitionEvent $event
   *   The transition event that just fired.
   */
  public function onTransition(WorkbenchModerationTransitionEvent $event) {
    $entity = $event->getEntity();

    if ($entity->getEntityTypeId() == 'workspace' && $this->wasDefaultRevision($event)) {
      /** @var WorkspaceInterface $entity */
      $this->mergeWorkspaceToParent($entity);
    }
  }

  /**
   * Determines if the transition is to a "default revision" state.
   *
   * @param \Drupal\workbench_moderation\Event\WorkbenchModerationTransitionEvent $event
   *   The transition event.
   *
   * @return bool
   *   TRUE if the event is moving an entity to a default-revision state.
   */
  protected function wasDefaultRevision(WorkbenchModerationTransitionEvent $event) {
    /** @var ModerationState $post_state */
    $post_state = $this->entityTypeManager->getStorage('moderation_state')->load($event->getStateAfter());

    return $post_state->isPublishedState();
  }

  /**
   * Merges a workspace to its parent workspace, if any.
   *
   * @param \Drupal\multiversion\Entity\WorkspaceInterface $workspace
   *   The workspace entity to merge.
   */
  protected function mergeWorkspaceToParent(WorkspaceInterface $workspace) {
    // This may be insufficient for handling a missing parent.
    /** @var WorkspacePointerInterface $parent_workspace */
    $parent_workspace_pointer = $workspace->get('upstream')->entity;
    if (!$parent_workspace_pointer) {
      // @todo Should we silently ignore this, or throw an error, or...?
      return;
    }

    $source_pointer = $this->getPointerToWorkspace($workspace);

    // Derive a replication task from the source Workspace.
    $task = $this->replicatorManager->getTask($workspace, 'push');

    $this->replicatorManager->replicate($source_pointer, $parent_workspace_pointer, $task);
  }

  /**
   * Returns a pointer to the specified workspace.
   *
   * In most cases this pointer will be unique, but that is not guaranteed
   * by the schema. If there are multiple pointers, which one is returned is
   * undefined.
   *
   * @todo Move this to somewhere more logical and globally accessible.
   *
   * @param \Drupal\multiversion\Entity\WorkspaceInterface $workspace
   *   The workspace for which we want a pointer.
   * @return WorkspacePointerInterface
   *   The pointer to the provided workspace.
   */
  protected function getPointerToWorkspace(WorkspaceInterface $workspace) {
    $pointers = $this->entityTypeManager
      ->getStorage('workspace_pointer')
      ->loadByProperties(['workspace_pointer' => $workspace->id()]);
    $pointer = reset($pointers);
    return $pointer;
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events = [];
    if (class_exists(WorkbenchModerationEvents::class)) {
      $events[WorkbenchModerationEvents::STATE_TRANSITION][] = ['onTransition'];
    }
    return $events;
  }
}
