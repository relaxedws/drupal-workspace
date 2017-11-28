<?php

namespace Drupal\workspace\Controller;

use Doctrine\CouchDB\CouchDBClient;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\multiversion\Entity\Index\RevisionIndexInterface;
use Drupal\multiversion\Entity\WorkspaceInterface;
use Drupal\multiversion\Workspace\WorkspaceManagerInterface;
use Drupal\replication\ChangesFactoryInterface;
use Drupal\replication\ReplicationTask\ReplicationTask;
use Drupal\replication\RevisionDiffFactoryInterface;
use LogicException;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ChangesListController extends ControllerBase {

  /**
   * @var \Drupal\multiversion\Workspace\WorkspaceManagerInterface
   */
  protected $workspaceManager;

  /**
   * @var ChangesFactoryInterface
   */
  protected $changesFactory;

  /**
   * @var RevisionDiffFactoryInterface
   */
  protected $revisionDiffFactory;

  /**
   * @var RevisionIndexInterface
   */
  protected $revIndex;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * ChangesListController constructor.
   *
   * @param \Drupal\multiversion\Workspace\WorkspaceManagerInterface $workspace_manager
   * @param \Drupal\replication\ChangesFactoryInterface $changes_factory
   * @param \Drupal\replication\RevisionDiffFactoryInterface $revisiondiff_factory
   * @param \Drupal\multiversion\Entity\Index\RevisionIndexInterface $rev_index
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct(WorkspaceManagerInterface $workspace_manager, ChangesFactoryInterface $changes_factory, RevisionDiffFactoryInterface $revisiondiff_factory, RevisionIndexInterface $rev_index, EntityTypeManagerInterface $entity_type_manager) {
    $this->workspaceManager = $workspace_manager;
    $this->changesFactory = $changes_factory;
    $this->revisionDiffFactory = $revisiondiff_factory;
    $this->revIndex = $rev_index;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('workspace.manager'),
      $container->get('replication.changes_factory'),
      $container->get('replication.revisiondiff_factory'),
      $container->get('multiversion.entity_index.rev'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * View a list of changes between current workspace and the target workspace.
   *
   * @param int $workspace
   *
   * @return array The render array to display for the page.
   * The render array to display for the page.
   */
  public function viewChanges($workspace) {
    $source_workspace = $this->workspaceManager->load($workspace);
    $source_workspace_pointer = $this->getPointerToWorkspace($source_workspace);
    $target_workspace_pointer = $source_workspace->get('upstream')->entity;
    if (!$target_workspace_pointer) {
      return ['#content' => $this->t('The upstream is not set.')];
    }
    $target_workspace = $target_workspace_pointer->getWorkspace();
    if ($target_workspace instanceof WorkspaceInterface) {
      $entities = $this->getChangesBetweenLocalWorkspaces($source_workspace, $target_workspace);
    }
    else {
      $entities = $this->getChangesBetweenRemoteWorkspaces($source_workspace_pointer, $target_workspace_pointer);
    }

    return $this->adminOverview($entities);
  }

  /**
   * Return the array with changed entities for workspaces on the same site.
   *
   * @param $source_workspace
   * @param $target_workspace
   *
   * @return array
   */
  protected function getChangesBetweenLocalWorkspaces($source_workspace, $target_workspace) {
    // @todo Check if it's possible to get the since value from replication log.
    $since = \Drupal::state()->get('last_sequence.workspace.' . $source_workspace->id(), 0);
    // Get changes on the source workspace.
    $source_changes = $this->changesFactory->get($source_workspace)
      ->setSince($since)
      ->getNormal();
    $data = [];
    foreach (array_reverse($source_changes) as $source_change) {
      $data[$source_change['id']] = [];
      foreach ($source_change['changes'] as $change) {
        $data[$source_change['id']][] = $change['rev'];
      }
    }

    // Get revisions the target workspace is missing.
    $revs_diff = $this->revisionDiffFactory->get($target_workspace)->setRevisionIds($data)->getMissing();
    return $this->getEntitiesFromRevsDiff($source_workspace, $revs_diff);
  }

  /**
   * Return the array with changed entities when target is a remote workspace.
   *
   * @param $source_workspace_pointer \Drupal\workspace\WorkspacePointerInterface
   * @param $target_workspace_pointer \Drupal\workspace\WorkspacePointerInterface
   *
   * @return array
   */
  protected function getChangesBetweenRemoteWorkspaces($source_workspace_pointer, $target_workspace_pointer) {
    $since = \Drupal::state()->get('last_sequence.workspace.' . $source_workspace_pointer->getWorkspaceId(), 0);
    $replicator = \Drupal::service('relaxed.couchdb_replicator');
    /** @var CouchDBClient $source */
    $source = $replicator->setupEndpoint($source_workspace_pointer);
    /** @var CouchDBClient $target */
    $target = $replicator->setupEndpoint($target_workspace_pointer);
    $revs_diff = [];
    $source_workspace = $source_workspace_pointer->getWorkspace();
    $task = $this->getTask($source_workspace, 'push_replication_settings');
    while (1) {
      $changes = $source->getChanges(
        array(
          'feed' => 'normal',
          'style' => 'all_docs',
          'since' => $since,
          'filter' => $task->getFilter(),
          'parameters' => $task->getParameters(),
          'doc_ids' => NULL,
          'limit' => 100,
        )
      );
      if (empty($changes['results']) || empty($changes['last_seq'])) {
        break;
      }
      $data = [];
      foreach (array_reverse($changes['results']) as $source_change) {
        $data[$source_change['id']] = [];
        foreach ($source_change['changes'] as $change) {
          $data[$source_change['id']][] = $change['rev'];
        }
      }
      $revs_diff += !empty($data) ? $target->getRevisionDifference($data) : [];
      if (!in_array($changes['last_seq'], array_column($changes['results'], 'seq'))) {
        break;
      }
      $since = $changes['last_seq'];
    }

    return $this->getEntitiesFromRevsDiff($source_workspace, $revs_diff);
  }

  /**
   * @param $source_workspace \Drupal\multiversion\Entity\WorkspaceInterface
   * @param $revs_diff array
   *
   * @return array
   */
  protected function getEntitiesFromRevsDiff($source_workspace, $revs_diff) {
    $entities = [];
    foreach ($revs_diff as $uuid => $revs) {
      foreach ($revs['missing'] as $rev) {
        $item = $this->revIndex->useWorkspace($source_workspace->id())->get("$uuid:$rev");
        $entity_type_id = $item['entity_type_id'];
        $revision_id = $item['revision_id'];

        $storage = $this->entityTypeManager()->getStorage($entity_type_id);
        $entity = $storage->loadRevision($revision_id);
        if ($entity instanceof ContentEntityInterface) {
          $entities[] = $entity;
        }
      }
    }
    return $entities;
  }

  /**
   * Returns an administrative overview of all changes.
   *
   * @param array $entities
   *
   * @return array A render array representing the administrative page content.
   *   A render array representing the administrative page content.
   */
  protected function adminOverview(array $entities = []) {
    $rows = [];

    $headers = [t('Entities'), t('Entity type'), t('Operations')];
    /** @var \Drupal\Core\Entity\ContentEntityInterface[] $entities */
    foreach ($entities as $entity) {
      $row = [
        $entity->label() ?: '*** ' . $this->t('No label for this entity') . ' ***',
        $entity->getEntityTypeId(),
      ];
      $links = [];
      if ($entity->hasLinkTemplate('canonical')) {
        $links['view'] = [
          'title' => t('View'),
          'url' => $entity->toUrl('canonical', ['absolute' => TRUE]),
        ];
      }
      else {
        $links['view'] = [
          'title' => t('No view link for this entity'),
        ];
      }
      $row[] = [
        'data' => [
          '#type' => 'operations',
          '#links' => $links,
        ],
      ];
      $rows[] = $row;
    }

    $build['prefix']['#markup'] = '<p>' . t('The array is sorted by last change first.') . '</p>';

    $build['changes-list'] = [
      '#type' => 'table',
      '#header' => $headers,
      '#rows' => $rows,
      '#empty' => t('There are no changes.'),
    ];

    $build['pager'] = [
      '#type' => 'pager',
    ];

    return $build;
  }

  /**
   * Get the page title for the list of changes page.
   *
   * @param int $workspace
   *
   * @return string The page title.
   * The page title.
   */
  public function getViewChangesTitle($workspace) {
    /** @var WorkspaceInterface $active_workspace */
    $active_workspace = $this->workspaceManager->load($workspace);
    $active_workspace_label = $active_workspace->label();
    $target_workspace_pointer = $active_workspace->get('upstream')->entity;
    if (!$target_workspace_pointer) {
      $target_workspace_label = $this->t('target');
    }
    else {
      $target_workspace_label = $target_workspace_pointer->label();
    }
    return $this->t(
      'Changes between @source workspace and @target workspace',
      [
        '@source' => $active_workspace_label,
        '@target' => $target_workspace_label
      ]
    );
  }

  /**
   * Returns a pointer to the specified workspace.
   *
   * In most cases this pointer will be unique, but that is not guaranteed
   * by the schema. If there are multiple pointers, which one is returned is
   * undefined.
   *
   * @param \Drupal\multiversion\Entity\WorkspaceInterface $workspace
   *   The workspace for which we want a pointer.
   *
   * @return \Drupal\workspace\WorkspacePointerInterface
   *   The pointer to the provided workspace.
   */
  protected function getPointerToWorkspace(WorkspaceInterface $workspace) {
    $pointers = $this->entityTypeManager
      ->getStorage('workspace_pointer')
      ->loadByProperties(['workspace_pointer' => $workspace->id()]);
    $pointer = reset($pointers);
    return $pointer;
  }

  /**
   * Create a task using workspace info.
   *
   * @param \Drupal\multiversion\Entity\WorkspaceInterface $entity
   * @param $field_name
   *
   * @return \Drupal\replication\ReplicationTask\ReplicationTask
   */
  protected function getTask(WorkspaceInterface $entity, $field_name) {
    $task = new ReplicationTask();
    $items = $entity->get($field_name);

    if (!$items instanceof EntityReferenceFieldItemListInterface) {
      throw new LogicException('Replication settings field does not exist.');
    }

    $referenced_entities = $items->referencedEntities();
    if (count($referenced_entities) > 0) {
      $task->setFilter($referenced_entities[0]->getFilterId());
      $task->setParameters($referenced_entities[0]->getParameters());
    }

    return $task;
  }

}
