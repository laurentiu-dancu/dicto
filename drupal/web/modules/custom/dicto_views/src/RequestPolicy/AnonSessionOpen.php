<?php

namespace Drupal\dicto_views\RequestPolicy;

use Drupal\Core\PageCache\RequestPolicyInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Session\SessionConfigurationInterface;
use Drupal\user\ContextProvider\CurrentUserContext;
use Symfony\Component\HttpFoundation\Request;

/**
 * A policy allowing delivery of cached pages when there is no session open.
 *
 * Do not serve cached pages to authenticated users, or to anonymous users when
 * $_SESSION is non-empty. $_SESSION may contain status messages from a form
 * submission, the contents of a shopping cart, or other user-specific content
 * that should not be cached and displayed to other users.
 */
class AnonSessionOpen implements RequestPolicyInterface {

  protected AccountProxyInterface $currentUser;

  /**
   * AnonSessionOpen constructor.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   */
  public function __construct(AccountProxyInterface $currentUser) {
    $this->currentUser = $currentUser;
  }

  /**
   * {@inheritdoc}
   */
  public function check(Request $request) {
    if (str_starts_with($request->getPathInfo(), '/random')) {
      return null;
    }
    if ($this->currentUser->isAnonymous() && !str_starts_with($request->getPathInfo(), '/admin')) {
      return static::ALLOW;
    }

    return NULL;
  }

}
