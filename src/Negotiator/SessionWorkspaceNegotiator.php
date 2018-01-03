<?php

namespace Drupal\workspace\Negotiator;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\workspace\WorkspaceInterface;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines the session workspace negotiator.
 *
 * This implementation uses the private tempstore of a user to store the ID of
 * the active workspace in order to make it persistent between login/logout
 * actions.
 */
class SessionWorkspaceNegotiator implements WorkspaceNegotiatorInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The tempstore factory.
   *
   * @var \Drupal\user\PrivateTempStore
   */
  protected $tempstore;

  /**
   * The workspace storage handler.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $workspaceStorage;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\user\PrivateTempStoreFactory $tempstore_factory
   *   The tempstore factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(AccountInterface $current_user, PrivateTempStoreFactory $tempstore_factory, EntityTypeManagerInterface $entity_type_manager) {
    $this->currentUser = $current_user;
    $this->tempstore = $tempstore_factory->get('workspace.negotiator.session');
    $this->workspaceStorage = $entity_type_manager->getStorage('workspace');
  }

  /**
   * {@inheritdoc}
   */
  public function applies(Request $request) {
    // This negotiator only applies if the current user is authenticated.
    return $this->currentUser->isAuthenticated();
  }

  /**
   * {@inheritdoc}
   */
  public function getActiveWorkspace(Request $request) {
    $workspace_id = $this->tempstore->get('active_workspace_id');

    if ($workspace_id && ($workspace = $this->workspaceStorage->load($workspace_id))) {
      return $workspace;
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setActiveWorkspace(WorkspaceInterface $workspace) {
    $this->tempstore->set('active_workspace_id', $workspace->id());
  }

}
