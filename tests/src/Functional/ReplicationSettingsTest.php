<?php

namespace Drupal\Tests\workspace\Functional;

use Drupal\replication\ReplicationTask\ReplicationTask;
use Drupal\simpletest\BlockCreationTrait;
use Drupal\simpletest\BrowserTestBase;

/**
 * Test replication settings on replicate.
 *
 * @group workspace
 */
class ReplicationSettingsTest extends BrowserTestBase {

  use WorkspaceTestUtilities;

  use BlockCreationTrait {
    placeBlock as drupalPlaceBlock;
  }

  public static $modules = [
    'system',
    'node',
    'user',
    'block',
    'block_content',
    'workspace',
    'multiversion',
    'entity_reference',
  ];

  /**
   * Verify pull replication settings using the published filter as an example.
   */
  public function testPullReplicationSettings() {
    $permissions = [
      'create_workspace',
      'edit_own_workspace',
      'view_own_workspace',
      'create test content',
      'access content overview',
      'administer content types',
      'administer nodes',
      'access content overview',
    ];

    $this->createNodeType('Test', 'test');
    $this->setupWorkspaceSwitcherBlock();
    $this->drupalPlaceBlock('local_actions_block', ['id' => 'actions_block']);
    $test_user = $this->drupalCreateUser($permissions);
    $this->drupalLogin($test_user);
    $session = $this->getSession();

    // Create a published node.
    $this->drupalGet('/node/add/test');
    $this->drupalPostForm(NULL, [
      'title[0][value]' => 'Published node',
    ], t('Save and publish'));
    $page = $session->getPage();
    $page->hasContent('Published node has been created');
    $this->assertTrue($this->isLabelInContentOverview('Published node'));

    // Create an unpublished node.
    $this->drupalGet('/node/add/test');
    $this->drupalPostForm(NULL, [
      'title[0][value]' => 'Unpublished node',
    ], t('Save as unpublished'));
    $page = $session->getPage();
    $page->hasContent('Unpublished node has been created');
    $this->assertTrue($this->isLabelInContentOverview('Unpublished node'));

    // Create a target workspace with replication settings.
    $this->drupalGet('/admin/structure/workspace/add');
    $session = $this->getSession();
    $this->assertEquals(200, $session->getStatusCode());
    $page = $session->getPage();
    $page->fillField('label', 'Target');
    $page->fillField('machine_name', 'target');
    $page->selectFieldOption('upstream', '1');
    $page->selectFieldOption('edit-pull-replication-settings', 'published');
    $page->findButton(t('Save'))->click();
    $session->getPage()->hasContent("'Target (target)");

    $live = $this->getOneEntityByLabel('workspace', 'Live');
    $target = $this->getOneWorkspaceByLabel('Target');
    $source_pointer = $this->getPointerToWorkspace($live);
    $target_pointer = $this->getPointerToWorkspace($target);

    // Derive a replication task from the target Workspace.
    $task = new ReplicationTask();
    $replication_settings = $target->get('pull_replication_settings')->referencedEntities();
    $replication_settings = reset($replication_settings);
    $task->setFilter($replication_settings->getFilterId());
    $task->setParametersByArray($replication_settings->getParameters());

    // Replicate from Live to Target.
    /** @var ReplicatorManager $replicator */
    $replicator = \Drupal::service('workspace.replicator_manager');
    $replicator->replicate($source_pointer, $target_pointer, $task);

    // Verify the correct nodes were replicated.
    $this->switchToWorkspace($target);
    $this->assertTrue($this->isLabelInContentOverview('Published node'));
    $this->assertFalse($this->isLabelInContentOverview('Unpublished node'));
  }

  /**
   * Verify push replication settings using the published filter as an example.
   */
  public function testPushReplicationSettings() {
    $permissions = [
      'create_workspace',
      'edit_own_workspace',
      'view_own_workspace',
      'create test content',
      'access content overview',
      'administer content types',
      'administer nodes',
      'access content overview',
    ];

    $this->createNodeType('Test', 'test');
    $this->setupWorkspaceSwitcherBlock();
    $this->drupalPlaceBlock('local_actions_block', ['id' => 'actions_block']);
    $test_user = $this->drupalCreateUser($permissions);
    $this->drupalLogin($test_user);
    $session = $this->getSession();

    // Create a target workspace with replication settings.
    $this->drupalGet('/admin/structure/workspace/add');
    $session = $this->getSession();
    $this->assertEquals(200, $session->getStatusCode());
    $page = $session->getPage();
    $page->fillField('label', 'Target');
    $page->fillField('machine_name', 'target');
    $page->selectFieldOption('upstream', '1');
    $page->selectFieldOption('edit-push-replication-settings', 'published');
    $page->findButton(t('Save'))->click();
    $session->getPage()->hasContent("'Target (target)");

    $live = $this->getOneEntityByLabel('workspace', 'Live');
    $target = $this->getOneWorkspaceByLabel('Target');
    $source_pointer = $this->getPointerToWorkspace($live);
    $target_pointer = $this->getPointerToWorkspace($target);

    // Switch to the target workspace.
    $this->switchToWorkspace($target);

    // Create a published node.
    $this->drupalGet('/node/add/test');
    $this->drupalPostForm(NULL, [
      'title[0][value]' => 'Published node',
    ], t('Save and publish'));
    $page = $session->getPage();
    $page->hasContent('Published node has been created');
    $this->assertTrue($this->isLabelInContentOverview('Published node'));

    // Create an unpublished node.
    $this->drupalGet('/node/add/test');
    $this->drupalPostForm(NULL, [
      'title[0][value]' => 'Unpublished node',
    ], t('Save as unpublished'));
    $page = $session->getPage();
    $page->hasContent('Unpublished node has been created');
    $this->assertTrue($this->isLabelInContentOverview('Unpublished node'));

    // Derive a replication task from the target Workspace.
    $task = new ReplicationTask();
    $replication_settings = $target->get('push_replication_settings')->referencedEntities();
    $replication_settings = reset($replication_settings);
    $task->setFilter($replication_settings->getFilterId());
    $task->setParametersByArray($replication_settings->getParameters());

    // Replicate from Target to Live.
    /** @var ReplicatorManager $replicator */
    $replicator = \Drupal::service('workspace.replicator_manager');
    $replicator->replicate($target_pointer, $source_pointer, $task);

    // Verify the correct nodes were replicated.
    $this->switchToWorkspace($live);
    $this->assertTrue($this->isLabelInContentOverview('Published node'));
    $this->assertFalse($this->isLabelInContentOverview('Unpublished node'));
  }

}
