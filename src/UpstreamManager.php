<?php

namespace Drupal\workspace;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Class UpstreamManager
 */
class UpstreamManager extends DefaultPluginManager {

  /**
   * UpstreamManager constructor.
   * 
   * @param \Traversable $namespaces
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/Upstream', $namespaces, $module_handler, 'Drupal\workspace\UpstreamInterface', 'Drupal\workspace\Annotation\Upstream');
    $this->alterInfo('workspace_upstream_info');
    $this->setCacheBackend($cache_backend, 'workspace_upstream');
  }

}
