<?php

namespace Drupal\common\Storage;

/**
 * Stub for unit tests. Declares methods used by DatabaseDataSource.
 */
interface DatabaseTableInterface {

  public function query(Query $query);

  public function getSchema(): array;

}
