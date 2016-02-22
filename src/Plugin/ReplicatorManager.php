<?php

/**
 * @file
 * Contains \Drupal\workspace\Plugin\ReplicatorManager.
 */

namespace Drupal\workspace\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the Replicator plugin manager.
 */
class ReplicatorManager extends DefaultPluginManager {

  /**
   * Constructor for ReplicatorManager objects.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/Replicator', $namespaces, $module_handler, 'Drupal\workspace\Plugin\ReplicatorInterface', 'Drupal\workspace\Annotation\Replicator');

    $this->alterInfo('workspace_replicator_info');
    $this->setCacheBackend($cache_backend, 'workspace_replicator_plugins');
  }

}
