<?php

namespace Drupal\workspace;

use Drupal\Core\Session\AccountInterface;
use Drupal\workspace\Entity\WorkspaceInterface;
use Symfony\Component\HttpFoundation\Request;

interface WorkspaceNegotiatorInterface {

  /**
   * @param \Drupal\Core\Session\AccountInterface $current_user
   */
  public function setCurrentUser(AccountInterface $current_user);

  /**
   * @param \Drupal\workspace\WorkspaceManagerInterface $entity_manager
   */
  public function setWorkspaceManager(WorkspaceManagerInterface $entity_manager);

  /**
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @return boolean
   */
  public function applies(Request $request);

  /**
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @return string
   */
  public function getWorkspaceId(Request $request);

  /**
   * @param \Drupal\workspace\Entity\WorkspaceInterface $workspace
   * @return boolean
   */
  public function persist(WorkspaceInterface $workspace);

}
