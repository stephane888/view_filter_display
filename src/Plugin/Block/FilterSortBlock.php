<?php

namespace Drupal\view_filter_display\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\views\Views;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides an form exposed catalogue.
 *
 * @deprecated
 * @Block(
 *   id = "view_filter_display_filter_sort",
 *   admin_label = @Translation(" Filter Sort by hidden @deprecated "),
 *   category = @Translation(" view filter display ")
 *  )
 */
class FilterSortBlock extends BlockBase {
  
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
    $view->setDisplay($view_display);
    // Execute view query.
    $view->initHandlers();
    // methode 1
    // $form_state = new \Drupal\Core\Form\FormState();
    // $form_state->setFormState([
    // 'view' => $view,
    // 'display' => $view->display_handler->display,
    // 'exposed_form_plugin' =>
    // $view->display_handler->getPlugin('exposed_form'),
    // 'method' => 'get',
    // 'rerender' => TRUE
    // ]);
    // $form_state->setMethod('get');
    // $form_state->setAlwaysProcess();
    // $form_state->disableRedirect();
    // $form =
    // \Drupal::formBuilder()->buildForm('Drupal\views\Form\ViewsExposedForm',
    // $form_state);
    // methode 2
    /**
     *
     * @var \Drupal\views\Plugin\views\exposed_form\Basic $exposed_form
     */
    $exposed_form = $view->display_handler->getPlugin('exposed_form');
    $form = $exposed_form->renderExposedForm(true);
    $hiddenfields = $this->configuration['hidden_fields'];
    //
    foreach ($hiddenfields as $field) {
      if ($field) {
        if (!empty($form[$field])) {
          // $form[$field]['#access'] = false;
          $form[$field]['#prefix'] = '<div class="d-none">';
          $form[$field]['#suffix'] = '</div>';
        } //
        elseif (!empty($form[$field . '_wrapper'])) {
          // $form[$field . '_wrapper']['#access'] = false;
          $form[$field . '_wrapper']['#prefix'] = '<div class="d-none">';
          $form[$field . '_wrapper']['#suffix'] = '</div>';
        }
      }
    }
    $form['#wrapper_attributes'] = [
      'class' => [
        'view_filter_display_filter',
        $this->configuration['view_name_display_class']
      ]
    ];
    if (isset($form['#attributes']['class'])) {
      $form['#attributes']['class'][] = 'view_filter_display_filter';
      $form['#attributes']['class'][] = $this->configuration['view_name_display_class'];
    }
    
    // dump($form);
    return $form;
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'view_name_display' => '',
      'view_name_display_class' => '',
      'hidden_fields' => []
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
      '#default_value' => $this->configuration['view_name_display']
    ];
    if (!empty($this->configuration['view_name_display'])) {
      $v = explode(" ", $this->configuration['view_name_display']);
      $form['hidden_fields'] = [
        '#title' => $this->t(' Select field to hidden'),
        '#type' => 'checkboxes',
        '#options' => $this->getViewExposedFields($v[0], $v[1]),
        '#default_value' => $this->configuration['hidden_fields']
      ];
      $form['view_name_display_class'] = [
        '#title' => $this->t(' class '),
        '#type' => 'textfield',
        '#required' => TRUE,
        '#default_value' => $this->configuration['view_name_display_class']
      ];
    }
    //
    return $form;
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['view_name_display'] = $form_state->getValue('view_name_display');
    $this->configuration['hidden_fields'] = $form_state->getValue([
      'hidden_fields'
    ]);
    $this->configuration['view_name_display_class'] = $form_state->getValue('view_name_display_class');
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
    if (!empty($view->display_handler->options['exposed_form'])) {
      $fields['sort_by'] = $view->display_handler->options['exposed_form']["options"]['exposed_sorts_label'];
    }
    $filters = $view->display_handler->options['filters'];
    foreach ($filters as $key => $val) {
      if (isset($val['exposed'])) {
        $fields[$key] = $val['expose']['label'];
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
  
}
