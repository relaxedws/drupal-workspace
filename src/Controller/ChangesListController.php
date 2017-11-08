<?php

namespace Drupal\workspace\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\multiversion\Entity\Index\RevisionIndexInterface;
use Drupal\multiversion\Workspace\WorkspaceManagerInterface;
use Drupal\replication\ChangesFactoryInterface;
use Drupal\replication\RevisionDiffFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
   * ChangesListController constructor.
   *
   * @param \Drupal\multiversion\Workspace\WorkspaceManagerInterface $workspace_manager
   * @param \Drupal\replication\ChangesFactoryInterface $changes_factory
   * @param \Drupal\replication\RevisionDiffFactoryInterface $revisiondiff_factory
   * @param \Drupal\multiversion\Entity\Index\RevisionIndexInterface $rev_index
   */
  public function __construct(WorkspaceManagerInterface $workspace_manager, ChangesFactoryInterface $changes_factory, RevisionDiffFactoryInterface $revisiondiff_factory, RevisionIndexInterface $rev_index) {
    $this->workspaceManager = $workspace_manager;
    $this->changesFactory = $changes_factory;
    $this->revisionDiffFactory = $revisiondiff_factory;
    $this->revIndex = $rev_index;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('workspace.manager'),
      $container->get('replication.changes_factory'),
      $container->get('replication.revisiondiff_factory'),
      $container->get('multiversion.entity_index.rev')
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
    if ($source_workspace === NULL) {
      throw new NotFoundHttpException();
    }
    $target_workspace_pointer = $source_workspace->get('upstream')->entity;
    if (!$target_workspace_pointer) {
      return ['#content' => $this->t('The target workspace is not set for the @label workspace.', ['@label' => $workspace])];
    }
    $entities = $this->getChanges($source_workspace);

    return $this->adminOverview($entities);
  }

  /**
   * Return the array with changed entities on a specific workspace.
   *
   * @param $source_workspace
   *
   * @return array
   */
  protected function getChanges($source_workspace) {
    $workspace_id = $source_workspace->id();
    $since = \Drupal::state()->get('last_sequence.workspace.' . $workspace_id, 0);
    // Get all changes on the source workspace starting with last sequence.
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
    $entities = [];
    foreach ($data as $uuid => $revs) {
      foreach ($revs as $rev) {
        $item = $this->revIndex->useWorkspace($workspace_id)->get("$uuid:$rev");
        $entity_type_id = $item['entity_type_id'];
        $revision_id = $item['revision_id'];

        $storage = $this->entityTypeManager()->getStorage($entity_type_id)->useWorkspace($workspace_id);
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

}
