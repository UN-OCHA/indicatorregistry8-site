<?php

namespace Drupal\ir_tome\Config;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Example configuration override.
 */
class IrConfigOverrider implements ConfigFactoryOverrideInterface {

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = array();

    if (in_array('views.view.indicators', $names)) {
      // @codingStandardsIgnoreLine
      $config = \Drupal::configFactory()->getEditable('views.view.indicators')->getRawData();
      $config['display']['indicators_table']['display_options']['path'] = 'indicators-disabled';
      $overrides['views.view.indicators'] = $config;
      return $overrides;
    }

    if (in_array('system.performance', $names)) {
      // @codingStandardsIgnoreLine
      $config = \Drupal::configFactory()->getEditable('system.performance')->getRawData();
      $config['css']['preprocess'] = TRUE;
      $config['js']['preprocess'] = TRUE;
      $overrides['system.performance'] = $config;
      return $overrides;
    }

    if (in_array('system.logging', $names)) {
      // @codingStandardsIgnoreLine
      $config = \Drupal::configFactory()->getEditable('system.logging')->getRawData();
      $config['error_level'] = 'hide';
      $overrides['system.logging'] = $config;
      return $overrides;
    }

    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'IndicatorRegistryTomeConfigOverrider';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($name) {
    return new CacheableMetadata();
  }

  /**
   * {@inheritdoc}
   */
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION) {
    return NULL;
  }

}
