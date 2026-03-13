<?php

namespace Drupal\Tests\datastore_data_preview\Unit\DataSource;

use Drupal\datastore_data_preview\DataSource\ArrayDataSource;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Drupal\datastore_data_preview\DataSource\ArrayDataSource
 * @group datastore_data_preview
 * @group unit
 */
class ArrayDataSourceTest extends TestCase {

  /**
   * Sample rows used across tests.
   */
  protected function getSampleRows(): array {
    return [
      ['name' => 'Alice', 'age' => 30, 'city' => 'Denver'],
      ['name' => 'Bob', 'age' => 25, 'city' => 'Austin'],
      ['name' => 'Carol', 'age' => 35, 'city' => 'Denver'],
      ['name' => 'Dave', 'age' => 28, 'city' => 'Boston'],
      ['name' => 'Eve', 'age' => 22, 'city' => 'Austin'],
    ];
  }

  /**
   * @covers ::fetchData
   */
  public function testBasicFetchReturnsAllRows(): void {
    $source = new ArrayDataSource($this->getSampleRows());
    $result = $source->fetchData('ignored', 0, 0, NULL, 'asc');

    $this->assertCount(5, $result->rows);
    $this->assertEquals(5, $result->totalCount);
    $this->assertEquals('Alice', $result->rows[0]->name);
  }

  /**
   * @covers ::fetchData
   */
  public function testLimitAndOffset(): void {
    $source = new ArrayDataSource($this->getSampleRows());
    $result = $source->fetchData('x', 2, 1, NULL, 'asc');

    $this->assertCount(2, $result->rows);
    $this->assertEquals(5, $result->totalCount);
    $this->assertEquals('Bob', $result->rows[0]->name);
    $this->assertEquals('Carol', $result->rows[1]->name);
  }

  /**
   * @covers ::fetchData
   */
  public function testOffsetOnly(): void {
    $source = new ArrayDataSource($this->getSampleRows());
    $result = $source->fetchData('x', 0, 3, NULL, 'asc');

    $this->assertCount(2, $result->rows);
    $this->assertEquals('Dave', $result->rows[0]->name);
  }

  /**
   * @covers ::fetchData
   */
  public function testSortAsc(): void {
    $source = new ArrayDataSource($this->getSampleRows());
    $result = $source->fetchData('x', 0, 0, 'age', 'asc');

    $this->assertEquals('Eve', $result->rows[0]->name);
    $this->assertEquals('Carol', $result->rows[4]->name);
  }

  /**
   * @covers ::fetchData
   */
  public function testSortDesc(): void {
    $source = new ArrayDataSource($this->getSampleRows());
    $result = $source->fetchData('x', 0, 0, 'age', 'desc');

    $this->assertEquals('Carol', $result->rows[0]->name);
    $this->assertEquals('Eve', $result->rows[4]->name);
  }

  /**
   * @covers ::fetchData
   */
  public function testSortNullFieldPreservesOrder(): void {
    $source = new ArrayDataSource($this->getSampleRows());
    $result = $source->fetchData('x', 0, 0, NULL, 'asc');

    $this->assertEquals('Alice', $result->rows[0]->name);
    $this->assertEquals('Eve', $result->rows[4]->name);
  }

