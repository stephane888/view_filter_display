<?php

namespace Drupal\view_filter_display\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for view filter display routes.
 */
class ViewFilterDisplayController extends ControllerBase {

  /**
   * Builds the response.
   */
  public function build() {

    $build['content'] = [
      '#type' => 'item',
      '#markup' => $this->t('It works!'),
    ];

    return $build;
  }

}
