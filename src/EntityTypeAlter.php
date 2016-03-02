<?php

namespace Drupal\workspace;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\multiversion\MultiversionManagerInterface;

class EntityTypeAlter {

  /**
   * @var \Drupal\multiversion\MultiversionManagerInterface
   */
  protected $multiversionManager;

  /**
   * Constructs a new Toolbar.
   *
   * @param \Drupal\multiversion\MultiversionManagerInterface $multiversion_manager
   */
  public function __construct(MultiversionManagerInterface $multiversion_manager) {
    $this->multiversionManager = $multiversion_manager;
  }

  /**
   * @param array $entity_types
   */
  public function entityTypeAlter(array &$entity_types) {
    if (isset($entity_types['workspace_type'])) {
      $this->alterWorkspaceType($entity_types['workspace_type']);
    }

    if (isset($entity_types['workspace'])) {
      $this->alterWorkspace($entity_types['workspace']);
    }

    foreach ($entity_types as $entity_type) {
      $this->addRevisionLinks($entity_type);
    }
  }

  /**
   * @param \Drupal\Core\Entity\EntityTypeInterface $workspace_type
   */
  protected function alterWorkspaceType(EntityTypeInterface &$workspace_type) {
    $workspace_type->setHandlerClass('list_builder', 'Drupal\workspace\WorkspaceTypeListBuilder');
    $workspace_type->setHandlerClass('route_provider', ['html' => 'Drupal\Core\Entity\Routing\AdminHtmlRouteProvider']);
    $workspace_type->setHandlerClass('form', [
      'default' => 'Drupal\workspace\Entity\Form\WorkspaceTypeForm',
      'add' => 'Drupal\workspace\Entity\Form\WorkspaceTypeForm',
      'edit' => 'Drupal\workspace\Entity\Form\WorkspaceTypeForm',
      'delete' => 'Drupal\workspace\Entity\Form\WorkspaceTypeDeleteForm'
    ]);
    $workspace_type->setLinkTemplate('edit-form', '/admin/structure/workspace/types/{workspace_type}/edit');
    $workspace_type->setLinkTemplate('delete-form', '/admin/structure/workspace/types/{workspace_type}/delete');
    $workspace_type->setLinkTemplate('collection', '/admin/structure/workspace/types');
  }

  /**
   * @param \Drupal\Core\Entity\EntityTypeInterface $workspace
   */
  protected function alterWorkspace(EntityTypeInterface &$workspace) {
    $workspace ->setHandlerClass('list_builder', 'Drupal\workspace\WorkspaceListBuilder');
    $workspace->setHandlerClass('route_provider', ['html' => 'Drupal\Core\Entity\Routing\AdminHtmlRouteProvider']);
    $workspace->setHandlerClass('form', [
      'default' => 'Drupal\workspace\Entity\Form\WorkspaceForm',
      'add' => 'Drupal\workspace\Entity\Form\WorkspaceForm',
      'edit' => 'Drupal\workspace\Entity\Form\WorkspaceForm',
    ]);
    $workspace->setLinkTemplate('edit-form', '/admin/structure/workspace/{workspace}/edit');
    $workspace->setLinkTemplate('collection', '/admin/structure/workspace');
    $workspace->set('field_ui_base_route', 'entity.workspace_type.edit_form');
  }

  /**
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   */
  protected function addRevisionLinks(EntityTypeInterface &$entity_type) {
    if ($this->multiversionManager->isEnabledEntityType($entity_type)) {
      if ($entity_type->hasViewBuilderClass() && $entity_type->hasLinkTemplate('canonical')) {
        $entity_type->setLinkTemplate('version-tree', $entity_type->getLinkTemplate('canonical') . '/tree');
        $entity_type->setLinkTemplate('version-history', $entity_type->getLinkTemplate('canonical') . '/revisions');
        $entity_type->setLinkTemplate('revision', $entity_type->getLinkTemplate('canonical') . '/revisions/{' . $entity_type->id() . '_revision}/view');
      }
    }
  }
}