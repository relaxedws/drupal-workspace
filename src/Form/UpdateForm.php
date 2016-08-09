<?php

namespace Drupal\workspace\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\PrependCommand;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\replication\Entity\ReplicationLogInterface;

class UpdateForm extends ConfirmFormBase {

  /**
   * Get the current active workspace's pointer.
   *
   * @return \Drupal\workspace\WorkspacePointerInterface
   */
  protected function getActive() {
    /** @var \Drupal\multiversion\Entity\WorkspaceInterface $workspace */
    $workspace = \Drupal::service('workspace.manager')->getActiveWorkspace();
    /** @var \Drupal\workspace\WorkspacePointerInterface[] $pointers */
    $pointers = \Drupal::service('entity_type.manager')
      ->getStorage('workspace_pointer')
      ->loadByProperties(['workspace_pointer' => $workspace->id()]);
    return reset($pointers);
  }

  /**
   * Returns the upstream for the given workspace.
   *
   * @return \Drupal\multiversion\Entity\WorkspaceInterface
   */
  protected function getUpstream() {
    $workspace = \Drupal::service('workspace.manager')->getActiveWorkspace();
    if (isset($workspace->upstream)) {
      return $workspace->upstream->entity;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form['actions']['submit']['#ajax'] = [
      'callback' => [$this, 'update'],
      'event' => 'mousedown',
      'prevent' => 'click',
      'progress' => [
        'type' => 'throbber',
        'message' => 'Updating',
      ],
    ];

    if (!$this->getUpstream()) {
      unset($form['actions']['submit']);
    }
    unset($form['actions']['cancel']);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Update @workspace', ['@workspace' => $this->getActive()->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('system.admin');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'workspace_update_form';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $upstream = $this->getUpstream();
    $active = $this->getActive();
    try {
      // Derive a replication task from the source Workspace.
      $task = $this->replicatorManager->getTask($active->getWorkspace(), 'push_replication_settings');

      $response = $this->replicatorManager->update($upstream, $active, $task);

      if (($response instanceof ReplicationLogInterface) && $response->get('ok')) {
        drupal_set_message($this->t('%workspace has been updated with content from %upstream.', ['%upstream' => $upstream->label(), '%workspace' => $active->label()]));
      }
      else {
        drupal_set_message($this->t('Error updating %workspace from %upstream.', ['%upstream' => $upstream->label(), '%workspace' => $active->label()]), 'error');
      }
    }
    catch(\Exception $e) {
      watchdog_exception('Workspace', $e);
      drupal_set_message($e->getMessage(), 'error');
    }
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function update(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new CloseModalDialogCommand());
    $status_messages = ['#type' => 'status_messages'];
    $response->addCommand(new PrependCommand('.region-highlighted', \Drupal::service('renderer')->renderRoot($status_messages)));
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    if (!$this->getUpstream()) {
      return $this->t('%workspace has no upstream set.', ['%workspace' => $this->getActive()->label()]);
    }
    return $this->t('Do you want to pull changes from %upstream to %workspace?', ['%upstream' => $this->getUpstream()->label(), '%workspace' => $this->getActive()->label()]);
  }
}
