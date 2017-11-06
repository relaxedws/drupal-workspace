<?php

namespace Drupal\workspace\Index;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\workspace\KeyValueStore\KeyValueSortedSetFactoryInterface;
use Drupal\Workspace\WorkspaceManagerInterface;

/**
 * Provides the default implementation for a sequence index.
 */
class SequenceIndex implements SequenceIndexInterface {

  /**
   * The collection prefix for the sorted set key/value store.
   *
   * @var string
   */
  protected $collectionPrefix = 'workspace.sequence_index.';

  /**
   * The ID of the workspace to use.
   *
   * @var string
   */
  protected $workspaceId;

  /**
   * The sorted set key/value factory.
   *
   * @var \Drupal\workspace\KeyValueStore\KeyValueSortedSetFactoryInterface
   */
  protected $sortedSetFactory;

  /**
   * The workspace manager.
   *
   * @var \Drupal\workspace\WorkspaceManagerInterface
   */
  protected $workspaceManager;

  /**
   * Constructs a new SequenceIndex.
   *
   * @param \Drupal\workspace\KeyValueStore\KeyValueSortedSetFactoryInterface $sorted_set_factory
   *   The sorted set key/value factory.
   * @param \Drupal\Workspace\WorkspaceManagerInterface $workspace_manager
   *   The workspace manager.
   */
  public function __construct(KeyValueSortedSetFactoryInterface $sorted_set_factory, WorkspaceManagerInterface $workspace_manager) {
    $this->sortedSetFactory = $sorted_set_factory;
    $this->workspaceManager = $workspace_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function useWorkspace($id) {
    $this->workspaceId = $id;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function add(ContentEntityInterface $entity) {
    $record = $this->buildRecord($entity);
    $this->sortedSetStore()->add($record['seq'], $record);
  }

  /**
   * {@inheritdoc}
   */
  public function getRange($start, $stop = NULL) {
    $range = $this->sortedSetStore()->getRange($start, $stop);
    return $range;
  }

  /**
   * {@inheritdoc}
   */
  public function getLastSequenceId() {
    $max_key = $this->sortedSetStore()->getMaxKey();
    return $max_key;
  }

  /**
   * Gets the sorted set key/value collection for a given workspace ID.
   *
   * @param string $workspace_id
   *   (optional) A workspace ID to use. Defaults to NULL, which means the
   *   current active workspace is used.
   *
   * @return \Drupal\workspace\KeyValueStore\KeyValueStoreSortedSetInterface
   */
  protected function sortedSetStore($workspace_id = NULL) {
    if (!$workspace_id) {
      $workspace_id = $this->workspaceId ?: $this->workspaceManager->getActiveWorkspace()->id();
    }
    return $this->sortedSetFactory->get($this->collectionPrefix . $workspace_id);
  }

  /**
   * Builds a record to add to the sorted set key/value store.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   A content entity object.
   *
   * @return array
   *   An array containing the relevant information about the given entity.
   */
  protected function buildRecord(ContentEntityInterface $entity) {
    return [
      'entity_type_id' => $entity->getEntityTypeId(),
      'entity_id' => $entity->id(),
      'entity_uuid' => $entity->uuid(),
      'revision_id' => $entity->getRevisionId(),
      'seq' => (int) (microtime(TRUE) * 1000000),
    ];
  }

}
