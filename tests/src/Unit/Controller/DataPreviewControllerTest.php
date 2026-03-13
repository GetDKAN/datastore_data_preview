<?php

namespace Drupal\Tests\datastore_data_preview\Unit\Controller;

use Drupal\datastore_data_preview\Controller\DataPreviewController;
use Drupal\datastore_data_preview\Service\ResourceIdResolver;
use Drupal\metastore\MetastoreService;
use Drupal\Tests\datastore_data_preview\Traits\StringTranslationStubTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @coversDefaultClass \Drupal\datastore_data_preview\Controller\DataPreviewController
 * @group datastore_data_preview
 * @group unit
 */
class DataPreviewControllerTest extends TestCase {

  use StringTranslationStubTrait;

  /**
   * @covers ::preview
   */
  public function testPreviewReturnsRenderArray(): void {
    $controller = $this->createController('abc__v1');
    $result = $controller->preview('some-uuid');

    $this->assertEquals('data_preview', $result['#type']);
    $this->assertEquals('abc__v1', $result['#resource_id']);
  }

  /**
   * @covers ::preview
   */
  public function testPreviewThrowsNotFound(): void {
    $controller = $this->createController(NULL);

    $this->expectException(NotFoundHttpException::class);
    $controller->preview('bad-id');
  }

  /**
   * @covers ::title
   */
  public function testTitleWithDistributionTitle(): void {
    $metadata = (object) [
      'data' => (object) [
        'title' => 'My Dataset',
      ],
    ];

    $metastore = $this->createMock(MetastoreService::class);
    $metastore->method('get')
      ->with('distribution', 'some-uuid')
      ->willReturn(json_encode($metadata));

    $controller = $this->createController('abc__v1', $metastore);
    $title = $controller->title('some-uuid');

    $this->assertEquals('Data Preview: My Dataset', (string) $title);
  }

  /**
   * @covers ::title
   */
  public function testTitleFallbackWhenNoTitle(): void {
    $metadata = (object) [
      'data' => (object) [
        'description' => 'No title here',
      ],
    ];

    $metastore = $this->createMock(MetastoreService::class);
    $metastore->method('get')
      ->willReturn(json_encode($metadata));

    $controller = $this->createController('abc__v1', $metastore);
    $title = $controller->title('some-uuid');

    $this->assertEquals('Data Preview', (string) $title);
  }

  /**
   * @covers ::title
   */
  public function testTitleFallbackOnException(): void {
    $metastore = $this->createMock(MetastoreService::class);
    $metastore->method('get')
      ->willThrowException(new \Exception('Not found'));

    $controller = $this->createController('abc__v1', $metastore);
    $title = $controller->title('some-uuid');

    $this->assertEquals('Data Preview', (string) $title);
  }

  /**
   * @covers ::title
   */
  public function testTitleWithIdentifierVersion(): void {
    $metastore = $this->createMock(MetastoreService::class);
    $metastore->expects($this->never())->method('get');

    $controller = $this->createController('id__v1', $metastore);
    $title = $controller->title('id__v1');

    $this->assertEquals('Data Preview', (string) $title);
  }

  /**
   * @covers ::title
   */
  public function testTitleWhenUnresolvable(): void {
    $controller = $this->createController(NULL);
    $title = $controller->title('bad-id');

    $this->assertEquals('Data Preview', (string) $title);
  }

  /**
   * Create a DataPreviewController with mocked dependencies.
   */
  protected function createController(
    ?string $resolvedId,
    ?MetastoreService $metastore = NULL,
  ): DataPreviewController {
    $resolver = $this->createMock(ResourceIdResolver::class);
    $resolver->method('resolve')->willReturn($resolvedId);

    $metastore = $metastore ?? $this->createMock(MetastoreService::class);

    $controller = new DataPreviewController($resolver, $metastore);
    $controller->setStringTranslation($this->getStringTranslationStub());

    return $controller;
  }

}
