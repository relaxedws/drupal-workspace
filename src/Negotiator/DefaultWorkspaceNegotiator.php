<?php

namespace Drupal\workspace\Negotiator;

use Drupal\workspace\Entity\WorkspaceInterface;
use Drupal\workspace\WorkspaceManager;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines the default workspace negotiator.
 */
class DefaultWorkspaceNegotiator implements WorkspaceNegotiatorInterface {

  /**
   * {@inheritdoc}
   */
  public function applies(Request $request) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getWorkspaceId(Request $request) {
    return WorkspaceManager::DEFAULT_WORKSPACE;
  }

  /**
   * {@inheritdoc}
   */
  public function setWorkspace(WorkspaceInterface $workspace) {}

}
