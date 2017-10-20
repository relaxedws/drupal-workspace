<?php

namespace Drupal\workspace\Replication;

use Drupal\workspace\UpstreamPluginInterface;

/**
 * Interface ReplicationInterface
 */
interface ReplicationInterface {

  /**
   * @param \Drupal\workspace\UpstreamPluginInterface $source
   * @param \Drupal\workspace\UpstreamPluginInterface $target
   *
   * @return bool
   */
  public function applies(UpstreamPluginInterface $source, UpstreamPluginInterface $target);

  /**
   * @param \Drupal\workspace\UpstreamPluginInterface $source
   * @param \Drupal\workspace\UpstreamPluginInterface $target
   *
   * @return \Drupal\workspace\Entity\ReplicationLogInterface
   */
  public function replicate(UpstreamPluginInterface $source, UpstreamPluginInterface $target);

}
