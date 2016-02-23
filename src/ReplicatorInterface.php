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
   * @return boolean
   */
  public function applies();

  /**
   * Parses the URI for use by the push method.
   *
   * @param \Drupal\workspace\Pointer $source
   * @return \Drupal\workspace\ReplicatorInterface
   */
  public function setSource(Pointer $source);

  /**
   * Parses the URI to use by the push method
   *
   * @param \Drupal\workspace\Pointer $target
   * @return \Drupal\workspace\ReplicatorInterface
   */
  public function setTarget(Pointer $target);

  /**
   * @return mixed
   */
  public function getSource();

  /**
   * @return mixed
   */
  public function getTarget();

  /**
   * @return array
   */
  public function replicate();

}
