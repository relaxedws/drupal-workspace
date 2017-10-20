<?php

namespace Drupal\workspace;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides a plugin manager for upstreams.
 *
 * @see \Drupal\workspace\Annotation\Upstream
 * @see \Drupal\workspace\UpstreamPluginInterface
 * @see plugin_api
 */
class UpstreamPluginManager extends DefaultPluginManager {

  /**
   * Constructs a new UpstreamPluginManager.
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
    parent::__construct('Plugin/Upstream', $namespaces, $module_handler, 'Drupal\workspace\UpstreamPluginInterface', 'Drupal\workspace\Annotation\Upstream');
    $this->alterInfo('workspace_upstream_info');
    $this->setCacheBackend($cache_backend, 'workspace_upstream');
  }

}
