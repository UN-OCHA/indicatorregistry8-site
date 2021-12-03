<?php

namespace Drupal\lunr_filters\Plugin\views\display;

use Drupal\lunr\Plugin\views\display\LunrSearchIndex;
use Drupal\views\Plugin\views\display\PathPluginBase;
use Drupal\views\Plugin\views\display\ResponseDisplayPluginInterface;

/**
 * The plugin that handles Lunr search indexes.
 *
 * This class is largely based on the core REST module.
 *
 * @ingroup views_display_plugins
 *
 * @ViewsDisplay(
 *   id = "lunr_filters_search_index",
 *   title = @Translation("Lunr filters search index"),
 *   help = @Translation("Create a Lunr search index with filters."),
 *   admin = @Translation("Lunr filters search index"),
 *   uses_route = TRUE,
 *   returns_response = TRUE,
 *   lunr_search_display = TRUE
 * )
 */
class LunrFiltersSearchIndex extends LunrSearchIndex implements ResponseDisplayPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return 'lunr_filters_search_index';
  }

  /**
   * {@inheritdoc}
   */
  public function usesExposed() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function displaysExposed() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = PathPluginBase::defineOptions();

    $options['style']['contains']['type']['default'] = 'lunr_search_index_json';
    $options['row']['contains']['type']['default'] = 'lunr_search_index_row';
    $options['defaults']['default']['style'] = FALSE;
    $options['defaults']['default']['row'] = FALSE;

    // Remove css/exposed form settings.
    unset($options['exposed_block']);
    unset($options['css_class']);

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function optionsSummary(&$categories, &$options) {
    PathPluginBase::optionsSummary($categories, $options);

    unset($categories['page']);
    // Hide some settings, as they aren't useful for pure data output.
    unset($options['show_admin_links'], $options['analyze-theme']);

    $categories['path'] = [
      'title' => $this->t('Path settings'),
      'column' => 'second',
      'build' => [
        '#weight' => -10,
      ],
    ];

    $options['path']['category'] = 'path';

    // Remove css/exposed form settings.
    unset($options['exposed_block']);
    unset($options['css_class']);
  }

}