<?php

namespace Drupal\Tests\workspace\Functional;

use Drupal\multiversion\Entity\Workspace;
use Drupal\node\Entity\Node;
use Drupal\simpletest\WebTestBase;
use Drupal\workspace\Entity\WorkspacePointer;

/**
 * Tests Internal Replicator.
 *
 * @group workspace
 */
class InternalReplicatorTest extends WebTestBase {

  protected $strictConfigSchema = FALSE;

  public static $modules = [
    'multiversion',
    'node',
    'user',
    'replication',
    'workspace',
  ];

  /**
   * @var \Drupalser\Entity\User
   *   The logged in user.
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create Basic page and Article node types.
    if ($this->profile != 'standard') {
      $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);
    }

    $this->user = $this->drupalCreateUser(['administer workspaces']);
    $this->drupalLogin($this->user);
  }

  /**
   * Test internal replicator's replicate.
   */
  public function testReplicate() {
    $container = \Drupal::getContainer();
    $replicator = $container->get('workspace.internal_replicator');
    $workspace_manager = $container->get('workspace.manager');

    $workspace1 = Workspace::create(['machine_name' => 'workspace1', 'type' => 'basic']);
    $workspace1->save();

    $entity = Node::create([
      'type' => 'article',
      'title' => 'Test Entity 1',
      'uid' => $this->user->id(),
    ]);
    $entity->workspace = $workspace1;
    $entity->save();

    $workspace2 = Workspace::create(['machine_name' => 'workspace2', 'type' => 'basic']);
    $workspace2->save();

    /** @var WorkspacePointer $pointer1 */
    $pointer1 = WorkspacePointer::create();
    $pointer1->setWorkspace($workspace1);
    $pointer1->save();

    /** @var WorkspacePointer $pointer2 */
    $pointer2 = WorkspacePointer::create();
    $pointer2->setWorkspace($workspace2);
    $pointer2->save();

    // Replicate the entity from workspace 1 to workspace 2.
    $replicator->replicate($pointer1, $pointer2);

    // Verify that the entity can be found in workspace1.
    $workspace_manager->setActiveWorkspace($workspace1);
    $this->assertEqual('workspace1', $workspace_manager->getActiveWorkspace()->getMachineName());
    $node = Node::load($entity->id());
    $this->assertEqual(FALSE, empty($node), 'Entity was found in source workspace');

    // Verify that the entity has been replicated to workspace2.
    $workspace_manager->setActiveWorkspace($workspace2);
    $this->assertEqual('workspace2', $workspace_manager->getActiveWorkspace()->getMachineName());
    $node = Node::load($entity->id());
    $this->assertEqual(FALSE, empty($node), 'Entity was found in target workspace');
  }

}
