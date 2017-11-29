<?php

namespace Drupal\workspace\EntityQuery;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Query\Sql\Query as BaseQuery;
use Drupal\workspace\WorkspaceManager;
use Drupal\workspace\WorkspaceManagerInterface;

/**
 * Alters entity queries to use a workspace revision instead of the default one.
 */
class Query extends BaseQuery {

  /**
   * The workspace manager.
   *
   * @var \Drupal\workspace\WorkspaceManagerInterface
   */
  protected $workspaceManager;

  /**
   * Flag indicating whether to query a workspace-specific revision.
   *
   * @var bool
   */
  protected $workspaceRevision = FALSE;

  /**
   * Constructs a Query object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param string $conjunction
   *   - AND: all of the conditions on the query need to match.
   *   - OR: at least one of the conditions on the query need to match.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection to run the query against.
   * @param array $namespaces
   *   List of potential namespaces of the classes belonging to this query.
   * @param \Drupal\workspace\WorkspaceManagerInterface $workspace_manager
   *   The workspace manager.
   */
  public function __construct(EntityTypeInterface $entity_type, $conjunction, Connection $connection, array $namespaces, WorkspaceManagerInterface $workspace_manager) {
    parent::__construct($entity_type, $conjunction, $connection, $namespaces);

    $this->workspaceManager = $workspace_manager;

    // Only alter the query if the active workspace is not the default one and
    // the entity type is supported.
    if ($workspace_manager->getActiveWorkspace() !== WorkspaceManager::DEFAULT_WORKSPACE
        && $workspace_manager->entityTypeCanBelongToWorkspaces($entity_type)) {
      $this->workspaceRevision = TRUE;
      $this->allRevisions = TRUE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function allRevisions() {
    // Do not alter entity revision queries.
    // @todo How about queries for the latest revision? Should we alter them to
    //   look for the latest workspace-specific revision?
    $this->workspaceRevision = FALSE;

    return parent::allRevisions();
  }

  /**
   * {@inheritdoc}
   */
  protected function prepare() {
    parent::prepare();

    if ($this->workspaceRevision) {
      $active_workspace = $this->workspaceManager->getActiveWorkspace();
      $id_field = $this->entityType->getKey('id');

      // LEFT join the Content Workspace entity's field revision table so we can
      // properly include live content along with a possible workspace-specific
      // revision.
      $this->sqlQuery->leftJoin('content_workspace_field_revision', 'cwfr', "cwfr.content_entity_type_id = '{$this->entityTypeId}' AND cwfr.content_entity_id = base_table.$id_field AND cwfr.workspace = '$active_workspace'");
    }

    return $this;
  }

}
