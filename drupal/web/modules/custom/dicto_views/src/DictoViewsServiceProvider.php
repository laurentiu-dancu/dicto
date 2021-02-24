<?php


namespace Drupal\dicto_views;


use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

class DictoViewsServiceProvider extends ServiceProviderBase {
  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    if ($container->hasDefinition('votingapi_reaction.manager')) {
      $definition = $container->getDefinition('votingapi_reaction.manager');
      $definition->setClass('Drupal\dicto_views\ReactionManager');
    }
    if ($container->hasDefinition('amp_event_subscriber')) {
      $definition = $container->getDefinition('amp_event_subscriber');
      $definition->setTags([]);
    }
    if ($container->hasDefinition('form_builder')) {
      $definition = $container->getDefinition('form_builder');
      $definition->setClass('Drupal\dicto_views\Form\DictoFormBuilder');
    }
    if ($container->hasDefinition('http_middleware.cors:')) {
      $definition = $container->getDefinition('http_middleware.cors:');
      $definition->setClass('Drupal\dicto_views\Cors\Cors');
    }
  }
}
