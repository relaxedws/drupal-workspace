<?php

namespace Drupal\workspace;

use Drupal\Core\Entity\EntityTypeInterface;

class EntityTypeAlter {

  /**
   * @param array $entity_types
   */
  public function entityTypeAlter(array &$entity_types) {
    $this->alterWorkspaceType($entity_types['workspace_type']);
    $this->alterWorkspace($entity_types['workspace']);
  }

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

  protected function alterWorkspace(EntityTypeInterface &$workspace) {
    $workspace ->setHandlerClass('list_builder', 'Drupal\workspace\WorkspaceListBuilder');
    $workspace->setHandlerClass('route_provider', ['html' => 'Drupal\Core\Entity\Routing\AdminHtmlRouteProvider']);
    $workspace->setHandlerClass('form', [
      'default' => 'Drupal\workspace\Entity\Form\WorkspaceForm',
      'add' => 'Drupal\workspace\Entity\Form\WorkspaceForm',
      'edit' => 'Drupal\workspace\Entity\Form\WorkspaceForm',
    ]);
    $workspace->setLinkTemplate('canonical', '/admin/structure/workspace/{workspace}');
    $workspace->setLinkTemplate('edit-form', '/admin/structure/workspace/{workspace}/edit');
    $workspace->setLinkTemplate('collection', '/admin/structure/workspace');
    $workspace->set('field_ui_base_route', 'entity.workspace_type.edit_form');
  }
}