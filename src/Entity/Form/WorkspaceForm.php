<?php

namespace Drupal\workspace\Entity\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityConstraintViolationListInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\multiversion\Workspace\ConflictTrackerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for the workspace edit forms.
 */
class WorkspaceForm extends ContentEntityForm {

  /**
   * The injected service to track conflicts during replication.
   *
   * @var ConflictTrackerInterface
   */
  protected $conflictTracker;

  /**
   * The workspace content entity.
   *
   * @var \Drupal\multiversion\Entity\WorkspaceInterface
   */
  protected $entity;

  /**
   * Constructs a ContentEntityForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param ConflictTrackerInterface $conflict_tracker
   *   The confict tracking service.
   */
  public function __construct(EntityManagerInterface $entity_manager, ConflictTrackerInterface $conflict_tracker) {
    $this->entityManager = $entity_manager;
    $this->conflictTracker = $conflict_tracker;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('workspace.conflict_tracker')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $workspace = $this->entity;

    if ($this->operation == 'edit') {
      // Allow the user to not abort on conflicts.
      // @todo turn conflict management into an abstract pluggable system
      $this->conflictTracker->useWorkspace($workspace);
      $conflicts = $this->conflictTracker->getAll();
      if ($conflicts) {
        $form['message'] = $this->generateMessageRenderArray('error', $this->t(
          'There are @count conflicts. The replication logic has arbitrarily chosen the entity revision that should be used and it may not have been the one you desired. Proceeding with replication will push these conflicts upstream, which may cause a loss of data. See the full list of conflicts <a href=":link">here</a>.',
          [
            '@count' => count($conflicts),
            ':link' => \Drupal::url('entity.workspace.conflicts', ['workspace_id' => $workspace->id()]),
          ]
        ));
        $form['is_aborted_on_conflict'] = [
          '#type' => 'radios',
          '#title' => $this->t('Abort if there are conflicts?'),
          '#default_value' => 'true',
          '#options' => [
            'true' => $this->t('Yes, if conflicts are found do not replicate to upstream.'),
            'false' => $this->t('No, go ahead and push any conflicts to the upstream.'),
          ],
          '#weight' => 0,
        ];
      }
      else {
        $form['message'] = $this->generateMessageRenderArray('status', 'There are no conflicts.');
      }

      // Set the form title based on workspace.
      $form['#title'] = $this->t('Edit workspace %label', array('%label' => $workspace->label()));
    }

    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $workspace->label(),
      '#description' => $this->t("Label for the Workspace."),
      '#required' => TRUE,
    );

    $form['machine_name'] = array(
      '#type' => 'machine_name',
      '#title' => $this->t('Workspace ID'),
      '#maxlength' => 255,
      '#default_value' => $workspace->get('machine_name')->value,
      '#machine_name' => array(
        'exists' => '\Drupal\multiversion\Entity\Workspace::load',
      ),
      '#element_validate' => array(),
    );

    return parent::form($form, $form_state, $workspace);;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditedFieldNames(FormStateInterface $form_state) {
    return array_merge(array(
      'label',
      'machine_name',
    ), parent::getEditedFieldNames($form_state));
  }

  /**
   * {@inheritdoc}
   */
  protected function flagViolations(EntityConstraintViolationListInterface $violations, array $form, FormStateInterface $form_state) {
    // Manually flag violations of fields not handled by the form display. This
    // is necessary as entity form displays only flag violations for fields
    // contained in the display.
    $field_names = array(
      'label',
      'machine_name',
    );
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
    // Pass the abort flag to the ReplicationManager using runtime-only state,
    // i.e. a static.
    $is_aborted_on_conflict = !$form_state->hasValue('is_aborted_on_conflict') || $form_state->getValue('is_aborted_on_conflict') === 'true';
    drupal_static('workspace_is_aborted_on_conflict', $is_aborted_on_conflict);

    $workspace = $this->entity;
    $insert = $workspace->isNew();
    $workspace->save();
    $info = ['%info' => $workspace->label()];
    $context = array('@type' => $workspace->bundle(), '%info' => $workspace->label());
    $logger = $this->logger('workspace');

    if ($insert) {
      $logger->notice('@type: added %info.', $context);
      drupal_set_message($this->t('Workspace %info has been created.', $info));
    }
    else {
      $logger->notice('@type: updated %info.', $context);
      drupal_set_message($this->t('Workspace %info has been updated.', $info));
    }

    if ($workspace->id()) {
      $form_state->setValue('id', $workspace->id());
      $form_state->set('id', $workspace->id());
      $redirect = $this->currentUser()->hasPermission('administer workspaces') ? $workspace->toUrl('collection') : $workspace->toUrl('canonical');
      $form_state->setRedirectUrl($redirect);
    }
    else {
      drupal_set_message($this->t('The workspace could not be saved.'), 'error');
      $form_state->setRebuild();
    }
  }

  /**
   * Generate a message render array with the given text.
   *
   * @param string $type
   *   The type of message: status, warning, or error.
   * @param string $message
   *   The message to create with.
   *
   * @see \Drupal\Core\Render\Element\StatusMessages
   */
  protected function generateMessageRenderArray($type, $message) {
    return [
      '#theme' => 'status_messages',
      '#message_list' => [
        $type => [Markup::create($message)],
      ],
    ];
  }

}
