<?php

namespace Drupal\workspace;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\multiversion\Entity\Index\RevisionIndexInterface;
use Drupal\multiversion\Entity\Workspace;
use Drupal\multiversion\Entity\WorkspaceInterface;
use Drupal\replication\ChangesFactoryInterface;
use Drupal\replication\RevisionDiffFactoryInterface;

/**
 * @Replicator(
 *   id = "internal",
 *   label = "Internal Replicator"
 * )
 */
class InternalReplicator implements ReplicatorInterface {

  /** @var  EntityTypeManagerInterface */
  protected $entityTypeManager;

  /** @var  ChangesFactoryInterface */
  protected $changesFactory;

  /** @var  RevisionDiffFactoryInterface */
  protected $revisionDiffFactory;

  /** @var RevisionIndexInterface  */
  protected $revIndex;

  public function __construct(EntityTypeManagerInterface $entity_type_manager, ChangesFactoryInterface $changes_factory, RevisionDiffFactoryInterface $revisiondiff_factory, RevisionIndexInterface $rev_index) {
    $this->entityTypeManager = $entity_type_manager;
    $this->changesFactory = $changes_factory;
    $this->revisionDiffFactory = $revisiondiff_factory;
    $this->revIndex = $rev_index;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(WorkspacePointerInterface $source, WorkspacePointerInterface $target) {
    $source_workspace = $source->getWorkspace();
    $target_workspace = $target->getWorkspace();

    return ($source_workspace instanceof WorkspaceInterface) && ($target_workspace instanceof WorkspaceInterface);
  }

  /**
   * {@inheritdoc}
   */
  public function replicate(WorkspacePointerInterface $source, WorkspacePointerInterface $target) {
    // Set active workspace to source.
    $source_workspace = $source->getWorkspace();
    $target_workspace = $target->getWorkspace();

    $source_info = $this->getPeerInformation($source_workspace);
    $target_info = $this->getPeerInformation($target_workspace);
    $source_changes = $this->changesFactory->get($source_workspace)->getNormal();
    $data = [];
    foreach ($source_changes as $source_change) {
      $data[$source_change['id']] = [];
      foreach ($source_change['changes'] as $change) {
        $data[$source_change['id']][] = $change['rev'];
      }
    }
    $revs_diff = $this->revisionDiffFactory->get($target_workspace)->setRevisionIds($data)->getMissing();
    foreach ($revs_diff as $uuid => $revs) {
      foreach ($revs['missing'] as $rev) {
        $item = $this->revIndex->useWorkspace($source_workspace->id())->get("$uuid:$rev");
        $entity_type_id = $item['entity_type_id'];
        $entity_id = $item['entity_id'];
        $revision_id = $item['revision_id'];
        $storage = $this->entityTypeManager->getStorage($entity_type_id);
        $entity = $storage->loadRevision($revision_id);
        $entity->workspace = $target_workspace;
        $entity->save();
      }
    }

  }

  protected function getPeerInformation(WorkspaceInterface $workspace) {
    return ['update_seq' => (int) $workspace->getUpdateSeq(), 'instance_start_time' => (string) $workspace->getStartTime()];
  }
}

