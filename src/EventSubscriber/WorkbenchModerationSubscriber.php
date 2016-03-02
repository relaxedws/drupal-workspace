<?php

namespace Drupal\workspace\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\multiversion\Entity\WorkspaceInterface;
use Drupal\workbench_moderation\Entity\ModerationState;
use Drupal\workbench_moderation\Event\WorkbenchModerationEvents;
use Drupal\workbench_moderation\Event\WorkbenchModerationTransitionEvent;
use Drupal\workspace\PointerInterface;
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
   * @var \Drupal\workspace\WorkspacePointerInterface
   */
  protected $workspacePointer;

  /**
   * @var \Drupal\workspace\ReplicatorManager
   */
  protected $replicatorManager;

  public function __construct(EntityTypeManagerInterface $entity_type_manager, WorkspacePointerInterface $workspace_pointer, ReplicatorManager $replicator_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->workspacePointer = $workspace_pointer;
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

    // @todo should this look for the Published flag instead? Published means
    // nothing else for Workspaces.
    return $post_state->isDefaultRevisionState();
  }

  /**
   * Merges a workspace to its parent workspace, if any.
   *
   * @param \Drupal\multiversion\Entity\WorkspaceInterface $workspace
   *   The workspace entity to merge.
   */
  protected function mergeWorkspaceToParent(WorkspaceInterface $workspace) {
    // This may be insufficient for handling a missing parent.
    /** @var WorkspaceInterface $parent_workspace */
    $parent_workspace = $workspace->get('upstream')->entity;
    if (!$parent_workspace) {
      // @todo Should we silently ignore this, or throw an error, or...?
      return;
    }

    /** @var PointerInterface $source_pointer */
    $source_pointer = $this->workspacePointer->get('workspace:' . $workspace->id());
    /** @var PointerInterface $target_pointer */
    $target_pointer = $this->workspacePointer->get('workspace:' . $parent_workspace->id());

    $this->replicatorManager->replicate($source_pointer, $target_pointer);
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
