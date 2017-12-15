<?php

namespace Drupal\workspace\Plugin\Deriver;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a local repository handler plugin for each workspace.
 */
class LocalWorkspaceRepositoryHandlerDeriver extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The workspace entity storage handler.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $workspaceStorage;

  /**
   * Constructs a new LocalWorkspaceRepositoryHandlerDeriver..
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
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = [];

    // Provide a local repository handler plugin for each workspace.
    foreach ($this->workspaceStorage->loadMultiple() as $workspace_id => $workspace) {
      $this->derivatives[$workspace_id] = $base_plugin_definition;
      $this->derivatives[$workspace_id]['label'] = $workspace->label();
      $this->derivatives[$workspace_id]['category'] = $base_plugin_definition['label'];
    }
    return $this->derivatives;
  }

}
