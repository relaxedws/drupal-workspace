<?php

namespace Drupal\workspace;

use Drupal\workspace\Entity\WorkspaceInterface;

/**
 * Class DefaultReplication
 */
class DefaultReplication {

  /**
   * @var WorkspaceManagerInterface
   */
  protected $workspaceManager;

  /**
   * DefaultReplication constructor.
   *
   * @param \Drupal\workspace\WorkspaceManagerInterface $workspace_manager
   */
  public function __construct(WorkspaceManagerInterface $workspace_manager) {
    $this->workspaceManager = $workspace_manager;
  }

  /**
   * @param \Drupal\workspace\Entity\WorkspaceInterface $source
   * @param \Drupal\workspace\Entity\WorkspaceInterface $target
   */
  public function replication(WorkspaceInterface $source, WorkspaceInterface $target) {
    // Set the source as the active workspace.
    $this->workspaceManager->setActiveWorkspace($source);

    // Get changes for the current workspace.

    // Get revision diff between source and target

    // Load each missing revision

    // Save each revision on the target workspace

    // Log

  }

}