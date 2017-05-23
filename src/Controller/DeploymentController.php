<?php

namespace Drupal\workspace\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\workspace\Entity\Workspace;
use Drupal\workspace\Form\DeploymentForm;
use Drupal\workspace\UpstreamManager;
use Drupal\workspace\WorkspaceManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
   * @var \Drupal\workspace\UpstreamManager
   */
  protected $upstreamManager;

  /**
   * DeploymentController constructor.
   *
   * @param \Drupal\workspace\WorkspaceManagerInterface $workspace_manager
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   * @param \Drupal\workspace\UpstreamManager
   */
  public function __construct(WorkspaceManagerInterface $workspace_manager, FormBuilderInterface $form_builder, UpstreamManager $upstream_manager) {
    $this->workspaceManager = $workspace_manager;
    $this->formBuilder = $form_builder;
    $this->upstreamManager = $upstream_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('workspace.manager'),
      $container->get('form_builder'),
      $container->get('workspace.upstream_manager')
    );
  }

  /**
   * @return array
   */
  public function workspaces($workspace = NULL) {
    $active_workspace = Workspace::load($workspace);
    $form = $this->formBuilder->getForm(DeploymentForm::class, $active_workspace);

    return [
      '#type' => 'details',
      '#title' => $this->t('Deploy content'),
      '#open' => TRUE,
      'from' => [
        '#prefix' => '<p>',
        '#markup' => $this->t('Update %from from %to or deploy to %to.', [
          '%from' => $active_workspace->label(),
          '%to' => $this->upstreamManager
            ->createInstance($active_workspace->get('upstream')->value)
            ->getLabel()
        ]),
        '#suffix' => '</p>',
      ],
      'form' => $form,
    ];
  }

}
