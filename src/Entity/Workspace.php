<?php

namespace Drupal\workspace\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\user\UserInterface;
use Drupal\workspace\RepositoryHandlerInterface;

/**
 * The workspace entity class.
 *
 * @ContentEntityType(
 *   id = "workspace",
 *   label = @Translation("Workspace"),
 *   label_collection = @Translation("Workspaces"),
 *   label_singular = @Translation("workspace"),
 *   label_plural = @Translation("workspaces"),
 *   label_count = @PluralTranslation(
 *     singular = "@count workspace",
 *     plural = "@count workspaces"
 *   ),
 *   handlers = {
 *     "storage" = "Drupal\Core\Entity\Sql\SqlContentEntityStorage",
 *     "list_builder" = "\Drupal\workspace\WorkspaceListBuilder",
 *     "access" = "Drupal\workspace\WorkspaceAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "\Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "form" = {
 *       "default" = "\Drupal\workspace\Form\WorkspaceForm",
 *       "add" = "\Drupal\workspace\Form\WorkspaceForm",
 *       "edit" = "\Drupal\workspace\Form\WorkspaceForm",
 *       "activate" = "\Drupal\workspace\Form\WorkspaceActivateForm",
 *       "deploy" = "\Drupal\workspace\Form\WorkspaceDeployForm",
 *     },
 *   },
 *   admin_permission = "administer workspaces",
 *   base_table = "workspace",
 *   revision_table = "workspace_revision",
 *   data_table = "workspace_field_data",
 *   revision_data_table = "workspace_field_revision",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "revision_id",
 *     "uuid" = "uuid",
 *     "label" = "label",
 *     "uid" = "uid",
 *   },
 *   links = {
 *     "add-form" = "/admin/config/workflow/workspace/add",
 *     "edit-form" = "/admin/config/workflow/workspace/{workspace}/edit",
 *     "activate-form" = "/admin/config/workflow/workspace/{workspace}/activate",
 *     "deploy-form" = "/admin/config/workflow/workspace/{workspace}/deploy",
 *     "collection" = "/admin/config/workflow/workspace",
 *   },
 * )
 */
class Workspace extends ContentEntityBase implements WorkspaceInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['id'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Workaspace ID'))
      ->setDescription(new TranslatableMarkup('The workspace ID.'))
      ->setSetting('max_length', 128)
      ->setRequired(TRUE)
      ->addPropertyConstraints('value', ['Regex' => ['pattern' => '/^[\da-z_$()+-\/]*$/']]);

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Workaspace name'))
      ->setDescription(new TranslatableMarkup('The workspace name.'))
      ->setRevisionable(TRUE)
      ->setSetting('max_length', 128)
      ->setRequired(TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Owner'))
      ->setDescription(new TranslatableMarkup('The workspace owner.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback('Drupal\workspace\Entity\Workspace::getCurrentUserId')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(new TranslatableMarkup('Changed'))
      ->setDescription(new TranslatableMarkup('The time that the workspace was last edited.'))
      ->setRevisionable(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(new TranslatableMarkup('Created'))
      ->setDescription(new TranslatableMarkup('The UNIX timestamp of when the workspace has been created.'));

    $fields['upstream'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Target workspace'))
      ->setDescription(new TranslatableMarkup('The workspace to push to and pull from.'))
      ->setRevisionable(TRUE)
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'workspace_upstream',
        'weight' => 4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->addPropertyConstraints('value', [
        'Upstream' => [],
      ]);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getRepositoryHandlerPlugin() {
    if (($upstream = $this->upstream->value) && $upstream !== RepositoryHandlerInterface::EMPTY_VALUE) {
      return \Drupal::service('plugin.manager.workspace.repository_handler')->createInstance($upstream);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getLocalRepositoryHandlerPlugin() {
    return \Drupal::service('plugin.manager.workspace.repository_handler')->createInstance('local_workspace:' . $this->id());
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($created) {
    $this->set('created', (int) $created);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStartTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * Default value callback for 'uid' base field definition.
   *
   * @see ::baseFieldDefinitions()
   *
   * @return array
   *   An array of default values.
   */
  public static function getCurrentUserId() {
    return [\Drupal::currentUser()->id()];
  }

}
