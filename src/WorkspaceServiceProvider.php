<?php

namespace Drupal\workspace;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Defines a service profiler for the workspace module.
 */
class WorkspaceServiceProvider extends ServiceProviderBase {

  public function alter(ContainerBuilder $container) {
    $renderer_config = $container->getParameter('renderer.config');
    $renderer_config['required_cache_contexts'][] = 'workspace';
    $container->setParameter('renderer.config', $renderer_config);
  }

}
