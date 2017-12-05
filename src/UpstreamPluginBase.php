<?php

namespace Drupal\workspace;

use Drupal\Core\Entity\DependencyTrait;
use Drupal\Core\Plugin\PluginBase;

/**
 * Defines a base Upstream plugin implementation.
 *
 * @see \Drupal\workspace\UpstreamPluginInterface
 * @see \Drupal\workspace\UpstreamPluginManager
 * @see \Drupal\workspace\Annotation\Upstream
 * @see plugin_api
 */
abstract class UpstreamPluginBase extends PluginBase implements UpstreamPluginInterface {

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
