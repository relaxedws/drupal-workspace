<?php

namespace Drupal\workspace\Plugin\Field\FieldWidget;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\workspace\RepositoryHandlerInterface;
use Drupal\workspace\WorkspaceManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'workspace_upstream' widget.
 *
 * @FieldWidget(
 *   id = "workspace_upstream",
 *   label = @Translation("Workspace upstream widget"),
 *   description = @Translation("A Workspace upstream plugin field widget."),
 *   field_types = {
 *     "string"
 *   }
 * )
 */
class WorkspaceUpstreamWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * The upstream plugin manager.
   *
   * @var \Drupal\workspace\RepositoryHandlerManager
   */
  protected $upstreamPluginManager;

  /**
   * Constructs a new ModerationStateWidget object.
   *
   * @param string $plugin_id
   *   Plugin id.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   Field definition.
   * @param array $settings
   *   Field settings.
   * @param array $third_party_settings
   *   Third party settings.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $upstream_plugin_manager
   *   The upstream plugin manager.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, PluginManagerInterface $upstream_plugin_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->upstreamPluginManager = $upstream_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('plugin.manager.workspace.repository_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    // Gather the list of upstreams grouped by category.
    $upstream_options = [];
    foreach ($this->upstreamPluginManager->getGroupedDefinitions() as $category => $upstream_plugin_definitions) {
      foreach ($upstream_plugin_definitions as $plugin_id => $plugin_definition) {
        // Do not include the local workspace itself as an option.
        if ($plugin_id !== 'local_workspace' . PluginBase::DERIVATIVE_SEPARATOR . $items->getEntity()->id()) {
          $upstream_options[$category][$plugin_id] = $plugin_definition['label'];
        }
      }
    }

    // The default ('Live') workspace can not have another local workspace as an
    // upstream value, so we need to remove all options from the
    // 'Local workspace' category.
    if ($items->getEntity()->id() === WorkspaceManager::DEFAULT_WORKSPACE) {
      unset($upstream_options['Local workspace']);
    }

    // In case we don't have any options to display, just provide the existing
    // value. This can happen for example when editing the 'Live' workspace and
    // the only available repository handler plugin is 'local_workspace'.
    if (!$upstream_options) {
      $element += [
        '#type' => 'value',
        '#value' => isset($items[$delta]->value) ? $items[$delta]->value : RepositoryHandlerInterface::EMPTY_VALUE,
      ];
    }
    else {
      $element += [
        '#type' => 'select',
        '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : 'local_workspace' . PluginBase::DERIVATIVE_SEPARATOR . WorkspaceManager::DEFAULT_WORKSPACE,
        '#options' => $upstream_options,
      ];

      // Simplify the form and use radio buttons if we only have one category to
      // display.
      if (count($upstream_options) == 1) {
        $element['#type'] = 'radios';
        $element['#options'] = reset($upstream_options);
      }
    }

    return ['value' => $element];
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    // We only want to have this widget available for the 'upstream' base field
    // from the Workspace entity type.
    return $field_definition->getTargetEntityTypeId() === 'workspace';
  }

}
