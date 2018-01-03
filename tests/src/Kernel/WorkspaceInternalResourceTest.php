<?php

namespace Drupal\Tests\workspace\Kernel;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\KernelTests\KernelTestBase;
use Drupal\rest\Entity\RestResourceConfig;
use Drupal\rest\RestResourceConfigInterface;

/**
 * Tests REST module with internal workspace entity types.
 *
 * @group workspace
 */
class WorkspaceInternalResourceTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['user', 'serialization', 'rest', 'workspace'];

  /**
   * Tests enabling content workspaces for REST throws an exception.
   *
   * @see workspace_rest_resource_alter()
   */
  public function testCreateContentWorkspaceResource() {
    $this->setExpectedException(PluginNotFoundException::class, 'The "entity:content_workspace" plugin does not exist.');
    RestResourceConfig::create([
      'id' => 'entity.content_workspace',
      'granularity' => RestResourceConfigInterface::RESOURCE_GRANULARITY,
      'configuration' => [
        'methods' => ['GET'],
        'formats' => ['json'],
        'authentication' => ['cookie'],
      ],
    ])
      ->enable()
      ->save();
  }

  /**
   * Tests enabling replication logs for REST throws an exception.
   *
   * @see workspace_rest_resource_alter()
   */
  public function testCreateReplicationLogResource() {
    $this->setExpectedException(PluginNotFoundException::class, 'The "entity:replication_log" plugin does not exist.');
    RestResourceConfig::create([
      'id' => 'entity.replication_log',
      'granularity' => RestResourceConfigInterface::RESOURCE_GRANULARITY,
      'configuration' => [
        'methods' => ['GET'],
        'formats' => ['json'],
        'authentication' => ['cookie'],
      ],
    ])
      ->enable()
      ->save();
  }

}