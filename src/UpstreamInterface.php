<?php

namespace Drupal\workspace;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Interface UpstreamInterface
 */
interface UpstreamInterface extends PluginInspectionInterface {

  /**
   * Return the label of the upstream.
   *
   * @return string
   *   The of the upstream.
   */
  public function getLabel();
  
}
