<?php

namespace Drupal\workspace\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks if the upstream value is valid.
 *
 * @Constraint(
 *   id = "Upstream",
 *   label = @Translation("Upstream value", context = "Validation")
 * )
 */
class Upstream extends Constraint {

  /**
   * The default violation message.
   *
   * @var string
   */
  public $message = 'The target workspace can not be the same as the local workspace.';

}
