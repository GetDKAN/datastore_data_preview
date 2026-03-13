<?php

namespace Drupal\Tests\datastore_data_preview\Traits;

use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Provides a stub string translation service for unit tests.
 */
trait StringTranslationStubTrait {

  /**
   * Returns a stub translation service that returns the untranslated string.
   */
  protected function getStringTranslationStub(): TranslationInterface {
    $translation = $this->createMock(TranslationInterface::class);
    $translation->method('translateString')
      ->willReturnCallback(function ($wrapper) {
        return $wrapper->getUntranslatedString();
      });
    return $translation;
  }

}
