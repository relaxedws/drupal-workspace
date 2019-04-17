<?php

namespace Drupal\workspace\Form;

use Drupal\Component\Datetime\DateTimePlus;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class ChangesListForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'changes_list_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $current_page_changes = []) {
    $headers = [
      'label' => $this->t('Label'),
      'type' => $this->t('Entity type'),
      'status' => $this->t('Status'),
      'author' => $this->t('Author'),
      'time' => $this->t('Changed time'),
      'operations' => $this->t('Operations'),
    ];
    $options = [];
    $default = [];
    $uuids = [];
    /** @var \Drupal\multiversion\EntityReferencesManagerInterface $references_manager */
    $references_manager = \Drupal::service('multiversion.entity_references.manager');
    /** @var \Drupal\Core\Entity\ContentEntityInterface[] $current_page_changes */
    foreach ($current_page_changes as $entity) {
      $row = [
        'label' => $entity->label() ?: '* ' . $this->t('No label') . ' *',
        'type' => $entity->getEntityTypeId(),
      ];
      //Set status.
      if ($entity->_deleted->value) {
        $row['status'] = $this->t('Deleted');
      }
      elseif (!empty($entity->_rev->value) && $entity->_rev->value[0] == 1) {
        $row['status'] = $this->t('Added');
      }
      else {
        $row['status'] = $this->t('Changed');
      }
      // Set the author.
      if (method_exists($entity, 'getOwner')) {
        $row['author'] = ($name = $entity->getOwner()->get('name')->value) ? $name : '* ' . $this->t('No author') . ' *';
      }
      else {
        $row['author'] = '* ' . $this->t('No author') . ' *';
      }
      // Set changed value.
      if (method_exists($entity, 'getChangedTime') && $changed = $entity->getChangedTime()) {
        $row['time'] = DateTimePlus::createFromTimestamp($changed)->format('m/d/Y | H:i:s | e');
      }
      else {
        $row['time'] = '* ' . $this->t('No changed time') . ' *';
      }
      // Set operations.
      $links = [];
      if ($entity->hasLinkTemplate('canonical') && !$entity->_deleted->value) {
        $links['view'] = [
          'title' => t('View'),
          'url' => $entity->toUrl('canonical', ['absolute' => TRUE]),
        ];
      }
      else {
        $links['view'] = [
          'title' => '* ' . $this->t('No view link') . ' *',
        ];
      }
      $row['operations'] = [
        'data' => [
          '#type' => 'operations',
          '#links' => $links,
        ],
      ];
      $uuid = $entity->uuid();
      $row['#attributes'] = ['id' => $uuid];
      $default[$uuid] = $uuid;
      $options[$entity->uuid()] = $row;
      if ($markup = $this->buildReferencesMarkup($entity)) {
        $id = $uuid . '-references';
        $options[$id] = [
          'data' => [
            '#markup' => $markup,
        ],
          'colspan' => 7,
          'class' => [
            'references-row',
          ],
          '#attributes' => [
            'id' => $id,
//            'style' => 'display:none',
          ],
        ];
      }
      $uuids[$uuid] = $references_manager->getReferencedEntitiesUuids($entity);
    }

    $form['prefix']['#markup'] = '<p>' . $this->t('The array is sorted by last change first.') . '</p>';
    $form['changes-list'] = [
      '#type' => 'changes_list_tableselect',
      '#default_value' => $default,
      '#header' => $headers,
      '#options' => $options,
      '#empty' => t('There are no changes.'),
      '#sticky' => TRUE,
    ];
    $form['pager'] = [
      '#type' => 'pager',
    ];

    $form['#attached']['drupalSettings']['uuids'] = $uuids;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Does nothing.
  }

  protected function buildReferencesMarkup($entity) {
    // @todo Inject the service.
    /** @var \Drupal\multiversion\EntityReferencesManagerInterface $references_manager */
    $references_manager = \Drupal::service('multiversion.entity_references.manager');
    $referenced_entities = $references_manager->getMultiversionableReferencedEntities($entity);
    if (empty($referenced_entities)) {
      return FALSE;
    }
    $markup = '<p class="referenced-entities">Referenced entities:</p>';
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity_type = NULL;
    foreach ($referenced_entities as $referenced_entity) {
      $entity_type_id = $referenced_entity->getEntityTypeId();
      if ($entity_type != $entity_type_id) {
        if ($entity_type !== NULL) {
          $markup .= '</ul>';
        }
        $markup .= '<p class="referenced-entity-type">' . $referenced_entity->getEntityType()->getLabel() . ':</p>';
        $markup .= '<ul>';
        $entity_type = $entity_type_id;
      }
      $markup .= '<li id="' . $referenced_entity->uuid() . '-reference">' . $referenced_entity->label() . '</li>';
    }
    $markup .= '</ul>';
    return $markup;
  }

}