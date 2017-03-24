<?php

namespace Drupal\workspace\Field\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsButtonsWidget;

/**
 * Plugin implementation of the 'options_upstream_buttons' widget.
 *
 * @FieldWidget(
 *   id = "options_upstream_buttons",
 *   label = @Translation("Check boxes/radio buttons"),
 *   field_types = {
 *     "entity_reference",
 *   },
 *   multiple_values = TRUE
 * )
 */
class UpstreamButtonsWidget extends OptionsButtonsWidget {

  /**
   * {@inheritdoc}
   */
  protected function getOptions(FieldableEntityInterface $entity) {
    $options = parent::getOptions($entity);
    return $options;
  }

}
