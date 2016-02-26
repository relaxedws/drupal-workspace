<?php

/**
 * @file
 * Contains \Drupal\workspace\Entity\Form\ReplicationSettingsForm.
 */

namespace Drupal\workspace\Entity\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ReplicationSettingsForm.
 *
 * @package Drupal\workspace\Form
 *
 * @ingroup workspace
 */
class ReplicationSettingsForm extends ConfigFormBase {
  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'Replication_settings';
  }

  /**
   * Defines the settings form for Replication entities.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   Form definition array.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    return parent::buildForm($form, $form_state);
  }

  /**
   * @inheritDoc
   */
  protected function getEditableConfigNames() {
    return ['workspace.replication.settings'];
  }

}
