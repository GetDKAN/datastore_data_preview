<?php

namespace Drupal\common\Storage;

/**
 * Stub for unit tests. Declares methods used by DatabaseDataSource.
 */
interface DatabaseTableInterface {

  /**
   * Execute a query against the storage.
   */
  public function query(Query $query);

  /**
   * Get the table schema.
   */
  public function getSchema(): array;

}
