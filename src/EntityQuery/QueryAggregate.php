<?php

namespace Drupal\workspace\EntityQuery;

use Drupal\Core\Entity\Query\Sql\QueryAggregate as BaseQueryAggregate;

/**
 * Alters aggregate entity queries to use a workspace revision if possible.
 */
class QueryAggregate extends BaseQueryAggregate {

  use QueryTrait {
    prepare as traitPrepare;
  }

  /**
   * {@inheritdoc}
   */
  public function prepare() {
    $this->traitPrepare();

    // Throw away the ID fields.
    $this->sqlFields = [];
    return $this;
  }

}
