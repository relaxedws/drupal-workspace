<?php

namespace Drupal\workspace\Plugin\Field;

use Drupal\Core\Field\EntityReferenceFieldItemList;

/**
 * A computed field that provides a content entity's workspace.
 *
 * It links content entities to a workspace configuration entity via a
 * workspace content entity.
 */
class WorkspaceFieldItemList extends EntityReferenceFieldItemList {

  /**
   * Gets the workspace entity linked to a content entity revision.
   *
   * @return \Drupal\workspace\Entity\WorkspaceInterface|null
   *   The workspace configuration entity linked to a content entity
   *   revision.
   */
  protected function getWorkspace() {
    $entity = $this->getEntity();

    if (!$entity->getEntityType()->isRevisionable()) {
      return NULL;
    }

    if ($entity->id() && $entity->getRevisionId()) {
      $revisions = \Drupal::service('entity.query')->get('content_workspace')
        ->condition('content_entity_type_id', $entity->getEntityTypeId())
        ->condition('content_entity_id', $entity->id())
        ->condition('content_entity_revision_id', $entity->getRevisionId())
        ->allRevisions()
        ->sort('revision_id', 'DESC')
        ->execute();

      $revision_to_load = key($revisions);
      if (!empty($revision_to_load)) {
        /** @var \Drupal\workspace\Entity\ContentWorkspaceInterface $content_workspace */
        $content_workspace = \Drupal::entityTypeManager()
          ->getStorage('content_workspace')
          ->loadRevision($revision_to_load);

        // Return the correct translation.
        $langcode = $entity->language()->getId();
        if (!$content_workspace->hasTranslation($langcode)) {
          $content_workspace->addTranslation($langcode);
        }
        if ($content_workspace->language()->getId() !== $langcode) {
          $content_workspace = $content_workspace->getTranslation($langcode);
        }

        return $content_workspace->get('workspace')->entity;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function get($index) {
    if ($index !== 0) {
      throw new \InvalidArgumentException('An entity can not have multiple workspaces at the same time.');
    }
    $this->computeWorkspaceFieldItemList();
    return isset($this->list[$index]) ? $this->list[$index] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getIterator() {
    $this->computeWorkspaceFieldItemList();
    return parent::getIterator();
  }

  /**
   * Recalculate the workspace field item list.
   */
  protected function computeWorkspaceFieldItemList() {
    // Compute the value of the workspace.
    $index = 0;
    if (!isset($this->list[$index]) || $this->list[$index]->isEmpty()) {
      $workspace = $this->getWorkspace();
      // Do not store NULL values in the static cache.
      if ($workspace) {
        $this->list[$index] = $this->createItem($index, ['entity' => $workspace]);
      }
    }
  }

}
