<?php

namespace Drupal\Tests\workspace\Functional;

use Drupal\simpletest\BlockCreationTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * Class DefaultReplicatorTest
 * 
 * @group workspace
 */
class DefaultReplicatorTest extends BrowserTestBase {

  use WorkspaceTestUtilities;

  use BlockCreationTrait {
    placeBlock as drupalPlaceBlock;
  }

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
      'create_workspace',
      'view_any_workspace',
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
    ];
    $test_user = $this->drupalCreateUser($permissions);
    $this->drupalLogin($test_user);

    $this->setupWorkspaceSwitcherBlock();
  }

  public function testNodeReplication() {
    $dev = $this->createWorkspaceThroughUI('Dev', 'dev');
    $stage = $this->getOneWorkspaceByLabel('Stage');
    $live = $this->getOneWorkspaceByLabel('Live');
    $this->switchToWorkspace($dev);

    $this->drupalGet('/node/add/test');
    $session = $this->getSession();
    $this->assertEquals(200, $session->getStatusCode());
    $page = $session->getPage();
    $page->fillField('Title', 'Test node');
    $page->findButton(t('Save'))->click();
    $page = $session->getPage();
    $page->hasContent("Test node has been created");
    $this->drupalGet('/node/1/edit');
    $session = $this->getSession();
    $this->assertEquals(200, $session->getStatusCode());
    $page->findButton(t('Save'))->click();

    $this->assertEquals($dev->id(), $this->getOneEntityByLabel('node', 'Test node')->workspace->entity->id());

    /** @var \Drupal\workspace\DefaultReplicator $replicator */
    $replicator = \Drupal::service('workspace.replicator');
    $replicator->replication($dev, $stage);

    $this->switchToWorkspace($stage);
    $this->assertEquals($stage->id(), $this->getOneEntityByLabel('node', 'Test node')->workspace->entity->id());

    $this->drupalGet('/node/add/test');
    $session = $this->getSession();
    $this->assertEquals(200, $session->getStatusCode());
    $page = $session->getPage();
    $page->fillField('Title', 'Test stage node');
    $page->findButton(t('Save'))->click();
    $page = $session->getPage();
    $page->hasContent("Test stage node has been created");

    $this->assertEquals($stage->id(), $this->getOneEntityByLabel('node', 'Test stage node')->workspace->entity->id());

    $replicator->replication($stage, $live);

    $this->switchToWorkspace($live);
    $this->assertEquals($live->id(), $this->getOneEntityByLabel('node', 'Test node')->workspace->entity->id());
    $this->assertEquals($live->id(), $this->getOneEntityByLabel('node', 'Test stage node')->workspace->entity->id());
  }
}