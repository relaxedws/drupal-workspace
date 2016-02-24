<?php

namespace Drupal\workspace;


interface PointerInterface {

  /**
   * PointerInterface constructor.
   * @param string $id
   * @param string $label
   * @param array $data
   */
  function __construct($id, $label, array $data = []);

  /**
   * @return string
   */
  function id();

  /**
   * @return string
   */
  function label();

  /**
   * @return array
   */
  function data();
}