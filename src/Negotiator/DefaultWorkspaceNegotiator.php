<?php

namespace Drupal\workspace\Negotiator;

use Symfony\Component\HttpFoundation\Request;

/**
 * Class DefaultWorkspaceNegotiator
 */
class DefaultWorkspaceNegotiator extends WorkspaceNegotiatorBase {

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
    return $this->container->getParameter('workspace.default');
  }

}
