<?php

namespace Drupal\Tests\workspace\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\multiversion\Entity\Workspace;
use Drupal\multiversion\Entity\WorkspaceType;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * Tests for deleting workspaces.
 *
 * @requires key_value
 * @requires multiversion
 *
 * @group workspace
 */
class WorkspaceDeletionTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['datetime', 'node', 'workspace', 'replication', 'multiversion', 'key_value', 'serialization', 'user', 'system'];

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Cron
   */
  protected $cron;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installSchema('node', 'node_access');
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installSchema('key_value', 'key_value_sorted');
    $this->installConfig('multiversion');
    $this->installEntitySchema('workspace');
    $this->installEntitySchema('workspace_pointer');
    $this->entityTypeManager = \Drupal::entityTypeManager();
    $this->cron = \Drupal::service('cron');
    \Drupal::service('multiversion.manager')->enableEntityTypes();
  }

  public function testDeletingWorkspace() {
    $workspace_type = WorkspaceType::create([
      'id' => 'test',
      'label' => 'Workspace bundle',
    ]);
    $workspace_type->save();

    $workspace = Workspace::create([
      'type' => 'test',
      'machine_name' => 'le_workspace',
      'label' => 'Le Workspace',
    ]);
    $workspace->save();

    $node_type = NodeType::create([
      'name' => 'le content type',
      'type' => 'le_content_type',
    ]);
    $node_type->save();
    $node = Node::create([
      'type' => 'le_content_type',
      'workspace' => $workspace->id(),
      'title' => 'le content',
    ]);
    $node->save();
    $node2 = Node::create([
      'type' => 'le_content_type',
      'workspace' => $workspace->id(),
      'title' => 'le content deux',
    ]);
    $node2->save();

    $workspace->delete();
    $this->cron->run();

    $workspaces = $this->entityTypeManager->getStorage('workspace')->getQuery()->execute();
    $nodes = $this->entityTypeManager->getStorage('node')->getQuery()->execute();
    $this->assertEmpty($workspaces, 'No workspaces');
    $this->assertEmpty($nodes, 'No nodes');
  }

}
