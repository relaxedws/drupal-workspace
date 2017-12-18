<?php

namespace Drupal\workspace\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\workspace\WorkspaceManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form that activates a different workspace.
 */
class WorkspaceSwitcherForm extends FormBase {

  /**
   * The workspace manager.
   *
   * @var \Drupal\workspace\WorkspaceManagerInterface
   */
  protected $workspaceManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a new WorkspaceSwitcherForm.
   *
   * @param \Drupal\workspace\WorkspaceManagerInterface $workspace_manager
   *   The workspace manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(WorkspaceManagerInterface $workspace_manager, EntityTypeManagerInterface $entity_type_manager, MessengerInterface $messenger) {
    $this->workspaceManager = $workspace_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('workspace.manager'),
      $container->get('entity_type.manager'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'workspace_switcher_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $workspaces = $this->entityTypeManager->getStorage('workspace')->loadMultiple();
    $workspace_labels = [];
    foreach ($workspaces as $workspace) {
      $workspace_labels[$workspace->id()] = $workspace->label();
    }

    $active_workspace = $this->workspaceManager->getActiveWorkspace(TRUE);
    unset($workspace_labels[$active_workspace->id()]);

    $form['current'] = [
      '#type' => 'item',
      '#title' => $this->t('Current workspace'),
      '#markup' => $active_workspace->label(),
      '#wrapper_attributes' => [
        'class' => ['container-inline'],
      ],
    ];

    $form['workspace_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Select workspace'),
      '#required' => TRUE,
      '#options' => $workspace_labels,
      '#wrapper_attributes' => [
        'class' => ['container-inline'],
      ],
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Activate'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $id = $form_state->getValue('workspace_id');

    // Ensure the workspace by that id exists.
    /** @var \Drupal\workspace\WorkspaceInterface $workspace */
    $workspace = $this->entityTypeManager->getStorage('workspace')->load($id);
    if (!$workspace) {
      $form_state->setErrorByName('workspace_id', $this->t('This workspace does not exist.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $id = $form_state->getValue('workspace_id');

    /** @var \Drupal\workspace\WorkspaceInterface $workspace */
    $workspace = $this->entityTypeManager->getStorage('workspace')->load($id);

    try {
      $this->workspaceManager->setActiveWorkspace($workspace);
      $this->messenger->addMessage($this->t("@workspace is now the active workspace.", ['@workspace' => $workspace->label()]));
      $form_state->setRedirect('<front>');
    }
    catch (\Exception $e) {
      watchdog_exception('workspace', $e);
      $this->messenger->addError($e->getMessage());
    }
  }

}
