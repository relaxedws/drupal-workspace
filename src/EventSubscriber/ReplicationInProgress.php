<?php

namespace Drupal\workspace\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\multiversion\Workspace\WorkspaceManagerInterface;
use Drupal\workspace\Entity\Replication;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class ReplicationInProgress
 * @package Drupal\workspace\EventSubscriber
 */
class ReplicationInProgress implements EventSubscriberInterface {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\multiversion\Workspace\WorkspaceManagerInterface
   */
  protected $workspaceManager;

  /**
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, WorkspaceManagerInterface $workspace_manager, AccountProxyInterface $current_user) {
    $this->entityTypeManager = $entity_type_manager;
    $this->workspaceManager = $workspace_manager;
    $this->currentUser = $current_user;
  }

  /**
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function displayMessage(GetResponseEvent $event) {
    if ($this->currentUser->isAuthenticated()) {
      $active_workspace_id = $this->workspaceManager->getActiveWorkspaceId();
      $replicating = $this->entityTypeManager->getStorage('replication')->getQuery()->condition('target', $active_workspace_id)->condition('replication_status', Replication::REPLICATING)->execute();
      if (!empty($replicating)) {
        drupal_set_message(t('There is a deployment to the active workspace in progress. You may not see all changes yet.'), 'warning');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['displayMessage'];
    return $events;
  }

}
