<?php

namespace Drupal\workspace\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Defines an interface for the workspace entity type.
 */
interface WorkspaceInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets an instance of the upstream plugin configured for the workspace.
   *
   * @return \Drupal\workspace\UpstreamPluginInterface
   *   An upstream plugin object.
   */
  public function getUpstreamPlugin();

  /**
   * Gets an instance of the local upstream plugin for the workspace.
   *
   * @return \Drupal\workspace\Plugin\Upstream\LocalWorkspaceUpstream
   *   A local upstream plugin object.
   */
  public function getLocalUpstreamPlugin();

  /**
   * Sets the workspace creation timestamp.
   *
   * @param int $timestamp
   *   The workspace creation timestamp.
   *
   * @return $this
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the workspace creation timestamp.
   *
   * @return int
   *   Creation timestamp of the workspace.
   */
  public function getStartTime();

}
