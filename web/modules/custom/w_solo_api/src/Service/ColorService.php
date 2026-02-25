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

    // Get PMS to Hex list to array.
    $directory = 'public://';
    $filepath = $directory . 'colorCodes.json';
    $realpath = $file_system->realpath($filepath);

    if ($realpath && file_exists($realpath)) {
      $colorPM = Json::decode(file_get_contents($realpath));
    }

    $flatCPM = ['red' => '#FF0000', 'green' => '#00FF00', 'silver' => '#C0C0C0',
      'yellow' => '#FFFF00', 'blue' => '#0000FF',
      'white' => '#FFFFFF', 'black' => '#000000', 'cork color' => '#ffffff'];
    foreach ($colorPM as $key => $item) {
      $flatCPM[strtolower(preg_replace('/[^0-9]/', '', $item['name']))] = $item['hex'];
    }
    //dpm($colorPM);

    // Get T-shirts.
    $directory = 'public://t-shirt/';
    $filepath = $directory . '/' . $masterCode . '.json';
    $realpath = $file_system->realpath($filepath);

    if ($realpath && file_exists($realpath)) {
      $data = Json::decode(file_get_contents($realpath));

      $colors = [];
      $colorCodes = [];

      dpm($data);

      foreach ($data['variants'] as $variant) {
        if (!in_array($variant['color_code'], $colorCodes)) {
          $colorCodes[] = $variant['color_code'];

          if (isset($variant['pms_color'])) {
            $hexV = $flatCPM[strtolower(preg_replace('/[^0-9]/', '', $variant['pms_color']))];
          } elseif (isset($variant['color_group'])) {
            $hexV = $flatCPM[strtolower($variant['color_group'])];
          }

          if (empty($hexV)) {
            $hexV = $flatCPM[strtolower(preg_replace('/[^0-9]/', '', $variant['color_group']))];
          }

          $colors[] = [
            'color-code' => $variant['color_code'],
            'pms' => $variant['pms_color'],
            'image-front' => $variant['digital_assets'][0]['url'],
            'image-back' => $variant['digital_assets'][1]['url'],
            'image-side' => $variant['digital_assets'][2]['url'],
            'images' => $variant['digital_assets'],
            'term-label' => $variant['color_description'],
            'term-hex' => $hexV,
          ];
        }
      }

      return $colors;
    }

    return [];
  }
}
