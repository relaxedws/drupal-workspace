<?php

namespace Drupal\workspace\KeyValueStore;

use Drupal\Component\Serialization\SerializationInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\SchemaObjectExistsException;

/**
 * Defines the default key/value implementation for sorted collections.
 *
 * This key/value store implementation uses the database to store key/value
 * data in a sorted collection.
 */
class DatabaseStorageSortedSet implements KeyValueStoreSortedSetInterface {

  /**
   * The name of the collection holding key and value pairs.
   *
   * @var string
   */
  protected $collection;

  /**
   * The serialization class to use.
   *
   * @var \Drupal\Component\Serialization\SerializationInterface
   */
  protected $serializer;

  /**
   * The database connection to use.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The name of the SQL table to use, defaults to key_value_sorted.
   *
   * @var string
   */
  protected $table;

  /**
   * Constructs the database key/value implementation for sorted collections.
   *
   * @param string $collection
   *   The name of the collection holding key and value pairs.
   * @param \Drupal\Component\Serialization\SerializationInterface $serializer
   *   The serialization class to use.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection to use.
   * @param string (optional) $table
   *   The name of the SQL table to use, defaults to key_value_sorted.
   */
  public function __construct($collection, SerializationInterface $serializer, Connection $connection, $table = 'key_value_sorted') {
    $this->collection = $collection;
    $this->serializer = $serializer;
    $this->connection = $connection;
    $this->table = $table;
  }

  /**
   * {@inheritdoc}
   */
  public function add($key, $value) {
    return $this->addMultiple([[$key => $value]]);
  }

  /**
   * {@inheritdoc}
   */
  public function addMultiple(array $pairs) {
    foreach ($pairs as $pair) {
      foreach ($pair as $key => $value) {
        $try_again = FALSE;
        try {
          $encoded_value = $this->serializer->encode($value);
          $this->connection->merge($this->table)
            ->fields([
              'collection' => $this->collection,
              'name' => $key,
              'value' => $encoded_value,
            ])
            ->condition('collection', $this->collection)
            ->condition('value', $encoded_value)
            ->execute();
        }
        catch (\Exception $e) {
          // If there was an exception, try to create the table.
          if (!$try_again = $this->ensureTableExists()) {
            // If the exception happened for other reason than the missing
            // table, propagate the exception.
            throw $e;
          }
        }
        // Now that the table has been created, try again if necessary.
        if ($try_again) {
          $this->add($key, $value);
        }
      }
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCount() {
    try {
      return $this->connection->query('SELECT COUNT(*) FROM {' . $this->connection->escapeTable($this->table) . '} WHERE collection = :collection', [
        ':collection' => $this->collection
      ])->fetchField();
    }
    catch(\Exception $e) {
      $this->catchException($e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getRange($start, $stop = NULL) {
    try {
      $query = $this->connection->select($this->table, 't')
        ->fields('t', ['value'])
        ->orderBy('name', 'ASC')
        ->condition('collection', $this->collection)
        ->condition('name', $start, '>=');

      if (is_int($stop)) {
        $query->condition('name', $stop, '<=');
      }

      $values = [];
      foreach ($query->execute() as $item) {
        $values[] = $this->serializer->decode($item->value);
      }
      return $values;
    }
    catch (\Exception $e) {
      $this->catchException($e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getMaxKey() {
    try {
      return $this->connection->query('SELECT MAX(name) FROM {' . $this->connection->escapeTable($this->table) . '} WHERE collection = :collection', [
        ':collection' => $this->collection
      ])->fetchField();
    }
    catch (\Exception $e) {
      $this->catchException($e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getMinKey() {
    try {
      return $this->connection->query('SELECT MIN(name) FROM {' . $this->connection->escapeTable($this->table) . '} WHERE collection = :collection', [
        ':collection' => $this->collection
      ])->fetchField();
    }
    catch (\Exception $e) {
      $this->catchException($e);
    }
  }

  /**
   * Checks if the table exists and creates if not.
   *
   * @return bool
   */
  protected function ensureTableExists() {
    try {
      $database_schema = $this->connection->schema();
      if (!$database_schema->tableExists($this->table)) {
        $database_schema->createTable($this->table, $this->schemaDefinition());
        return TRUE;
      }
    }
    // If the table already exists, then attempting to recreate it will throw an
    // exception. In this case just catch the exception and do nothing.
    catch (SchemaObjectExistsException $e) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Act on an exception when the table might not have been created.
   *
   * If the table does not yet exist, that's fine, but if the table exists and
   * something else caused the exception, then propagate it.
   *
   * @param \Exception $e
   *   The exception.
   *
   * @throws \Exception
   */
  protected function catchException(\Exception $e) {
    if ($this->connection->schema()->tableExists($this->table)) {
      throw $e;
    }
  }

  /**
   * The schema definition for the sorted key-value list storage table.
   *
   * @return array
   */
  protected function schemaDefinition() {
    return [
      'description' => 'Sorted key-value list storage table.',
      'fields' => [
        'collection' => [
          'description' => 'A named collection of key and value pairs.',
          'type' => 'varchar',
          'length' => 128,
          'not null' => TRUE,
          'default' => '',
        ],
        // KEY is an SQL reserved word, so use 'name' as the key's field name.
        'name' => [
          'description' => 'The index or score key for the value.',
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
          'size' => 'big',
        ],
        'value' => [
          'description' => 'The value.',
          'type' => 'blob',
          'not null' => TRUE,
          'size' => 'big',
        ],
      ],
      'indexes' => [
        'collection_name' => ['collection', 'name'],
      ],
    ];
  }

}
