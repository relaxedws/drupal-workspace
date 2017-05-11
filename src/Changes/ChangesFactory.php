<?php

namespace Drupal\workspace\Changes;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\workspace\Entity\WorkspaceInterface;
use Drupal\workspace\Index\SequenceIndexInterface;

/**
 * Class ChangesFactory
 */
class ChangesFactory implements ChangesFactoryInterface {
  
  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\workspace\Index\SequenceIndexInterface
   */
  protected $sequenceIndex;
  
  /**
   * @var \Drupal\workspace\Changes\Changes[]
   */
  protected $instances = [];
  
  /**
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
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
