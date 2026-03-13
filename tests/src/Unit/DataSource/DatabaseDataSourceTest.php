<?php

namespace Drupal\Tests\datastore_data_preview\Unit\DataSource;

use Drupal\common\Storage\DatabaseTableInterface;
use Drupal\common\Storage\Query;
use Drupal\datastore\DatastoreService;
use Drupal\datastore_data_preview\DataSource\DatabaseDataSource;
use Drupal\datastore_data_preview\DataSource\DataSourceInterface;
use MockChain\Chain;
use MockChain\Options;
use MockChain\Sequence;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Drupal\datastore_data_preview\DataSource\DatabaseDataSource
 * @group datastore_data_preview
 * @group unit
 */
class DatabaseDataSourceTest extends TestCase {

  /**
   * @covers ::fetchData
   */
  public function testFetchDataReturnsResult(): void {
    $rows = [(object) ['name' => 'Alice'], (object) ['name' => 'Bob']];
    $schema = [
      'fields' => [
        'name' => ['type' => 'text', 'description' => 'Name'],
      ],
    ];

    $service = $this->buildChain($rows, 42, $schema, 'abc', 'v1');
    $source = new DatabaseDataSource($service);
    $result = $source->fetchData('abc__v1', 25, 0, NULL, 'asc');

    $this->assertSame($rows, $result->rows);
    $this->assertSame(42, $result->totalCount);
    $this->assertArrayHasKey('name', $result->schema['fields']);
  }

  /**
   * @covers ::fetchData
   */
  public function testRecordNumberExcludedFromSchema(): void {
    $schema = [
      'fields' => [
        DataSourceInterface::HIDDEN_FIELD => ['type' => 'serial'],
        'name' => ['type' => 'text'],
      ],
    ];

    $service = $this->buildChain([], 0, $schema, 'abc', 'v1');
    $source = new DatabaseDataSource($service);
    $result = $source->fetchData('abc__v1', 10, 0, NULL, 'asc');

    $this->assertArrayNotHasKey(DataSourceInterface::HIDDEN_FIELD, $result->schema['fields']);
    $this->assertArrayHasKey('name', $result->schema['fields']);
  }

  /**
   * @covers ::fetchData
   */
  public function testParseResourceIdWithVersion(): void {
    $service = $this->buildChain([], 0, ['fields' => []], 'abc', 'v1');
    $source = new DatabaseDataSource($service);
    $source->fetchData('abc__v1', 10, 0, NULL, 'asc');

    // Assertion is in the mock expectation: getStorage('abc', 'v1').
    $this->addToAssertionCount(1);
  }

  /**
   * @covers ::fetchData
   */
  public function testParseResourceIdWithoutVersion(): void {
    $service = $this->buildChain([], 0, ['fields' => []], 'abc', NULL);
    $source = new DatabaseDataSource($service);
    $source->fetchData('abc', 10, 0, NULL, 'asc');

    // Assertion is in the mock expectation: getStorage('abc', NULL).
    $this->addToAssertionCount(1);
  }

  /**
   * @covers ::fetchData
   */
  public function testBuildQueryWithLimitOffset(): void {
    $capturedQuery = NULL;
    $service = $this->buildCapturingChain($capturedQuery);

    $source = new DatabaseDataSource($service);
    $source->fetchData('id', 25, 10, NULL, 'asc');

    $this->assertSame(25, $capturedQuery->limit);
    $this->assertSame(10, $capturedQuery->offset);
  }

  /**
   * @covers ::fetchData
   */
  public function testBuildQueryWithSortAsc(): void {
    $capturedQuery = NULL;
    $service = $this->buildCapturingChain($capturedQuery);

    $source = new DatabaseDataSource($service);
    $source->fetchData('id', 10, 0, 'name', 'asc');

    $this->assertCount(1, $capturedQuery->sorts);
    $this->assertEquals('name', $capturedQuery->sorts[0]->property);
    $this->assertEquals('asc', $capturedQuery->sorts[0]->order);
  }

