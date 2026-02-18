<?php

namespace Drupal\w_solo_api\Service;

use Drupal\Component\Serialization\Json;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;

class ColorService {

  public function __construct(
    protected ClientInterface $httpClient,
    protected LoggerInterface $logger,
  ) {}

  public function getColors($masterCode): array {

    $file_system = \Drupal::service('file_system');

    $directory = 'public://t-shirt/';
    $filepath = $directory . '/' . $masterCode . '.json';
    $realpath = $file_system->realpath($filepath);

    if ($realpath && file_exists($realpath)) {
      $data = Json::decode(file_get_contents($realpath));

      $colors = [];
      $colorCodes = [];
      foreach ($data['variants'] as $variant) {
        if (!in_array($variant['color_code'], $colorCodes)) {
          $colorCodes[] = $variant['color_code'];

          $term = \Drupal::entityTypeManager()
            ->getStorage('taxonomy_term')
            ->loadByProperties(['field_color_code' => $variant['color_code'], 'vid' => 't_shirt_colors']);
          $term = reset($term);

          $colors[] = [
            'color-code' => $variant['color_code'],
            'image-front' => $variant['digital_assets'][0]['url'],
            'image-back' => $variant['digital_assets'][1]['url'],
            'image-side' => $variant['digital_assets'][2]['url'],
            'term-label' => ($term) ? $term->label() : '',
            'term-hex' => ($term) ? $term->get('field_color_hex')->value : '',
            'term-id' => ($term) ? $term->id() : '',
          ];
        }
      }

      return $colors;
    }

    return [];
  }
}
