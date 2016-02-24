<?php

namespace Drupal\workspace;

class Pointer implements PointerInterface{

  /**
   * @var string
   */
  protected $id;

  /**
   * @var string
   */
  protected $label;

  /**
   * @var array
   */
  protected $data = [];

  /**
   * {@inheritdoc}
   */
  public function __construct($id, $label, array $data = []) {
    $this->id = $id;
    $this->label = $label;
    $this->data = $data;
  }

  /**
   * {@inheritdoc}
   */
  public function id() {
    return (string) $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return (string) $this->label;
  }

  /**
   * {@inheritdoc}
   */
  public function data() {
    return (array) $this->data;
  }
}