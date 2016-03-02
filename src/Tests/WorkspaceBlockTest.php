<?php

/**
 * @file
 * Contains \Drupal\workspace\Tests\WorkspaceBlockTest.
 */

namespace Drupal\workspace\Tests;

use Drupal\Core\Url;
use Drupal\multiversion\Entity\Workspace;
use Drupal\node\Entity\Node;
use Drupal\simpletest\WebTestBase;

/**
 * Tests workspace block functionality.
 *
 * @group workspace
 */
class WorkspaceBlockTest extends WebTestBase {

  protected $strictConfigSchema = FALSE;

  /**
   * The profile to install as a basis for testing.
   *
   * @var string
   */
  protected $profile = 'standard';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['workspace'];

  /**
   * A web user.
   */
  protected $webUser;

  protected function setUp() {
    parent::setUp();
    $this->webUser = $this->drupalCreateUser([
      'administer blocks',
      'create article content',
      'access administration pages',
      'access content',
    ]);
    $this->drupalLogin($this->webUser);
    $this->drupalPlaceBlock('local_tasks_block');
  }

  public function testBlock() {
    $this->drupalPlaceBlock('workspace_switcher_block', ['region' => 'sidebar_first', 'label' => 'Workspace switcher']);
    $this->drupalGet('');

    // Confirm that the block is being displayed.
    $this->assertText('Workspace switcher', t('Block successfully being displayed on the page.'));
    $front = Url::fromRoute('<front>')->toString(TRUE)->getGeneratedUrl();
    $this->assertRaw('href="'. $front .'"', 'The id of the default workspace was displayed in the Workspace switcher block as a link.');
    $machine_name = $this->randomMachineName();
    $entity = Workspace::create(['machine_name' => $machine_name, 'label' => $machine_name, 'type' => 'basic']);
    $entity->save();
    $id = $entity->id();
    $node = Node::create(['type' => 'article', 'title' => 'Test article']);
    $node->save();
    $nid = $node->id();
    $this->drupalGet('');
    $this->assertText('Test article', 'The title of the test article was displayed on the front page.');
    $this->drupalGet("node/$nid");
    $this->assertText('Test article');
    $this->drupalGet('<front>');
    $url = $front . "?workspace=$id";
    $this->assertRaw('href="'. $url .'"', 'The id of the new workspace was displayed in the Workspace switcher block as a link.');
    $this->drupalGet("/node/$nid", ['query' => ['workspace' => $id]]);
    $this->assertText('Page not found');
    $this->drupalGet('<front>', ['query' =>['workspace' => 'default']]);
    $this->assertText('Test article', 'The title of the test article was displayed on the front page.');
    $this->drupalGet('<front>', ['query' => ['workspace' => $id]]);
    $this->drupalGet('/node/add/article');
    $this->assertText('Create Article');
    $this->drupalGet('<front>', ['query' => ['workspace' => $id]]);
    $this->assertNoText('Test article', 'The title of the test article was not displayed on the front page after switching the workspace.');
    $entity->delete();
    $this->drupalGet('');
    $this->assertNoText($machine_name, 'The name of the deleted workspace was not displayed in the Workspace switcher block.');
  }

}