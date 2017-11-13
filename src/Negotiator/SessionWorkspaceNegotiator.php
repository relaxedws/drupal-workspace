<?php

namespace Drupal\workspace\Negotiator;

use Drupal\Core\Session\AccountInterface;
use Drupal\workspace\Entity\WorkspaceInterface;
use Drupal\user\PrivateTempStoreFactory;
use Drupal\workspace\WorkspaceManager;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines the session workspace negotiator.
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
   * The default workspace ID.
   *
   * @var string
   */
  protected $defaultWorkspaceId;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\user\PrivateTempStoreFactory $tempstore_factory
   *   The tempstore factory.
   */
  public function __construct(AccountInterface $current_user, PrivateTempStoreFactory $tempstore_factory) {
    $this->currentUser = $current_user;
    $this->tempstore = $tempstore_factory->get('workspace.negotiator.session');
  }

  /**
   * {@inheritdoc}
   */
  public function applies(Request $request) {
    // This negotiator only applies if the current user is authenticated,
    // i.e. a session exists.
    return $this->currentUser->isAuthenticated();
  }

  /**
   * {@inheritdoc}
   */
  public function getWorkspaceId(Request $request) {
    $workspace_id = $this->tempstore->get('active_workspace_id');
    return $workspace_id ?: WorkspaceManager::DEFAULT_WORKSPACE;
  }

  /**
   * {@inheritdoc}
   */
  public function setWorkspace(WorkspaceInterface $workspace) {
    $this->tempstore->set('active_workspace_id', $workspace->id());
  }

}
