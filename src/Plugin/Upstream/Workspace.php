<?php

namespace Drupal\workspace\Plugin\Upstream;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\workspace\UpstreamInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Workspace' upstream plugin.
 *
 * @Upstream(
 *   id = "workspace",
 *   deriver = "Drupal\workspace\Plugin\Derivative\Workspace"
 * )
 */
class Workspace extends PluginBase implements UpstreamInterface, ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\workspace\Entity\Workspace
   */
  protected $workspace;

  /**
   * Workspace constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->workspace = $entity_type_manager->getStorage('workspace')->load($this->getDerivativeId());
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   *
   * @return static
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * @inheritDoc
   */
  public function getLabel() {
    return $this->workspace->label();
  }

}
