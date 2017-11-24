<?php

namespace Drupal\workspace;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\workspace\Entity\ContentWorkspace;
use Drupal\workspace\Entity\ContentWorkspaceInterface;
use Drupal\workspace\Entity\WorkspaceInterface;
use Drupal\workspace\Negotiator\WorkspaceNegotiatorInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides the workspace manager.
 */
class WorkspaceManager implements WorkspaceManagerInterface {

  use StringTranslationTrait;

  /**
   * The default workspace ID.
   */
  const DEFAULT_WORKSPACE = 'live';

  /**
   * An array of entity type IDs that can not belong to a workspace.
   *
   * By default, only entity types which are revisionable and publishable can
   * belong to a workspace.
   *
   * @var string[]
   */
  protected $blacklist = [
    'content_workspace',
    'workspace'
  ];

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * A list of workspace negotiators.
   *
   * @var \Drupal\workspace\Negotiator\WorkspaceNegotiatorInterface[]
   */
  protected $negotiators = [];

  /**
   * A list of workspace negotiators sorted by their priority.
   *
   * @var \Drupal\workspace\Negotiator\WorkspaceNegotiatorInterface[]
   */
  protected $sortedNegotiators;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a new WorkspaceManager.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   */
  public function __construct(RequestStack $request_stack, EntityTypeManagerInterface $entity_type_manager, AccountProxyInterface $current_user, LoggerInterface $logger = NULL) {
    $this->requestStack = $request_stack;
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
    $this->logger = $logger ?: new NullLogger();
  }

  /**
   * {@inheritdoc}
   */
  public function entityTypeCanBelongToWorkspaces(EntityTypeInterface $entity_type) {
    if (!in_array($entity_type->id(), $this->blacklist, TRUE)
      && is_a($entity_type->getClass(), EntityPublishedInterface::class, TRUE)
      && $entity_type->isRevisionable()) {
      return TRUE;
    }
    $this->blacklist[] = $entity_type->id();
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedEntityTypes() {
    $entity_types = [];
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      if ($this->entityTypeCanBelongToWorkspaces($entity_type)) {
        $entity_types[$entity_type_id] = $entity_type;
      }
    }
    return $entity_types;
  }

  /**
   * {@inheritdoc}
   */
  public function addNegotiator(WorkspaceNegotiatorInterface $negotiator, $priority) {
    $this->negotiators[$priority][] = $negotiator;
    $this->sortedNegotiators = NULL;
  }

  /**
   * {@inheritdoc}
   *
   * @todo {@link https://www.drupal.org/node/2600382 Access check.}
   */
  public function getActiveWorkspace($object = FALSE) {
    $request = $this->requestStack->getCurrentRequest();
    foreach ($this->getSortedNegotiators() as $negotiator) {
      if ($negotiator->applies($request)) {
        if ($workspace_id = $negotiator->getWorkspaceId($request)) {
          if ($object) {
            return $this->entityTypeManager->getStorage('workspace')->load($workspace_id);
          }
          else {
            return $workspace_id;
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setActiveWorkspace(WorkspaceInterface $workspace) {
    // If the current user doesn't have access to view the workspace, they
    // shouldn't be allowed to switch to it.
    // @todo Could this be handled better?
    if (!$workspace->access('view') && ($workspace->id() != static::DEFAULT_WORKSPACE)) {
      $this->logger->error('Denied access to view workspace {workspace}', ['workspace' => $workspace->label()]);
      throw new WorkspaceAccessException('The user does not have permission to view that workspace.');
    }

    // Set the workspace on the proper negotiator.
    $request = $this->requestStack->getCurrentRequest();
    foreach ($this->getSortedNegotiators() as $negotiator) {
      if ($negotiator->applies($request)) {
        $negotiator->setWorkspace($workspace);
        break;
      }
    }

    $supported_entity_types = $this->getSupportedEntityTypes();
    foreach ($supported_entity_types as $supported_entity_type) {
      $this->entityTypeManager->getStorage($supported_entity_type->id())->resetCache();
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function updateOrCreateFromEntity(EntityInterface $entity) {
    // Only run if the entity type can belong to workspaces.
    if (!$this->entityTypeCanBelongToWorkspaces($entity->getEntityType())) {
      return;
    }

    // If the entity is not new there should already be a ContentWorkspace
    // entity for it.
    if (!$entity->isNew()) {
      $content_workspaces = $this->entityTypeManager
        ->getStorage('content_workspace')
        ->loadByProperties([
          'content_entity_type_id' => $entity->getEntityTypeId(),
          'content_entity_id' => $entity->id(),
        ]);

      /** @var \Drupal\workspace\Entity\ContentWorkspaceInterface $content_workspace */
      $content_workspace = reset($content_workspaces);
    }

    // If there was a ContentWorkspace entry create a new revision, otherwise
    // create a new entity with the type and ID.
    if (!empty($content_workspace) && $content_workspace instanceof ContentWorkspaceInterface) {
      $content_workspace->setNewRevision(TRUE);
    }
    else {
      $content_workspace = ContentWorkspace::create([
        'content_entity_type_id' => $entity->getEntityTypeId(),
        'content_entity_id' => $entity->id()
      ]);
    }

    // Add the revision ID and the workspace ID.
    $content_workspace->set('content_entity_revision_id', $entity->getRevisionId());
    $content_workspace->set('workspace', $this->getActiveWorkspace());

    // Save without updating the content entity.
    ContentWorkspace::updateOrCreateFromEntity($content_workspace);
  }

  /**
   * @return \Drupal\workspace\Negotiator\WorkspaceNegotiatorInterface[]
   */
  protected function getSortedNegotiators() {
    if (!isset($this->sortedNegotiators)) {
      // Sort the negotiators according to priority.
      krsort($this->negotiators);
      // Merge nested negotiators from $this->negotiators into
      // $this->sortedNegotiators.
      $this->sortedNegotiators = [];
      foreach ($this->negotiators as $builders) {
        $this->sortedNegotiators = array_merge($this->sortedNegotiators, $builders);
      }
    }
    return $this->sortedNegotiators;
  }

}
