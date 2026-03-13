<?php

namespace Drupal\common\Storage;

/**
 * Stub for unit tests. Mirrors the public API of DKAN's Query class.
 */
class Query {

  /**
   * Query conditions.
   *
   * @var array
   */
  public array $conditions = [];

  /**
   * Whether this is a count query.
   *
   * @var bool
   */
  public bool $count = FALSE;

  /**
   * Row limit.
   *
   * @var int|null
   */
  public ?int $limit = NULL;

  /**
   * Row offset.
   *
   * @var int
   */
  public int $offset = 0;

  /**
   * Sort definitions.
   *
   * @var array
   */
  public array $sorts = [];

  /**
   * Property filters.
   *
   * @var array
   */
  public array $properties = [];

  /**
   * Collection name.
   *
   * @var string
   */
  public string $collection = '';

  /**
   * Limit the number of results.
   */
  public function limitTo(int $number_of_items): void {
    $this->limit = $number_of_items;
  }

  /**
   * Offset the results.
   */
  public function offsetBy(int $offset): void {
    $this->offset = $offset;
  }

  /**
   * Sort by a property in ascending order.
   */
  public function sortByAscending(string $property): void {
    $this->sorts[] = (object) [
      'property' => $property,
      'order' => 'asc',
    ];
  }

  /**
   * Sort by a property in descending order.
   */
  public function sortByDescending(string $property): void {
    $this->sorts[] = (object) [
      'property' => $property,
      'order' => 'desc',
    ];
  }

  /**
   * Switch to a count query.
   */
  public function count(): void {
    $this->count = TRUE;
    $this->limit = NULL;
  }

  /**
   * Filter by a property.
   */
  public function filterByProperty(string $property): void {
    $this->properties[] = $property;
  }

}
