<?php

namespace Drupal\workspace\Plugin\Replicator;

use Drupal\workspace\Plugin\ReplicatorBase;

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
  public function push() {
    // Set active workspace to source.
    \Drupal::service('workspace.manager')->setActiveWorkspace($this->source);
    // Get multiversion supported content entities.
    $entity_types = \Drupal::service('multiversion.manager')->getSupportedEntityTypes();
    // Load all entities.
    foreach ($entity_types as $entity_type) {
      $entities = \Drupal::service('entity_type.manager')->getStorage($entity_type->id())->loadMultiple();
      foreach ($entities as $entity) {
        // Add target workspace id to the workspace field.
        $id = $this->target->id();
        $entity->workspace = $this->target;
        $workspace = $entity->workspace;
        $entity->save();
      }
    }
  }

}