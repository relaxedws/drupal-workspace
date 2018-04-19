<?php

namespace Drupal\workspace\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\multiversion\Entity\Storage\ContentEntityStorageInterface;
use Drupal\multiversion\Entity\Workspace;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class DeletedWorkspaceQueue
 *
 * @QueueWorker(
 *   id = "deleted_workspace_queue",
 *   title = @Translation("Queue of deleted workspaces"),
 *   cron = {"time" = 60}
 * )
 */
class DeletedWorkspaceQueue extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

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
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }


  /**
   * @param mixed $data
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function processItem($data) {
    /** @var \Drupal\multiversion\Workspace\WorkspaceManagerInterface $workspace_manager */
    $workspace_manager = \Drupal::service('workspace.manager');
    $storage = $this->entityTypeManager->getStorage($data['entity_type_id']);
    $active_workspace_id = $workspace_manager->getActiveWorkspaceId();
    if ($data['entity_type_id'] != 'workspace' && $data['workspace'] != $active_workspace_id) {
      $workspace = Workspace::load($data['workspace']);
      if ($workspace) {
        $workspace_manager->setActiveWorkspace($workspace);
        $storage->useWorkspace($data['workspace']);
      }
    }
    $entity = $storage->load($data['entity_id']);
    if ($entity && $storage instanceof ContentEntityStorageInterface) {
      $storage->purge([$entity]);
    }
    elseif ($entity) {
      $storage->delete([$entity]);
    }
  }
}