  /**
   * @covers ::fetchData
   */
  public function testBuildQueryWithSortDesc(): void {
    $capturedQuery = NULL;
    $service = $this->buildCapturingChain($capturedQuery);

    $source = new DatabaseDataSource($service);
    $source->fetchData('id', 10, 0, 'age', 'desc');

    $this->assertCount(1, $capturedQuery->sorts);
    $this->assertEquals('age', $capturedQuery->sorts[0]->property);
    $this->assertEquals('desc', $capturedQuery->sorts[0]->order);
  }

  /**
   * @covers ::fetchData
   */
  public function testBuildQueryWithConditions(): void {
    $capturedQuery = NULL;
    $service = $this->buildCapturingChain($capturedQuery);

    $conditions = [
      ['property' => 'state', 'value' => 'VA', 'operator' => '='],
      ['property' => 'age', 'value' => '30', 'operator' => '>'],
    ];

    $source = new DatabaseDataSource($service);
    $source->fetchData('id', 10, 0, NULL, 'asc', $conditions);

    $this->assertCount(2, $capturedQuery->conditions);
    $this->assertEquals('state', $capturedQuery->conditions[0]->property);
    $this->assertEquals('VA', $capturedQuery->conditions[0]->value);
    $this->assertEquals('=', $capturedQuery->conditions[0]->operator);
    $this->assertEquals('age', $capturedQuery->conditions[1]->property);
    $this->assertEquals('>', $capturedQuery->conditions[1]->operator);
  }

  /**
   * @covers ::fetchData
   */
  public function testBuildQueryWithProperties(): void {
    $capturedQuery = NULL;
    $service = $this->buildCapturingChain($capturedQuery);

    $source = new DatabaseDataSource($service);
    $source->fetchData('id', 10, 0, NULL, 'asc', [], ['name', 'age']);

    $this->assertEquals(['name', 'age'], $capturedQuery->properties);
  }

  /**
   * @covers ::fetchData
   */
  public function testBuildQueryNoLimitWhenZero(): void {
    $capturedQuery = NULL;
    $service = $this->buildCapturingChain($capturedQuery);

    $source = new DatabaseDataSource($service);
    $source->fetchData('id', 0, 0, NULL, 'asc');

    $this->assertNull($capturedQuery->limit);
    $this->assertSame(0, $capturedQuery->offset);
  }

  /**
   * Build a DatastoreService mock using Chain+Sequence.
   */
  protected function buildChain(
    array $rows,
    int $count,
    array $schema,
    string $identifier,
    ?string $version,
  ): DatastoreService {
    $storage = (new Chain($this))
      ->add(DatabaseTableInterface::class, 'query', (new Sequence())
        ->add($rows)
        ->add([(object) ['expression' => $count]])
      )
      ->addd('getSchema', $schema)
      ->getMock();

    $service = $this->createMock(DatastoreService::class);
    $service->expects($this->once())
      ->method('getStorage')
      ->with($identifier, $version)
      ->willReturn($storage);

    return $service;
  }

  /**
   * Build a DatastoreService mock that captures the first Query argument.
   */
  protected function buildCapturingChain(?Query &$capturedQuery): DatastoreService {
    // Build storage mock via Chain for getSchema, with manual query callback.
    $storage = (new Chain($this))
      ->add(DatabaseTableInterface::class, 'getSchema', ['fields' => []])
      ->getMock();

    $callIndex = 0;
    $storage->method('query')
      ->willReturnCallback(function (Query $query) use (&$capturedQuery, &$callIndex) {
        $callIndex++;
        if ($callIndex === 1) {
          $capturedQuery = $query;
          return [];
        }
        return [(object) ['expression' => 0]];
      });

    $service = $this->createMock(DatastoreService::class);
    $service->method('getStorage')->willReturn($storage);

    return $service;
  }

}
