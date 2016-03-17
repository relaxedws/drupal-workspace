<?php

/**
 * @file
 * Contains \Drupal\workspace\Tests\ReplicatorTest.
 */

namespace Drupal\workspace\Tests;

use Drupal\multiversion\Entity\Workspace;
use Drupal\node\Entity\Node;
use Drupal\simpletest\WebTestBase;
use Drupal\workspace\Entity\WorkspacePointer;

/**
 * Test the workspace entity.
 *
 * @group workspace
 */
class ReplicatorTest extends WebTestBase {

  protected $strictConfigSchema = FALSE;

  public static $modules = ['node', 'workspace'];

  /** @var  Workspace */
  protected $developmentWorkspace;

  /** @var  Node */
  protected $node;

  public function setUp() {
    parent::setUp();
    $this->developmentWorkspace = Workspace::create([
      'type' => 'basic',
      'label' => 'Development',
      'machine_name' => 'development'
    ]);
    $this->developmentWorkspace->save();
    \Drupal::service('workspace.manager')->setActiveWorkspace($this->developmentWorkspace);
    $this->node = Node::create([
      'type' => 'article',
      'title' => 'test'
    ]);
    $this->node->save();
  }

  public function testReplicator() {
    $this->assertEqual(count($this->node->get('workspace')), 1, 'Node is in one workspace');
    \Drupal::service('workspace.replicator_manager')->replicate(WorkspacePointer::load(2), WorkspacePointer::load(1));
    $reloaded_node = Node::load($this->node->id());
    $this->assertEqual(count($reloaded_node->get('workspace')), 2, 'Node is in two workspaces');
  }
}