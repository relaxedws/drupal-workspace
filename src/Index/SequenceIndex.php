<?php

namespace Drupal\workspace\Index;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\workspace\KeyValueStore\KeyValueSortedSetFactoryInterface;
use Drupal\Workspace\WorkspaceManagerInterface;

/**
 * Class SequenceIndex
 */
class SequenceIndex implements SequenceIndexInterface {

  /**
   * @var string
   */
  protected $collectionPrefix = 'workspace.sequence_index.';

  /**
   * @var string
   */
  protected $workspaceId;

  /**
   * @var \Drupal\workspace\KeyValueStore\KeyValueSortedSetFactoryInterface
   */
  protected $sortedSetFactory;

  /**
   * @var \Drupal\workspace\WorkspaceManagerInterface
   */
  protected $workspaceManager;


  /**
   * @param \Drupal\workspace\KeyValueStore\KeyValueSortedSetFactoryInterface $sorted_set_factory
   * @param \Drupal\Workspace\WorkspaceManagerInterface $workspace_manager
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
    $workspace_id = NULL;
    $record = $this->buildRecord($entity);
    if ($entity->getEntityType()->get('workspace') === FALSE) {
      $workspace_id = 0;
    }
    $this->sortedSetStore($workspace_id)->add($record['seq'], $record);
  }

  /**
   * {@inheritdoc}
   */
  public function getRange($start, $stop = NULL, $inclusive = TRUE) {
    $range = $this->sortedSetStore()->getRange($start, $stop, $inclusive);
    if (empty($range)) {
      $range = $this->sortedSetStore(0)->getRange($start, $stop, $inclusive);
    }
    return $range;
  }

  /**
   * {@inheritdoc}
   */
  public function getLastSequenceId() {
    $max_key = $this->sortedSetStore()->getMaxKey();
    if (empty($max_key)) {
      $max_key = $this->sortedSetStore(0)->getMaxKey();
    }
    return $max_key;
  }

  /**
   * @param $workspace_id
   * @return \Drupal\key_value\KeyValueStore\KeyValueStoreSortedSetInterface
   */
  protected function sortedSetStore($workspace_id = NULL) {
    if (!$workspace_id) {
      $workspace_id = $this->workspaceId ?: $this->workspaceManager->getActiveWorkspace()->id();
    }
    return $this->sortedSetFactory->get($this->collectionPrefix . $workspace_id);
  }

  /**
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   * @return array
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
