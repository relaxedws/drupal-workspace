<?php

namespace Drupal\workspace\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * The replication log entity type.
 *
 * @ContentEntityType(
 *   id = "replication_log",
 *   label = @Translation("Replication log"),
 *   handlers = {
 *     "storage" = "Drupal\Core\Entity\Sql\SqlContentEntityStorage",
 *   },
 *   base_table = "replication_log",
 *   revision_table = "replication_log_revision",
 *   fieldable = FALSE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "revision" = "revision_id",
 *   },
 * )
 */
class ReplicationLog extends ContentEntityBase implements ReplicationLogInterface {

  /**
   * {@inheritdoc}
   */
  public function getHistory() {
    return $this->get('history')->getValue();
  }

  /**
   * {@inheritdoc}
   */
  public function setHistory($history) {
    $histories = array_merge([$history], $this->getHistory());
    $this->set('history', $histories);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSessionId() {
    return $this->get('session_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setSessionId($session_id) {
    $this->set('session_id', $session_id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceLastSequence() {
    return $this->get('source_last_sequence')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setSourceLastSequence($source_last_sequence) {
    $this->set('source_last_sequence', $source_last_sequence);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function loadOrCreate($id) {
    if ($entity = static::load($id)) {
      return $entity;
    }
    else {
      return static::create(['id' => $id]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['id'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Replication log ID'))
      ->setDescription(new TranslatableMarkup('The replication log ID.'))
      ->setReadOnly(TRUE)
      ->setRequired(TRUE)
      ->setSetting('max_length', 128)
      ->setSetting('is_ascii', TRUE);

    $fields['history'] = BaseFieldDefinition::create('replication_history')
      ->setLabel(new TranslatableMarkup('Replication log history'))
      ->setDescription(new TranslatableMarkup('The version id of the test entity.'))
      ->setReadOnly(TRUE)
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);

    $fields['session_id'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Replication session ID'))
      ->setDescription(new TranslatableMarkup('The unique session ID of the last replication. Shortcut to the session_id in the last history item.'))
      ->setReadOnly(TRUE);

    $fields['source_last_sequence'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Last processed checkpoint'))
      ->setDescription(new TranslatableMarkup('The last processed checkpoint. Shortcut to the source_last_sequence in the last history item.'))
      ->setReadOnly(TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('Status'))
      ->setDescription(new TranslatableMarkup('Replication status'))
      ->setDefaultValue(TRUE)
      ->setReadOnly(TRUE);

    return $fields;
  }

}
