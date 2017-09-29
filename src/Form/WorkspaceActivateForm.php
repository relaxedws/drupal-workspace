<?php

namespace Drupal\workspace\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\workspace\Entity\WorkspaceInterface;

/**
 * Handle activation of a workspace on administrative pages.
 */
class WorkspaceActivateForm extends ConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'activate';
  }

  /**
   * @var \Drupal\workspace\Entity\WorkspaceInterface;
   */
  protected $workspace;

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Would you like to activate the %workspace workspace?', ['%workspace' => $this->workspace->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Activate the %workspace workspace.', ['%workspace' => $this->workspace->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->workspace->toUrl('collection');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    try {
      \Drupal::service('workspace.manager')->setActiveWorkspace($this->workspace);
      drupal_set_message($this->t("@workspace is now the active workspace.", ['@workspace' => $this->workspace->label()]));
      $form_state->setRedirect($this->workspace->toUrl('collection')->getRouteName());
    }
    catch (\Exception $e) {
      watchdog_exception('Workspace', $e);
      drupal_set_message($e->getMessage(), 'error');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, WorkspaceInterface $workspace = NULL) {
    $this->workspace = $workspace;
    return parent::buildForm($form, $form_state);
  }

}
