<?php

namespace Drupal\amp_optimizer\EventSubscriber;

use AmpProject\Optimizer\ErrorCollection;
use AmpProject\Optimizer\TransformationEngine;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Render\HtmlResponse;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class OptimizerSubscriber.
 *
 * @package Drupal\amp_optimizer\EventSubscriber
 */
class OptimizerSubscriber implements EventSubscriberInterface {

  /**
   * Configuration storage name.
   */
  public const CONFIG_NAME = 'amp_optimizer.settings';

  /**
   * Response transformation engine.
   *
   * @var \AmpProject\Optimizer\TransformationEngine
   */
  protected $transformationEngine;

  /**
   * Configs.
   *
   * @var array|mixed
   */
  protected $config;

  /**
   * Logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Error collection.
   *
   * @var \AmpProject\Optimizer\ErrorCollection
   */
  protected $errorCollection;

  /**
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  public function __construct(TransformationEngine $transformation_engine, ConfigFactoryInterface $config_factory, LoggerChannelFactoryInterface $logger_channel_factory, AccountProxyInterface $current_user) {
    $this->transformationEngine = $transformation_engine;
    $this->config = $config_factory->get(static::CONFIG_NAME)->get() ?? [];
    $this->logger = $logger_channel_factory->get('amp_optimizer');
    $this->errorCollection = new ErrorCollection();
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      KernelEvents::RESPONSE => [
        ['optimizer'],
      ],
    ];
  }

  /**
   * Optimizer event subscriber callback.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent|\Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   Event instance.
   */
  public function optimizer($event): void {
    if ($this->currentUser->isAuthenticated()) {
      return;
    }
    if (
      !array_key_exists('transform_enabled', $this->config) ||
      FALSE === (bool) $this->config['transform_enabled'] ||
      !$event->isMasterRequest() ||
      !$this->isAmpHtml($event->getResponse(), $event->getRequest())
    ) {
      return;
    }

    $optimized_html = $this->transformationEngine->optimizeHtml(
      $event->getResponse()->getContent(),
      $this->errorCollection
    );

    $this->handleErrors();

    $event->getResponse()->setContent($optimized_html);
  }

  /**
   * @param Response $response
   * @param Request $request
   *
   * @return bool
   */
  private function isAmpHtml(Response $response, Request $request): bool {
    if (!($response instanceof HtmlResponse) || $request->getRequestFormat() !== 'html') {
      return FALSE;
    }

    return preg_match(
      '/<html.*(\samp(>|\s.*>|=.*>)|\s⚡(>|\s.*>|=.*>))/muU',
      $response->getContent()
    );
  }

  /**
   * Error logger.
   */
  private function handleErrors(): void {
    if ($this->errorCollection->count() > 0) {
      foreach ($this->errorCollection as $error) {
        $this->logger->error(sprintf(
          "AMP-Optimizer Error code: %s\nError Message: %s\n",
          $error->getCode(),
          $error->getMessage()
        ));
      }
    }
  }

}
