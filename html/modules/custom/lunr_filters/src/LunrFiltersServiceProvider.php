<?php

namespace Drupal\lunr_filters;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;
use Drupal\lunr_filters\EventSubscriber\TomePathSubscriber;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers services in the container.
 */
class LunrFiltersServiceProvider implements ServiceProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    $modules = $container->getParameter('container.modules');
    if (isset($modules['tome_static'])) {
      $container->register('lunr_filters.tome_path_subscriber', TomePathSubscriber::class)
        ->addTag('event_subscriber')
        ->addArgument(new Reference('entity_type.manager'))
        ->addArgument(new Reference('file_system'));
    }
  }

}
