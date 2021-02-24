<?php


namespace Drupal\dicto_views\EventSubscriber;


use Drupal\Core\Routing\LocalRedirectResponse;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ResponseEventSubscriber implements EventSubscriberInterface {
  public function onResponse(ResponseEvent $event) {
    $response = $event->getResponse();
    if (!$response instanceof LocalRedirectResponse) {
      return;
    }
    $request = $event->getRequest();
    if ($request->query->get('__amp_source_origin') === null) {
      return;
    }
    $reflection = new \ReflectionProperty(LocalRedirectResponse::class, 'targetUrl');
    $reflection->setAccessible(true);
    $response->headers->set('AMP-Redirect-To', $reflection->getValue($response));
    $response->setStatusCode(200);
    $response->setContent(json_encode(['redirecting']));
  }

  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = array('onResponse');
    return $events;
  }
}
