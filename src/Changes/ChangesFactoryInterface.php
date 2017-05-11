<?php

namespace Drupal\workspace\Changes;

use Drupal\workspace\Entity\WorkspaceInterface;

/**
 * Interface ChangesFactoryInterface
 */
interface ChangesFactoryInterface {

  /**
   * Constructs a new Changes instance.
   *
   * @param \Drupal\workspace\Entity\WorkspaceInterface $workspace
   *
   * @return \Drupal\workspace\Changes\ChangesInterface
   */
  public function get(WorkspaceInterface $workspace);

}
