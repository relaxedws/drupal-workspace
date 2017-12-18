<?php

namespace Drupal\workspace\Negotiator;

use Drupal\workspace\WorkspaceInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Workspace negotiators provide a way to get the active workspace.
 *
 * WorkspaceManager acts as the service collector for Workspace negotiators.
 */
interface WorkspaceNegotiatorInterface {

  /**
   * Checks whether the negotiator applies or not.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request.
   *
   * @return bool
   *   TRUE if the negotiator applies for the current request, FALSE otherwise.
   */
  public function applies(Request $request);

  /**
   * Gets the ID of the active workspace.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request.
   *
   * @return string
   *   The workspace ID.
   */
  public function getWorkspaceId(Request $request);

  /**
   * Sets the active workspace.
   *
   * @param \Drupal\workspace\WorkspaceInterface $workspace
   *   The workspace entity.
   */
  public function setWorkspace(WorkspaceInterface $workspace);

}
