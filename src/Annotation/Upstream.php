<?php

namespace Drupal\workspace\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Upstream annotation object.
 *
 * @Annotation
 */
class Upstream extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the upstream plugin.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * A short description of the upstream plugin.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $description;

  /**
   * Whether the upstream plugin is a remote destination or not.
   *
   * @var bool
   */
  public $remote;

}
