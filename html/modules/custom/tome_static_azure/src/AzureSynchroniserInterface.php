<?php

namespace Drupal\tome_static_azure;

/**
 * Provides an interface for the static azure syncer.
 */
interface AzureSynchroniserInterface {

  /**
   * The key user interfaces should use to get/set if they're running a build.
   */
  const STATE_KEY_AZURE_SYNC = 'tome_static_azure.syncing';

  /**
   * The container that contains the website in the storage account.
   */
  const AZURE_SITE_CONTAINER = '$web';

  /**
   * Gets all files for the static site.
   *
   * @return string[]
   *   An array of file paths.
   */
  public function getFileList();

  /**
   * Copy all files to the Azure storage container.
   */
  public function synchronise(array $paths);
}
