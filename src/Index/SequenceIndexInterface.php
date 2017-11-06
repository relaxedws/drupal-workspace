<?php

namespace Drupal\workspace\Index;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface for the sequence index.
 *
 * Every time an entity is created or updated in a workspace, a new entry is
 * created in the sequence index. This provides a sequential list of changes
 * needed when replicating content changes between two workspaces.
 */
interface SequenceIndexInterface {

  /**
   * Sets the workspace to use
   *
   * @param string $id
   *   A workspace ID.
   *
   * @return $this
   */
  public function useWorkspace($id);

  /**
   * Adds an entity to the sequence index.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   A content entity type.
   *
   * @return $this
   */
  public function add(ContentEntityInterface $entity);

  /**
   * Get multiple items within a range of keys.
   *
   * @param int $start
   *   The lower sorted set score limit of the range.
   * @param int $stop
   *   The upper sorted set score limit of the range.
   *
   * @return array
   *   An array of items within the given range.
   */
  public function getRange($start, $stop = NULL);

  /**
   * Get the last sequence ID from the sorted set collection.
   *
   * @return int
   *   The highest key in the collection.
   */
  public function getLastSequenceId();

}
