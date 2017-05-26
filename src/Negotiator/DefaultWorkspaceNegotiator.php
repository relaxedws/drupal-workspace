<?php

namespace Drupal\workspace\Negotiator;

use Symfony\Component\HttpFoundation\Request;

/**
 * Class DefaultWorkspaceNegotiator
 */
class DefaultWorkspaceNegotiator extends WorkspaceNegotiatorBase {

  /**
   * The default workspace ID.
   *
   * @var string
   */
  protected $defaultWorkspaceId;

  /**
   * Constructor.
   *
   * @param string $default_workspace_id
   *   The default workspace ID.
   */
  public function __construct($default_workspace_id) {
    $this->defaultWorkspaceId = $default_workspace_id;
  }

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
    return $this->defaultWorkspaceId;
  }

}
