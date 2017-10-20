<?php

namespace Drupal\workspace\Changes;

/**
 * Defines a value object representing a change between two workspaces.
 */
class Change {

  /**
   * The ID of the entity type the change is for.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * The ID of the entity the change is for.
   *
   * @var int|string
   */
  protected $entityId;

  /**
   * The ID of the revision the change is for.
   *
   * @var int
   */
  protected $revisionId;

  /**
   * Constructs a Change object.
   *
   * @param string $entity_type_id
   *   The ID of the entity type the change is for.
   * @param int|string $entity_id
   *   The ID of the entity the change is for.
   * @param int $revision_id
   *   The ID of the revision the change is for.
   */
  public function __construct($entity_type_id, $entity_id, $revision_id) {
    $this->entityTypeId = $entity_type_id;
    $this->entityId = $entity_id;
    $this->revisionId = $revision_id;
  }

  /**
   * Gets the ID of the entity type the change is for.
   *
   * @return string
   *   The entity type ID.
   */
  public function getEntityTypeId() {
    return $this->entityTypeId;
  }

  /**
   * Gets the ID of the entity the change is for.
   *
   * @return int|string
   *   The entity ID.
   */
  public function getEntityId() {
    return $this->entityId;
  }

  /**
   * Gets the ID of the revision the change is for.
   *
   * @return int
   *   The entity revision ID.
   */
  public function getRevisionId() {
    return $this->revisionId;
  }

  /**
   * Gets the entity object of the revision the change is for.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   *   An entity object.
   */
  public function getEntity() {
    \Drupal::entityTypeManager()
      ->getStorage($this->getEntityTypeId())
      ->loadRevision($this->getRevisionId());
  }

}
