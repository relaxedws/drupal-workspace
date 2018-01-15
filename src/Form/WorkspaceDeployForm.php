<?php

namespace Drupal\workspace\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Provides the workspace deploy form.
 */
class WorkspaceDeployForm extends ContentEntityForm {

  /**
   * The workspace entity.
   *
   * @var \Drupal\workspace\WorkspaceInterface
   */
  protected $entity;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a new WorkspaceDeployForm.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(EntityManagerInterface $entity_manager, MessengerInterface $messenger, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, TimeInterface $time = NULL) {
    parent::__construct($entity_manager, $entity_type_bundle_info, $time);
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('messenger'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /* @var \Drupal\workspace\WorkspaceInterface $workspace */
    $workspace = $this->entity;

    // We can not deploy if we do not have a valid target.
    if (!$workspace->getRepositoryHandlerPlugin()) {
      throw new HttpException(500, 'The specified repository handler plugin does not exist.');
    }

    $form['help'] = [
      '#markup' => $this->t('Deploy all %source_label content to %target_label, or refresh %source_label with content from %target_label.', ['%source_label' => $workspace->label(), '%target_label' => $workspace->getRepositoryHandlerPlugin()->getLabel()]),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function actions(array $form, FormStateInterface $form_state) {
    $elements = parent::actions($form, $form_state);

    $target_label = $this->entity->getRepositoryHandlerPlugin()->getLabel();

    $elements['submit']['#value'] = $this->t('Deploy to @target', ['@target' => $target_label]);
    $elements['submit']['#submit'] = ['::submitForm', '::deploy'];
    $elements['update'] = [
      '#type' => 'submit',
      '#value' => $this->t('Refresh from @target', ['@target' => $target_label]),
      '#submit' => ['::submitForm', '::update'],
    ];
    $elements['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#attributes' => ['class' => ['button']],
      '#url' => $this->entity->toUrl('collection'),
    ];

    return $elements;
  }

  /**
   * Form submission handler; deploys the content to the workspace's target.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function deploy(array &$form, FormStateInterface $form_state) {
    $workspace = $this->entity;

    try {
      $repository_handler = $workspace->getRepositoryHandlerPlugin();
      $repository_handler->replicate($workspace->getLocalRepositoryHandlerPlugin(), $repository_handler);
      $this->messenger->addMessage($this->t('Successful deployment.'));
    }
    catch (\Exception $e) {
      watchdog_exception('workspace', $e);
      $this->messenger->addMessage($this->t('Deployment error'), 'error');
    }
  }

  /**
   * Form submission handler; pulls the target's content into a workspace.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function update(array &$form, FormStateInterface $form_state) {
    $workspace = $this->entity;

    try {
      $repository_handler = $workspace->getRepositoryHandlerPlugin();
      $repository_handler->replicate($repository_handler, $workspace->getLocalRepositoryHandlerPlugin());
      $this->messenger->addMessage($this->t('Update successful.'));
    }
    catch (\Exception $e) {
      watchdog_exception('workspace', $e);
      $this->messenger->addMessage($this->t('Update error'), 'error');
    }
  }

}
