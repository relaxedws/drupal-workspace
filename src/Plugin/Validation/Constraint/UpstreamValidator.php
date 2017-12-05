<?php

namespace Drupal\workspace\Plugin\Validation\Constraint;

use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\TypedData\Validation\TypedDataAwareValidatorTrait;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Constraint;

/**
 * Checks if the upstream value is valid.
 */
class UpstreamValidator extends ConstraintValidator {

  use TypedDataAwareValidatorTrait;

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    if (!isset($value)) {
      return;
    }

    /** @var \Drupal\Core\Field\FieldItemInterface $field_item */
    $field_item = $this->getTypedData()->getParent();
    $entity = $field_item->getEntity();

    if (!$entity->isNew() && $value === 'local_workspace' . PluginBase::DERIVATIVE_SEPARATOR . $entity->id()) {
      $this->context->addViolation($constraint->message);
    }
  }

}
