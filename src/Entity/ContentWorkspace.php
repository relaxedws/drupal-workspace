<?php

namespace Drupal\workspace\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the Content workspace entity.
 *
 * @ContentEntityType(
 *   id = "content_workspace",
 *   label = @Translation("Content workspace"),
 *   label_singular = @Translation("content workspace"),
 *   label_plural = @Translation("content workspaces"),
 *   label_count = @PluralTranslation(
 *     singular = "@count content workspace",
 *     plural = "@count content workspaces"
 *   ),
 *   base_table = "content_workspace",
 *   revision_table = "content_workspace_revision",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "revision_id",
 *     "uuid" = "uuid",
 *   }
 * )
 */
class ContentWorkspace extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['workspace'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('workspace'))
      ->setDescription(t('The workspace of the referenced content.'))
      ->setSetting('target_type', 'workspace')
      ->setRequired(TRUE)
      ->setRevisionable(TRUE)
      ->addConstraint('workspace', []);

    $fields['content_entity_type_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Content entity type ID'))
      ->setDescription(t('The ID of the content entity type this workspace is for.'))
      ->setRequired(TRUE)
      ->setRevisionable(TRUE);

    $fields['content_entity_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Content entity ID'))
      ->setDescription(t('The ID of the content entity this workspace is for.'))
      ->setRequired(TRUE)
      ->setRevisionable(TRUE);

    $fields['content_entity_revision_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Content entity revision ID'))
      ->setDescription(t('The revision ID of the content entity this workspace is for.'))
      ->setRequired(TRUE)
      ->setRevisionable(TRUE);

    return $fields;
  }

}
