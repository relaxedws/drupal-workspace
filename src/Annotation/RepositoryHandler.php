<?php

namespace Drupal\workspace\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a RepositoryHandler annotation object.
 *
 * @Annotation
 */
class RepositoryHandler extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the repository handler plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * A short description of the repository handler plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

  /**
   * The human-readable category.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $category = '';

  /**
   * Whether the repository handles a remote destination or not.
   *
   * @var bool
   */
  public $remote;

}
