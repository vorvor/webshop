<?php

namespace Drupal\w_solo_api\Service;

use Drupal\Component\Serialization\Json;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;

class DetailsService {

  public function __construct(
    protected ClientInterface $httpClient,
    protected LoggerInterface $logger,
  ) {}

  public function getDetails($masterCode): array {

    $file_system = \Drupal::service('file_system');

    $directory = 'public://t-shirt/';
    $filepath = $directory . '/' . $masterCode . '.json';
    $realpath = $file_system->realpath($filepath);

    if ($realpath && file_exists($realpath)) {
      $data = Json::decode(file_get_contents($realpath));

      $details = [
        'short' => $data['short_description'],
        'long' => $data['long_description'],
      ];
    }

    return $details ?? [];
  }
}
