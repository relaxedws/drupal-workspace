<?php

namespace Drupal\workspace;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Defines an interface for the workspace entity type.
 */
interface WorkspaceInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets an instance of the repository handler configured for the workspace.
   *
   * @return \Drupal\workspace\RepositoryHandlerInterface|null
   *   An upstream plugin object or NULL if there is no upstream configured for
   *   the workspace. A NULL return value can only be returned for the default
   *   (i.e. Live) workspace.
   */
  public function getRepositoryHandlerPlugin();

  /**
   * Gets an instance of the local repository handler plugin for the workspace.
   *
   * @return \Drupal\workspace\Plugin\RepositoryHandler\LocalWorkspaceRepositoryHandler
   *   A local upstream plugin object.
   */
  public function getLocalRepositoryHandlerPlugin();

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
