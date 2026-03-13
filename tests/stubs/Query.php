<?php

namespace Drupal\common\Storage;

/**
 * Stub for unit tests. Mirrors the public API of DKAN's Query class.
 */
class Query {

  public array $conditions = [];
  public bool $count = FALSE;
  public ?int $limit = NULL;
  public int $offset = 0;
  public array $sorts = [];
  public array $properties = [];
  public string $collection = '';

  public function limitTo(int $number_of_items): void {
    $this->limit = $number_of_items;
  }

  public function offsetBy(int $offset): void {
    $this->offset = $offset;
  }

  public function sortByAscending(string $property): void {
    $this->sorts[] = (object) ['property' => $property, 'order' => 'asc'];
  }

  public function sortByDescending(string $property): void {
    $this->sorts[] = (object) ['property' => $property, 'order' => 'desc'];
  }

  public function count(): void {
    $this->count = TRUE;
    $this->limit = NULL;
  }

  public function filterByProperty(string $property): void {
    $this->properties[] = $property;
  }

}
