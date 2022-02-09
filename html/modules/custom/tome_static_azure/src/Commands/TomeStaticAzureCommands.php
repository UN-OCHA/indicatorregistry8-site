<?php

namespace Drupal\tome_static_azure\Commands;

use Drush\Commands\DrushCommands;
use Drupal\Core\Site\Settings;
use MicrosoftAzure\Storage\Blob\Models\CreateBlockBlobOptions;
use MicrosoftAzure\Storage\Common\Exceptions\ServiceException;
use Mimey\MimeTypes;

/**
 * A Drush commandfile.
 *
 * This is a very basic command that uses the azure_storage wrapper to upload
 * a directory to Azure blob storage.
 *
 * It assumes you create the container outside Drupal.
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
   *   Copies all files from the configured tome_static directory to Azure.
   *
   * @command tome:azure-sync
   */
  public function sync() {

    // This should use $settings eh?
    $tome_dir = Settings::get('tome_static_directory', '../html');

    if (!file_exists($tome_dir) || !is_dir($tome_dir)) {
      $this->logger()->error(dt('The tome_static source directory @dir does not exist!', ['@dir' => $tome_dir]));
      exit(1);
    }

    // @codingStandardsIgnoreLine
    $storage_client = \Drupal::service('azure_storage.client');
    $storage_blob_service = $storage_client->getStorageBlobService();

    $files = $this->getFileList($tome_dir);
    $this->logger()->info(dt('Going to synchronise @count files from @dir',
      ['@count' => count($files), '@dir' => $tome_dir]
    ));

    foreach ($files as $file) {
      try {
        // Set the file content-type, or Azure will default to forcing a
        // download for everything.
        $options = new CreateBlockBlobOptions();
        $options->setContentType($file['filemime']);

        $content = fopen("${tome_dir}/${file['filename']}", "r");
        $storage_blob_service->createBlockBlob(TomeStaticAzureCommands::AZURE_SITE_CONTAINER, $file['filename'], $content, $options);

        $this->logger()->success(dt('Uploaded @file', ['@file' => $file['filename']]));
      }
      catch (ServiceException $e) {
        $this->logger()->error(dt('Service Error @code: @message',
          ['@code' => $e->getCode(), '@message' => $e->getMessage()]
        ));
      }
      catch (InvalidArgumentTypeException $e) {
        $this->logger()->error(dt('Invalid Argument @code: @message.',
          ['@code' => $e->getCode(), '@message' => $e->getMessage()]
        ));
      }
    }
  }

  /**
   * Helper to generate a list of files for syncing.
   *
   * @param string $path
   *   A directory.
   *
   * @return array
   *   An array of keyed values containing a filename and file mimetype.
   */
  private function getFileList($path) {
    $files = [];

    // Make sure we do not end with a slash.
    $path = rtrim($path, '/');

    $directory = new \RecursiveDirectoryIterator($path);
    $filter = new \RecursiveCallbackFilterIterator($directory, function ($current, $key, $iterator) {
      // Skip hidden files and directories.
      // @codingStandardsIgnoreLine
      if ($current->getFilename()[0] === '.') return FALSE;
      return TRUE;
    });
    $iterator = new \RecursiveIteratorIterator($filter);

    // Look up the mime type based on the extension because the magic file
    // via the finfo_* makes trouble.
    $mimes = new MimeTypes();

    foreach ($iterator as $file) {
      $files[] = [
        'filename' => substr($file->getPathname(), strlen($path) + 1),
        'filemime' => $mimes->getMimeType(pathinfo($file->getPathname(), PATHINFO_EXTENSION)),
      ];
    }

    return $files;
  }

}
