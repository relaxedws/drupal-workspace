<?php

namespace Drupal\workspace;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\workspace\Entity\ContentWorkspace;
use Drupal\workspace\Entity\ContentWorkspaceInterface;
use Drupal\workspace\Entity\WorkspaceInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides the workspace manager.
 */
class WorkspaceManager implements WorkspaceManagerInterface {
  use StringTranslationTrait;

  /**
   * @var string[]
   */
  protected $blacklist = [
    'content_workspace',
    'workspace'
  ];

  /**
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * @var array
   */
  protected $negotiators = [];

  /**
   * @var array
   */
  protected $sortedNegotiators;

  /**
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   * @param \Psr\Log\LoggerInterface $logger
   */
  public function __construct(RequestStack $request_stack, EntityManagerInterface $entity_manager, AccountProxyInterface $current_user, LoggerInterface $logger = NULL) {
    $this->requestStack = $request_stack;
    $this->entityManager = $entity_manager;
    $this->currentUser = $current_user;
    $this->logger = $logger ?: new NullLogger();
  }

  /**
   * {@inheritdoc}
   */
  public function entityCanBelongToWorkspaces(EntityInterface $entity) {
    return $this->entityTypeCanBelongToWorkspaces($entity->getEntityType());
  }

  /**
   * {@inheritdoc}
   */
  public function entityTypeCanBelongToWorkspaces(EntityTypeInterface $entity_type) {
    if (is_a($entity_type->getClass(), EntityPublishedInterface::class, TRUE)
      && $entity_type->isRevisionable()
      && !in_array($entity_type->id(), $this->blacklist)) {
      return TRUE;
    }
    return FALSE;
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
   */
  public function load($workspace_id) {
    return $this->entityManager->getStorage('workspace')->load($workspace_id);
  }

  /**
   * {@inheritdoc}
   */
  public function loadMultiple(array $workspace_ids = NULL) {
    return $this->entityManager->getStorage('workspace')->loadMultiple($workspace_ids);
  }

  /**
   * {@inheritdoc}
   */
  public function loadByMachineName($machine_name) {
    $workspaces = $this->entityManager->getStorage('workspace')->loadByProperties(['machine_name' => $machine_name]);
    return current($workspaces);
  }

  /**
   * {@inheritdoc}
   *
   * @todo {@link https://www.drupal.org/node/2600382 Access check.}
   */
  public function getActiveWorkspace() {
    $request = $this->requestStack->getCurrentRequest();
    foreach ($this->getSortedNegotiators() as $negotiator) {
      if ($negotiator->applies($request)) {
        if ($workspace_id = $negotiator->getWorkspaceId($request)) {
          if ($workspace = $this->load($workspace_id)) {
            return $workspace;
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setActiveWorkspace(WorkspaceInterface $workspace) {
    $default_workspace_id = \Drupal::getContainer()->getParameter('workspace.default');
    // If the current user doesn't have access to view the workspace, they
    // shouldn't be allowed to switch to it.
    // @todo Could this be handled better?
    if (!$workspace->access('view') && ($workspace->id() != $default_workspace_id)) {
      $this->logger->error('Denied access to view workspace {workspace}', ['workspace' => $workspace->label()]);
      throw new WorkspaceAccessException('The user does not have permission to view that workspace.');
    }

    // Set the workspace on the proper negotiator.
    $request = $this->requestStack->getCurrentRequest();
    foreach ($this->getSortedNegotiators() as $negotiator) {
      if ($negotiator->applies($request)) {
        $negotiator->persist($workspace);
        break;
      }
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function updateOrCreateFromEntity(EntityInterface $entity) {
    if (!$this->entityCanBelongToWorkspaces($entity)) {
      return;
    }

    // If the entity is not new there should be a ContentWorkspace entry for it.
    if (!$entity->isNew()) {
      $content_workspaces = $this->entityManager
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

    // Add the revision ID, workspace, and publishing status.
    $content_workspace->set('content_entity_revision_id', $entity->getRevisionId());
    $content_workspace->set('workspace', $this->getActiveWorkspace());
    $entity->initial_published ? $content_workspace->setPublished() : $content_workspace->setUnpublished();
    ContentWorkspace::updateOrCreateFromEntity($content_workspace);
  }

  /**
   * @return \Drupal\workspace\WorkspaceNegotiatorInterface[]
   */
  protected function getSortedNegotiators() {
    if (!isset($this->sortedNegotiators)) {
      // Sort the negotiators according to priority.
      krsort($this->negotiators);
      // Merge nested negotiators from $this->negotiators into
      // $this->sortedNegotiators.
      $this->sortedNegotiators = array();
      foreach ($this->negotiators as $builders) {
        $this->sortedNegotiators = array_merge($this->sortedNegotiators, $builders);
      }
    }
    return $this->sortedNegotiators;
  }

}
