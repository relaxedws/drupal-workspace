<?php

namespace Drupal\workspace\Replication;

use Drupal\workspace\UpstreamPluginInterface;

/**
 * Defines an interface for replication services.
 *
 * Replications are tagged services which are used to synchronize content
 * revisions between a source and a target, which are denoted by upstream
 * plugins. These could be local, remote, or may not even be Drupal at all.
 * Therefore different replication services can be used depending on the source
 * and the target.
 */
interface ReplicationInterface {

  /**
   * Returns whether the replication service applies or not.
   *
   * @param \Drupal\workspace\UpstreamPluginInterface $source
   *   The source upstream plugin to replicate from.
   * @param \Drupal\workspace\UpstreamPluginInterface $target
   *   The target upstream plugin to replicate to.
   *
   * @return bool
   *   TRUE if the replication service applies for the given source and target,
   *   FALSE otherwise.
   */
  public function applies(UpstreamPluginInterface $source, UpstreamPluginInterface $target);

  /**
   * Replicates content revisions between the source and the target upstreams.
   *
   * @param \Drupal\workspace\UpstreamPluginInterface $source
   *   The source upstream plugin to replicate from.
   * @param \Drupal\workspace\UpstreamPluginInterface $target
   *   The target upstream plugin to replicate to.
   *
   * @return \Drupal\workspace\Entity\ReplicationLogInterface
   *   A ReplicationLog entity that provides detailed information about the
   *   replication process.
   */
  public function replicate(UpstreamPluginInterface $source, UpstreamPluginInterface $target);

}
