<?php

namespace Drupal\workspace;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class to build a listing of workspace entities.
 *
 * @see \Drupal\workspace\Entity\Workspace
 */
class WorkspaceListBuilder extends EntityListBuilder {

  /**
   * @var \Drupal\workspace\WorkspaceManagerInterface
   */
  protected $workspaceManager;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('workspace.manager')
    );
  }

  /**
   * Constructs a new EntityListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\workspace\WorkspaceManagerInterface $workspace_manager
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, WorkspaceManagerInterface $workspace_manager) {
    parent::__construct($entity_type, $storage);
    $this->workspaceManager = $workspace_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = t('Workspace');
    $header['uid'] = t('Owner');
    $header['status'] = t('Status');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\workspace\Entity\WorkspaceInterface $entity */
    $row['label'] = $entity->label() . ' (' . $entity->getMachineName() . ')';
    $row['owner'] = $entity->getOwner()->getDisplayname();
    $active_workspace = $this->workspaceManager->getActiveWorkspace();
    $row['status'] = $active_workspace == $entity->id() ? 'Active' : 'Inactive';
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    /** @var \Drupal\workspace\Entity\WorkspaceInterface $entity */
    $operations = parent::getDefaultOperations($entity);
    if (isset($operations['edit'])) {
      $operations['edit']['query']['destination'] = $entity->url('collection');
    }

    $active_workspace = $this->workspaceManager->getActiveWorkspace();
    if ($entity->id() != $active_workspace) {
      $operations['activate'] = [
        'title' => $this->t('Set Active'),
        'weight' => 20,
        'url' => $entity->urlInfo('activate-form', ['query' => ['destination' => $entity->url('collection')]]),
      ];
    }

    if ('workspace:' . $entity->id() != $entity->get('upstream')->value) {
      $operations['deployment'] = [
        'title' => $this->t('Deploy content'),
        'weight' => 20,
        'url' => $entity->urlInfo('deployment-form', ['query' => ['destination' => $entity->url('collection')]]),
      ];
    }

    return $operations;
  }

}
