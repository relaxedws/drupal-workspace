<?php

namespace Drupal\workspace\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\workspace\Replication\ReplicationManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Provides the workspace deploy form.
 */
class WorkspaceDeployForm extends ContentEntityForm {

  /**
   * The upstream plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $upstreamPluginManager;

  /**
   * The workspace replication manager.
   *
   * @var \Drupal\workspace\Replication\ReplicationManager
   */
  protected $workspaceReplicationManager;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Constructs a new WorkspaceDeployForm.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $upstream_plugin_manager
   *   The upstream plugin manager.
   * @param \Drupal\workspace\Replication\ReplicationManager $replication_manager
   *   The workspace replication manager.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(PluginManagerInterface $upstream_plugin_manager, ReplicationManager $replication_manager, TimeInterface $time) {
    $this->upstreamPluginManager = $upstream_plugin_manager;
    $this->workspaceReplicationManager = $replication_manager;
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.workspace.upstream'),
      $container->get('workspace.replication_manager'),
      $container->get('datetime.time')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /* @var \Drupal\workspace\Entity\WorkspaceInterface $workspace */
    $workspace = $this->entity;

    if (empty($workspace->upstream->value) || 'local_workspace:' . $workspace->id() == $workspace->upstream->value) {
      throw new BadRequestHttpException();
    }

    $source_upstream = $this->upstreamPluginManager->createInstance('local_workspace:' . $workspace->id());
    $target_upstream = $this->upstreamPluginManager->createInstance($workspace->upstream->value);

    $form['help'] = [
      '#markup' => $this->t('Deploy all %source_upstream_label content to %target_upstream_label, or update %source_upstream_label with content from %target_upstream_label.', ['%source_upstream_label' => $source_upstream->getLabel(), '%target_upstream_label' => $target_upstream->getLabel()]),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function actions(array $form, FormStateInterface $form_state) {
    $elements = parent::actions($form, $form_state);

    $upstream_plugin = $this->upstreamPluginManager->createInstance($this->entity->upstream->value);

    $elements['submit']['#value'] = $this->t('Deploy to @upstream', ['@upstream' => $upstream_plugin->getLabel()]);
    $elements['submit']['#submit'] = ['::submitForm', '::deploy'];
    $elements['update'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update from @upstream', ['@upstream' => $upstream_plugin->getLabel()]),
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
   * Form submission handler; deploys the content to the upstream workspace.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function deploy(array &$form, FormStateInterface $form_state) {
    /* @var \Drupal\workspace\Entity\WorkspaceInterface $workspace */
    $workspace = $this->entity;

    try {
      $this->workspaceReplicationManager->replicate(
        $this->upstreamPluginManager->createInstance('local_workspace:' . $workspace->id()),
        $this->upstreamPluginManager->createInstance($workspace->upstream->value)
      );
      drupal_set_message($this->t('Successful deployment.'));
    }
    catch (\Exception $e) {
      watchdog_exception('workspace', $e);
      drupal_set_message($this->t('Deployment error'), 'error');
    }
  }

  /**
   * Form submission handler; pulls the upstream content into a workspace.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function update(array &$form, FormStateInterface $form_state) {
    /* @var \Drupal\workspace\Entity\WorkspaceInterface $workspace */
    $workspace = $this->entity;

    try {
      $this->workspaceReplicationManager->replicate(
        $this->upstreamPluginManager->createInstance($workspace->upstream->value),
        $this->upstreamPluginManager->createInstance('local_workspace:' . $workspace->id())
      );
      drupal_set_message($this->t('Update successful.'));
    }
    catch (\Exception $e) {
      watchdog_exception('workspace', $e);
      drupal_set_message($this->t('Update error'), 'error');
    }
  }

}
