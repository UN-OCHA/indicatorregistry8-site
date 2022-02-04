<?php

namespace Drupal\tome_static_azure\Commands;

use Drush\Commands\DrushCommands;
use Drupal\Core\Site\Settings;
use MicrosoftAzure\Storage\Common\Exceptions\ServiceException;
use MicrosoftAzure\Storage\Blob\Models\ListBlobsOptions;


/**
 * A Drush commandfile.
 *
 * This is a very basic command that uses the azure_storage wrapper to upload
 * a directory to Azure blob storage.
 *
 * It assumes you create the container outside Drupal. With terraform, for instance.
 */
class TomeStaticAzureCommands extends DrushCommands {

  /**
   * A static website container is always this one.
   */
  const AZURE_SITE_CONTAINER = '$web';

  /**
   * Synchronise the tome_static output directory to an azure storsage account.
   *
   * @usage drush tome-static-azure-sync
   *   Copies all files from the configured tome_static directory to an Azure storage account.
   *
   * @command tome:azure-sync
   */
  public function sync() {

    // This should use $settings eh?
    $tome_dir = Settings::get('tome_static_directory', '../html');

    $storage_client = \Drupal::service('azure_storage.client');
    $storage_blob_service = $storage_client->getStorageBlobService();

    $files = $this->getFileList($tome_dir);
    $this->logger()->info(dt('Going to synchronise @count files.', ['@count' => count($files)]));

    foreach ($files as $file) {
      try {
        $content = fopen("${tome_dir}/${file}", "r");
        $storage_blob_service->createBlockBlob(TomeStaticAzureCommands::AZURE_SITE_CONTAINER, $file, $content);
        $this->logger()->success(dt('Uploaded @file.', ['@file' => $file]));
      }
      catch (ServiceException $e) {
        $this->logger()->error(dt('Service Error @code: @essage files.', ['@code' => $e->getCode(), '@message' => $e->getMessage()]));
      }
      catch (InvalidArgumentTypeException $e) {
        $this->logger()->error(dt('Invalid Argument @code: @essage files.', ['@code' => $e->getCode(), '@message' => $e->getMessage()]));
      }
    }
  }

  /**
   * Helper to generate a list of files for syncing.
   *
   * @param string $path
   *   A directory.
   */
  private function getFileList($path) {
    $files = [];

    // Make sure we do not end with a slash.
    $path  = rtrim($path, '/');

    $directory = new \RecursiveDirectoryIterator($path);
    $filter = new \RecursiveCallbackFilterIterator($directory, function ($current, $key, $iterator) {
      // Skip hidden files and directories.
      if ($current->getFilename()[0] === '.') return FALSE;
      return TRUE;
    });
    $iterator = new \RecursiveIteratorIterator($filter);

    foreach ($iterator as $file) {
      $files[] = substr($file->getPathname(), strlen($path) + 1);
    }
    return $files;
  }
}
