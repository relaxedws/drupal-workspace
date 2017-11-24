<?php

namespace Drupal\Tests\workspace\Functional;

use Drupal\Tests\block\Traits\BlockCreationTrait;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\node\Traits\NodeCreationTrait;

/**
 * Tests replication of entities between workspaces.
 *
 * @group workspace
 */
class ReplicationTest extends BrowserTestBase {

  use WorkspaceTestUtilities;

  use BlockCreationTrait {
    placeBlock as drupalPlaceBlock;
  }

  use NodeCreationTrait;

  public static $modules = [
    'system',
    'node',
    'user',
    'block',
    'workspace',
    'taxonomy',
    'entity_reference',
    'field',
    'field_ui',
    'field_test',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->createNodeType('Test', 'test');

    $permissions = [
      'create workspace',
      'view any workspace',
      'create test content',
      'edit own test content',
      'access administration pages',
      'administer taxonomy',
      'administer menu',
      'access content overview',
      'administer content types',
      'administer node display',
      'administer node fields',
      'administer node form display',
      'administer workspaces',
    ];
    $test_user = $this->drupalCreateUser($permissions);
    $this->drupalLogin($test_user);

    $this->setupWorkspaceSwitcherBlock();
  }

  /**
   * Tests replicating Nodes between workspaces.
   */
  public function testNodeReplication() {
    // Create dev, get stage and live, set dev to the active workspace.
    $dev = $this->createWorkspaceThroughUI('Dev', 'dev', 'local_workspace:stage');
    $stage = $this->getOneWorkspaceByLabel('Stage');
    $live = $this->getOneWorkspaceByLabel('Live');
    $this->switchToWorkspace($dev);

    // Add a test node.
    $this->createNode(['type' => 'test', 'title' => 'Test node']);
    // Made an edit to the test node.
    $this->drupalGet('/node/1/edit');
    $session = $this->getSession();
    $this->assertEquals(200, $session->getStatusCode());
    $session->getPage()->findButton(t('Save'))->click();

    // Loading the node should get revision 2 on the dev workspace.
    $entity = $this->getNodeByTitle('Test node');
    $this->assertEquals(2, $entity->getRevisionId());
    $this->assertEquals($dev->id(), $entity->workspace->target_id);

    $this->drupalGet('/admin/config/workflow/workspace/' . $dev->id() . '/deploy');
    $session = $this->getSession();
    $this->assertEquals(200, $session->getStatusCode());
    $session->getPage()->findButton('edit-submit')->click();
    $session->getPage()->hasContent('Successful deployment');

    // Switch to stage and the node will still be revision 2, but now on the
    // stage workspace.
    $this->switchToWorkspace($stage);
    $entity = $this->getOneEntityByLabel('node', 'Test node');
    $this->assertEquals(2, $entity->getRevisionId());
    $this->assertEquals($stage->id(), $entity->workspace->getValue()[0]['target_id']);

    // Add a test node on the stage workspace.
    $this->createNode(['type' => 'test', 'title' => 'Test stage node']);

    // Loading the node should get revision 3 on the stage workspace.
    $entity2 = $this->getNodeByTitle('Test stage node');
    $this->assertEquals(3, $entity2->getRevisionId());
    $this->assertEquals($stage->id(), $entity2->workspace->target_id);

    // Run a deployment from stage to live.
    $this->drupalGet('/admin/config/workflow/workspace/' . $stage->id() . '/deploy');
    $session = $this->getSession();
    $page = $session->getPage();
    $this->assertEquals(200, $session->getStatusCode());
    $page->findButton('edit-submit')->click();
    $session->getPage()->hasContent('Successful deployment');

    // Switch to the live workspace and load both nodes. They should both have
    // the original revision IDs and be on the live workspace.
    $this->switchToWorkspace($live);
    $entity = $this->getNodeByTitle('Test node');
    $this->assertEquals(2, $entity->getRevisionId());
    $entity2 = $this->getNodeByTitle('Test stage node');
    $this->assertEquals(3, $entity2->getRevisionId());
    $this->assertEquals($live->id(), $entity->workspace->target_id);
    $this->assertEquals($live->id(), $entity2->workspace->target_id);

    // Switch back to dev and update 'Test node'.
    $this->switchToWorkspace($dev);
    // Made an edit to the test node.
    $this->drupalGet('/node/1/edit');
    $session = $this->getSession();
    $this->assertEquals(200, $session->getStatusCode());
    $session->getPage()->findButton(t('Save'))->click();

    // Loading the node should get revision 4 on the dev workspace.
    $entity = $this->getNodeByTitle('Test node');
    $this->assertEquals(4, $entity->getRevisionId());
    $this->assertEquals($dev->id(), $entity->workspace->target_id);

    // Loading the node should get revision 2 on the stage workspace.
    $this->switchToWorkspace($stage);
    $entity = $this->getNodeByTitle('Test node');
    $this->assertEquals(2, $entity->getRevisionId());
    $this->assertEquals($stage->id(), $entity->get('workspace')->getValue()[1]['target_id']);

    // Loading the node should get revision 2 on the live workspace.
    $this->switchToWorkspace($live);
    $entity = $this->getNodeByTitle('Test node');
    $this->assertEquals(2, $entity->getRevisionId());
    $this->assertEquals($live->id(), $entity->workspace->target_id);

    // Run deployment from dev to stage.
    $this->switchToWorkspace($dev);
    $this->drupalGet('/admin/config/workflow/workspace/' . $dev->id() . '/deploy');
    $session = $this->getSession();
    $this->assertEquals(200, $session->getStatusCode());
    $session->getPage()->findButton('edit-submit')->click();
    $session->getPage()->hasContent('Successful deployment');

    // Loading the node should get revision 4 on the dev workspace.
    $entity = $this->getNodeByTitle('Test node');
    $this->assertEquals(4, $entity->getRevisionId());
    $this->assertEquals($dev->id(), $entity->get('workspace')->getValue()[1]['target_id']);

    // Loading the node should get revision 4 on the stage workspace.
    $this->switchToWorkspace($stage);
    $entity = $this->getNodeByTitle('Test node');
    $this->assertEquals(4, $entity->getRevisionId());
    $this->assertEquals($stage->id(), $entity->get('workspace')->getValue()[0]['target_id']);

    // Loading the node should get revision 2 on the live workspace.
    $this->switchToWorkspace($live);
    $entity = $this->getNodeByTitle('Test node');
    $this->assertEquals(2, $entity->getRevisionId());
    $this->assertEquals($live->id(), $entity->workspace->target_id);

    // Run deployment from stage to live.
    $this->switchToWorkspace($stage);
    $this->drupalGet('/admin/config/workflow/workspace/' . $stage->id() . '/deploy');
    $session = $this->getSession();
    $this->assertEquals(200, $session->getStatusCode());

    $session->getPage()->findButton('edit-submit')->click();
    $session->getPage()->hasContent('Successful deployment');

    // Loading the node should get revision 4 on the dev workspace.
    $entity = $this->getNodeByTitle('Test node');
    $this->assertEquals(4, $entity->getRevisionId());
    $this->assertEquals($dev->id(), $entity->get('workspace')->getValue()[2]['target_id']);

    // Loading the node should get revision 4 on the stage workspace.
    $this->switchToWorkspace($stage);
    $entity = $this->getNodeByTitle('Test node');
    $this->assertEquals(4, $entity->getRevisionId());
    $this->assertEquals($stage->id(), $entity->get('workspace')->getValue()[1]['target_id']);

    // Loading the node should get revision 4 on the live workspace.
    $this->switchToWorkspace($live);
    $entity = $this->getNodeByTitle('Test node');
    $this->assertEquals(4, $entity->getRevisionId());
    $this->assertEquals($live->id(), $entity->workspace->target_id);
  }

}
