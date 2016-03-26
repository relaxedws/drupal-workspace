<?php

/**
 * @file
 * Contains \Drupal\workspace\Tests\ReplicatorTest.
 */

namespace Drupal\workspace\Tests;

use Drupal\multiversion\Entity\Workspace;
use Drupal\multiversion\Entity\WorkspaceInterface;
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
    $test_user = $this->drupalCreateUser(['administer workspaces']);
    $this->drupalLogin($test_user);
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
    $default_workspace_id = \Drupal::getContainer()->getParameter('workspace.default');
    $default_workspace = Workspace::load($default_workspace_id);
    $source_pointer = $this->loadPointer($this->developmentWorkspace);
    $target_pointer = $this->loadPointer($default_workspace);
    /** @var \Drupal\replication\Entity\ReplicationLogInterface $replication_log */
    $replication_log = \Drupal::service('workspace.replicator_manager')->replicate($source_pointer, $target_pointer);
    $reloaded_node = Node::load($this->node->id());
    $this->assertEqual(count($reloaded_node->get('workspace')), 2, 'Node is in two workspaces');
    $this->assertTrue(($replication_log instanceof ReplicationLogInterface), "ReplicationLog returned.");
    $this->assertTrue($replication_log->get('ok'), "Replication went ok");
    $this->assertEqual(1, $replication_log->getHistory()[0]['docs_read'], "ReplicationLog states 1 document was read");
    $this->assertEqual(1, $replication_log->getHistory()[0]['docs_written'], "ReplicationLog states 1 document was written");
  }

  protected function loadPointer(WorkspaceInterface $workspace) {
    $pointers = \Drupal::entityTypeManager()
      ->getStorage('workspace_pointer')
      ->loadByProperties(['workspace_pointer' => $workspace->id()]);
    $pointer = reset($pointers);
    return $pointer;
  }
}