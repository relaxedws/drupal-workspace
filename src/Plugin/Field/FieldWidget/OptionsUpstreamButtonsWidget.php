<?php

namespace Drupal\workspace\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsButtonsWidget;

/**
 * Plugin implementation of the 'options_upstream_buttons' widget.
 *
 * @FieldWidget(
 *   id = "options_upstream_buttons",
 *   label = @Translation("Upstream check boxes/radio buttons"),
 *   field_types = {
 *     "entity_reference",
 *   },
 *   multiple_values = TRUE
 * )
 */
class OptionsUpstreamButtonsWidget extends OptionsButtonsWidget {

  /**
   * {@inheritdoc}
   */
  protected function getOptions(FieldableEntityInterface $entity) {
    $options = parent::getOptions($entity);
    foreach ($options as $key => $option) {
      if ((string) $option === $entity->label()) {
        unset($options[$key]);
      }
    }
    return $options;
  }

}
