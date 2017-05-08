<?php

namespace Drupal\workspace\Entity\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\multiversion\Entity\Workspace;
use Drupal\multiversion\Workspace\WorkspaceManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a confirmation form for deleting a workspace entity.
 */
class WorkspaceDeleteForm extends ContentEntityDeleteForm {

  /**
   * @var \Drupal\multiversion\Workspace\WorkspaceManagerInterface
   */
  private $workspaceManager;

  /**
   * @var \Drupal\Core\Entity\EntityInterface|null
   */
  private $defaultWorkspace;

  /**
   * WorkspaceDeleteForm constructor.
   *
   * @param \Drupal\multiversion\Workspace\WorkspaceManagerInterface $workspace_manager
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $default_workspace_id
   */
  public function __construct(WorkspaceManagerInterface $workspace_manager, $default_workspace_id) {
    $this->workspaceManager = $workspace_manager;
    $this->defaultWorkspace = Workspace::load($default_workspace_id);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('workspace.manager'),
      $container->getParameter('workspace.default')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if (in_array($this->entity, [
      $this->workspaceManager->getActiveWorkspace(),
      $this->defaultWorkspace
      ])) {
      drupal_set_message('It is not possible to delete the active or default workspaces.', 'error');
      return $this->redirect('entity.workspace.collection');
    }
    return parent::buildForm($form, $form_state);
  }
}