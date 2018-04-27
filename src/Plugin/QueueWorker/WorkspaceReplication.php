<?php

namespace Drupal\workspace\Plugin\QueueWorker;

use Drupal\Component\Datetime\Time;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Session\AccountSwitcherInterface;
use Drupal\replication\Entity\ReplicationLogInterface;
use Drupal\user\Entity\User;
use Drupal\workspace\ReplicatorManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

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

  /**
   * @var \Drupal\workspace\ReplicatorManager
   */
  protected $replicatorManager;

  /**
   * @var \Drupal\Component\Datetime\Time
   */
  protected $time;

  /**
   * @var \Drupal\Core\Session\AccountSwitcherInterface
   */
  protected $accountSwitcher;

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
      $container->get('account_switcher')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ReplicatorManager $replicator_manager, Time $time, AccountSwitcherInterface $account_switcher) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->replicatorManager = $replicator_manager;
    $this->time = $time;
    $this->accountSwitcher = $account_switcher;
  }

  /**
   * @param mixed $data
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function processItem($data) {
    $account = User::load(1);
    $this->accountSwitcher->switchTo($account);
    /**
     * @var \Drupal\workspace\Entity\Replication $replication
     */
    $replication = $data['replication'];
    $replication->setReplicationStatusReplicating();
    $replication->save();
    $response = $this->replicatorManager->doReplication($data['source'], $data['target'], $data['task']);
    if (($response instanceof ReplicationLogInterface) && ($response->get('ok')->value == TRUE)) {
      $replication->setReplicationStatusReplicated();
      $replication->set('replicated', $this->time->getRequestTime());
      $replication->save();
    }
    else {
      $replication->setReplicationStatusFailed();
      $replication->save();
    }
    $this->accountSwitcher->switchBack();
  }

}
