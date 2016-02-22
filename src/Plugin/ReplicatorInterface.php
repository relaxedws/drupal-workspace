<?php

/**
 * @file
 * Contains \Drupal\workspace\Plugin\ReplicatorInterface.
 */

namespace Drupal\workspace\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\multiversion\Entity\WorkspaceInterface;

/**
 * Defines an interface for Replicator plugins.
 */
interface ReplicatorInterface extends PluginInspectionInterface {

  /**
   * Parses the URI for use by the push method.
   *
   * @param \Drupal\multiversion\Entity\WorkspaceInterface $source
   * @return \Drupal\workspace\Plugin\ReplicatorInterface
   */
  public function setSource(WorkspaceInterface $source);

  /**
   * Parses the URI to use by the push method
   *
   * @param \Drupal\multiversion\Entity\WorkspaceInterface $target
   * @return \Drupal\workspace\Plugin\ReplicatorInterface
   */
  public function setTarget(WorkspaceInterface $target);

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
  public function push();

}
