<?php

namespace Drupal\workspace\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\Plugin\PluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Derive an upstream plugin for each workspace.
 */
class Workspace extends DeriverBase implements ContainerDeriverInterface {

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $workspaceStorage;

  /**
   * Workspace constructor.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $workspace_storage
   */
  public function __construct(EntityStorageInterface $workspace_storage) {
    $this->workspaceStorage = $workspace_storage;
  }

  /**
   * @inheritDoc
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_type.manager')->getStorage('workspace')
    );
  }

  /**
   * Add a workspace plugin per workspace, with an ID in the format
   * 'workspace:{id}', for example 'workspace:live'.
   *
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $workspaces = $this->workspaceStorage->loadMultiple();
    foreach ($workspaces as $workspace) {
      $this->derivatives[$workspace->id()] = $base_plugin_definition;
      $this->derivatives[$workspace->id()]['id'] = $base_plugin_definition['id'] . PluginBase::DERIVATIVE_SEPARATOR . $workspace->id();
    }
    return $this->derivatives;
  }

}
