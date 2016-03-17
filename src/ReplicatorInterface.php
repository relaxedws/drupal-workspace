<?php

/**
 * @file
 * Contains \Drupal\workspace\ReplicatorInterface.
 */

namespace Drupal\workspace;

/**
 * Defines an interface for Replicator plugins.
 */
interface ReplicatorInterface {

  /**
   * @param \Drupal\workspace\WorkspacePointerInterface $source
   * @param \Drupal\workspace\WorkspacePointerInterface $target
   * @return bool
   */
  public function applies(WorkspacePointerInterface $source, WorkspacePointerInterface $target);

  /**
   * @param \Drupal\workspace\WorkspacePointerInterface $source
   * @param \Drupal\workspace\WorkspacePointerInterface $target
   * @return array
   */
  public function replicate(WorkspacePointerInterface $source, WorkspacePointerInterface $target);

}
