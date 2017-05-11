<?php

namespace Drupal\workspace\Negotiator;

use Drupal\Core\Session\AccountInterface;
use Drupal\workspace\Entity\WorkspaceInterface;
use Drupal\workspace\WorkspaceManagerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Interface WorkspaceNegotiatorInterface
 */
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
   * @return bool
   */
  public function applies(Request $request);

  /**
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @return string
   */
  public function getWorkspaceId(Request $request);

  /**
   * @param \Drupal\workspace\Entity\WorkspaceInterface $workspace
   * @return bool
   */
  public function persist(WorkspaceInterface $workspace);

}
