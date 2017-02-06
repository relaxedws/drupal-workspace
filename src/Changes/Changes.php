<?php

namespace Drupal\workspace\Changes;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\multiversion\Entity\WorkspaceInterface;

/**
 * {@inheritdoc}
 */
class Changes implements ChangesInterface {
  use DependencySerializationTrait;

  /**
   * The workspace to generate the changeset from.
   *
   * @var string
   */
  protected $workspaceId;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Whether to include entities in the changeset.
   *
   * @var boolean
   */
  protected $includeDocs = FALSE;

  /**
   * The sequence ID to start including changes from. Result includes $lastSeq.
   *
   * @var int
   */
  protected $lastSeq = 0;

  /**
   * @param \Drupal\multiversion\Entity\WorkspaceInterface $workspace
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct(WorkspaceInterface $workspace, EntityTypeManagerInterface $entity_type_manager) {
    $this->workspaceId = $workspace->id();
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function includeDocs($include_docs) {
    $this->includeDocs = $include_docs;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function lastSeq($seq) {
    $this->lastSeq = $seq;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getNormal() {
    $sequences = $this->sequenceIndex
      ->useWorkspace($this->workspaceId)
      ->getRange($this->lastSeq, NULL);

    // Format the result array.
    $changes = array();
    foreach ($sequences as $sequence) {
      if (!empty($sequence['local']) || !empty($sequence['is_stub'])) {
        continue;
      }

      // Get the document.
      $revision = NULL;
      if ($this->includeDocs == TRUE || $filter !== NULL) {
        /** @var \Drupal\multiversion\Entity\Storage\ContentEntityStorageInterface $storage */
        $storage = $this->entityTypeManager->getStorage($sequence['entity_type_id']);
        $storage->useWorkspace($this->workspaceId);
        $revision = $storage->loadRevision($sequence['revision_id']);
      }

      // Filter the document.
      if ($filter !== NULL && !$filter->filter($revision)) {
        continue;
      }

      $uuid = $sequence['entity_uuid'];
      $changes[$uuid] = array(
        'changes' => array(
          array('rev' => $sequence['rev']),
        ),
        'id' => $uuid,
        'seq' => $sequence['seq'],
      );
      if ($sequence['deleted']) {
        $changes[$uuid]['deleted'] = TRUE;
      }

      // Include the document.
      if ($this->includeDocs == TRUE) {
        $changes[$uuid]['doc'] = $this->serializer->normalize($revision);
      }
    }

    // Now when we have rebuilt the result array we need to ensure that the
    // results array is still sorted on the sequence key, as in the index.
    $return = array_values($changes);
    usort($return, function($a, $b) {
      return $a['seq'] - $b['seq'];
    });

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function getLongpoll() {
    $no_change = TRUE;
    do {
      $change = $this->sequenceIndex
        ->useWorkspace($this->workspaceId)
        ->getRange($this->lastSeq, NULL);
      $no_change = empty($change) ? TRUE : FALSE;
    } while ($no_change);
    return $change;
  }

}