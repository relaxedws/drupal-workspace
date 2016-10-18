<?php

namespace Drupal\workspace\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Workspace type entity.
 *
 * @ConfigEntityType(
 *   id = "workspace_type",
 *   label = @Translation("Workspace type"),
 *   handlers = {
 *     "list_builder" = "\Drupal\workspace\WorkspaceTypeListBuilder",
 *     "route_provider" = {
 *       "html" = "\Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "form" = {
 *       "default" = "\Drupal\workspace\Entity\Form\WorkspaceTypeForm",
 *       "add" = "\Drupal\workspace\Entity\Form\WorkspaceTypeForm",
 *       "edit" = "\Drupal\workspace\Entity\Form\WorkspaceTypeForm",
 *       "delete" = "\Drupal\workspace\Entity\Form\WorkspaceTypeDeleteForm",
 *     },
 *   },
 *   config_prefix = "type",
 *   bundle_of = "workspace",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *    links = {
 *     "edit-form" = "/admin/structure/workspace/types/{workspace_type}/edit",
 *     "delete-form" = "/admin/structure/workspace/types/{workspace_type}/delete",
 *     "collection" = "/admin/structure/workspace/types",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *   }
 * )
 */
class WorkspaceType extends ConfigEntityBundleBase implements WorkspaceTypeInterface {
  /**
   * The Workspace type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Workspace type label.
   *
   * @var string
   */
  protected $label;

}
