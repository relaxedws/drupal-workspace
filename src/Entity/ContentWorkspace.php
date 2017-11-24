<?php

namespace Drupal\workspace\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\TypedData\TranslatableInterface;

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
 *   data_table = "content_workspace_field_data",
 *   revision_data_table = "content_workspace_field_revision",
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "revision_id",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *   }
 * )
 */
class ContentWorkspace extends ContentEntityBase implements ContentWorkspaceInterface {

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

  /**
   * Creates or updates an entity's workspace whilst saving that entity.
   *
   * @param \Drupal\workspace\Entity\ContentWorkspaceInterface $content_workspace
   *   The content moderation entity content entity to create or save.
   *
   * @internal
   *   This method should only be called as a result of saving the related
   *   content entity.
   */
  public static function updateOrCreateFromEntity(ContentWorkspaceInterface $content_workspace) {
    $content_workspace->realSave();
  }

  /**
   * {@inheritdoc}
   */
  public function save() {
    $related_entity = \Drupal::entityTypeManager()
      ->getStorage($this->content_entity_type_id->value)
      ->loadRevision($this->content_entity_revision_id->value);
    if ($related_entity instanceof TranslatableInterface) {
      $related_entity = $related_entity->getTranslation($this->activeLangcode);
    }
    $related_entity->workspace->target_id = $this->workspace->target_id;
    return $related_entity->save();
  }

  /**
   * Saves an entity permanently.
   *
   * When saving existing entities, the entity is assumed to be complete,
   * partial updates of entities are not supported.
   *
   * @return int
   *   Either SAVED_NEW or SAVED_UPDATED, depending on the operation performed.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   In case of failures an exception is thrown.
   */
  protected function realSave() {
    return parent::save();
  }

}
