<?php

namespace Drupal\Tests\workspace\Functional;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\workspace\Entity\Workspace;

/**
 * Tests access on workspaces.
 *
 * @group workspace
 */
class WorkspaceAccessTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'user',
    'system',
    'workspace',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('system', ['sequences']);

    $this->installEntitySchema('workspace');
    $this->installEntitySchema('user');

    // User 1.
    $this->createUser();
  }

  /**
   * Test cases for testWorkspaceAccess().
   *
   * @return array
   *   An array of operations and permissions to test with.
   */
  public function operationCases() {
    return [
      ['create', 'create workspace'],
      ['create', 'view workspace oak'],
      ['create', 'view any workspace'],
      ['create', 'view own workspace'],
      ['create', 'edit workspace oak'],
      ['create', 'edit any workspace'],
      ['create', 'edit own workspace'],
      ['view', 'create workspace'],
      ['view', 'view workspace oak'],
      ['view', 'view any workspace'],
      ['view', 'view own workspace'],
      ['view', 'edit workspace oak'],
      ['view', 'edit any workspace'],
      ['view', 'edit own workspace'],
      ['update', 'create workspace'],
      ['update', 'view workspace oak'],
      ['update', 'view any workspace'],
      ['update', 'view own workspace'],
      ['update', 'edit workspace oak'],
      ['update', 'edit any workspace'],
      ['update', 'edit own workspace'],
    ];
  }

  /**
   * Verifies all workspace roles have the correct access for the operation.
   *
   * @param string $operation
   *   The operation to test with.
   * @param string $permission
   *   The permission to test with.
   *
   * @dataProvider operationCases
   */
  public function testWorkspaceAccess($operation, $permission) {
    $user = $this->createUser();
    $this->setCurrentUser($user);
    $workspace = Workspace::create(['id' => 'oak']);
    $workspace->save();
    $role = $this->createRole([$permission]);
    $user->addRole($role);
    $operation_permission = $operation === 'update' ? 'edit' : $operation;
    if (strpos($permission, $operation_permission) === FALSE || $operation === 'delete') {
      $this->assertFalse($workspace->access($operation, $user));
    }
    else {
      $this->assertTrue($workspace->access($operation, $user));
    }
  }

}
