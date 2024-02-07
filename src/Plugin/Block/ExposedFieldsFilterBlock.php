<?php

namespace Drupal\view_filter_display\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\views\Views;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Render\Element;
use Drupal\Core\Form\FormState;
use Drupal\views\Form\ViewsExposedForm;

/**
 * Provides an form exposed catalogue.
 *
 * @Block(
 *   id = "view_filter_display_exposed_fields_filter",
 *   admin_label = @Translation("  Filter Sort by active fields "),
 *   category = @Translation(" view filter display ")
 * )
 */
class ExposedFieldsFilterBlock extends BlockBase {

  /**
   *
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'view_name_display' => NULL,
      'view_name_display_class' => '',
      'fields' => [],
      'show_submit' => false,
      'show_sort_by' => false,
      'show_sort_order' => false,
      'show_reset_link' => false
    ];
  }

  /**
   *
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['view_name_display'] = [
      '#title' => $this->t(' Use view exposed form to create custom form '),
      '#type' => 'select',
      '#options' => $this->getViews(),
      '#required' => TRUE,
      '#default_value' => $this->configuration['view_name_display'],
      '#ajax' => [
        'callback' => self::class . '::ExposedFieldsFilter_select',
        'wrapper' => 'view_filter_display_exposed_fields_filter_id',
        'effect' => 'fade'
      ]
    ];

    $form['fields'] = [
      '#type' => 'details',
      '#open' => true,
      '#title' => t('Select fields'),
      '#attributes' => [
        'id' => 'view_filter_display_exposed_fields_filter_id'
      ],
      '#tree' => true
    ];
    /**
     * On a une erreur avec $form_state->getValue('settings').
     *
     * @see https://www.drupal.org/project/drupal/issues/2798261#comment-12735075
     */
    if ($form_state instanceof SubformState) {
      $settings = $form_state->getCompleteFormState()->getValue('settings');
    }
    $view_name_display = !empty($settings['view_name_display']) ? $settings['view_name_display'] : $this->configuration['view_name_display'];

    if ($view_name_display) {
      list($view_id, $display_id) = explode(" ", $view_name_display);
      $fields = $this->getViewExposedFields($view_id, $display_id);
      foreach ($fields as $fieldname => $label) {
        $form['fields'][$fieldname] = [
          '#title' => $this->t(" $label "),
          '#type' => 'details',
          '#open' => false
        ];
        $form['fields'][$fieldname]['status'] = [
          '#title' => $this->t(" Enable "),
          '#type' => 'checkbox',
          '#default_value' => $this->configuration['fields'][$fieldname]['status'] ?? false
        ];
        $form['fields'][$fieldname]['show_label'] = [
          '#title' => $this->t(" Show label "),
          '#type' => 'checkbox',
          '#default_value' => $this->configuration['fields'][$fieldname]['show_label'] ?? true
        ];
        $form['fields'][$fieldname]['hide_all_option'] = [
          '#title' => $this->t(" Hide 'all' option on list "),
          '#type' => 'checkbox',
          '#default_value' => $this->configuration['fields'][$fieldname]['hide_all_option'] ?? true
        ];
        $form['fields'][$fieldname]['show_more_options'] = [
          '#title' => $this->t(" Show mores options button "),
          '#type' => 'checkbox',
          '#default_value' => $this->configuration['fields'][$fieldname]['show_more_options'] ?? true
        ];
        $form['fields'][$fieldname]['number_options_to_show'] = [
          '#title' => $this->t(" Number options to show "),
          '#type' => 'number',
          '#default_value' => !empty($this->configuration['fields'][$fieldname]['number_options_to_show']) ? $this->configuration['fields'][$fieldname]['number_options_to_show'] : 10
        ];
        $form['fields'][$fieldname]['class'] = [
          '#title' => $this->t(" Class css "),
          '#type' => 'textfield',
          '#default_value' => !empty($this->configuration['fields'][$fieldname]['class']) ? $this->configuration['fields'][$fieldname]['class'] : ''
        ];
      }
    }
    $form['view_name_display_class'] = [
      '#title' => $this->t(' class '),
      '#type' => 'textfield',
      '#default_value' => $this->configuration['view_name_display_class']
    ];

