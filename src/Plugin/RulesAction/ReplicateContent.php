<?php

/**
 * @file
 * Contains \Drupal\workspace\Plugin\RulesAction\ReplicateContent.
 */

namespace Drupal\workspace\Plugin\RulesAction;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\multiversion\Entity\Workspace;
use Drupal\multiversion\Workspace\WorkspaceManagerInterface;
use Drupal\rules\Core\RulesActionBase;
use Drupal\workspace\Pointer;
use Drupal\workspace\ReplicatorManager;
use Drupal\workspace\WorkspacePointerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Save entity' action.
 *
 * @RulesAction(
 *   id = "workspace_replicate_content",
 *   label = @Translation("Replicate content"),
 *   category = @Translation("Workspace")
 * )
 *
 */
class ReplicateContent extends RulesActionBase implements ContainerFactoryPluginInterface{

  /** @var  WorkspaceManagerInterface */
  protected $workspaceManager;

  /** @var  WorkspacePointerInterface */
  protected $workspacePointer;

  /** @var  ReplicatorManager */
  protected $replicatorManager;

  /**
   * Constructs an ReplicateContent object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\multiversion\Workspace\WorkspaceManagerInterface $workspace_manager
   * @param \Drupal\workspace\WorkspacePointerInterface $workspace_pointer
   * @param \Drupal\workspace\ReplicatorManager $replicator_manager
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, WorkspaceManagerInterface $workspace_manager, WorkspacePointerInterface $workspace_pointer, ReplicatorManager $replicator_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->workspaceManager = $workspace_manager;
    $this->workspacePointer = $workspace_pointer;
    $this->replicatorManager = $replicator_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('workspace.manager'),
      $container->get('workspace.pointer'),
      $container->get('workspace.replicator_manager')
    );
  }
  /**
   * Replicate content from active Workspace to it's upstream.
   */
  protected function doExecute() {
    /** @var Workspace $worksapce */
    $workspace = $this->workspaceManager->getActiveWorkspace();
    /** @var Workspace $upstream */
    $upstream = $workspace->get('upstream')->entity;
    /** @var Pointer $source */
    $source = $this->workspacePointer->get('workspace:' . $workspace->id());
    /** @var Pointer $target */
    $target = $this->workspacePointer->get('workspace:' . $upstream->id());
    $result = $this->replicatorManager->replicate($source, $target);
    if (!isset($result['error'])) {
      drupal_set_message($this->t('Content replicated from workspace @source to workspace @target',
        ['@source' => $workspace->label(), '@target' => $upstream->label()]));
    }
    else {
      drupal_set_message($this->t('Error replicating content: @message', ['@message' => $result['error']]));
    }
  }

}
