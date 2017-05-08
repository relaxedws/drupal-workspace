<?php

namespace Drupal\workspace;

use Drupal\Core\Entity\EntityTypeManagerInterface;
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
   * Constructs a new Toolbar.
   *
   * @param \Drupal\multiversion\MultiversionManagerInterface $multiversion_manager
   */
  public function __construct(MultiversionManagerInterface $multiversion_manager, EntityTypeManagerInterface $entity_type_manager) {
    $this->multiversionManager = $multiversion_manager;
    $this->entityTypeManager = $entity_type_manager;
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
    /** @var \Drupal\workspace\WorkspacePointerInterface[] $workspace_pointers */
    $workspace_pointers = $this->entityTypeManager->getStorage('workspace_pointer')->loadByProperties(['workspace_pointer' => $workspace->id()]);
    $workspace_pointer = reset($workspace_pointers);
    $workspace_pointer->delete();
  }

}
