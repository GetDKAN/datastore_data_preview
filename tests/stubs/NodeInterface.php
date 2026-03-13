<?php

namespace Drupal\node;

/**
 * Stub for unit tests. Declares only methods used by ResourceIdResolver.
 */
interface NodeInterface {

  /**
   * Get a field value.
   */
  public function get(string $field_name);

}
