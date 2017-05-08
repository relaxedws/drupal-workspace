<?php

namespace Drupal\workspace;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
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
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs a new Toolbar.
   *
   * @param \Drupal\multiversion\MultiversionManagerInterface $multiversion_manager
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\State\StateInterface $state
   */
  public function __construct(MultiversionManagerInterface $multiversion_manager, EntityTypeManagerInterface $entity_type_manager, StateInterface $state) {
    $this->multiversionManager = $multiversion_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->state = $state;
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

    /** @var WorkspacePointerInterface $pointer */
    $pointer = $this->entityTypeManager->getStorage('workspace_pointer')->create();
    $pointer->setWorkspace($workspace);
    $pointer->save();
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

    // Store a list of workspace IDs that still need processing.
    $workspace_deleted = $this->state->get('workspace_deleted') ?: [];
    $workspace_deleted[] = $workspace->id();
    $this->state->set('workspace_deleted', $workspace_deleted);
  }

}
