<?php

/**
 * @file
 * Contains \Drupal\workspace\ReplicatorManager.
 */

namespace Drupal\workspace;

/**
 * Provides the Replicator manager.
 */
class ReplicatorManager implements ReplicatorInterface{

  protected $replicators = [];

  /**
   * {@inheritdoc}
   */
  public function applies(PointerInterface $source, PointerInterface $target) {
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
  public function replicate(PointerInterface $source, PointerInterface $target) {
    foreach ($this->replicators as $replicator) {
      if ($replicator->applies($source, $target)) {
        $replicator->replicate($source, $target);
      }
    }
  }
}
