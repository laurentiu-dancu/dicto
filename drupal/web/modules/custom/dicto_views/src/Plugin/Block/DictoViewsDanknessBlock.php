<?php


namespace Drupal\dicto_views\Plugin\Block;


use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\dicto_views\Form\DictoViewsDanknessForm;
use Drupal\dicto_views\Form\DictoViewsSearchForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Hello' Block.
 *
 * @Block(
 *   id = "dicto_views_dankness_block",
 *   admin_label = @Translation("Dankness Block"),
 *   category = @Translation("Dicto"),
 * )
 */
class DictoViewsDanknessBlock extends BlockBase implements ContainerFactoryPluginInterface {
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * @inheritDoc
   */
  public function build() {
    return [
      '#markup' => 'helo',
    ];
  }
}
