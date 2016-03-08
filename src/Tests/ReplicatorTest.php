<?php

/**
 * @file
 * Contains \Drupal\workspace\Tests\ReplicatorTest.
 */

namespace Drupal\workspace\Tests;

use Drupal\multiversion\Entity\Workspace;
use Drupal\node\Entity\Node;
use Drupal\simpletest\WebTestBase;

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
    /** @var PointerInterface $source_pointer */
    $source_pointer = \Drupal::service('workspace.pointer')->get('workspace:2');
    /** @var PointerInterface $target_pointer */
    $target_pointer = \Drupal::service('workspace.pointer')->get('workspace:1');
    \Drupal::service('workspace.replicator_manager')->replicate($source_pointer, $target_pointer);
    $reloaded_node = Node::load($this->node->id());
    $this->assertEqual(count($reloaded_node->get('workspace')), 2, 'Node is in two workspaces');
  }
}