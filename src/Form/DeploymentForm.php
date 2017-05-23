<?php

namespace Drupal\workspace\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\workspace\Entity\Workspace;
use Drupal\workspace\Entity\WorkspaceInterface;

/**
 * Class DeploymentForm
 */
class DeploymentForm extends FormBase {

  /**
   * @inheritDoc
   */
  public function getFormId() {
    return 'workspace_deployment_form';
  }

  /**
   * @inheritDoc
   */
  public function buildForm(array $form, FormStateInterface $form_state, WorkspaceInterface $workspace = NULL) {
    if ('workspace:' . $workspace->id() == $workspace->get('upstream')->value) {
      return [];
    }

    $upstream_plugin = \Drupal::service('workspace.upstream_manager')->createInstance($workspace->get('upstream')->value);
    $form['workspace_id'] = [
      '#type' => 'hidden',
      '#value' => $workspace->id(),
    ];

    $form['update'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update'),
      '#submit' => [[$this, 'updateHandler']],
    ];

    $form['deploy'] = [
      '#type' => 'submit',
      '#value' => $this->t('Deploy to %upstream', ['%upstream' => $upstream_plugin->getLabel()]),
      '#submit' => [[$this, 'deployHandler']],
      '#attributes' => [
        'class' => ['primary', 'button--primary'],
      ],
    ];

    return $form;
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function updateHandler(array &$form, FormStateInterface $form_state) {
    $workspace = Workspace::load($form_state->getValue('workspace_id'));
    $upstream_manager = \Drupal::service('workspace.upstream_manager');
    try {
      \Drupal::service('workspace.replication_manager')->replicate(
        $upstream_manager->createInstance($workspace->get('upstream')->value),
        $upstream_manager->createInstance('workspace:' . $workspace->id())
      );
      drupal_set_message('Update successful.');
    }
    catch (\Exception $e) {
      drupal_set_message('Deployment error', 'error');
    }
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function deployHandler(array &$form, FormStateInterface $form_state) {
    $workspace = Workspace::load($form_state->getValue('workspace_id'));
    $upstream_manager = \Drupal::service('workspace.upstream_manager');
    try {
      \Drupal::service('workspace.replication_manager')->replicate(
        $upstream_manager->createInstance('workspace:' . $workspace->id()),
        $upstream_manager->createInstance($workspace->get('upstream')->value)
      );
      drupal_set_message('Successful deployment.');
    }
    catch (\Exception $e) {
      drupal_set_message('Deployment error', 'error');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Submission handled in custom handlers.
  }

}
