<?php

namespace Drupal\workspace;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Defines a service provider for the workspace module.
 */
class WorkspaceServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Add the 'workspace' cache context as required.
    $renderer_config = $container->getParameter('renderer.config');
    $renderer_config['required_cache_contexts'][] = 'workspace';
    $container->setParameter('renderer.config', $renderer_config);

    // Switch core's SQL entity query factory to our own so we can reliably
    // alter entity queries.
    // @todo Do the same for the pgsql entity query backend override.
    $definition = $container->getDefinition('entity.query.sql');
    $definition->setClass('Drupal\workspace\EntityQuery\SqlQueryFactory');
    $definition->addArgument($container->getDefinition('workspace.manager'));
  }

}
