<?php

namespace Drupal\workspace\Negotiator;

use Drupal\workspace\Entity\WorkspaceInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Interface WorkspaceNegotiatorInterface
 */
interface WorkspaceNegotiatorInterface {

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
