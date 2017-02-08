<?php

namespace Drupal\workspace\Negotiator;

use Drupal\Core\Session\AccountInterface;
use Drupal\workspace\Entity\WorkspaceInterface;
use Drupal\workspace\WorkspaceManagerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Class WorkspaceNegotiatorBase
 */
abstract class WorkspaceNegotiatorBase implements WorkspaceNegotiatorInterface, ContainerAwareInterface {

  use ContainerAwareTrait;

  /**
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * @var \Drupal\workspace\WorkspaceManagerInterface
   */
  protected $workspaceManager;

  /**
   * {@inheritdoc}
   */
  public function setCurrentUser(AccountInterface $current_user) {
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public function setWorkspaceManager(WorkspaceManagerInterface $entity_manager) {
    $this->workspaceManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function persist(WorkspaceInterface $workspace) {
    return TRUE;
  }

}
