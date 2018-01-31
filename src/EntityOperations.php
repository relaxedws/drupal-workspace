<?php

namespace Drupal\workspace;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\multiversion\Entity\WorkspaceInterface;
use Drupal\multiversion\MultiversionManagerInterface;

/**
 * Defines a class for reacting to entity events.
 */
class EntityOperations {

  /**
   * @var \Drupal\multiversion\MultiversionManagerInterface
   */
  protected $multiversionManager;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $state;

  /**
   * Constructs a new Toolbar.
   *
   * @param \Drupal\multiversion\MultiversionManagerInterface $multiversion_manager
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\Queue\QueueInterface $queue
   */
  public function __construct(MultiversionManagerInterface $multiversion_manager, EntityTypeManagerInterface $entity_type_manager, QueueFactory $queue) {
    $this->multiversionManager = $multiversion_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->queue = $queue;
  }

  /**
   * Hook bridge for hook_workspace_insert()
   *
   * @see hook_ENTITY_TYPE_insert()
   *
   * @param \Drupal\multiversion\Entity\WorkspaceInterface $workspace
   *   The workspace entity that is being created.
   */
  public function workspaceInsert(WorkspaceInterface $workspace) {
    // Create a new pointer to every Workspace that gets created.
    // This is mainly so that we can always backtrack from a Workspace to a
    // pointer to provide a consistent API to replicators.
    $this->loadOrCreateWorkspacePointer($workspace);
  }

  /**
   * Hook bridge for hook_workspace_update()
   *
   * @see hook_ENTITY_TYPE_update()
   *
   * @param \Drupal\multiversion\Entity\WorkspaceInterface $workspace
   *   The workspace entity that is being updated.
   */
  public function workspaceUpdate(WorkspaceInterface $workspace) {
    $workspace_pointer = $this->loadOrCreateWorkspacePointer($workspace);
    if (!$workspace->isPublished()) {
      $workspace_pointer->delete();
    }
  }

  /**
   * Load or create the workspace pointer for a workspace.
   *
   * @param \Drupal\multiversion\Entity\WorkspaceInterface $workspace
   *   The workspace entity.
   *
   * @return \Drupal\workspace\WorkspacePointerInterface
   *   The workspace pointer entity.
   */
  public function loadOrCreateWorkspacePointer(WorkspaceInterface $workspace) {
    $storage = $this->entityTypeManager->getStorage('workspace_pointer');
    $workspace_pointers = $storage->loadByProperties(['workspace_pointer' => $workspace->id()]);
    if (empty($workspace_pointers)) {
      /** @var \Drupal\workspace\WorkspacePointerInterface $pointer */
      $pointer = $storage->create();
      $pointer->setWorkspace($workspace);
      $pointer->save();
      return $pointer;
    }
    return reset($workspace_pointers);
  }

  /**
   * Hook bridge for hook_workspace_delete()
   *
   * @see hook_ENTITY_TYPE_delete()
   *
   * @param \Drupal\multiversion\Entity\WorkspaceInterface $workspace
   */
  public function workspaceDelete(WorkspaceInterface $workspace) {
    // Delete related workspace pointer entities.
    /** @var \Drupal\workspace\WorkspacePointerInterface[] $workspace_pointers */
    $workspace_pointers = $this->entityTypeManager->getStorage('workspace_pointer')->loadByProperties(['workspace_pointer' => $workspace->id()]);
    if (!empty($workspace_pointers)) {
      $workspace_pointer = reset($workspace_pointers);
      $workspace_pointer->delete();
    }

    /** @var \Drupal\Core\Queue\QueueInterface $queue */
    $queue = $this->queue->get('deleted_workspace_queue');
    $queue->createQueue();
    /** @var ContentEntityTypeInterface $entity_type */
    foreach ($this->multiversionManager->getEnabledEntityTypes() as $entity_type) {
      $entity_ids = $this->entityTypeManager
        ->getStorage($entity_type->id())
        ->getQuery()
        ->condition('workspace', $workspace->id())
        ->execute();
      foreach ($entity_ids as $entity_id) {
        $data = [
          'workspace' => $workspace->id(),
          'entity_type_id' => $entity_type->id(),
          'entity_id' => $entity_id,
        ];
        $queue->createItem($data);
      }
    }
  }

}
