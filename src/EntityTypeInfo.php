<?php

namespace Drupal\workspace;

use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Service class for manipulating entity type information.
 *
 * This class contains primarily bridged hooks for compile-time or
 * cache-clear-time hooks. Runtime hooks should be placed in EntityOperations.
 */
class EntityTypeInfo {

  /**
   * @param \Drupal\Core\Entity\EntityTypeInterface[] $entity_types
   */
  public function entityTypeAlter(array &$entity_types) {
    foreach ($entity_types as $type_name => $entity_type) {
      if ($entity_type->isRevisionable()) {
        $entity_types[$type_name] = $this->addRevisionLinks($entity_type);
      }
    }
  }

  /**
   * Adds additional link relationships to an entity.
   *
   * If these links already exist they will not be overridden.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   An entity type defintion to which to add links.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface
   *   The modified type definition.
   */
  protected function addRevisionLinks(EntityTypeInterface $entity_type) {
    if ($entity_type->hasLinkTemplate('canonical')) {
      if (!$entity_type->hasLinkTemplate('version-tree')) {
        $entity_type->setLinkTemplate('version-tree', $entity_type->getLinkTemplate('canonical') . '/tree');
      }

      if (!$entity_type->hasLinkTemplate('revision')) {
        $entity_type->setLinkTemplate('revision', $entity_type->getLinkTemplate('canonical') . '/revisions/{' . $entity_type->id() . '_revision}/view');
      }
    }

    return $entity_type;
  }

}
