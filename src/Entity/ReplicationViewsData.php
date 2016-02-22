<?php

/**
 * @file
 * Contains \Drupal\workspace\Entity\Replication.
 */

namespace Drupal\workspace\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Replication entities.
 */
class ReplicationViewsData extends EntityViewsData implements EntityViewsDataInterface {
  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['replication']['table']['base'] = array(
      'field' => 'id',
      'title' => $this->t('Replication'),
      'help' => $this->t('The Replication ID.'),
    );

    return $data;
  }

}
