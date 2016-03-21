<?php

/**
 * @file
 * Contains \Drupal\workspace\Tests\ReplicatorTest.
 */

namespace Drupal\workspace\Tests;

use Drupal\multiversion\Entity\Workspace;
use Drupal\node\Entity\Node;
use Drupal\replication\Entity\ReplicationLogInterface;
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
    /** @var \Drupal\replication\Entity\ReplicationLogInterface $replication_log */
    $replication_log = \Drupal::service('workspace.replicator_manager')->replicate(WorkspacePointer::load(2), WorkspacePointer::load(1));
    $reloaded_node = Node::load($this->node->id());
    $this->assertEqual(count($reloaded_node->get('workspace')), 2, 'Node is in two workspaces');
    $this->assertTrue(($replication_log instanceof ReplicationLogInterface), "ReplicationLog returned.");
    $this->assertTrue($replication_log->get('ok'), "Replication went ok");
    $this->assertEqual(1, $replication_log->getHistory()[0]['docs_read'], "ReplicationLog states 1 document was read");
    $this->assertEqual(1, $replication_log->getHistory()[0]['docs_written'], "ReplicationLog states 1 document was written");
  }
}