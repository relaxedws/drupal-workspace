<?php

namespace Drupal\Tests\workspace\Functional;

use Drupal\simpletest\BlockCreationTrait;
use Drupal\Tests\BrowserTestBase;
use Drupal\workspace\Entity\Workspace;

/**
 * Tests using workspaces with existing content.
 *
 * @group workspace
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class ExistingContentTest extends BrowserTestBase {
  use WorkspaceTestUtilities;
  use BlockCreationTrait {
    placeBlock as drupalPlaceBlock;
  }

  public static $modules = ['node', 'user', 'block'];

  protected $profile = 'standard';

  /**
   * Tests workspaces with existing nodes.
   */
  public function testExistingContent() {
    $this->createNodeType('Test', 'test');

    $this->drupalLogin($this->rootUser);

    $published_node = $this->createNodeThroughUI('Published node', 'test', TRUE);
    $unpublished_node = $this->createNodeThroughUI('Unpublished node', 'test', FALSE);

    $this->drupalLogout();

    \Drupal::service('module_installer')->install(['workspace']);
    $this->rebuildContainer();
    $this->drupalLogin($this->rootUser);
    $live = Workspace::load('live');
    $stage = Workspace::load('stage');
    $this->setupWorkspaceSwitcherBlock();

    $this->assertNull($published_node->workspace->target_id);
    $this->assertNull($unpublished_node->workspace->target_id);

    $this->switchToWorkspace($stage);
    $session = $this->assertSession();
    $session->pageTextContains('Published node');
    $session->pageTextNotContains('Unpublished node');

    $this->drupalGet('/node/' . $published_node->id() . '/edit');
    $session->pageTextContains('Published node');
    $this->drupalPostForm(NULL, [
      'title[0][value]' => 'Published Stage node'
    ], t('Save and keep published'));
    $session->pageTextContains('Published stage node');

    $this->drupalGet('/node/' . $unpublished_node->id() . '/edit');
    $session->pageTextContains('Unpublished node');
    $this->drupalPostForm(NULL, [
      'title[0][value]' => 'Published Unpublished Stage node'
    ], t('Save and publish'));
    $session->pageTextContains('Published Unpublished stage node');

    $this->drupalGet('<front>');
    $session->pageTextContains('Published Stage node');
    $session->pageTextContains('Published Unpublished stage node');

    $this->switchToWorkspace($live);
    $session->pageTextContains('Published node');
    $session->pageTextNotContains('Unpublished node');

    /** @var \Drupal\workspace\Replication\ReplicationManager $replicator */
    $replicator = \Drupal::service('workspace.replication_manager');
    /** @var \Drupal\workspace\UpstreamManager $upstream */
    $upstream = \Drupal::service('workspace.upstream_manager');
    $replicator->replicate(
      $upstream->createInstance('workspace:' . $stage->id()),
      $upstream->createInstance('workspace:' . $live->id())
    );

    $this->drupalGet('<front>');
    $session->pageTextContains('Published Stage node');
    $session->pageTextContains('Published Unpublished stage node');

    $this->switchToWorkspace($stage);
    $session->pageTextContains('Published Stage node');
    $session->pageTextContains('Published Unpublished stage node');

  }

}
