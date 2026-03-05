<?php

declare(strict_types=1);

namespace Drupal\w_image_editor\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a details block.
 */
#[Block(
  id: 'w_image_editor_print_area',
  admin_label: new TranslatableMarkup('printArea'),
  category: new TranslatableMarkup('Custom'),
)]
final class PrintAreaBlock extends BlockBase implements ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
          $plugin_id,
          $plugin_definition,
    protected CurrentPathStack $currentPath,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('path.current'),
      $container->get('entity_type.manager')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $parts = explode('/', $this->currentPath->getPath());
    if ($parts[1] == 'node' && is_numeric($parts[2])) {
      $nid = $parts[2];
      $product = $this->entityTypeManager->getStorage('node')->load($nid);
      $masterCode = $product->get('field_master_code')->getValue()[0]['value'];
      $printAreas = \Drupal::service('w_solo_api.get_print_areas')->getPrintArea($masterCode);

      //dpm($printAreas);
      $content = 'Print areas';
    }
    $build['content'] = [
      '#markup' => Markup::create($content),
      '#allowed_tags' => ['style', 'div', 'span'],
    ];
    return $build;
  }

}
