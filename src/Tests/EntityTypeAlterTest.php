<?php

/**
 * @file
 * Contains \Drupal\workspace\Tests\EntityTypeAlterTest.
 */

namespace Drupal\workspace\Tests;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\simpletest\WebTestBase;

/**
 * Test the workspace entity.
 *
 * @group workspace
 */
class EntityTypeAlterTest extends WebTestBase {

  protected $strictConfigSchema = FALSE;

  public static $modules = ['workspace'];

  public function testEntityTypeAlter() {
    $entity_types = \Drupal::service('entity_type.manager')->getDefinitions();

    /** @var EntityTypeInterface $workspace_type */
    $workspace_type = $entity_types['workspace_type'];
    $this->assertTrue($workspace_type->getHandlerClass('list_builder'));
    $this->assertTrue($workspace_type->getHandlerClass('route_provider', 'html'));
    $this->assertTrue($workspace_type->getHandlerClass('form', 'default'));
    $this->assertTrue($workspace_type->getHandlerClass('form', 'add'));
    $this->assertTrue($workspace_type->getHandlerClass('form', 'edit'));
    $this->assertTrue($workspace_type->getHandlerClass('form', 'delete'));
    $this->assertTrue($workspace_type->getLinkTemplate('collection'));
    $this->assertTrue($workspace_type->getLinkTemplate('edit-form'));
    $this->assertTrue($workspace_type->getLinkTemplate('delete-form'));

    /** @var EntityTypeInterface $workspace */
    $workspace = $entity_types['workspace'];
    $this->assertTrue($workspace->getHandlerClass('list_builder'));
    $this->assertTrue($workspace->getHandlerClass('route_provider', 'html'));
    $this->assertTrue($workspace->getHandlerClass('form', 'default'));
    $this->assertTrue($workspace->getHandlerClass('form', 'add'));
    $this->assertTrue($workspace->getHandlerClass('form', 'edit'));
    $this->assertTrue($workspace->getLinkTemplate('collection'));
    $this->assertTrue($workspace->getLinkTemplate('edit-form'));
    $this->assertTrue($workspace->getLinkTemplate('activate-form'));
    $this->assertTrue($workspace->get('field_ui_base_route'));

    foreach ($entity_types as $entity_type) {
      if (\Drupal::service('multiversion.manager')->isSupportedEntityType($entity_type)) {
        if ($entity_type->hasViewBuilderClass() && $entity_type->hasLinkTemplate('canonical')) {
          $this->assertTrue($entity_type->getLinkTemplate('version-tree'));
          $this->assertTrue($entity_type->getLinkTemplate('version-history'));
          $this->assertTrue($entity_type->getLinkTemplate('revision'));
        }
      }
    }
  }
}