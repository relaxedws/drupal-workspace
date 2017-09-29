<?php

namespace Drupal\workspace\Index;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Interface SequenceIndexInterface
 */
interface SequenceIndexInterface {

  /**
   * @param $id
   *
   * @return $this
   */
  public function useWorkspace($id);

  /**
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *
   * @return $this
   */
  public function add(ContentEntityInterface $entity);

  /**
   * @param float $start
   * @param float $stop
   * @param bool $inclusive
   *
   * @return array
   */
  public function getRange($start, $stop = NULL, $inclusive = TRUE);

  /**
   * @return float
   */
  public function getLastSequenceId();

}
