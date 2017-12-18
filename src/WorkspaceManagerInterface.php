<?php

namespace Drupal\workspace;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\workspace\Entity\WorkspaceInterface;

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
   * @param bool $object
   *   TRUE for the active workspace to be returned as an object, FALSE
   *   otherwise.
   *
   * @return \Drupal\workspace\Entity\WorkspaceInterface|string
   *   The active workspace entity object or workspace ID, depending on the
   *   $object parameter.
   */
  public function getActiveWorkspace($object = FALSE);

  /**
   * Sets the active workspace via the workspace negotiators.
   *
   * @param \Drupal\workspace\Entity\WorkspaceInterface $workspace
   *   The workspace to set as active.
   *
   * @return $this
   *
   * @throws \Drupal\workspace\WorkspaceAccessException
   *   Thrown when the current user doesn't have access to view the workspace.
   */
  public function setActiveWorkspace(WorkspaceInterface $workspace);

  /**
   * Update or create a ContentWorkspace entity from another entity.
   *
   * If the entity passed in can belong to a workspace and already has a
   * ContentWorkspace entity, then a new revision of this will be created with
   * the new information. Otherwise, a new ContentWorkspace entity is created to
   * store the passed-in entity's information.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to update or create from.
   */
  public function updateOrCreateFromEntity(EntityInterface $entity);

}