  /**
   * Provides condition arrays with expected result counts and names.
   */
  public static function conditionProvider(): array {
    return [
      'equals' => [
        [['property' => 'city', 'value' => 'Denver']],
        2,
        ['Alice', 'Carol'],
      ],
      'greater than' => [
        [['property' => 'age', 'value' => 28, 'operator' => '>']],
        2,
        ['Alice', 'Carol'],
      ],
      'not equal (<>)' => [
        [['property' => 'city', 'value' => 'Denver', 'operator' => '<>']],
        3,
        NULL,
      ],
      'not equal (!=)' => [
        [['property' => 'city', 'value' => 'Denver', 'operator' => '!=']],
        3,
        NULL,
      ],
      'IN' => [
        [['property' => 'city', 'value' => ['Austin', 'Boston'], 'operator' => 'IN']],
        3,
        NULL,
      ],
      'NOT IN' => [
        [['property' => 'city', 'value' => ['Austin', 'Boston'], 'operator' => 'NOT IN']],
        2,
        NULL,
      ],
      'LIKE with wildcards' => [
        [['property' => 'name', 'value' => '%a%', 'operator' => 'LIKE']],
        3,
        ['Alice', 'Carol', 'Dave'],
      ],
      'LIKE without wildcards' => [
        [['property' => 'name', 'value' => 'Alice', 'operator' => 'LIKE']],
        1,
        ['Alice'],
      ],
      'less than or equal' => [
        [['property' => 'age', 'value' => 25, 'operator' => '<=']],
        2,
        ['Bob', 'Eve'],
      ],
      'multiple conditions (AND)' => [
        [
          ['property' => 'city', 'value' => 'Austin'],
          ['property' => 'age', 'value' => 25, 'operator' => '<='],
        ],
        2,
        NULL,
      ],
      'IN with empty array' => [
        [['property' => 'city', 'value' => [], 'operator' => 'IN']],
        0,
        NULL,
      ],
      'NOT IN with empty array' => [
        [['property' => 'city', 'value' => [], 'operator' => 'NOT IN']],
        5,
        NULL,
      ],
      'missing property (NULL = NULL)' => [
        [['property' => 'nonexistent', 'value' => NULL, 'operator' => '=']],
        5,
        NULL,
      ],
      'unknown operator defaults to TRUE' => [
        [['property' => 'age', 'value' => 25, 'operator' => 'BETWEEN']],
        5,
        NULL,
      ],
    ];
  }

  /**
   * @covers ::fetchData
   * @covers ::evaluateCondition
   */
  #[DataProvider('conditionProvider')]
  public function testCondition(array $conditions, int $expectedCount, ?array $expectedNames): void {
    $source = new ArrayDataSource($this->getSampleRows());
    $result = $source->fetchData('x', 0, 0, NULL, 'asc', $conditions);

    $this->assertCount($expectedCount, $result->rows);
    $this->assertEquals($expectedCount, $result->totalCount);

    if ($expectedNames !== NULL) {
      $names = array_map(fn($r) => $r->name, $result->rows);
      foreach ($expectedNames as $name) {
        $this->assertContains($name, $names);
      }
    }
  }

  /**
   * @covers ::fetchData
   */
  public function testPropertyFiltering(): void {
    $source = new ArrayDataSource($this->getSampleRows());
    $result = $source->fetchData('x', 0, 0, NULL, 'asc', [], ['name', 'age']);

    $this->assertCount(5, $result->rows);
    $first = $result->rows[0];
    $this->assertTrue(property_exists($first, 'name'));
    $this->assertTrue(property_exists($first, 'age'));
    $this->assertFalse(property_exists($first, 'city'));
  }

  /**
   * @covers ::inferSchema
   */
  public function testSchemaAutoInference(): void {
    $source = new ArrayDataSource($this->getSampleRows());
    $result = $source->fetchData('x', 1, 0, NULL, 'asc');

    $schema = $result->schema;
    $this->assertArrayHasKey('fields', $schema);
    $this->assertEquals('text', $schema['fields']['name']['type']);
    $this->assertEquals('number', $schema['fields']['age']['type']);
    $this->assertEquals('text', $schema['fields']['city']['type']);
    $this->assertEquals('Name', $schema['fields']['name']['description']);
    $this->assertEquals('Age', $schema['fields']['age']['description']);
  }

  /**
   * @covers ::__construct
   */
  public function testExplicitSchemaPassthrough(): void {
    $schema = [
      'fields' => [
        'name' => ['type' => 'text', 'description' => 'Full Name'],
        'age' => ['type' => 'integer', 'description' => 'Years Old'],
        'city' => ['type' => 'text', 'description' => 'Hometown'],
      ],
    ];
    $source = new ArrayDataSource($this->getSampleRows(), $schema);
    $result = $source->fetchData('x', 1, 0, NULL, 'asc');

    $this->assertEquals('Full Name', $result->schema['fields']['name']['description']);
    $this->assertEquals('integer', $result->schema['fields']['age']['type']);
  }

