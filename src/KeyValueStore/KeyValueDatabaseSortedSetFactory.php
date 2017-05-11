<?php

namespace Drupal\workspace\KeyValueStore;

use Drupal\Component\Serialization\SerializationInterface;
use Drupal\Core\Database\Connection;

/**
 * Defines the key/value store factory for the database backend.
 */
class KeyValueDatabaseSortedSetFactory implements KeyValueSortedSetFactoryInterface {

  /**
   * The serialization class to use.
   *
   * @var \Drupal\Component\Serialization\SerializationInterface
   */
  protected $serializer;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs this factory object.
   *
   * @param \Drupal\Component\Serialization\SerializationInterface $serializer
   *   The serialization class to use.
   * @param \Drupal\Core\Database\Connection $connection
   *   The Connection object containing the key-value tables.
   */
  public function __construct(SerializationInterface $serializer, Connection $connection) {
    $this->serializer = $serializer;
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public function get($collection) {
    return new DatabaseStorageSortedSet($collection, $this->serializer, $this->connection);
  }

}
