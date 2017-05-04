<?php

namespace Drupal\workspace\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\workspace\Entity\Workspace;
use Drupal\workspace\Form\DeploymentForm;
use Drupal\workspace\Form\WorkspaceSwitcherForm;
use Drupal\workspace\WorkspaceManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class DeploymentController
 */
class DeploymentController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * @var \Drupal\workspace\WorkspaceManagerInterface
   */
  protected $workspaceManager;

  /**
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * DeploymentController constructor.
   *
   * @param \Drupal\workspace\WorkspaceManagerInterface $workspace_manager
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   */
  public function __construct(WorkspaceManagerInterface $workspace_manager, FormBuilderInterface $form_builder) {
    $this->workspaceManager = $workspace_manager;
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('workspace.manager'),
      $container->get('form_builder')
    );
  }

  /**
   * @return array
   */
  public function workspaces() {
    $active_workspace_id = $this->workspaceManager->getActiveWorkspace();
    $workspaces = Workspace::loadMultiple();
    $active_workspace = $workspaces[$active_workspace_id];
    unset($workspaces[$active_workspace_id]);
    return $this->formBuilder->getForm(DeploymentForm::class, $active_workspace);
    return [
      '#theme' => 'workspace_deployment',
      '#active_workspace' => $active_workspace,
      '#workspaces' => $workspaces,
      '#deploy' => $deploy,
      '#attached' => [
        'library' => [
          'workspace/drupal.workspace.deployment',
        ],
      ],
      '#cache' => [
        'contexts' => ['workspace'],
      ],
    ];
  }
}