<?php

namespace Drupal\Tests\workspace\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\multiversion\Entity\Workspace;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\simpletest\BlockCreationTrait;
use Drupal\workspace\Entity\WorkspacePointer;

/**
 * Tests replication settings with the internal replicator.
 *
 * @group workspace
 */
class ReplicationSettings extends BrowserTestBase {

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
    'multiversion',
    'taxonomy',
    'entity_reference',
    'field',
    'field_ui',
    'menu_link_content',
    'menu_ui',
    'replication',
  ];

/*
  public static $modules = [
    'replication',
  ];
*/

  public function setUp() {
    parent::setUp();

    $workspace_type_storage = \Drupal::entityManager()->getStorage('workspace_type');
    $workspace_type = $workspace_type_storage->load('basic');

    $is_workspace_moderatable = $workspace_type->getThirdPartySetting('workbench_moderation', 'enabled');
    $this->assertFalse($is_workspace_moderatable);

    // Make the "basic" Workspace moderatable.
    $workspace_type->setThirdPartySetting('workbench_moderation', 'enabled', TRUE);
    $workspace_type->setThirdPartySetting('workbench_moderation', 'allowed_moderation_states', ['draft', 'needs_review', 'published']);
    $workspace_type->setThirdPartySetting('workbench_moderation', 'default_moderation_state', 'draft');
    $workspace_type->save();

    $is_workspace_moderatable = $workspace_type->getThirdPartySetting('workbench_moderation', 'enabled');
    $this->assertTrue($is_workspace_moderatable);
  }

  /**
   * Test replication settings with the InternalReplicator.
   */
  public function testReplicate() {
    $permissions = [
      'create_workspace',
      'edit_own_workspace',
      'view_own_workspace',
      'create test content',
      'access administration pages',
      'administer taxonomy',
      'administer menu',
      'access content overview',
      'administer content types',
      'administer node display',
      'administer node fields',
      'administer node form display',
    ];

    $container = \Drupal::getContainer();
    $workspace_manager = $container->get('workspace.manager');

    $this->createNodeType('Test', 'test');

    $this->setupWorkspaceSwitcherBlock();

    $test_user = $this->drupalCreateUser($permissions);
    $this->drupalLogin($test_user);

    $session = $this->getSession();

    // Validate Workspaces are moderatable.
    $this->drupalGet('/admin/structure/workspace/add');
    $this->assertEquals(200, $session->getStatusCode());
    $page = $session->getPage();
    $this->assertTrue($page->hasContent('Save and Create New Draft'), 'Workspaces are moderatable');

    // Create Child workspace that replicates only published to/from live.
    $this->drupalGet('/admin/structure/workspace/add');
    $this->assertEquals(200, $session->getStatusCode());
    $page = $session->getPage();
    $page->fillField('Label', 'Child');
    $page->selectFieldOption('upstream', '1');
    $page->selectFieldOption('edit-pull-replication-settings', 'published');
    $page->selectFieldOption('edit-push-replication-settings', 'published');
    $page->findButton(t('Save and Create New Draft'))->click();
    $page = $session->getPage();
    $page->hasContent('Workspace Child has been created.');

    // Add a published node and an unpublished node on Live.
    $this->drupalGet('/node/add/test');
    $session = $this->getSession();
    $this->assertEquals(200, $session->getStatusCode());
    $page = $session->getPage();
    $page->fillField('Title', 'Live published');
    $page->findButton(t('Save and Create New Draft'))->click();
    $page = $session->getPage();
    $page->hasContent('Live published node has been created');
    $live_published_node_url = $page->getCurrentUrl();

    $this->drupalGet('/node/add/test');
    $session = $this->getSession();
    $this->assertEquals(200, $session->getStatusCode());
    $page = $session->getPage();
    $page->fillField('Title', 'Live unpublished');
    $page->findButton(t('Save and Create New Draft'))->click();
    $page = $session->getPage();
    $page->hasContent('Live unpublished node has been created');
    $live_unpublished_node_url = $page->getCurrentUrl();

    // Switch to child and update it (pull from live to child).
    $this->drupalGet('/admin/structure/workspace');
    $session = $this->getSession();
    $this->assertEquals(200, $session->getStatusCode());
    $page = $session->getPage();
    // @todo ensure this is the correct element to click on to switch
    $page->findLink('.block-system tbody tr:last-child .activate a')->click();
    $page = $session->getPage();
    $page->hasContent('Activate workspace Child');
    $page->findButton(t('Activate'))->click();
    // @todo assert the active workspace has switched (there is no set message)

    // Assert child has only the published node.
    $this->drupalGet($live_published_node_url);
    $session = $this->getSession();
    $this->assertEquals(200, $session->getStatusCode());

    $this->drupalGet($live_unpublished_node_url);
    $session = $this->getSession();
    $this->assertEquals(404, $session->getStatusCode());

    // Add a published node and an unpublished node on child.
    $this->drupalGet('/node/add/test');
    $session = $this->getSession();
    $this->assertEquals(200, $session->getStatusCode());
    $page = $session->getPage();
    $page->fillField('Title', 'Child published');
    $page->findButton(t('Save and Create New Draft'))->click();
    $page = $session->getPage();
    $page->hasContent('Child published node has been created');
    $child_published_node_url = $page->getCurrentUrl();

    $this->drupalGet('/node/add/test');
    $session = $this->getSession();
    $this->assertEquals(200, $session->getStatusCode());
    $page = $session->getPage();
    $page->fillField('Title', 'Child unpublished');
    $page->findButton(t('Save and Create New Draft'))->click();
    $page = $session->getPage();
    $page->hasContent('Child unpublished node has been created');
    $child_unpublished_node_url = $page->getCurrentUrl();

    // Publish the child workspace (push from child to live).
    // @todo write this portion; can this be done without using a JavaScript test?

    // Assert live has only the published node.
    /* @todo once the action to "publish" the child workspace is written, uncomment this
    $this->drupalGet($child_published_node_url);
    $session = $this->getSession();
    $this->assertEquals(200, $session->getStatusCode());

    $this->drupalGet($child_unpublished_node_url);
    $session = $this->getSession();
    $this->assertEquals(404, $session->getStatusCode());
    */
  }

}

