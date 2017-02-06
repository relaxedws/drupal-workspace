<?php

namespace Drupal\workspace\KeyValueStore;

use Drupal\Core\KeyValueStore\KeyValueFactory;
/**
 * Defines the key/value store factory.
 */
class KeyValueSortedSetFactory extends KeyValueFactory implements KeyValueSortedSetFactoryInterface {

  const DEFAULT_SERVICE = 'workspace.keyvalue.sorted_set.database';

  const SPECIFIC_PREFIX = 'workspace_keyvalue_sorted_set_service_';

  const DEFAULT_SETTING = 'workspace_keyvalue_sorted_set_default';

}
