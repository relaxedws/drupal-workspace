<?php

namespace Drupal\workspace\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\TypedData\TranslatableInterface;
use Drupal\user\UserInterface;

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
 *   handlers = {
 *     "storage_schema" = "Drupal\content_moderation\ContentWorkspaceStorageSchema",
 *     "views_data" = "\Drupal\views\EntityViewsData",
 *   },
 *   base_table = "content_workspace",
 *   revision_table = "content_workspace_revision",
 *   data_table = "content_workspace_field_data",
 *   revision_data_table = "content_workspace_field_revision",
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "revision_id",
 *     "uuid" = "uuid",
 *     "uid" = "uid",
 *     "langcode" = "langcode",
 *     "published" = "published",
 *   }
 * )
 */
class ContentWorkspace extends ContentEntityBase implements ContentWorkspaceInterface {

  use EntityPublishedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type) + self::publishedBaseFieldDefinitions($entity_type);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User'))
      ->setDescription(t('The username of the entity creator.'))
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback('Drupal\workspace\Entity\ContentWorkspace::getCurrentUserId')
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE);

    $fields['workspace'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('workspace'))
      ->setDescription(t('The workspace of the referenced content.'))
      ->setSetting('target_type', 'workspace')
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
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
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->getEntityKey('uid');
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
  }

  /**
   * Creates or updates an entity's workspace whilst saving that entity.
   *
   * @param \Drupal\workspace\Entity\ContentWorkspace $content_workspace
   *   The content moderation entity content entity to create or save.
   *
   * @internal
   *   This method should only be called as a result of saving the related
   *   content entity.
   */
  public static function updateOrCreateFromEntity(ContentWorkspace $content_workspace) {
    $content_workspace->realSave();
  }

  /**
   * Default value callback for the 'uid' base field definition.
   *
   * @see \Drupal\content_moderation\Entity\ContentWorkspace::baseFieldDefinitions()
   *
   * @return array
   *   An array of default values.
   */
  public static function getCurrentUserId() {
    return [\Drupal::currentUser()->id()];
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
