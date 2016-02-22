<?php

/**
 * @file
 * Contains \Drupal\workspace\Entity\Form\ReplicationForm.
 */

namespace Drupal\workspace\Entity\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Replication edit forms.
 *
 * @ingroup workspace
 */
class ReplicationForm extends ContentEntityForm {
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $replicators = \Drupal::service('plugin.manager.replicator')->getDefinitions();
    if (empty($replicators)) {
      drupal_set_message('There are no replicator plugins defined.', 'warning');
      return;
    }

    $form = parent::buildForm($form, $form_state);

    if (count($replicators) == 1) {
      $form['replicator']['#attributes']['style'] = 'display: none;';
    }

    if ($this->getDefaultSource()) {
      $form['source']['widget']['#default_value'] = $this->getDefaultSource();
    }
    if ($this->getDefaultTarget()) {
      $form['target']['widget']['#default_value'] = $this->getDefaultTarget();
    }
    $form['actions']['submit']['#value'] = t('Review');

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $status = $entity->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Replication.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Replication.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.replication.canonical', ['replication' => $entity->id()]);
  }

  protected function getDefaultSource() {
    //$workspace = \Drupal::service('workspace.manager')->getActiveWorkspace();
    //$machine_name = $workspace->getMachineName();
    //$endpoints = Endpoint::loadMultiple();
    //foreach ($endpoints as $endpoint) {
    //  $pluginid = $endpoint->getPlugin()->getPluginId();
    //  if ($pluginid == 'workspace:' . $machine_name) {
    //    return $endpoint->id();
    //  }
    //}
  }

  protected function getDefaultTarget() {
    //$workspace = \Drupal::service('workspace.manager')->getActiveWorkspace();
    //$upstream = $workspace->get('upstream')->entity;
    //if (!$upstream) {
    //  return null;
    //}
    //$machine_name = $upstream->getMachineName();
    //$endpoints = Endpoint::loadMultiple();
    //foreach ($endpoints as $endpoint) {
    //  $pluginid = $endpoint->getPlugin()->getPluginId();
    //  if ($pluginid == 'workspace:' . $machine_name) {
    //    return $endpoint->id();
    //  }
    //}
  }
}
