<?php

namespace Drupal\workspace\Plugin\Field;

use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;

/**
 * A computed field that provides a content entity's workspace.
 *
 * It links content entities to a workspace configuration entity via a
 * workspace content entity.
 */
class WorkspaceFieldItemList extends EntityReferenceFieldItemList {

  use ComputedItemListTrait;

  /**
   * {@inheritdoc}
   */
  protected function computeValue() {
    $workspaces = $this->getWorkspaces();
    foreach ($workspaces as $index => $workspace) {
      $this->list[$index] = $this->createItem($index, ['entity' => $workspace]);
    }
  }

  /**
   * Gets the workspace entities linked to a content entity revision.
   *
   * @return \Drupal\workspace\Entity\WorkspaceInterface[]
   *   An array of workspace entities in which a entity revision appears.
   */
  protected function getWorkspaces() {
    $entity = $this->getEntity();
    $workspaces = [];

    if (!$entity->isNew()) {
      $revisions = \Drupal::service('entity.query')->get('content_workspace')
        ->condition('content_entity_type_id', $entity->getEntityTypeId())
        ->condition('content_entity_id', $entity->id())
        // Ensure the correct revision is loaded in scenarios where a revision
        // is being reverted.
        ->condition('content_entity_revision_id', $entity->isNewRevision() ? $entity->getLoadedRevisionId() : $entity->getRevisionId())
        ->allRevisions()
        ->sort('revision_id', 'DESC')
        ->accessCheck(FALSE)
        ->execute();

      foreach ($revisions as $revision_id => $entity_id) {
        /** @var \Drupal\workspace\Entity\ContentWorkspaceInterface $content_workspace */
        $content_workspace = \Drupal::entityTypeManager()
          ->getStorage('content_workspace')
          ->loadRevision($revision_id);

        // Return the correct translation.
        if ($entity->getEntityType()->hasKey('langcode')) {
          $langcode = $entity->language()->getId();
          if (!$content_workspace->hasTranslation($langcode)) {
            $content_workspace->addTranslation($langcode);
          }
          if ($content_workspace->language()->getId() !== $langcode) {
            $content_workspace = $content_workspace->getTranslation($langcode);
          }
        }

        $workspaces[] = $content_workspace->get('workspace')->entity;
      }
    }
    return $workspaces;
  }

}
