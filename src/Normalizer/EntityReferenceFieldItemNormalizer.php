<?php

/**
 * @file
 * Contains \Drupal\workspace\Normalizer\EntityReferenceFieldItemNormalizer.
 */

namespace Drupal\workspace\Normalizer;

use Drupal\serialization\Normalizer\EntityReferenceFieldItemNormalizer as CoreEntityReferenceFieldItemNormalizer;

/**
 * Adds the canonical link for workspace entities.
 */
class EntityReferenceFieldItemNormalizer extends CoreEntityReferenceFieldItemNormalizer {

  /**
   * {@inheritdoc}
   */
  public function normalize($field_item, $format = NULL, array $context = []) {
    if (($entity = $field_item->get('entity')->getValue()) && $entity->getEntityTypeId() == 'workspace') {
      $values['url'] = '/admin/structure/workspace/{workspace}';
    }
    else {
      $values = parent::normalize($field_item, $format, $context);
    }

    return $values;
  }

}
