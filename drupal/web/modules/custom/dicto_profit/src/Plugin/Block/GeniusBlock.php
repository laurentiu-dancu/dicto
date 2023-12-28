<?php


namespace Drupal\dicto_profit\Plugin\Block;


use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\dicto_views\Form\DictoViewsDanknessForm;
use Drupal\dicto_views\Form\DictoViewsSearchForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @Block(
 *   id = "dicto_profit_genius_block",
 *   admin_label = @Translation("Genius Block"),
 *   category = @Translation("Dicto"),
 * )
 */
class GeniusBlock extends BlockBase implements ContainerFactoryPluginInterface {
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * @inheritDoc
   */
  public function build(): array {
    return [
      '#markup' => 'helo',
    ];
  }

  public function getCacheMaxAge(): int {
    return 1;
  }
}
