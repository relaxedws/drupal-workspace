<?php


namespace Drupal\workspace;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\multiversion\Entity\WorkspaceInterface;
use Drupal\multiversion\Workspace\WorkspaceManagerInterface;


/**
 * Service for hooks and utilities related to Toolbar integration.
 */
class Toolbar {
  use StringTranslationTrait;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\multiversion\Workspace\WorkspaceManagerInterface
   */
  protected $workspaceManager;

  /**
   * Constructs a new Toolbar.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\multiversion\Workspace\WorkspaceManagerInterface $workspace_manager
   *   The workspace manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, WorkspaceManagerInterface $workspace_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->workspaceManager = $workspace_manager;
  }

  /**
   * Hook bridge;  Responds to hook_toolbar().
   *
   * @see hook_toolbar().
   */
  public function toolbar() {
    $items = [];

    $active = $this->workspaceManager->getActiveWorkspace();

    $items['workspace_switcher'] = [
      // Include the toolbar_tab_wrapper to style the link like a toolbar tab.
      // Exclude the theme wrapper if custom styling is desired.
      '#type' => 'toolbar_item',
      '#weight' => 125,
      '#wrapper_attributes' => [
        'class' => ['workspace-toolbar-tab'],
      ],
      '#attached' => [
        'library' => [
          'workspace/drupal.workspace.toolbar',
        ],
      ],
    ];

    $items['workspace_switcher']['tab'] = [
      '#type' => 'link',
      '#title' => $this->t('Workspaces (@active)', ['@active' => $active->label()]),
      // @todo This should likely be something else, but not sure what.
      '#url' => Url::fromRoute('<front>'),
      '#attributes' => [
        'title' => $this->t('Switch workspaces'),
        'class' => ['toolbar-icon', 'toolbar-icon-workspace'],
      ],
    ];

    $items['workspace_switcher']['tray'] = [
      '#heading' => $this->t('Switch to workspace'),
      'workspace_links' => [
        '#pre_render' => ['workspace.toolbar:preRenderWorkspaceLinks'],
        '#cache' => [
          'contexts' => $this->entityTypeManager->getDefinition('workspace')->getListCacheContexts(),
          'tags' => $this->entityTypeManager->getDefinition('workspace')->getListCacheTags(),
        ],
        '#theme' => 'links__toolbar_workspaces',
        // This will be filled in during pre-render.
        '#links' => [],
        '#attributes' => [
          'class' => ['toolbar-menu'],
        ],
      ],
    ];

    return $items;
  }

  /**
   * Prerender callback; Adds the workspace links to the render array.
   *
   * @param array $element
   *
   * @return array
   *   The modified $element.
   */
  public function preRenderWorkspaceLinks(array $element) {
    $element['#links'] = $this->workspaceLinks();

    return $element;
  }

  /**
   * Builds a render array of links to switch to all workspaces.
   *
   * Note: This is an expensive call so should only be made from within a
   * pre-render callback, so it gets cached.
   *
   * @return array
   *   A render array of links to switch to each workspace.
   */
  protected function workspaceLinks() {
    $links = [];

    $links['add'] = [
      'title' => $this->t('Create new workspace'),
      'url' => Url::fromRoute('entity.workspace.add'),
    ];

    /** @var WorkspaceInterface $workspace */
    foreach ($this->allWorkspaces() as $workspace) {
      $links['workspace_' . $workspace->getMachineName()] = [
        'title' => $workspace->label(),
        'url' => Url::fromRoute('<front>', ['workspace' => $workspace->id()]),
        'attributes' => [
          'title' => t('Switch to %workspace workspace', ['%workspace' => $workspace->label()])
        ],
      ];
    }

    return $links;
  }

  /**
   * Returns a list of all defined workspaces.
   *
   * Note: This assumes that the total number of workspaces on the site is
   * very small.  If it's actually large this method will have memory issues.
   *
   * @return WorkspaceInterface[]
   */
  protected function allWorkspaces() {
    return $this->entityTypeManager->getStorage('workspace')->loadMultiple();
  }
}