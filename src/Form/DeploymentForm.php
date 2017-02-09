<?php

namespace Drupal\workspace\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
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
    $form['workspace_id'] = [
      '#type' => 'hidden',
      '#value' => $workspace->id(),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'Deploy to ' . $workspace->upstream->entity->label(),
      '#ajax' => [
        'callback' => [self::class, 'ajaxResponse'],
        'progress' => [
          'type' => 'bar',
          'message' => 'Deploying from ' . $workspace->label() . ' to ' . $workspace->upstream->entity->label(),
        ]
      ],
    ];

    return $form;
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function ajaxResponse(array &$form, FormStateInterface $form_state) {
    $workspace = Workspace::load($form_state->getValue('workspace_id'));
    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand(
      '#workspace-deployment-form',
      'Deployed to ' . $workspace->upstream->entity->label()
    ));
    return $response;
  }

  /**
   * @inheritDoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $workspace = Workspace::load($form_state->getValue('workspace_id'));
    \Drupal::service('workspace.replicator')->replication($workspace, $workspace->upstream->entity);
  }

}