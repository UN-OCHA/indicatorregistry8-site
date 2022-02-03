<?php

namespace Drupal\tome_static_azure;

use Drupal\Core\Site\Settings;

/**
 * The meat of all the synchronisation.
 */
class AzureSynchroniser implements AzureSynchroniserInterface {

  /**
   * The local path with the static site.
   */
  private $path;

  /**
   * A list of files to be synchronised.
   */
  private $files = [];

  public function getFileList() {
    return $this->files;
  }

  public function synchronise() {

  }

  /**
   * {@inheritdoc}
   */
  public function createFileList($path) {
    $this->path = rtrim($path, '/');

    $directory = new \RecursiveDirectoryIterator($this->path);
    $filter = new \RecursiveCallbackFilterIterator($directory, function ($current, $key, $iterator) {
      // Skip hidden files and directories.
      if ($current->getFilename()[0] === '.') return FALSE;
      return TRUE;
    });
    $iterator = new \RecursiveIteratorIterator($filter);

    foreach ($iterator as $file) {
      $files[] = substr($file->getPathname(), strlen($path) + 1);
    }
    $this->files = $files;
  }

}
