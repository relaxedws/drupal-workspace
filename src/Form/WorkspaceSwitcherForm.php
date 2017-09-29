<?php

namespace Drupal\workspace\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\workspace\Entity\Workspace;
use Drupal\workspace\WorkspaceManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Switcher for to activate a different workspace.
 *
 * This is a separate form for each workspace rather than one big form with
 * many buttons for scaling reasons. For example, this form may show up in a
 * toolbar. We may want to show just a subset of workspaces to switch to, maybe
 * access control, etc. This approach keeps that logic out of the switching
 * process itself.
 */
class WorkspaceSwitcherForm extends FormBase {

  /**
   * @var \Drupal\workspace\WorkspaceManagerInterface
   */
  protected $workspaceManager;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('workspace.manager'),
      $container->get('entity_type.manager')
    );
  }

  public function __construct(WorkspaceManagerInterface $workspace_manager, EntityTypeManagerInterface $entity_type_manager) {
    $this->workspaceManager = $workspace_manager;
    $this->entityTypeManager = $entity_type_manager;
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
    $workspaces = Workspace::loadMultiple();
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
      ]
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
    /** @var \Drupal\workspace\Entity\WorkspaceInterface $workspace */
    $workspace = $this->entityTypeManager->getStorage('workspace')->load($id);
    if (!$workspace) {
      $form_state->setErrorByName('workspace_id', 'This workspace no longer exists.');
    }
  }

  /**
   * @inheritDoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $id = $form_state->getValue('workspace_id');
    $workspace = Workspace::load($id);

    try {
      $this->workspaceManager->setActiveWorkspace($workspace);
      drupal_set_message($this->t("@workspace is now the active workspace.", ['@workspace' => $workspace->label()]));
      $form_state->setRedirect('<front>');
    }
    catch (\Exception $e) {
      watchdog_exception('Workspace', $e);
      drupal_set_message($e->getMessage(), 'error');
    }
  }

}
