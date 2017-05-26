<?php

namespace Drupal\workspace\Negotiator;

use Drupal\workspace\Entity\WorkspaceInterface;

/**
 * Class WorkspaceNegotiatorBase
 */
abstract class WorkspaceNegotiatorBase implements WorkspaceNegotiatorInterface {

  /**
   * {@inheritdoc}
   */
  public function persist(WorkspaceInterface $workspace) {
    return TRUE;
  }

}
