<?php

namespace Drupal\ir_tome\Controller;

/**
 * @file
 * Redirect controller.
 */

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Redirect controller.
 */
class RedirectController extends ControllerBase {

  /**
   * Redirect.
   */
  public function redirectToLunr($id) {
    return new RedirectResponse('/indicators?field_sectors=' . $id);
  }

}