    $form['show_submit'] = [
      '#title' => $this->t(' Show submit '),
      '#type' => 'checkbox',
      '#default_value' => $this->configuration['show_submit']
    ];
    $form['show_sort_by'] = [
      '#title' => $this->t(' Show sort_by '),
      '#type' => 'checkbox',
      '#default_value' => $this->configuration['show_sort_by']
    ];
    $form['show_sort_order'] = [
      '#title' => $this->t(' Show sort_order '),
      '#type' => 'checkbox',
      '#default_value' => $this->configuration['show_sort_order']
    ];
    $form['show_reset_link'] = [
      '#title' => $this->t(' Show reset_link '),
      '#type' => 'checkbox',
      '#default_value' => $this->configuration['show_reset_link']
    ];
    return $form;
  }

  /**
   */
  public static function ExposedFieldsFilter_select($form, FormStateInterface $form_state) {
    return $form['settings']['fields'];
  }

  /**
   *
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['view_name_display'] = $form_state->getValue('view_name_display');
    $this->configuration['fields'] = $form_state->getValue("fields");
    $this->configuration['view_name_display_class'] = $form_state->getValue('view_name_display_class');
    $this->configuration['show_submit'] = $form_state->getValue('show_submit');
    $this->configuration['show_sort_by'] = $form_state->getValue('show_sort_by');
    $this->configuration['show_sort_order'] = $form_state->getValue('show_sort_order');
    $this->configuration['show_reset_link'] = $form_state->getValue('show_reset_link');
  }

  /**
   *
   * @param string $view_name
   * @param string $view_display
   */
  protected function getViewExposedFields($view_name, $view_display) {
    $fields = [];
    $view = Views::getView($view_name);
    $view->setDisplay($view_display);
    $view->initHandlers();
    $exposed_form = $view->display_handler->getOption('exposed_form');
    if (!empty($exposed_form["options"])) {
      $fields['sort_by'] = $exposed_form["options"]['exposed_sorts_label'];
    }
    $filters = $view->display_handler->getOption('filters');
    foreach ($filters as $key => $val) {
      if (!empty($val['exposed'])) {
        $fields[$key] = $val['expose']['label'] . ' (' . $val['expose']['identifier'] . ')';
      }
    }
    return $fields;
  }

  /**
   * --
   */
  protected function getViews() {
    $options = [];
    $views = Views::getEnabledViews();
    foreach ($views as $val) {
      /**
       *
       * @var \Drupal\views\ViewExecutable $view
       */
      $view = $val->getExecutable();
      $displays = $view->storage->get('display');
      // dump($displays);
      foreach ($displays as $display_id => $v) {
        if (str_contains($display_id, "page_")) {
          $view->setDisplay($display_id);
          $prev_filters = $view->display_handler->options;
          if (!empty($prev_filters['exposed_block'])) {
            $options[$view->id() . ' ' . $display_id] = $view->getTitle() . ' (' . $v['display_title'] . ')';
          }
        }
      }
    }
    return $options;
  }

  /**
   *
   * {@inheritdoc}
   */
  public function build() {
    $view_name_display = explode(" ", $this->configuration['view_name_display']);
    $view_name = $view_name_display[0];
    $view_display = $view_name_display[1];
    // Get view and execute.
    $view = Views::getView($view_name);
    if ($view) {
      /**
       * il faut les arguments afin que l'url puisse etre correctement generer.
       *
       * @var \Drupal\Core\Entity\Plugin\DataType\EntityAdapter $taxonomy_term
       */
      $taxonomy_term = \Drupal::routeMatch()->getParameter('taxonomy_term');
      if ($taxonomy_term) {
        $args = [
          $taxonomy_term->id()
        ];
        $view->setArguments($args);
      }

      $form = $this->getFormExposed($view, $view_display);

      $enableInputs = $this->configuration['fields'];

      $inputs = Element::children($form);
      if ($inputs && $enableInputs) {
        foreach ($inputs as $fieldName) {
          $form[$fieldName]['#access'] = false;
          if (!empty($enableInputs[$fieldName]['status'])) {
            $form[$fieldName]['#access'] = true;
            $form[$fieldName]['#title_display'] = !$enableInputs[$fieldName]['show_label'] ? 'invisible' : $form[$fieldName]['#title_display'];
            $form[$fieldName]['#attributes']['class'][] = $enableInputs[$fieldName]['class'];
            if ($enableInputs[$fieldName]['hide_all_option']) {
              if (isset($form[$fieldName]['#options']['All']))
                unset($form[$fieldName]['#options']['All']);
            }
          }
        }
        // Enable show_reset_link
        if ($this->configuration['show_reset_link']) {
          $form['show_reset_link'] = [
            '#type' => 'html_tag',
            '#tag' => 'div',
            '#attributes' => [
              'class' => [
                'show_reset_link'
              ]
            ],
            [
              '#type' => 'html_tag',
              '#tag' => 'a',
              '#attributes' => [
                'href' => $view->getUrl()->toString()
              ],
              '#value' => 'Réinitialiser la recherche'
            ]
          ];
        }
        $form['#wrapper_attributes'] = [
          'class' => [
            'view_filter_display_filter',
            $this->configuration['view_name_display_class']
          ]
        ];
        if (isset($form['#attributes']['class'])) {
          $form['#attributes']['class'][] = 'view_filter_display_exposed_fields_filter';
          $form['#attributes']['class'][] = $this->configuration['view_name_display_class'];
        }
        return $form;
      }
    }
    return [];
  }

  /**
   * Retourne le formualire exposed.
   *
   * @param \Drupal\views\ViewExecutable $view
   * @param string $view_display
   * @return array
   */
  protected function getFormExposed(\Drupal\views\ViewExecutable $view, string $view_display) {
    $view->setDisplay($view_display);
    // Execute view query.
    $view->initHandlers();
    /**
     *
     * @var \Drupal\views\Plugin\views\exposed_form\Basic $exposed_form
     */
    $exposed_form = $view->display_handler->getPlugin('exposed_form');
    $form = $exposed_form->renderExposedForm(true);
    return $form;
  }

}
