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
  }
}
