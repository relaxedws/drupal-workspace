<?php

namespace Drupal\workspace\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\PrependCommand;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\multiversion\Workspace\WorkspaceManagerInterface;
use Drupal\replication\Entity\ReplicationLogInterface;
use Drupal\workspace\ReplicatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The form to update the current workspace with its upstream.
 */
class UpdateForm extends ConfirmFormBase {

  /**
   * The workspace manager.
   *
   * @var \Drupal\multiversion\Workspace\WorkspaceManagerInterface
   */
  protected $workspaceManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The replicator manager.
   *
   * @var \Drupal\workspace\ReplicatorInterface
   */
  protected $replicatorManager;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Inject services needed by the form.
   *
   * @param \Drupal\multiversion\Workspace\WorkspaceManagerInterface $workspace_manager
   *   The workspace manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\workspace\ReplicatorInterface $replicator_manager
   *   The replicator manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(WorkspaceManagerInterface $workspace_manager, EntityTypeManagerInterface $entity_type_manager, ReplicatorInterface $replicator_manager, RendererInterface $renderer) {
    $this->workspaceManager = $workspace_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->replicatorManager = $replicator_manager;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('workspace.manager'),
      $container->get('entity_type.manager'),
      $container->get('workspace.replicator_manager'),
      $container->get('renderer')
    );
  }

  /**
   * Get the current active workspace's pointer.
   *
   * @return \Drupal\workspace\WorkspacePointerInterface
   *   The active workspace.
   */
  protected function getActive() {
    /** @var \Drupal\multiversion\Entity\WorkspaceInterface $workspace */
    $workspace = $this->workspaceManager->getActiveWorkspace();
    /** @var \Drupal\workspace\WorkspacePointerInterface[] $pointers */
    $pointers = $this->entityTypeManager
      ->getStorage('workspace_pointer')
      ->loadByProperties(['workspace_pointer' => $workspace->id()]);
    return reset($pointers);
  }

  /**
   * Returns the upstream for the given workspace.
   *
   * @return \Drupal\multiversion\Entity\WorkspaceInterface
   *   The upstream workspace.
   */
  protected function getUpstream() {
    $workspace = $this->workspaceManager->getActiveWorkspace();
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
      // Derive a replication task from the Workspace we are acting on.
      $task = $this->replicatorManager->getTask($active->getWorkspace(), 'pull_replication_settings');

      $response = $this->replicatorManager->update($upstream, $active, $task);

      if (($response instanceof ReplicationLogInterface) && ($response->get('ok')->value === TRUE)) {
        drupal_set_message($this->t('%workspace has been updated with content from %upstream.', ['%upstream' => $upstream->label(), '%workspace' => $active->label()]));
      }
      else {
        drupal_set_message($this->t('Error updating %workspace from %upstream.', ['%upstream' => $upstream->label(), '%workspace' => $active->label()]), 'error');
      }
    }
    catch (\Exception $e) {
      watchdog_exception('Workspace', $e);
      drupal_set_message($e->getMessage(), 'error');
    }
  }

  /**
   * Callback handler for the update form button.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state data.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The AJAX response.
   */
  public function update(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new CloseModalDialogCommand());
    $status_messages = ['#type' => 'status_messages'];
    $response->addCommand(new PrependCommand('.region-highlighted', $this->renderer->renderRoot($status_messages)));
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
