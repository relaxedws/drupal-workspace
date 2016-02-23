<?php

/**
 * @file
 * Contains \Drupal\workspace\ReplicatorManager.
 */

namespace Drupal\workspace;

/**
 * Provides the Replicator manager.
 */
class ReplicatorManager extends ReplicatorBase {

  protected $replicators = [];

  /**
   * {@inheritdoc}
   */
  public function applies() {
    return TRUE;
  }

  /**
   * Adds replication services.
   *
   * @param \Drupal\workspace\ReplicatorInterface $replicator
   */
  public function addReplicator(ReplicatorInterface $replicator) {
    $this->replicators[] = $replicator;
  }

  /**
   * {@inheritdoc}
   */
  public function replicate() {
    foreach ($this->replicators as $replicator) {
      if ($replicator->applies()) {
        $replicator
          ->setSource($this->source)
          ->setTarget($this->target)
          ->replicate();
      }
    }
  }
}
