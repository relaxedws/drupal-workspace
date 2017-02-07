<?php

namespace Drupal\workspace;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\workspace\Changes\ChangesFactoryInterface;
use Drupal\workspace\Entity\WorkspaceInterface;

/**
 * Class DefaultReplicator
 */
class DefaultReplicator {

  /**
   * @var WorkspaceManagerInterface
   */
  protected $workspaceManager;

  /**
   * @var \Drupal\workspace\Changes\ChangesFactoryInterface
   */
  protected $changesFactory;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * DefaultReplication constructor.
   *
   * @param \Drupal\workspace\WorkspaceManagerInterface $workspace_manager
   * @param \Drupal\workspace\Changes\ChangesFactoryInterface $changes_factory
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct(WorkspaceManagerInterface $workspace_manager, ChangesFactoryInterface $changes_factory, EntityTypeManagerInterface $entity_type_manager) {
    $this->workspaceManager = $workspace_manager;
    $this->changesFactory = $changes_factory;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * @param \Drupal\workspace\Entity\WorkspaceInterface $source
   * @param \Drupal\workspace\Entity\WorkspaceInterface $target
   */
  public function replication(WorkspaceInterface $source, WorkspaceInterface $target) {
    $current_active = $this->workspaceManager->getActiveWorkspace(TRUE);

    // Set the source as the active workspace.
    $this->workspaceManager->setActiveWorkspace($source);

    // Get changes for the current workspace.
    $changes = $this->changesFactory->get($source)->getNormal();
    $rev_diffs = [];
    foreach ($changes as $change) {
      $rev_diffs[$change['type']] = [];
      foreach ($change['changes'] as $change_item) {
        $rev_diffs[$change['type']][] = $change_item['rev'];
      }
    }

    // Get revision diff between source and target
    $content_workspace_ids = [];
    foreach ($rev_diffs as $entity_type_id => $revs) {
      $content_workspace_ids[$entity_type_id] = $this->entityTypeManager
        ->getStorage('content_workspace')
        ->getQuery()
        ->allRevisions()
        ->condition('content_entity_type_id', $entity_type_id)
        ->condition('content_entity_revision_id', $revs, 'IN')
        ->condition('workspace', $target->id())
        ->execute();
    }
    foreach ($content_workspace_ids as $entity_type_id => $ids) {

    }

    $entities = [];
    // Load each missing revision.
    foreach ($rev_diffs as $entity_type_id => $revs) {
      foreach ($revs as $rev) {
        /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
        $entity = $this->entityTypeManager
          ->getStorage($entity_type_id)
          ->loadRevision($rev);
        $entity->workspace->target_id = $target->id();
        $entity->isDefaultRevision(($target->id() == \Drupal::getContainer()->getParameter('workspace.default')));
        $entities[] = $entity;
      }
    }

    // Before saving set the active workspace to the target.
    $this->workspaceManager->setActiveWorkspace($target);

    // Save each revision on the target workspace
    foreach ($entities as $entity) {
      $entity->save();
    }

    // Log
    $this->workspaceManager->setActiveWorkspace($current_active);
  }

}