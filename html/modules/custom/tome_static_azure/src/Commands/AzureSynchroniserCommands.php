<?php

namespace Drupal\tome_static_azure\Commands;

use Drush\Commands\DrushCommands;

use MicrosoftAzure\Storage\Common\Exceptions\ServiceException;
use MicrosoftAzure\Storage\Blob\Models\ListBlobsOptions;

use Drupal\tome_static_azure\AzureSyncrhoniserInterface;


/**
 * A Drush commandfile.
 *
 * This is a very basic command that uses the azure_storage wrapper to upload
 * a directory to Azure blob storage.
 *
 * It assumes you create the container outside Drupal. With terraform, for instance.
 */
class AzureSynchroniserCommands extends DrushCommands {

  private $synchroniser;

  /**
   * Create a new synchroniser object.
   */
  public function __construct() {
  }

  /**
   * Synchronise the tome_static output directory to an azure storsage account.
   *
   * @usage drush tome-static-azure-sync
   *   Copies all files from the configured tome_static directory to an Azure storage account.
   *
   * @command tome:azure-sync
   */
  public function sync() {

    // Because microsoft.
    $container = '$web';

    // This should use $settings eh?
    $tome_dir  = "/tmp/tome";

    $storage_client = \Drupal::service('azure_storage.client');
    $storage_blob_service = $storage_client->getStorageBlobService();

    $files = $synchroniser->getFileList($tome_dir);
    $this->logger()->success(dt('Going to synchronise @count files.', ['@count' => count($files)]));

    foreach ($files as $file) {
      try {
        $content = fopen("${tome_dir}/${file}", "r");
        $storage_blob_service->createBlockBlob($container, $file, $content);
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
}
