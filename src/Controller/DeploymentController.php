<?php

namespace Drupal\workspace\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\workspace\Entity\Workspace;
use Drupal\workspace\WorkspaceManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

class DeploymentController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * @var \Drupal\workspace\WorkspaceManagerInterface
   */
  protected $workspaceManager;

  public function __construct(WorkspaceManagerInterface $workspace_manager) {
    $this->workspaceManager = $workspace_manager;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('workspace.manager')
    );
  }


  public function workspaces() {
    $active_workspace_id = $this->workspaceManager->getActiveWorkspace();
    $workspaces = Workspace::loadMultiple();
    return [
      '#theme' => 'workspace_deployment',
      '#active_workspace_id' => $active_workspace_id,
      '#content' => $workspaces,
    ];
  }
}