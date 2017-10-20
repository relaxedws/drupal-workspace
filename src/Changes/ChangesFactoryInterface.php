<?php

namespace Drupal\workspace\Changes;

use Drupal\workspace\Entity\WorkspaceInterface;

/**
 * Defines an interface for ChangesFactory classes.
 */
interface ChangesFactoryInterface {

  /**
   * Constructs a new Changes instance.
   *
   * @param \Drupal\workspace\Entity\WorkspaceInterface $workspace
   *   A workspace entity.
   *
   * @return \Drupal\workspace\Changes\ChangesInterface
   *   A list of changes for the given workspace.
   */
  public function get(WorkspaceInterface $workspace);

}
