<?php

declare(strict_types=1);

namespace Drupal\w_select_with_image\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a colorselection block.
 */
#[Block(
  id: 'w_select_with_image_colorselection',
  admin_label: new TranslatableMarkup('colorSelection'),
  category: new TranslatableMarkup('Custom'),
)]
final class ColorselectionBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
    $content = '';
    if ($parts[1] == 'node' && is_numeric($parts[2])) {
      $nid = $parts[2];
      $product = $this->entityTypeManager->getStorage('node')->load($nid);
      $masterCode = $product->get('field_master_code')->getValue()[0]['value'];
      $colors = \Drupal::service('w_solo_api.get_colors')->getColors($masterCode);

      foreach ($colors as $color) {
        $colorCode = strtolower($color['color-code']);
          $colorName = $color['term-label'];
          $hex = $color['term-hex'];

          $content .= '<span class="color-dot" title="' . $colorName . '" class="color-dot" data-color-code="'
            . $colorCode . '" data-color-hex="' . $hex . '" style="background:'
            . $hex . '" data-image-front-url="' . $color['image-front'] . '" data-image-back-url="'
            . $color['image-back'] . '" data-image-side-url="' . $color['image-side'] . '"></span>';

          if (!$firstImageFront) {
            $firstImageFront = $color['image-front'];
            $firstImageBack = $color['image-back'];
            $firstImageSide = $color['image-side'];
          }
      }
      $content = '<div id="shirt-image-wrapper">
                        <div id="thumbs-wrapper">
                            <div id="front-thumb" class="thumb"><img id="shirt-image-front-thumb" src="' . $firstImageFront . '"></div>
                            <div id="back-thumb" class="thumb"><img id="shirt-image-back-thumb" src="' . $firstImageBack . '"></div>
                            <div id="side-thumb" class="thumb"><img id="shirt-image-side-thumb" src="' . $firstImageSide . '"></div>
                        </div>
                        <div id="original">
                            <img id="shirt-image" src="' . $firstImageFront . '">
                        </div>
                        </div>
                        <div id="color-selector">'
      . $content;
      $content .= '</div>';
    }
    $build['content'] = [
      '#markup' => Markup::create($content),
      '#allowed_tags' => ['style', 'div', 'span'],
    ];
    return $build;
  }

}
