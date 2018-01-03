<?php

namespace Drupal\workspace;

use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Provides an interface for managing Workspaces.
 */
interface WorkspaceManagerInterface {

  /**
   * Returns whether an entity type can belong to a workspace or not.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type to check.
   *
   * @return bool
   *   TRUE if the entity type can belong to a workspace, FALSE otherwise.
   */
  public function entityTypeCanBelongToWorkspaces(EntityTypeInterface $entity_type);

  /**
   * Returns an array of entity types that can belong to workspaces.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface[]
   *   The entity types what can belong to workspaces.
   */
  public function getSupportedEntityTypes();

  /**
   * Gets the active workspace.
   *
   * @return \Drupal\workspace\WorkspaceInterface
   *   The active workspace entity object.
   */
  public function getActiveWorkspace();

  /**
   * Sets the active workspace via the workspace negotiators.
   *
   * @param \Drupal\workspace\WorkspaceInterface $workspace
   *   The workspace to set as active.
   *
   * @return $this
   *
   * @throws \Drupal\workspace\WorkspaceAccessException
   *   Thrown when the current user doesn't have access to view the workspace.
   */
  public function setActiveWorkspace(WorkspaceInterface $workspace);

}
