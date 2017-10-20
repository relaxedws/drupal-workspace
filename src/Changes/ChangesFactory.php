<?php

namespace Drupal\workspace\Changes;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\workspace\Entity\WorkspaceInterface;
use Drupal\workspace\Index\SequenceIndexInterface;

/**
 * Defines a factory for Changes classes.
 */
class ChangesFactory implements ChangesFactoryInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The sequence index service.
   *
   * @var \Drupal\workspace\Index\SequenceIndexInterface
   */
  protected $sequenceIndex;

  /**
   * A list of changesets for a workspace, keyed by the workspace ID.
   *
   * @var \Drupal\workspace\Changes\Changes[]
   */
  protected $instances = [];

  /**
   * Constructs a ChangesFactory object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\workspace\Index\SequenceIndexInterface $sequence_index
   *   The sequence index service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, SequenceIndexInterface $sequence_index) {
    $this->entityTypeManager = $entity_type_manager;
    $this->sequenceIndex = $sequence_index;
  }

  /**
   * {@inheritdoc}
   */
  public function get(WorkspaceInterface $workspace) {
    if (!isset($this->instances[$workspace->id()])) {
      $this->instances[$workspace->id()] = new Changes(
        $workspace,
        $this->entityTypeManager,
        $this->sequenceIndex
      );
    }
    return $this->instances[$workspace->id()];
  }

}
