<?php

namespace Drupal\Tests\workspace\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\workspace\Entity\Workspace;

/**
 * Tests permission controls on workspaces.
 *
 * @group workspace
 *
 * @runTestsInSeparateProcesses
 *
 * @preserveGlobalState disabled
 */
class WorkspaceIndividualPermissionsTest extends BrowserTestBase {

  use WorkspaceTestUtilities;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['workspace'];

  /**
   * Verifies that a user can create and edit only their own workspace.
   */
  public function testEditIndividualWorkspace() {
    $permissions = [
      'access administration pages',
      'administer site configuration',
      'create workspace',
      'edit own workspace',
      'view own workspace',
    ];

    $editor1 = $this->drupalCreateUser($permissions);

    // Login as a limited-access user and create a workspace.
    $this->drupalLogin($editor1);

    $this->createWorkspaceThroughUi('Bears', 'bears');
    $bears = Workspace::load('bears');

    // Now login as a different user with permission to edit that workspace,
    // specifically.
    $editor2 = $this->drupalCreateUser(array_merge($permissions, ['edit workspace ' . $bears->id()]));

    $this->drupalLogin($editor2);
    $session = $this->getSession();

    $this->drupalGet("/admin/config/workflow/workspace/{$bears->id()}/edit");
    $this->assertEquals(200, $session->getStatusCode());
  }

  /**
   * Verifies that a user can view a specific workspace.
   */
  public function testViewIndividualWorkspace() {
    $permissions = [
      'access administration pages',
      'administer site configuration',
      'create workspace',
      'edit own workspace',
    ];

    $editor1 = $this->drupalCreateUser($permissions);

    // Login as a limited-access user and create a workspace.
    $this->drupalLogin($editor1);

    $this->createWorkspaceThroughUi('Bears', 'bears');
    $bears = Workspace::load('bears');

    // Now login as a different user and create a workspace.
    $editor2 = $this->drupalCreateUser(array_merge($permissions, ['view workspace ' . $bears->id()]));

    $this->drupalLogin($editor2);
    $session = $this->getSession();

    $this->createWorkspaceThroughUi('Packers', 'packers');

    $packers = Workspace::load('packers');

    // Load the activate form for the Bears workspace. It should work, because
    // the user has the permission specific to that workspace.
    $this->drupalGet("admin/config/workflow/workspace/{$bears->id()}/activate");
    $this->assertEquals(200, $session->getStatusCode());

    // But editor 1 cannot view the Packers workspace.
    $this->drupalLogin($editor1);
    $this->drupalGet("admin/config/workflow/workspace/{$packers->id()}/activate");
    $this->assertEquals(403, $session->getStatusCode());
  }

}