  /**
   * @covers ::fetchData
   */
  public function testEmptyRows(): void {
    $source = new ArrayDataSource([]);
    $result = $source->fetchData('x', 0, 0, NULL, 'asc');

    $this->assertCount(0, $result->rows);
    $this->assertEquals(0, $result->totalCount);
    $this->assertEquals(['fields' => []], $result->schema);
  }

  /**
   * @covers ::fetchData
   */
  public function testTotalCountReflectsFilteredNotPaged(): void {
    $source = new ArrayDataSource($this->getSampleRows());
    // Filter to Denver (2 rows), then page to 1 row.
    $result = $source->fetchData('x', 1, 0, NULL, 'asc', [
      ['property' => 'city', 'value' => 'Denver'],
    ]);

    $this->assertCount(1, $result->rows);
    $this->assertEquals(2, $result->totalCount);
  }

  /**
   * Provides rows and expected schema field definitions.
   */
  public static function schemaInferenceProvider(): array {
    return [
      'underscore to description' => [
        [['first_name' => 'Alice', 'total_score' => 95]],
        [
          'first_name' => ['type' => 'text', 'description' => 'First name'],
          'total_score' => ['type' => 'number', 'description' => 'Total score'],
        ],
      ],
      'null value infers text' => [
        [['name' => NULL, 'age' => 30]],
        [
          'name' => ['type' => 'text', 'description' => 'Name'],
          'age' => ['type' => 'number', 'description' => 'Age'],
        ],
      ],
      'mixed types uses first row' => [
        [['value' => 'hello'], ['value' => 42]],
        [
          'value' => ['type' => 'text', 'description' => 'Value'],
        ],
      ],
    ];
  }

  /**
   * @covers ::inferSchema
   */
  #[DataProvider('schemaInferenceProvider')]
  public function testSchemaInference(array $rows, array $expectedFields): void {
    $source = new ArrayDataSource($rows);
    $result = $source->fetchData('x', 0, 0, NULL, 'asc');

    foreach ($expectedFields as $field => $expected) {
      $this->assertEquals($expected['type'], $result->schema['fields'][$field]['type'], "Type mismatch for '$field'");
      $this->assertEquals($expected['description'], $result->schema['fields'][$field]['description'], "Description mismatch for '$field'");
    }
  }

  /**
   * @covers ::fetchData
   */
  public function testSortOnNonExistentField(): void {
    $source = new ArrayDataSource($this->getSampleRows());
    $result = $source->fetchData('x', 0, 0, 'nonexistent', 'asc');

    // Order should be unchanged (original insertion order).
    $this->assertEquals('Alice', $result->rows[0]->name);
    $this->assertEquals('Eve', $result->rows[4]->name);
  }

  /**
   * @covers ::fetchData
   */
  public function testOffsetBeyondTotal(): void {
    $source = new ArrayDataSource($this->getSampleRows());
    $result = $source->fetchData('x', 10, 100, NULL, 'asc');

    $this->assertCount(0, $result->rows);
    $this->assertEquals(5, $result->totalCount);
  }

  /**
   * @covers ::fetchData
   */
  public function testPropertyFilteringNonExistentColumn(): void {
    $source = new ArrayDataSource($this->getSampleRows());
    $result = $source->fetchData('x', 0, 0, NULL, 'asc', [], ['name', 'nonexistent']);

    $first = $result->rows[0];
    $this->assertTrue(property_exists($first, 'name'));
    // 'nonexistent' is not in schema, so it's excluded by array_intersect.
    $this->assertFalse(property_exists($first, 'nonexistent'));
    $this->assertFalse(property_exists($first, 'age'));
    $this->assertFalse(property_exists($first, 'city'));
  }

}
