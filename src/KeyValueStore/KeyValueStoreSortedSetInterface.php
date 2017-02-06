<?php

namespace Drupal\workspace\KeyValueStore;

/**
 * Defines the interface for sorted data in a key/value store.
 *
 * An example would be sequential log of data ordered by key of current time.
 * A value can just exist once, but a key could be used multiple times for items
 * in the same position in a sequence. This is based on the Redis sorted sets.
 */
interface KeyValueStoreSortedSetInterface {

  /**
   * Add a single item to a collection.
   *
   * @param int $key
   *   The key for the item, for example microtime(), which can be used to
   *   generate a sequential value.
   * @param mixed $value
   *   The value of the item.
   *
   * @return $this
   */
  public function add($key, $value);

  /**
   * Add multiple items to a collection.
   *
   * @example [[1 => 'a'], [2 => 'b']].
   *
   * @param array[] $map
   *   A map of keys and values to add.
   *
   * @return $this
   */
  public function addMultiple(array $map);

  /**
   * Get the highest key in a collection.
   *
   * @return int
   *   The highest key in the collection.
   */
  public function getMaxKey();

  /**
   * Get the lowest key in in a collection.
   *
   * @return int
   *   The lowest key in the collection.
   */
  public function getMinKey();

  /**
   * Get the number of items in a collection.
   *
   * @return int
   *   The number of items in a collection.
   */
  public function getCount();

  /**
   * Get multiple items within a range of keys.
   *
   * @param int $start
   *   The first key in the range.
   * @param int $stop
   *   The last key in the range.
   *
   * @return array
   *   An array of items within the given range.
   */
  public function getRange($start, $stop = NULL);

}
