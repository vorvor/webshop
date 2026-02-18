<?php

namespace Drupal\w_solo_api\Plugin\views\field;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @ViewsField("master_product_from_master_id")
 */
class MasterProductFromMasterId extends FieldPluginBase {

  /** @var \Drupal\Core\Entity\EntityTypeManagerInterface */
  protected $entityTypeManager;

  // IMPORTANT: do NOT redeclare $renderer with a type. Parent already has it.
  // protected $renderer; // not needed if FieldPluginBase already defines it

  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, RendererInterface $renderer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
    $this->renderer = $renderer; // uses parent property
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('renderer')
    );
  }

  public function query() {}
  public function render(ResultRow $values) {
    // Assumes the view row is a node. If your base table is node_field_data, this is true.
    $node = $values->_entity ?? NULL;
    if (!$node || !$node->hasField('field_master_code') || $node->get('field_master_code')->isEmpty()) {
      return [];
    }

    $masterCode = $node->get('field_master_code')->value;
    $colors = \Drupal::service('w_solo_api.get_colors')->getColors($masterCode);
    $details = \Drupal::service('w_solo_api.get_details')->getDetails($masterCode);
    $random_color = $colors[array_rand($colors)];

    // Build output: render whatever fields you want from the loaded product.
    // Replace these with your actual field names.
    $build = [
      '#type' => 'container',
      '#attributes' => ['class' => ['master-product']],
      'title' => [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#attributes' => ['class' => ['master-product__title']],
        '#value' => '', //$node->label(),
      ],
      'image' => ['#markup' => (string) ('<a href="' . $node->toUrl()->toString() . '"><img src="' . $random_color['image-front'] . '"></a>' ?? '')],
      'details' => ['#markup' => (string) ('<a class="details" href="' . $node->toUrl()->toString() . '"><div id="detail-short">' . $details['short'] . '</div></a>' ?? '')],
    ];

// IMPORTANT: render to string for Views field output.
    return [
      '#markup' => $this->renderer->render($build),
    ];
  }

}
