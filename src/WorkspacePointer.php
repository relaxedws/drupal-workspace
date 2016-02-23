<?php

namespace Drupal\workspace;

use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;

class WorkspacePointer implements WorkspacePointerInterface {

  /**
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected $keyValueStore;

  /**
   * @inheritDoc
   */
  public function __construct(KeyValueFactoryInterface $key_value_factory) {
    $this->keyValueStore = $key_value_factory->get('workspace.pointer');
  }

  /**
   * @inheritDoc
   */
  public function get($key) {
    return $this->keyValueStore->get($key);
  }

  /**
   * @inheritDoc
   */
  public function getMultiple(array $keys = []) {
    if (empty($keys)) {
      return $this->keyValueStore->getAll();
    }
    return $this->keyValueStore->getMultiple($keys);
  }

  /**
   * @inheritDoc
   */
  public function add(Pointer $pointer) {
    $this->keyValueStore->set($pointer->id(), $pointer);
  }

  /**
   * @inheritDoc
   */
  public function addMultiple(array $pointers) {
    foreach ($pointers as $pointer) {
      $this->add($pointer);
    }
  }

}