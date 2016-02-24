<?php

namespace Drupal\workspace;

use Drupal\multiversion\Entity\Workspace;
use Drupal\multiversion\Entity\WorkspaceInterface;

/**
 * @Replicator(
 *   id = "internal",
 *   label = "Internal Replicator"
 * )
 */
class InternalReplicator implements ReplicatorInterface{

  /**
   * {@inheritdoc}
   */
  public function applies(PointerInterface $source, PointerInterface $target) {
    if (isset($source->data()['workspace']) && isset($target->data()['workspace'])) {
      $source_workspace = Workspace::load($source->data()['workspace']);
      $target_workspace = Workspace::load($target->data()['workspace']);
      if (($source_workspace instanceof WorkspaceInterface) && ($target_workspace instanceof WorkspaceInterface)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function replicate(PointerInterface $source, PointerInterface $target) {
    // Set active workspace to source.
    $source_workspace = Workspace::load($source->data()['workspace']);
    $target_workspace = Workspace::load($target->data()['workspace']);
    \Drupal::service('workspace.manager')->setActiveWorkspace($source_workspace);
    // Get multiversion supported content entities.
    $entity_types = \Drupal::service('multiversion.manager')->getSupportedEntityTypes();
    // Load all entities.
    foreach ($entity_types as $entity_type) {
      $entities = \Drupal::service('entity_type.manager')->getStorage($entity_type->id())->loadMultiple();
      foreach ($entities as $entity) {
        // Add target workspace id to the workspace field.
        $entity->workspace = $target_workspace;
        $entity->save();
      }
    }
  }

}