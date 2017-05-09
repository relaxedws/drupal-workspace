<?php

namespace Drupal\workspace\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
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
   */
  public function processItem($data) {
    $storage = $this->entityTypeManager->getStorage($data['entity_type_id'])->useWorkspace($data['workspace']);
    $entity = $storage->load($data['entity_id']);
    $storage->purge([$entity]);
  }
}