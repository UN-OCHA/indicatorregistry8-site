<?php

namespace Drupal\ir_tome\Config;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Example configuration override.
 */
class IrConfigOverrider implements ConfigFactoryOverrideInterface {

  /**
   * The configuration storage service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new ConfigurableLanguageManager object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory service.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = [];

    if (in_array('views.view.indicators', $names)) {
      $config = $this->configFactory->getEditable('views.view.indicators')->getRawData();
      $config['display']['indicators_table']['display_options']['path'] = 'indicators-disabled';
      $overrides['views.view.indicators'] = $config;
      return $overrides;
    }

    if (in_array('system.performance', $names)) {
      $config = $this->configFactory->getEditable('system.performance')->getRawData();
      $config['css']['preprocess'] = TRUE;
      $config['js']['preprocess'] = TRUE;
      $overrides['system.performance'] = $config;
      return $overrides;
    }

    if (in_array('system.logging', $names)) {
      $config = $this->configFactory->getEditable('system.logging')->getRawData();
      $config['error_level'] = 'hide';
      $overrides['system.logging'] = $config;
      return $overrides;
    }

    if (in_array('common_design_subtheme.settings', $names)) {
      $config = $this->configFactory->getEditable('common_design_subtheme.settings')->getRawData();
      $config['common_design_node_title'] = [
        'full' => 0,
      ];
      $overrides['common_design_subtheme.settings'] = $config;
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
