<?php

namespace Drupal\workspace;

interface WorkspacePointerInterface {

  /**
   * @param string $key
   *
   * @return array
   */
  public function get($key);

  /**
   * @param array $keys
   *
   * @return array
   */
  public function getMultiple(array $keys);

  /**
   * @param \Drupal\workspace\Pointer $pointer
   * @return mixed
   */
  public function add(Pointer $pointer);

  /**
   * @param array $pointers
   */
  public function addMultiple(array $pointers);

}