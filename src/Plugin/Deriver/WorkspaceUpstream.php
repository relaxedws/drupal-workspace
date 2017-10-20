<?php

namespace Drupal\workspace\Plugin\Deriver;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\Plugin\PluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Derives an upstream plugin for each workspace.
 */
class WorkspaceUpstream extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The workspace entity storage handler.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $workspaceStorage;

  /**
   * Constructs a new Workspace plugin deriver.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $workspace_storage
   *   The workspace entity storage handler.
   */
  public function __construct(EntityStorageInterface $workspace_storage) {
    $this->workspaceStorage = $workspace_storage;
  }

  /**
   * {@inheritdoc}
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
