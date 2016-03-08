<?php

namespace Drupal\workspace;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\multiversion\Entity\Workspace;
use Drupal\multiversion\Entity\WorkspaceInterface;
use Drupal\multiversion\MultiversionManagerInterface;
use Drupal\multiversion\Workspace\WorkspaceManagerInterface;

/**
 * @Replicator(
 *   id = "internal",
 *   label = "Internal Replicator"
 * )
 */
class InternalReplicator implements ReplicatorInterface {

  /** @var  WorkspaceManagerInterface */
  protected $workspaceManager;

  /** @var  MultiversionManagerInterface */
  protected $multiversionManager;

  /** @var  EntityTypeManagerInterface */
  protected $entityTypeManager;

  public function __construct(WorkspaceManagerInterface $workspace_manager, MultiversionManagerInterface $multiversion_manager, EntityTypeManagerInterface $entity_type_manager) {
    $this->workspaceManager = $workspace_manager;
    $this->multiversionManager = $multiversion_manager;
    $this->entityTypeManager = $entity_type_manager;
  }

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
    $this->workspaceManager->setActiveWorkspace($source_workspace);
    // Get multiversion supported content entities.
    $entity_types = $this->multiversionManager->getSupportedEntityTypes();
    // Load all entities.
    foreach ($entity_types as $entity_type) {
      $entities = $this->entityTypeManager->getStorage($entity_type->id())->loadMultiple();
      foreach ($entities as $entity) {
        // Add target workspace id to the workspace field.
        $entity->workspace = $target_workspace;
        $entity->save();
      }
    }
  }

}