<?php

/**
 * @file
 * Contains \Drupal\workspace\Plugin\ReplicatorBase.
 */

namespace Drupal\workspace;

/**
 * Base class for Replicators.
 */
abstract class ReplicatorBase implements ReplicatorInterface {

  /**
   * @var
   *   The source to replicate from
   */
  protected $source;

  /**
   * @var
   *   The target to replicate too
   */
  protected $target;

  /**
   * @inheritDoc
   */
  public function setSource(Pointer $source) {
    $this->source = $source;
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function setTarget(Pointer $target) {
    $this->target = $target;
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getSource() {
    return $this->source;
  }

  /**
   * @inheritDoc
   */
  public function getTarget() {
    return $this->target;
  }

}
