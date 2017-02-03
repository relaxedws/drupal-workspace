<?php

namespace Drupal\workspace;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\workspace\Entity\WorkspaceInterface;

/**
 * Interface WorkspaceManagerInterface
 */
interface WorkspaceManagerInterface {

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @return boolean
   */
  public function entityCanBelongToWorkspaces(EntityInterface $entity);

  /**
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *
   * @return boolean
   */
  public function entityTypeCanBelongToWorkspaces(EntityTypeInterface $entity_type);

  /**
   * @param \Drupal\workspace\WorkspaceNegotiatorInterface $negotiator
   * @param int $priority
   */
  public function addNegotiator(WorkspaceNegotiatorInterface $negotiator, $priority);

  /**
   * @param int $workspace_id
   */
  public function load($workspace_id);

  /**
   * @param array|null $workspace_ids
   */
  public function loadMultiple(array $workspace_ids = NULL);

  /**
   * @param string $machine_name
   */
  public function loadByMachineName($machine_name);

  /**
   * @return \Drupal\workspace\Entity\WorkspaceInterface
   */
  public function getActiveWorkspace();

  /**
   * Sets the active workspace for the site/session.
   *
   * @param \Drupal\workspace\Entity\WorkspaceInterface $workspace
   *   The workspace to set as active.
   *
   * @return \Drupal\workspace\WorkspaceManagerInterface
   *
   * @throws WorkspaceAccessException
   */
  public function setActiveWorkspace(WorkspaceInterface $workspace);

  /**
   * Update or create a ContentWorkspace entity from another entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to update or create from.
   */
  public function updateOrCreateFromEntity(EntityInterface $entity);
}
