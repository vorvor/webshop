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
      foreach ($data['variants'] as $variant) {
        if (!in_array($variant['color_code'], $colors)) {
          $colors[] = $variant['color_code'];
        }
      }

      return $colors;
    }

    return [];
  }
}
