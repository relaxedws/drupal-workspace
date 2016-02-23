<?php

namespace Drupal\workspace;

use Drupal\multiversion\Entity\Workspace;

/**
 * @Replicator(
 *   id = "internal",
 *   label = "Internal Replicator"
 * )
 */
class InternalReplicator extends ReplicatorBase {

  /**
   * {@inheritdoc}
   */
  public function applies() {
    return true;
  }

  /**
   * {@inheritdoc}
   */
  public function replicate() {
    // Set active workspace to source.
    $source_workspace = Workspace::load($this->source->data()['workspace']);
    $target_workspace = Workspace::load($this->target->data()['workspace']);
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