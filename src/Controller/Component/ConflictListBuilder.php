<?php

namespace Drupal\workspace\Controller\Component;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\multiversion\Entity\Index\EntityIndexInterface;
use Drupal\multiversion\Entity\Workspace;
use Drupal\multiversion\Workspace\ConflictTrackerInterface;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A list builder for entity revision conflicts.
 *
 * @todo Is "Component" the correct place to put this class?
 * @todo Should we implement EntityListBuilderInterface? We don't need the getStorage method though.
 */
class ConflictListBuilder {
  use StringTranslationTrait;
  use DependencySerializationTrait;

  /**
   * Inject services needed to build the list.
   *
   * @param \Drupal\multiversion\Workspace\ConflictTrackerInterface $conflict_tracker
   *   The conflict tracking service.
   * @param \Drupal\multiversion\Entity\Index\EntityIndexInterface $entity_index
   *   The entity index service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   A date formatter to show pretty dates.
   */
  public function __construct(
    ConflictTrackerInterface $conflict_tracker,
    EntityIndexInterface $entity_index,
    EntityTypeManagerInterface $entity_type_manager,
    DateFormatterInterface $date_formatter
  ) {
    $this->conflictTracker = $conflict_tracker;
    $this->entityIndex = $entity_index;
    $this->entityTypeManager = $entity_type_manager;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * Instantiates a new instance of this list builder.
   *
   * Because we don't have a single entity type, we cannot use
   * EntityHandlerInterface::createInstance.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container this object should use.
   *
   * @return static
   *   A new instance of this list builder.
   */
  public static function createInstance(ContainerInterface $container) {
    return new self(
      $container->get('workspace.conflict_tracker'),
      $container->get('multiversion.entity_index.rev'),
      $container->get('entity_type.manager'),
      $container->get('date.formatter')
    );
  }

  /**
   * Build the table header.
   *
   * @return array
   *   The header array used by table render arrays.
   */
  public function buildHeader() {
    // Enable language column and filter if multiple languages are added.
    $header = array(
      'title' => $this->t('Title'),
      'type' => array(
        'data' => $this->t('Content type'),
        'class' => array(RESPONSIVE_PRIORITY_MEDIUM),
      ),
      'author' => array(
        'data' => $this->t('Author'),
        'class' => array(RESPONSIVE_PRIORITY_LOW),
      ),
      'status' => $this->t('Status'),
      'changed' => array(
        'data' => $this->t('Updated'),
        'class' => array(RESPONSIVE_PRIORITY_LOW),
      ),
    );
    if (\Drupal::languageManager()->isMultilingual()) {
      $header['language_name'] = array(
        'data' => $this->t('Language'),
        'class' => array(RESPONSIVE_PRIORITY_LOW),
      );
    }

    $header['operations'] = $this->t('Operations');

    return $header;
  }

  /**
   * Build a row for the given entity.
   *
   * @todo Should we use "mark" to show new vs old conflicts?
   * @todo Handle translations.
   * @todo Handle 'Status' column for non-publishable entities
   * @todo handle the case where the entity URI is on a different workspace
   *   than active (currently goes to a 404)
   * @todo Is there a better way to show entity type than "bundle"? @see node_get_type_label
   * @todo Properly handle "author" column for non-owned entities.
   *
   * @see \Drupal\node\NodeListBuilder::buildRow
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to build the render array row for.
   *
   * @return array
   *   A row render array used by a table render array.
   */
  public function buildRow(EntityInterface $entity) {
    // $langcode = $entity->language()->getId();
    $uri = $entity->urlInfo();
    $options = $uri->getOptions();
    // $options += ($langcode != LanguageInterface::LANGCODE_NOT_SPECIFIED && isset($languages[$langcode]) ? ['language' => $languages[$langcode]] : []);
    $uri->setOptions($options);
    $row['title']['data'] = [
      '#type' => 'link',
      '#title' => $entity->label(),
      '#url' => $uri,
    ];
    $row['type'] = $entity->bundle();
    if ($entity instanceof Node) {
      $row['author']['data'] = [
        '#theme' => 'username',
        '#account' => $entity->getOwner(),
      ];
    }
    else {
      $row['author']['data'] = [
        '#markup' => $this->t('None'),
      ];
    }
    if ($entity instanceof Node) {
      $row['status'] = $entity->isPublished() ? $this->t('published') : $this->t('not published');
    }
    else {
      $row['status'] = 'published';
    }
    $row['changed'] = $this->dateFormatter->format($entity->getChangedTime(), 'short');
    $language_manager = \Drupal::languageManager();
    if ($language_manager->isMultilingual()) {
      // $row['language_name'] = $language_manager->getLanguageName($langcode);
    }
    $row['operations']['data'] = $this->buildOperations($entity);
    return $row;
  }

  /**
   * Build the operations for an entity uesd in the Operations column.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to build the render array of operations for.
   *
   * @return array
   *   A row render array used by a table render array.
   */
  public function buildOperations(EntityInterface $entity) {
    $build = array(
      '#type' => 'operations',
      // @todo Get the operation links for a given entity.
      '#links' => [],
    );

    return $build;
  }

  /**
   * Get the title of the entire table.
   *
   * @return string
   *   The title to use for the whole table.
   */
  public function getTitle() {
    return '';
  }

  /**
   * Load the entities needed for the table.
   *
   * @see workspace_preprocess_workspace_rev
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   An array of entities.
   */
  public function load($workspace_id) {
    /* \Drupal\multiversion\Entity\Workspace $workspace */
    $workspace = Workspace::load($workspace_id);

    $conflicts = $this->conflictTracker
      ->useWorkspace($workspace)
      ->getAll();

    $entity_revisions = [];
    foreach ($conflicts as $uuid => $conflict) {
      // @todo figure out why this is an array and what to do if there is more than 1
      // @todo what happens when the conflict value is not "available"? what does this mean?
      $rev = reset(array_keys($conflict));
      $rev_info = $this->entityIndex
        ->useWorkspace($workspace->id())
        ->get("$uuid:$rev");

      if (!empty($rev_info['revision_id'])) {
        $entity_revisions[] = $this->entityTypeManager
          ->getStorage($rev_info['entity_type_id'])
          ->useWorkspace($workspace->id())
          ->loadRevision($rev_info['revision_id']);
      }
    }

    return $entity_revisions;
  }

  /**
   * Build the render array to display on the page.
   *
   * @param string $workspace_id
   *   The workspace ID to build the conflict list for.
   *
   * @return array
   *   A table render array to show on the page.
   */
  public function buildList($workspace_id) {
    $build['table'] = array(
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#title' => $this->getTitle(),
      '#rows' => array(),
      '#empty' => 'There are no conflicts.',
      /*
      @todo cache this beast
      '#cache' => [
        'contexts' => $this->entityType->getListCacheContexts(),
        'tags' => $this->entityType->getListCacheTags(),
      ],
      */
    );
    $entities = $this->load($workspace_id);
    foreach ($entities as $entity) {
      $row = $this->buildRow($entity);

      if ($row = $this->buildRow($entity)) {
        $build['table']['#rows'][$entity->id()] = $row;
      }
    }

    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $build['pager'] = array(
        '#type' => 'pager',
      );
    }

    return $build;
  }

}
