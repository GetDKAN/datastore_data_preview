<?php

namespace Drupal\Tests\datastore_data_preview\Unit\Service;

use Drupal\datastore_data_preview\Service\ResourceIdResolver;
use Drupal\metastore\MetastoreService;
use Drupal\node\NodeInterface;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Drupal\datastore_data_preview\Service\ResourceIdResolver
 * @group datastore_data_preview
 * @group unit
 */
class ResourceIdResolverTest extends TestCase {

  /**
   * @covers ::resolve
   */
  public function testResolvePassthroughWithDoubleUnderscore(): void {
    $metastore = $this->createMock(MetastoreService::class);
    $metastore->expects($this->never())->method('get');

    $resolver = new ResourceIdResolver($metastore);
    $this->assertSame('id__v1', $resolver->resolve('id__v1'));
  }

  /**
   * @covers ::resolve
   */
  public function testResolveUuidViaMetastore(): void {
    $metadata = (object) [
      'data' => (object) [
        '%Ref:downloadURL' => [
          (object) [
            'data' => (object) [
              'identifier' => 'abc123',
              'version' => 'ver1',
            ],
          ],
        ],
      ],
    ];

    $metastore = $this->createMock(MetastoreService::class);
    $metastore->method('get')
      ->with('distribution', 'some-uuid')
      ->willReturn(json_encode($metadata));

    $resolver = new ResourceIdResolver($metastore);
    $this->assertSame('abc123__ver1', $resolver->resolve('some-uuid'));
  }

  /**
   * @covers ::resolve
   */
  public function testResolveReturnsNullOnMissingRef(): void {
    $metadata = (object) [
      'data' => (object) [
        'title' => 'Some distribution',
      ],
    ];

    $metastore = $this->createMock(MetastoreService::class);
    $metastore->method('get')
      ->willReturn(json_encode($metadata));

    $resolver = new ResourceIdResolver($metastore);
    $this->assertNull($resolver->resolve('some-uuid'));
  }

  /**
   * @covers ::resolve
   */
  public function testResolveReturnsNullOnException(): void {
    $metastore = $this->createMock(MetastoreService::class);
    $metastore->method('get')
      ->willThrowException(new \Exception('Not found'));

    $resolver = new ResourceIdResolver($metastore);
    $this->assertNull($resolver->resolve('some-uuid'));
  }

  /**
   * @covers ::resolveFromNode
   */
  public function testResolveFromNodeValid(): void {
    $metadata = (object) [
      'data' => (object) [
        '%Ref:downloadURL' => [
          (object) [
            'data' => (object) [
              'identifier' => 'node-id',
              'version' => 'v2',
            ],
          ],
        ],
      ],
    ];

    $node = $this->createNodeMock(json_encode($metadata));
    $resolver = new ResourceIdResolver($this->createMock(MetastoreService::class));
    $this->assertSame('node-id__v2', $resolver->resolveFromNode($node));
  }

  /**
   * @covers ::resolveFromNode
   */
  public function testResolveFromNodeEmptyJson(): void {
    $node = $this->createNodeMock('');
    $resolver = new ResourceIdResolver($this->createMock(MetastoreService::class));
    $this->assertNull($resolver->resolveFromNode($node));
  }

  /**
   * @covers ::resolveFromNode
   */
  public function testResolveFromNodeInvalidJson(): void {
    $node = $this->createNodeMock('{not valid json');
    $resolver = new ResourceIdResolver($this->createMock(MetastoreService::class));
    $this->assertNull($resolver->resolveFromNode($node));
  }

  /**
   * @covers ::resolveFromNode
   */
  public function testResolveFromNodeMissingRef(): void {
    $metadata = (object) [
      'data' => (object) [
        'title' => 'No download ref',
      ],
    ];

    $node = $this->createNodeMock(json_encode($metadata));
    $resolver = new ResourceIdResolver($this->createMock(MetastoreService::class));
    $this->assertNull($resolver->resolveFromNode($node));
  }

  /**
   * Create a mock node with field_json_metadata.
   */
  protected function createNodeMock(string $jsonValue): NodeInterface {
    $fieldItem = new \stdClass();
    $fieldItem->value = $jsonValue;

    $node = $this->createMock(NodeInterface::class);
    $node->method('get')
      ->with('field_json_metadata')
      ->willReturn($fieldItem);

    return $node;
  }

}
