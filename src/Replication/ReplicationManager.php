<?php

namespace Drupal\workspace\Replication;

use Drupal\workspace\UpstreamPluginInterface;

/**
 * Manage the replication tagged services.
 */
class ReplicationManager {

  /**
   * A list of replication services.
   *
   * @var \Drupal\workspace\Replication\ReplicationInterface[]
   */
  protected $replicators = [];

  /**
   * Adds a replication service.
   *
   * @param \Drupal\workspace\Replication\ReplicationInterface $replicator
   *   The replication service to add.
   */
  public function addReplicator(ReplicationInterface $replicator, $priority) {
    $this->replicators[$priority][] = $replicator;
  }

  /**
   * Replicates content using all replication services that apply.
   *
   * @param \Drupal\workspace\UpstreamPluginInterface $source
   *   The source to replicate from.
   * @param \Drupal\workspace\UpstreamPluginInterface $target
   *   The target to replicate to.
   *
   * @return \Drupal\workspace\Entity\ReplicationLogInterface
   *   The created or updated ReplicationLog entity detailing the replication.
   */
  public function replicate(UpstreamPluginInterface $source, UpstreamPluginInterface $target) {
    // Loop through all replication services by priority.
    foreach ($this->replicators as $replicators) {
      /** @var \Drupal\workspace\Replication\ReplicationInterface $replicator */
      // Loop through all replication services for a given priority.
      foreach ($replicators as $replicator) {
        // Replicate the content from the source to the target.
        if ($replicator->applies($source, $target)) {
          return $replicator->replicate($source, $target);
        }
      }
    }
  }

}
