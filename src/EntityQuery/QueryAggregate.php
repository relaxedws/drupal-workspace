<?php

namespace Drupal\workspace\EntityQuery;

use Drupal\Core\Entity\Query\Sql\QueryAggregate as BaseQueryAggregate;

/**
 * Alters aggregate entity queries to use a workspace revision if possible.
 */
class QueryAggregate extends BaseQueryAggregate {

  use QueryTrait;

}
