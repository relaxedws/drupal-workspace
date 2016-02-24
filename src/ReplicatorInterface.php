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
   * @param \Drupal\workspace\PointerInterface $source
   * @param \Drupal\workspace\PointerInterface $target
   * @return bool
   */
  public function applies(PointerInterface $source, PointerInterface $target);

  /**
   * @param \Drupal\workspace\PointerInterface $source
   * @param \Drupal\workspace\PointerInterface $target
   * @return array
   */
  public function replicate(PointerInterface $source, PointerInterface $target);

}
