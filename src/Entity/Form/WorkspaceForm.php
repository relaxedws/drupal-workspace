<?php

namespace Drupal\workspace\Entity\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityConstraintViolationListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Form controller for the workspace edit forms.
 */
class WorkspaceForm extends ContentEntityForm {

  /**
   * The workspace content entity.
   *
   * @var \Drupal\workspace\Entity\WorkspaceInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $workspace = $this->entity;

    if ($this->operation == 'edit') {
      $form['#title'] = $this->t('Edit workspace %label', ['%label' => $workspace->label()]);
    }
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $workspace->label(),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#title' => $this->t('Workspace ID'),
      '#maxlength' => 255,
      '#default_value' => $workspace->id(),
      '#disabled' => !$workspace->isNew(),
      '#machine_name' => [
        'exists' => '\Drupal\workspace\Entity\Workspace::load',
      ],
      '#element_validate' => [],
    ];

    $upstreams = [];
    $upstream_manager = \Drupal::service('plugin.manager.workspace.upstream');
    $upstream_definitions = $upstream_manager->getDefinitions();
    foreach ($upstream_definitions as $upstream_definition) {
      /** @var \Drupal\workspace\UpstreamPluginInterface $instance */
      $instance = $upstream_manager->createInstance($upstream_definition['id']);
      $upstreams[$instance->getPluginId()] = $instance->getLabel();
    }
    $form['upstream'] = [
      '#type' => 'radios',
      '#title' => $this->t('Default upstream'),
      '#default_value' => $workspace->get('upstream')->value,
      '#options' => $upstreams,
    ];
    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditedFieldNames(FormStateInterface $form_state) {
    return array_merge([
      'label',
      'id',
    ], parent::getEditedFieldNames($form_state));
  }

  /**
   * {@inheritdoc}
   */
  protected function flagViolations(EntityConstraintViolationListInterface $violations, array $form, FormStateInterface $form_state) {
    // Manually flag violations of fields not handled by the form display. This
    // is necessary as entity form displays only flag violations for fields
    // contained in the display.
    $field_names = [
      'label',
      'id',
      'upstream'
    ];
    foreach ($violations->getByFields($field_names) as $violation) {
      list($field_name) = explode('.', $violation->getPropertyPath(), 2);
      $form_state->setErrorByName($field_name, $violation->getMessage());
    }
    parent::flagViolations($violations, $form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $workspace = $this->entity;
    $status = $workspace->save();

    \Drupal::service('plugin.manager.workspace.upstream')->clearCachedDefinitions();

    $info = ['%info' => $workspace->label()];
    $context = ['@type' => $workspace->bundle(), '%info' => $workspace->label()];
    $logger = $this->logger('workspace');

    if ($status == SAVED_UPDATED) {
      $logger->notice('@type: updated %info.', $context);
      drupal_set_message($this->t('Workspace %info has been updated.', $info));
    }
    else {
      $logger->notice('@type: added %info.', $context);
      drupal_set_message($this->t('Workspace %info has been created.', $info));
    }

    if ($workspace->id()) {
      $form_state->setValue('id', $workspace->id());
      $form_state->set('id', $workspace->id());
      $redirect = $this->currentUser()->hasPermission('administer workspaces') ? $workspace->toUrl('collection') : Url::fromRoute('<front>');
      $form_state->setRedirectUrl($redirect);
    }
    else {
      drupal_set_message($this->t('The workspace could not be saved.'), 'error');
      $form_state->setRebuild();
    }
  }

}
