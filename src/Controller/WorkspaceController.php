<?php

namespace Drupal\workspace\Controller;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\multiversion\Entity\Workspace;
use Drupal\multiversion\Entity\WorkspaceType;
use Drupal\multiversion\Entity\WorkspaceTypeInterface;

class WorkspaceController extends ControllerBase {

  public function add() {
    $types = WorkspaceType::loadMultiple();
    if ($types && count($types) == 1) {
      $type = reset($types);
      return $this->addForm($type);
    }
    if (count($types) === 0) {
      return array(
        '#markup' => $this->t('You have not created any Workspace types yet. Go to the <a href=":url">Workspace type creation page</a> to add a new Workspace type.', [
          ':url' => Url::fromRoute('entity.workspace_type.add')->toString(),
        ]),
      );
    }

    return array('#theme' => 'workspace_add_list', '#content' => $types);

  }

  public function addForm(WorkspaceTypeInterface $workspace_type) {
    $workspace = Workspace::create([
      'type' => $workspace_type->id()
    ]);
    return $this->entityFormBuilder()->getForm($workspace);
  }

  public function getAddFormTitle(WorkspaceTypeInterface $workspace_type) {
    return $this->t('Add %type workspace', array('%type' => $workspace_type->label()));
  }

  /**
   * View a list of conflicts for a workspace.
   *
   * @param string $workspace_id
   *   The workspace ID to get conflicts for.
   *
   * @return array
   *   The render array to display for the page.
   */
  public function viewConflicts($workspace_id) {
    // @todo inject these:
    $conflict_tracker = \Drupal::service('workspace.conflict_tracker');
    $entity_index = \Drupal::service('multiversion.entity_index.rev');
    $entity_type_manager = \Drupal::entityTypeManager();

    $workspace = Workspace::load($workspace_id);

    $conflict_tracker->useWorkspace($workspace);
    $conflicts = $conflict_tracker->getAll();

    $entity_revisions = [];
    foreach ($conflicts as $uuid => $conflict) {
      // @todo figure out why this is an array and what to do if there is more than 1
      // @todo what happens when the conflict value is not "available"? what does this mean?
      $rev = reset(array_keys($conflict));
      $rev_info = $entity_index
        ->useWorkspace($workspace->id())
        ->get("$uuid:$rev");

      if (!empty($rev_info['revision_id'])) {
        $entity_revisions[] = $entity_type_manager
          ->getStorage($rev_info['entity_type_id'])
          ->useWorkspace($workspace->id())
          ->loadRevision($rev_info['revision_id']);
      }

    }

    return ['#theme' => 'workspace_conflict_list', '#content' => $entity_revisions];
  }

  /**
   * Get the page title for the list of conflicts page.
   *
   * @return string
   *   The page title.
   */
  public function getViewConflictsTitle() {
    return 'Workspace Conflicts';
  }
}
