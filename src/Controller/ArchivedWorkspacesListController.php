<?php

namespace Drupal\workspace\Controller;

use Drupal\Core\Controller\ControllerBase;

class ArchivedWorkspacesListController extends ControllerBase {

  public function viewArchivedWorkspaces() {
    $archived_workspaces = $this->entityTypeManager()
      ->getStorage('workspace')
      ->loadByProperties(['published' => FALSE]);
    $headers = [
      $this->t('Workspace'),
      $this->t('Owner'),
      $this->t('Type'),
      $this->t('Status')
    ];
    $rows = [];
    if (!empty($archived_workspaces)) {
      foreach ($archived_workspaces as $entity) {
        $row[] = $entity->label() . ' (' . $entity->getMachineName() . ')';
        $row[] = $entity->getOwner()->getDisplayname();
        $row[] = $entity->get('type')->first()->entity->label();
        // All archived workspaces are inactive.
        $row[] = $this->t('Inactive')->render();
        $rows[] = $row;
      }
    }

    $build['archived-workspaces-list'] = [
      '#type' => 'table',
      '#header' => $headers,
      '#rows' => $rows,
      '#empty' => t('There are no archived workspaces.'),
    ];
    $build['pager'] = [
      '#type' => 'pager',
    ];

    return $build;
  }

}
