<?php

namespace Drupal\workspace\Replication;

use Drupal\workspace\UpstreamPluginInterface;

/**
 * Manage the replication tagged services.
 */
class ReplicationManager {

  /**
   * @var \Drupal\workspace\Replication\ReplicationInterface[]
   */
  protected $replicators = [];

  /**
   * @param \Drupal\workspace\Replication\ReplicationInterface $replicator
   */
  public function addReplicator(ReplicationInterface $replicator, $priority) {
    $this->replicators[$priority][] = $replicator;
  }

  /**
   * Find all replicators that apply for the source and target upstream plugins
   * and run the replication for each replicator.
   *
   * @param \Drupal\workspace\UpstreamPluginInterface $source
   * @param \Drupal\workspace\UpstreamPluginInterface $target
   *
   * @return \Drupal\workspace\Entity\ReplicationLogInterface
   */
  public function replicate(UpstreamPluginInterface $source, UpstreamPluginInterface $target) {
    foreach ($this->replicators as $replicators) {
      /** @var \Drupal\workspace\Replication\ReplicationInterface $replicator */
      foreach ($replicators as $replicator) {
        if ($replicator->applies($source, $target)) {
          return $replicator->replicate($source, $target);
        }
      }
    }
  }

}
