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

  /**
   * Replicates content from a source repository to a target repository.
   *
   * @param \Drupal\workspace\RepositoryHandlerInterface $source
   *   The repository handler to replicate from.
   * @param \Drupal\workspace\RepositoryHandlerInterface $target
   *   The repository handler to replicate to.
   *
   * @return \Drupal\workspace\ReplicationLogInterface
   *   The replication log for the replication.
   */
  abstract public function replicate(RepositoryHandlerInterface $source, RepositoryHandlerInterface $target);

}
