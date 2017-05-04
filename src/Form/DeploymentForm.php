<?php

namespace Drupal\workspace\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\workspace\Entity\Workspace;
use Drupal\workspace\Entity\WorkspaceInterface;
use Drupal\workspace\Replication\ReplicationManager;

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

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'Deploy to ' . $upstream_plugin->getLabel(),
    ];

    return $form;
  }

  /**
   * @inheritDoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
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

}