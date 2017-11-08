<?php

namespace Drupal\workspace\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Defines the 'replication_history' entity field type.
 *
 * For each replication a history should be maintained. The only required field
 * is session_id which is a unique ID for the replication. The recorded_sequence
 * field is another important field, it stores the sequence ID of the last
 * entity replicated. It is where replication is started from next time, and
 * therefore defaults to 0, denoting to start from the first sequence ID. All
 * other fields are for informational purposes which can be used for user
 * messages, logs, or an audit trail.
 *
 * @FieldType(
 *   id = "replication_history",
 *   label = @Translation("Replication history"),
 *   description = @Translation("History information for a replication."),
 *   no_ui = TRUE
 * )
 */
class ReplicationHistoryItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    return 'session_id';
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['entity_write_failures'] = DataDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Write failures'))
      ->setDescription(new TranslatableMarkup('Number of failed entity writes'))
      ->setRequired(FALSE);

    $properties['entities_read'] = DataDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Entities read'))
      ->setDescription(new TranslatableMarkup('Number of entities read.'))
      ->setRequired(FALSE);

    $properties['entities_written'] = DataDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Entities written'))
      ->setDescription(new TranslatableMarkup('Number of entities written.'))
      ->setRequired(FALSE);

    $properties['end_last_sequence'] = DataDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('End sequence'))
      ->setDescription(new TranslatableMarkup('Sequence ID where the replication ended.'))
      ->setRequired(FALSE);

    $properties['end_time'] = DataDefinition::create('datetime_iso8601')
      ->setLabel(new TranslatableMarkup('End time'))
      ->setDescription(new TranslatableMarkup('Date and time when replication ended.'))
      ->setRequired(FALSE);

    $properties['recorded_sequence'] = DataDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Recorded sequence'))
      ->setDescription(new TranslatableMarkup('Recorded intermediate sequence.'))
      ->setRequired(FALSE);

    $properties['session_id'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Session ID'))
      ->setDescription(new TranslatableMarkup('Unique session ID for the replication.'))
      ->setRequired(TRUE);

    $properties['start_last_sequence'] = DataDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Start sequence'))
      ->setDescription(new TranslatableMarkup('Sequence ID where the replication started.'))
      ->setRequired(FALSE);

    $properties['start_time'] = DataDefinition::create('datetime_iso8601')
      ->setLabel(new TranslatableMarkup('Start time'))
      ->setDescription(new TranslatableMarkup('Date and time when replication started.'))
      ->setRequired(FALSE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'entity_write_failures' => [
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => FALSE,
        ],
        'entities_read' => [
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => FALSE,
        ],
        'entities_written' => [
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => FALSE,
        ],
        'end_last_sequence' => [
          'type' => 'int',
          'size' => 'big',
          'not null' => FALSE,
        ],
        'end_time' => [
          'type' => 'varchar',
          'length' => 50,
          'not null' => FALSE,
        ],
        'recorded_sequence' => [
          'type' => 'int',
          'size' => 'big',
          'not null' => FALSE,
          'default' => 0,
        ],
        'session_id' => [
          'type' => 'varchar',
          'length' => 128,
          'not null' => TRUE,
        ],
        'start_last_sequence' => [
          'type' => 'int',
          'size' => 'big',
          'not null' => FALSE,
        ],
        'start_time' => [
          'type' => 'varchar',
          'length' => 50,
          'not null' => FALSE,
        ],
      ],
    ];
  }

}
