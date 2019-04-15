<?php

namespace Drupal\workspace\Element;

use Drupal\Component\Utility\Html as HtmlUtility;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\Tableselect;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * A custom extension of core tableselect form element.
 *
 * @see \Drupal\Core\Render\Element\Tableselect
 *
 * @FormElement("changes_list_tableselect")
 */
class ChangesListTableselect extends Tableselect {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#js_select' => TRUE,
      '#multiple' => TRUE,
      '#responsive' => TRUE,
      '#sticky' => FALSE,
      '#pre_render' => [
        [$class, 'preRenderTable'],
        [$class, 'preRenderChangesListTableselect'],
      ],
      '#process' => [
        [$class, 'processTableselect'],
      ],
      '#options' => [],
      '#empty' => '',
      '#theme' => 'table__changes_list_tableselect',
    ];
  }


  /**
   * Prepares a 'changes_list_tableselect' #type element for rendering.
   *
   * Adds a column of radio buttons or checkboxes for each row of a table.
   *
   * @param array $element
   *   An associative array containing the properties and children of
   *   the tableselect element. Properties used: #header, #options, #empty,
   *   and #js_select. The #options property is an array of selection options;
   *   each array element of #options is an array of properties. These
   *   properties can include #attributes, which is added to the
   *   table row's HTML attributes; see table.html.twig. An example of per-row
   *   options:
   *   @code
   *     $options = array(
   *       array(
   *         'title' => $this->t('How to Learn Drupal'),
   *         'content_type' => $this->t('Article'),
   *         'status' => 'published',
   *         '#attributes' => array('class' => array('article-row')),
   *       ),
   *       array(
   *         'title' => $this->t('Privacy Policy'),
   *         'content_type' => $this->t('Page'),
   *         'status' => 'published',
   *         '#attributes' => array('class' => array('page-row')),
   *       ),
   *     );
   *     $header = array(
   *       'title' => $this->t('Title'),
   *       'content_type' => $this->t('Content type'),
   *       'status' => $this->t('Status'),
   *     );
   *     $form['table'] = array(
   *       '#type' => 'changes_list_tableselect',
   *       '#header' => $header,
   *       '#options' => $options,
   *       '#empty' => $this->t('No content available.'),
   *     );
   *   @endcode
   *
   * @return array
   *   The processed element.
   */
  public static function preRenderChangesListTableselect($element) {
    $rows = [];
    $header = $element['#header'];
    if (!empty($element['#options'])) {
      // Generate a table row for each selectable item in #options.
      foreach (Element::children($element) as $key) {
        $row = [];

        $row['data'] = [];
        if (isset($element['#options'][$key]['#attributes'])) {
          $row += $element['#options'][$key]['#attributes'];
        }
        // Render the checkbox / radio element.
        if (strpos($key, '-references') === FALSE) {
          $form_data = [
            'data' => \Drupal::service('renderer')->render($element[$key]),
//            'rowspan' => 2,
          ];
          $row['data'][] = $form_data;
        }

        // As table.html.twig only maps header and row columns by order, create
        // the correct order by iterating over the header fields.
        foreach ($element['#header'] as $fieldname => $title) {
          // A row cell can span over multiple headers, which means less row
          // cells than headers could be present.
          if (isset($element['#options'][$key][$fieldname])) {
            // A header can span over multiple cells and in this case the cells
            // are passed in an array. The order of this array determines the
            // order in which they are added.
            if (is_array($element['#options'][$key][$fieldname]) && !isset($element['#options'][$key][$fieldname]['data'])) {
              foreach ($element['#options'][$key][$fieldname] as $cell) {
                $row['data'][] = $cell;
              }
            }
            else {
              $row['data'][] = $element['#options'][$key][$fieldname];
            }
          }
        }

        // Add the row with the references info.
        if (strpos($key, '-references') !== FALSE) {
          $row['data'][] = $element['#options'][$key];
          unset($element['#options'][$key]);
        }

        $rows[] = $row;
      }
      // Add an empty header or a "Select all" checkbox to provide room for the
      // checkboxes/radios in the first table column.
      if ($element['#js_select']) {
        // Add a "Select all" checkbox.
        $element['#attached']['library'][] = 'core/drupal.tableselect';
        array_unshift($header, ['class' => ['select-all']]);
      }
      else {
        // Add an empty header when radio buttons are displayed or a "Select all"
        // checkbox is not desired.
        array_unshift($header, '');
      }
    }

    $element['#header'] = $header;
    $element['#rows'] = $rows;

    return $element;
  }

  /**
   * Creates checkbox or radio elements to populate a tableselect table.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   tableselect element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The processed element.
   */
  public static function processChangesListTableselect(&$element, FormStateInterface $form_state, &$complete_form) {
    if ($element['#multiple']) {
      $value = is_array($element['#value']) ? $element['#value'] : [];
    }
    else {
      // Advanced selection behavior makes no sense for radios.
      $element['#js_select'] = FALSE;
    }

    $element['#tree'] = TRUE;

    if (count($element['#options']) > 0) {
      if (!isset($element['#default_value']) || $element['#default_value'] === 0) {
        $element['#default_value'] = [];
      }

      // Create a checkbox or radio for each item in #options in such a way that
      // the value of the tableselect element behaves as if it had been of type
      // checkboxes or radios.
      foreach ($element['#options'] as $key => $choice) {
        if (strpos($key, '-references') !== FALSE) {
          continue;
        }
        // Do not overwrite manually created children.
        if (!isset($element[$key])) {
          if ($element['#multiple']) {
            $title = '';
            if (isset($element['#options'][$key]['title']) && is_array($element['#options'][$key]['title'])) {
              if (!empty($element['#options'][$key]['title']['data']['#title'])) {
                $title = new TranslatableMarkup('Update @title', [
                  '@title' => $element['#options'][$key]['title']['data']['#title'],
                ]);
              }
            }
            $element[$key] = [
              '#type' => 'checkbox',
              '#title' => $title,
              '#title_display' => 'invisible',
              '#return_value' => $key,
              '#default_value' => isset($value[$key]) ? $key : NULL,
              '#attributes' => $element['#attributes'],
              '#ajax' => isset($element['#ajax']) ? $element['#ajax'] : NULL,
            ];
          }
          else {
            // Generate the parents as the autogenerator does, so we will have a
            // unique id for each radio button.
            $parents_for_id = array_merge($element['#parents'], [$key]);
            $element[$key] = [
              '#type' => 'radio',
              '#title' => '',
              '#return_value' => $key,
              '#default_value' => ($element['#default_value'] == $key) ? $key : NULL,
              '#attributes' => $element['#attributes'],
              '#parents' => $element['#parents'],
              '#id' => HtmlUtility::getUniqueId('edit-' . implode('-', $parents_for_id)),
              '#ajax' => isset($element['#ajax']) ? $element['#ajax'] : NULL,
            ];
          }
          if (isset($element['#options'][$key]['#weight'])) {
            $element[$key]['#weight'] = $element['#options'][$key]['#weight'];
          }
        }
      }
    }
    else {
      $element['#value'] = [];
    }
    return $element;
  }


}
