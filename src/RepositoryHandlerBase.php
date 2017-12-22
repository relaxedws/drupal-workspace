<?php

namespace Drupal\workspace;

use Drupal\Core\Entity\DependencyTrait;
use Drupal\Core\Plugin\PluginBase;

/**
 * Defines a base RepositoryHandler plugin implementation.
 *
 * @see \Drupal\workspace\RepositoryHandlerInterface
 * @see \Drupal\workspace\RepositoryHandlerManager
 * @see \Drupal\workspace\Annotation\RepositoryHandler
 * @see plugin_api
 */
abstract class RepositoryHandlerBase extends PluginBase implements RepositoryHandlerInterface {

  use DependencyTrait;

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->getPluginDefinition()['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->getPluginDefinition()['description'];
  }

  /**
   * {@inheritdoc}
   */
  public function isRemote() {
    return $this->getPluginDefinition()['remote'];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

}
