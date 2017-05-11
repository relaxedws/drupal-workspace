<?php

namespace Drupal\Tests\workspace\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the sorted set key-value database storage.
 *
 * @group workspace
 */
class DatabaseStorageSortedSetTest extends KernelTestBase {


  static public $modules = ['system', 'user', 'serialization', 'workspace'];

  /**
   * @var string
   */
  protected $collection;

  /**
   * @var \Drupal\Component\Serialization\SerializationInterface
   */
  protected $serializer;

  /**
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreSortedSetInterface
   */
  protected $store;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->collection = $this->randomMachineName();
    $this->serializer = \Drupal::service('serialization.phpserialize');
    $this->connection = \Drupal::service('database');
    $this->store = \Drupal::service('workspace.keyvalue.sorted_set')->get($this->collection);
  }

  /**
   * Helper method to assert key value pairs.
   *
   * @param $expected_pairs array
   *   Array of expected key value pairs.
   */
  public function assertPairs(array $expected_pairs) {
    $result = $this->connection->select('key_value_sorted', 't')
      ->fields('t', ['name', 'value'])
      ->condition('collection', $this->collection)
      ->condition('name', array_keys($expected_pairs), 'IN')
      ->execute()
      ->fetchAllAssoc('name');

    $expected_count = count($expected_pairs);
    $this->assertCount($expected_count, $result, "Query affected $expected_count records.");
    foreach ($expected_pairs as $key => $value) {
      $this->assertSame($value, $this->serializer->decode($result[$key]->value), "Key $key have value $value");
    }
  }

  /**
   * Helper method to assert the number of records.
   *
   * @param $expected int
   *   Expected number of records.
   * @param null $message string
   *   The message to display.
   */
  public function assertRecords($expected, $message = NULL) {
    $count = $this->store->getCount();
    $this->assertEquals($expected, $count, $message ? $message : "There are $expected records.");
  }

  /**
   * Helper method to generate a key based on microtime().
   *
   * @return int
   *   A key based on microtime().
   */
  public function newKey() {
    return (int) (microtime(TRUE) * 1000000);
  }

  /**
   * Tests getting and setting of sorted key value sets.
   */
  public function testCalls() {
    $key0 = $this->newKey();
    $value0 = $this->randomMachineName();
    $this->store->add($key0, $value0);
    $this->assertPairs([$key0 => $value0]);

    $key1 = $this->newKey();
    $value1 = $this->randomMachineName();
    $this->store->add($key1, $value1);
    $this->assertPairs([$key1 => $value1]);

    // Ensure it works to add sets with the same key.
    $key2 = $this->newKey();
    $value2 = $this->randomMachineName();
    $value3 = $this->randomMachineName();
    $value4 = $this->randomMachineName();
    $this->store->addMultiple([
      [$key2 => $value2],
      [$key2 => $value3],
      [$key2 => $value4],
    ]);

    $this->assertRecords(5, 'Correct number of records in the collection.');

    $value = $this->store->getRange($key1, $key2);
    $this->assertSame([$value1, $value2, $value3, $value4], $value);

    $value = $this->store->getRange($key1);
    $this->assertSame([$value1, $value2, $value3, $value4], $value);

    $new1 = $this->newKey();
    $this->store->add($new1, $value1);

    $value = $this->store->getRange($new1, $new1);
    $this->assertSame([$value1], $value, 'Value was successfully updated.');
    $this->assertRecords(5, 'Correct number of records in the collection after value update.');

    $value = $this->store->getRange($key1, $key1);
    $this->assertSame([], $value, 'Non-existing range returned empty array.');

    $max_key = $this->store->getMaxKey();
    $this->assertEquals($new1, $max_key, 'The getMaxKey method returned correct key.');

    $min_key = $this->store->getMinKey();
    $this->assertEquals($key0, $min_key, 'The getMinKey method returned correct key.');
  }

}
