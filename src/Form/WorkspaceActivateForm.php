<?php

namespace Drupal\workspace\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\workspace\WorkspaceManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Handle activation of a workspace on administrative pages.
 */
class WorkspaceActivateForm extends EntityConfirmFormBase {

  /**
   * The workspace replication manager.
   *
   * @var \Drupal\workspace\WorkspaceManagerInterface
   */
  protected $workspaceManager;

  /**
   * Constructs a new WorkspaceActivateForm.
   *
   * @param \Drupal\workspace\WorkspaceManagerInterface $workspace_manager
   *   The workspace manager.
   */
  public function __construct(WorkspaceManagerInterface $workspace_manager) {
    $this->workspaceManager = $workspace_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('workspace.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Would you like to activate the %workspace workspace?', ['%workspace' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Activate the %workspace workspace.', ['%workspace' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->entity->toUrl('collection');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    // Content entity forms do not use the parent's #after_build callback.
    unset($form['#after_build']);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    try {
      $this->workspaceManager->setActiveWorkspace($this->entity);
      drupal_set_message($this->t("@workspace is now the active workspace.", ['@workspace' => $this->entity->label()]));
      $form_state->setRedirectUrl($this->entity->toUrl('collection'));
    }
    catch (\Exception $e) {
      watchdog_exception('workspace', $e);
      drupal_set_message($e->getMessage(), 'error');
    }
  }

}
