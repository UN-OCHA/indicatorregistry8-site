<?php

namespace Drupal\tome_static_azure;

/**
 * Provides an interface for the static azure syncer.
 */
interface StaticAzureSyncInterface {

  /**
   * The key user interfaces should use to get/set if they're running a build.
   */
  const STATE_KEY_AZURE_SYNC = 'tome_static_azure.syncing';

  /**
   * Gets all uncached public-facing paths for the site.
   *
   * Entity paths will be returned in the format
   * "_entity:entity_type_id:langcode:entity_id" and should be resolved by the
   * caller using a batch process.
   *
   * @return string[]
   *   An array of paths.
   */
  public function getPaths();
}
