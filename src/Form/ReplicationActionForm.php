<?php

namespace Drupal\workspace\Form;


use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\workspace\Entity\ReplicationInterface;

class ReplicationActionForm extends FormBase {

  /**
   * @inheritDoc
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $entity = $this->getEntity($form_state);

    $form['#weight'] = 9999;
    $form['replication_id'] = [
      '#type' => 'hidden',
      '#value' => $entity->id()
    ];
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => $entity->get('replicated')->value ? $this->t('Re-deploy') : $this->t('Deploy'),
    );
    return $form;
  }

  /**
   * @inheritDoc
   */
  public function getFormId() {
    return 'replication_action';
  }

  /**
   * @inheritDoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity = $this->getEntity($form_state);
    $source = \Drupal::service('workspace.pointer')->get($entity->get('source')->value);
    $target = \Drupal::service('workspace.pointer')->get($entity->get('target')->value);
    $response = \Drupal::service('workspace.replicator_manager')->replicate($source, $target);
    if (!isset($response['error'])) {
      $entity->set('replicated', REQUEST_TIME)->save();
      drupal_set_message('Successful deployment.');
    }
    else {
      drupal_set_message($response['error'], 'error');
    }
  }

  protected function getEntity(FormStateInterface $form_state) {
    $args = $form_state->getBuildInfo()['args'];
    $entity = $args[0];
    if ($entity instanceof ReplicationInterface) {
      return $entity;
    }
    throw new \Exception;
  }

}