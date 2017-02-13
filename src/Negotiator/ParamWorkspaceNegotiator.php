<?php

namespace Drupal\workspace\Negotiator;

use Symfony\Component\HttpFoundation\Request;

/**
 * Class ParamWorkspaceNegotiator
 */
class ParamWorkspaceNegotiator extends WorkspaceNegotiatorBase {

  /**
   * {@inheritdoc}
   */
  public function applies(Request $request) {
    return is_string($request->query->get('workspace'));
  }

  /**
   * {@inheritdoc}
   */
  public function getWorkspaceId(Request $request) {
    return $request->query->get('workspace');
  }

}
