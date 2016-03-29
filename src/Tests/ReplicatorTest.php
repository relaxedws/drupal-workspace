<?php

/**
 * @file
 * Contains \Drupal\workspace\Tests\ReplicatorTest.
 */

namespace Drupal\workspace\Tests;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\multiversion\Entity\Workspace;
use Drupal\multiversion\Entity\WorkspaceInterface;
use Drupal\multiversion\Workspace\WorkspaceManager;
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

  /** @var  WorkspaceManager */
  protected $workspaceManager;

  /** @var  Workspace */
  protected $developmentWorkspace;

  /** @var  Node */
  protected $node;

  public function setUp() {
    parent::setUp();
    $this->workspaceManager = \Drupal::service('workspace.manager');

    $test_user = $this->drupalCreateUser(['administer workspaces']);
    $this->drupalLogin($test_user);
    $this->developmentWorkspace = Workspace::create([
      'type' => 'basic',
      'label' => 'Development',
      'machine_name' => 'development'
    ]);
    $this->developmentWorkspace->save();
    $this->workspaceManager->setActiveWorkspace($this->developmentWorkspace);

    // Create test content.
    // @todo: We need to test with a lot more entities and entity types.
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

    // Execute the replication.
    /** @var \Drupal\replication\Entity\ReplicationLogInterface $replication_log */
    $replication_log = \Drupal::service('workspace.replicator_manager')->replicate($source_pointer, $target_pointer);

    $this->assertTrue(($replication_log instanceof ReplicationLogInterface), "ReplicationLog returned.");
    $this->assertTrue($replication_log->get('ok'), "Replication went ok");
    $this->assertEqual(1, $replication_log->getHistory()[0]['docs_read'], "ReplicationLog states 1 document was read");
    $this->assertEqual(1, $replication_log->getHistory()[0]['docs_written'], "ReplicationLog states 1 document was written");

    // Check that the node exists on the target workspace.
    $this->workspaceManager->setActiveWorkspace($default_workspace);
    /** @var EntityTypeManager $entity_type_manager */
    $entity_type_manager = \Drupal::service('entity_type.manager');
    $entities = $entity_type_manager->getStorage('node')->loadByProperties(['uuid' => $this->node->uuid()]);
    $this->assertEqual(count($entities), 1, 'The node was replicated.');
    $entity = reset($entities);
    $this->assertEqual($entity->_rev->value, $this->node->_rev->value, 'The revision hash was retained during replication.');
  }

  protected function loadPointer(WorkspaceInterface $workspace) {
    $pointers = \Drupal::entityTypeManager()
      ->getStorage('workspace_pointer')
      ->loadByProperties(['workspace_pointer' => $workspace->id()]);
    $pointer = reset($pointers);
    return $pointer;
  }
}