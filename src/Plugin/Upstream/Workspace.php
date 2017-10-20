<?php

namespace Drupal\workspace\Plugin\Upstream;

use Drupal\workspace\UpstreamPluginBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\workspace\UpstreamPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Workspace' upstream plugin to allow a workspace to be set as a
 * source and / or target for content replication.
 *
 * @Upstream(
 *   id = "workspace",
 *   label = @Translation("Local workspace"),
 *   description = @Translation("A workspace that is defined in the local Drupal installation."),
 *   remote = FALSE,
 *   deriver = "Drupal\workspace\Plugin\Deriver\WorkspaceUpstream",
 * )
 */
class Workspace extends UpstreamPluginBase implements UpstreamPluginInterface, ContainerFactoryPluginInterface {

  /**
   * The local workspace entity.
   *
   * @var \Drupal\workspace\Entity\WorkspaceInterface
   */
  protected $workspace;

  /**
   * Constructs a new Workspace upstream plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->workspace = $entity_type_manager->getStorage('workspace')->load($this->getDerivativeId());
  }

  /**
   * {@inheritdoc}
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

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $this->dependencies = parent::calculateDependencies();

    $this->addDependency($this->workspace->getConfigDependencyKey(), $this->workspace->getConfigDependencyName());

    return $this->dependencies;

  }

}
