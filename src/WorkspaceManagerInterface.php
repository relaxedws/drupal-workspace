<?php

namespace Drupal\workspace;

use Drupal\workspace\Entity\WorkspaceInterface;

interface WorkspaceManagerInterface {

  /**
   * @param \Drupal\workspace\WorkspaceNegotiatorInterface $negotiator
   * @param int $priority
   */
  public function addNegotiator(WorkspaceNegotiatorInterface $negotiator, $priority);

  /**
   * @param string $workspace_id
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

}
