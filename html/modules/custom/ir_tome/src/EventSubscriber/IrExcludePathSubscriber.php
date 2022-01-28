<?php

namespace Drupal\ir_tome\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\tome_static\Event\CollectPathsEvent;
use Drupal\tome_static\Event\TomeStaticEvents;

/**
 * Excludes a paths from static generation.
 *
 * @internal
 */
class IrExcludePathSubscriber implements EventSubscriberInterface {

  /**
   * A hard-coded list of paths to always exclude.
   *
   * @var array
   */
  const EXCLUDED_PATHS = [
    '/extracts-disabled',
    '/extracts-header',
  ];

  /**
   * Reacts to a collect paths event.
   *
   * @param \Drupal\tome_static\Event\CollectPathsEvent $event
   *   The collect paths event.
   */
  public function collectPaths(CollectPathsEvent $event) {
    $excluded_paths = self::EXCLUDED_PATHS;
    $paths = $event->getPaths(TRUE);
    foreach ($paths as $path => $metadata) {
      if (in_array($path, $excluded_paths, TRUE) || (isset($metadata['original_path']) && in_array($metadata['original_path'], $excluded_paths, TRUE))) {
        unset($paths[$path]);
      }
    }
    $event->replacePaths($paths);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[TomeStaticEvents::COLLECT_PATHS][] = ['collectPaths', -2];
    return $events;
  }

}
