<?php

namespace Drupal\workspace;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\multiversion\Entity\Index\RevisionIndexInterface;
use Drupal\multiversion\Entity\Workspace;
use Drupal\multiversion\Entity\WorkspaceInterface;
use Drupal\multiversion\MultiversionManagerInterface;
use Drupal\multiversion\Workspace\WorkspaceManagerInterface;
use Drupal\replication\ChangesFactoryInterface;
use Drupal\replication\Entity\ReplicationLog;
use Drupal\replication\RevisionDiffFactoryInterface;

/**
 * @Replicator(
 *   id = "internal",
 *   label = "Internal Replicator"
 * )
 */
class InternalReplicator implements ReplicatorInterface {

  /** @var \Drupal\multiversion\Workspace\WorkspaceManagerInterface  */
  protected $workspaceManager;

  /** @var  EntityTypeManagerInterface */
  protected $entityTypeManager;

  /** @var  ChangesFactoryInterface */
  protected $changesFactory;

  /** @var  RevisionDiffFactoryInterface */
  protected $revisionDiffFactory;

  /** @var RevisionIndexInterface  */
  protected $revIndex;

  public function __construct(WorkspaceManagerInterface $workspace_manager, EntityTypeManagerInterface $entity_type_manager, ChangesFactoryInterface $changes_factory, RevisionDiffFactoryInterface $revisiondiff_factory, RevisionIndexInterface $rev_index) {
    $this->workspaceManager = $workspace_manager;
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
    $missing_found = 0;
    $docs_read = 0;
    $docs_written = 0;
    $doc_write_failures = 0;
    // Get the source and target workspaces
    $source_workspace = $source->getWorkspace();
    $target_workspace = $target->getWorkspace();
    // Set active workspace to source
    $this->workspaceManager->setActiveWorkspace($source_workspace);
    // Fetch the site time
    $start_time = new \DateTime();
    // Get changes on the source workspace
    $source_changes = $this->changesFactory->get($source_workspace)->getNormal();
    $data = [];
    foreach ($source_changes as $source_change) {
      $data[$source_change['id']] = [];
      foreach ($source_change['changes'] as $change) {
        $data[$source_change['id']][] = $change['rev'];
      }
    }
    // Get revisions the target workspace is missing
    $revs_diff = $this->revisionDiffFactory->get($target_workspace)->setRevisionIds($data)->getMissing();
    foreach ($revs_diff as $uuid => $revs) {
      foreach ($revs['missing'] as $rev) {
        $missing_found++;
        $item = $this->revIndex->useWorkspace($source_workspace->id())->get("$uuid:$rev");
        $entity_type_id = $item['entity_type_id'];
        $revision_id = $item['revision_id'];
        $storage = $this->entityTypeManager->getStorage($entity_type_id);
        $entity = $storage->loadRevision($revision_id);
        if ($entity instanceof ContentEntityInterface) {
          $docs_read++;
          $entity->workspace = $target_workspace;
          if ($entity->save()) {
            $docs_written++;
          }
          else {
            $doc_write_failures++;
          }
        }
      }
    }
    $end_time = new \DateTime();
    $replication_log = ReplicationLog::create([
      'ok' => TRUE,
      'session_id' => \md5($start_time->getTimestamp()),
      'source_last_seq' => $source_workspace->getUpdateSeq(),
      'history' => [
        'docs_read' => $docs_read,
        'docs_written' => $docs_written,
        'doc_write_failures' => $doc_write_failures,
        'missing_checked' => count($source_changes),
        'missing_found' => $missing_found,
        'start_time' => $start_time->format('D, d M Y H:i:s e'),
        'end_time' => $end_time->format('D, d M Y H:i:s e'),
        'session_id' => \md5($start_time->getTimestamp()),
        'start_last_seq' => $source_workspace->getUpdateSeq(),
      ]
    ]);
    $replication_log->save();
    return $replication_log;
  }

  protected function generateReplicationId(WorkspacePointerInterface $source, WorkspacePointerInterface $target) {
    return \md5(
      $source->getWorkspace()->getMachineName() .
      $target->getWorkspace()->getMachineName()
    );
  }

}
