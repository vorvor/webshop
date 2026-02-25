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
    $colorsContent = '';
    if ($parts[1] == 'node' && is_numeric($parts[2])) {
      $nid = $parts[2];
      $product = $this->entityTypeManager->getStorage('node')->load($nid);
      $masterCode = $product->get('field_master_code')->getValue()[0]['value'];
      $colors = \Drupal::service('w_solo_api.get_colors')->getColors($masterCode);

      foreach ($colors as $color) {
        $colorCode = strtolower($color['color-code']);
          $colorName = $color['term-label'];
          $hex = $color['term-hex'];

          $images = [];
          foreach ($color['images'] as $key => $image) {
            $images[] = 'data-image-' . $key . '-url="' . $image['url'] . '"';
          }

        $colorsContent .= '<span class="color-dot" title="' . $colorName . '" class="color-dot" data-color-code="'
          . $colorCode . '" data-color-hex="' . $hex . '" style="background:'
          . $hex . '" ' . implode(' ', $images) . '></span>';
      }

      $content = '<div id="shirt-image-wrapper">
                        <div id="thumbs-wrapper">';

      foreach ($colors[0]['images'] as $key =>$image) {
        $content .= '<div class="thumb thumb-' . $key . '"><img src="' . $image['url'] . '"></div>';
      }

      $content .= '                     </div>
                        <div id="original">
                            <img id="shirt-image" src="' . $colors[0]['images'][0]['url'] . '">
                        </div>
                        </div>
                        <div id="color-selector">'
      . $colorsContent;



      $content .= '</div>';
    }
    $build['content'] = [
      '#markup' => Markup::create($content),
      '#allowed_tags' => ['style', 'div', 'span'],
    ];
    return $build;
  }

}
