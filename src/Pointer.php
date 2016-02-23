<?php

namespace Drupal\workspace;

class Pointer {

  protected $id;

  protected $label;

  protected $data = [];

  function __construct($id, $label, array $data = []) {
    $this->id = $id;
    $this->label = $label;
    $this->data = $data;
  }

  function id() {
    return (string) $this->id;
  }

  function label() {
    return (string) $this->label;
  }

  function data() {
    return (array) $this->data;
  }
}