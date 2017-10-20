<?php

namespace Drupal\workspace\Changes;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\workspace\Entity\WorkspaceInterface;
use Drupal\workspace\Index\SequenceIndexInterface;

/**
 * Defines and builds a sorted list of changed entities in a workspace.
 */
class Changes implements ChangesInterface {

  use DependencySerializationTrait;

  /**
   * The workspace to generate the changeset from.
   *
   * @var string
   */
  protected $workspaceId;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The sequence index service.
   *
   * @var \Drupal\workspace\Index\SequenceIndex
   */
  protected $sequenceIndex;

  /**
   * The sequence ID to start including changes from. Result includes $lastSeq.
   *
   * @var int
   */
  protected $lastSequenceId = 0;

  /**
   * Constructs a new Changes object.
   *
   * @param \Drupal\workspace\Entity\WorkspaceInterface $workspace
   *   A workspace entity.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Workspace\Index\SequenceIndexInterface $sequence_index
   *   The sequence index service.
   */
  public function __construct(WorkspaceInterface $workspace, EntityTypeManagerInterface $entity_type_manager, SequenceIndexInterface $sequence_index) {
    $this->workspaceId = $workspace->id();
    $this->entityTypeManager = $entity_type_manager;
    $this->sequenceIndex = $sequence_index;
  }

  /**
   * {@inheritdoc}
   */
  public function setLastSequenceId($sequence_id) {
    $this->lastSequenceId = $sequence_id;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getChanges() {
    $sequences = $this->sequenceIndex
      ->useWorkspace($this->workspaceId)
      ->getRange($this->lastSequenceId, NULL);

    // Format the result array.
    $changes = [];
    foreach ($sequences as $sequence) {
      $changes[$sequence['seq']] = new Change(
        $sequence['entity_type_id'],
        $sequence['entity_id'],
        $sequence['revision_id']
      );
    }

    // Now when we have rebuilt the result array we need to ensure that the
    // changes array is sorted on the sequence key, as in the index.
    ksort($changes);

    return $changes;
  }

}
