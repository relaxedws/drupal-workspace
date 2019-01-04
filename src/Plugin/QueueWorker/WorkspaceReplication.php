<?php

namespace Drupal\workspace\Plugin\QueueWorker;

use Drupal\Component\Datetime\Time;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Queue\RequeueException;
use Drupal\Core\Session\AccountSwitcherInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Utility\Error;
use Drupal\multiversion\Entity\Workspace;
use Drupal\multiversion\Workspace\WorkspaceManagerInterface;
use Drupal\replication\Entity\ReplicationLogInterface;
use Drupal\user\Entity\User;
use Drupal\workspace\Entity\Replication;
use Drupal\workspace\Event\ReplicationEvent;
use Drupal\workspace\Event\ReplicationEvents;
use Drupal\workspace\ReplicatorManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class WorkspaceReplication.
 *
 * @QueueWorker(
 *   id = "workspace_replication",
 *   title = @Translation("Queue of replications"),
 *   cron = {"time" = 600}
 * )
 */
class WorkspaceReplication extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The replicator manager.
   *
   * @var \Drupal\workspace\ReplicatorManager
   */
  protected $replicatorManager;

  /**
   * Time service.
   *
   * @var \Drupal\Component\Datetime\Time
   */
  protected $time;

  /**
   * The service for safe account switching.
   *
   * @var \Drupal\Core\Session\AccountSwitcherInterface
   */
  protected $accountSwitcher;

  /**
   * State system service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  private $state;

  /**
   * The logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\multiversion\Workspace\WorkspaceManagerInterface
   */
  protected $workspaceManager;

  /**
   * @var string
   */
  protected $workspaceDefault;

  /**
   * The event dispatcher service.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('workspace.replicator_manager'),
      $container->get('datetime.time'),
      $container->get('account_switcher'),
      $container->get('state'),
      $container->get('logger.factory'),
      $container->get('entity_type.manager'),
      $container->get('workspace.manager'),
      $container->getParameter('workspace.default'),
      $container->get('event_dispatcher')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ReplicatorManager $replicator_manager, Time $time, AccountSwitcherInterface $account_switcher, StateInterface $state, LoggerChannelFactoryInterface $logger, EntityTypeManagerInterface $entity_type_manager, WorkspaceManagerInterface $workspace_manager, $workspace_default, EventDispatcherInterface $event_dispatcher) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->replicatorManager = $replicator_manager;
    $this->time = $time;
    $this->accountSwitcher = $account_switcher;
    $this->state = $state;
    $this->logger = $logger->get('workspace');
    $this->entityTypeManager = $entity_type_manager;
    $this->workspaceManager = $workspace_manager;
    $this->workspaceDefault = $workspace_default;
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function processItem($data) {
    if ($this->state->get('workspace.last_replication_failed', FALSE)) {
      // Requeue if replication blocked.
      throw new RequeueException('Replication is blocked!');
    }

    /** @var \Drupal\workspace\Entity\Replication $replication */
    $replication = $data['replication'];

    if ($replication_new = $this->entityTypeManager->getStorage('replication')->load($replication->id())) {
      $replication = $replication_new;
    }

    if ($replication->get('replication_status')->value == Replication::QUEUED) {
      $account = User::load(1);
      $this->accountSwitcher->switchTo($account);

      $replication->setReplicationStatusReplicating()->save();
      $this->logger->info('Replication "@replication" has started.', ['@replication' => $replication->label()]);

      $this->eventDispatcher->dispatch(ReplicationEvents::PRE_REPLICATION, new ReplicationEvent($replication));

      $response = FALSE;
      try {
        $response = $this->replicatorManager->doReplication($replication, $data['task']);
      }
      catch (\Exception $e) {
        // When exception is thrown during replication process we want
        // replication to be marked as failed and removed from queue.
        $this->logger->error('%type: @message in %function (line %line of %file).', $variables = Error::decodeException($e));
        $replication->set('fail_info', $e->getMessage());
        $replication->setArchiveSource(FALSE);
        $replication->save();
      }

      if (($response instanceof ReplicationLogInterface) && ($response->get('ok')->value == TRUE)) {
        $default_workspace = Workspace::load($this->workspaceDefault);
        if ($replication->getArchiveSource() && !empty($replication->get('source')->entity->getWorkspace())) {
          $source_workspace = $replication->get('source')->entity->getWorkspace();
          $source_workspace->setUnpublished()->save();
          if ($source_workspace->id() != $this->workspaceDefault) {
            $this->workspaceManager->setActiveWorkspace($default_workspace);
            drupal_set_message($this->t('Workspace %workspace has been archived and workspace %default has been set as active.',
              [
                '%workspace' => $replication->get('source')->entity->label(),
                '%default' => $default_workspace->label(),
              ]
            ));
          }
          else {
            drupal_set_message($this->t('Workspace %workspace has been archived.',
              [
                '%workspace' => $replication->get('source')->entity->label(),
              ]
            ));
          }
        }
        $replication->setReplicationStatusReplicated();
        $replication->set('replicated', $this->time->getRequestTime());
        $replication->save();
        $this->logger->info('Replication "@replication" has finished successfully.', ['@replication' => $replication->label()]);
      }
      else {
        if (($response instanceof ReplicationLogInterface) && !empty($response->history->fail_info)) {
          $replication->set('fail_info', $response->history->fail_info);
        }
        $replication->setReplicationStatusFailed();
        $replication->set('replicated', $this->time->getRequestTime());
        $replication->setArchiveSource(FALSE);
        $replication->save();
        $this->state->set('workspace.last_replication_failed', TRUE);
        $this->logger->info('Replication "@replication" has failed.', ['@replication' => $replication->label()]);
      }

      $this->eventDispatcher->dispatch(ReplicationEvents::POST_REPLICATION, new ReplicationEvent($replication));

      $this->accountSwitcher->switchBack();
    }
    else {
      // Requeue if replication is in progress.
      $this->logger->info('Replication "@replication" is already in progress.', ['@replication' => $replication->label()]);
      throw new RequeueException('Replication is already in progress!');
    }
  }

}
