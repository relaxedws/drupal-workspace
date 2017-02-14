<?php

namespace Drupal\workspace\Replication;

use Drupal\workspace\UpstreamInterface;

/**
 * Interface ReplicationInterface
 */
interface ReplicationInterface {

  /**
   * @param \Drupal\workspace\UpstreamInterface $source
   * @param \Drupal\workspace\UpstreamInterface $target
   *
   * @return bool
   */
  public function applies(UpstreamInterface $source, UpstreamInterface $target);

  /**
   * @param \Drupal\workspace\UpstreamInterface $source
   * @param \Drupal\workspace\UpstreamInterface $target
   *
   * @return \Drupal\workspace\Entity\ReplicationLogInterface
   */
  public function replicate(UpstreamInterface $source, UpstreamInterface $target);

}
