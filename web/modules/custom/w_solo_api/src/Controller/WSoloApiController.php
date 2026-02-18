<?php

declare(strict_types=1);

namespace Drupal\w_solo_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Component\Serialization\Json;
use Drupal\Core\File\FileSystemInterface;

/**
 * Returns responses for W solo api routes.
 */
final class WSoloApiController extends ControllerBase {

  /**
   * Builds the response.
   */
  public function __invoke(): array {

    $data = \Drupal::service('w_solo_api.midocean_client')->getProducts();
  dpm($data);
    /** @var \Drupal\Core\File\FileSystemInterface $file_system */
    $file_system = \Drupal::service('file_system');

    $directory = 'public://midocean';
    $file_system->prepareDirectory(
      $directory,
      FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS
    );

    $filepath = $directory . '/products.json';

    file_put_contents(
      $file_system->realpath($filepath),
      Json::encode($data)
    );



    $build['content'] = [
      '#type' => 'item',
      '#markup' => $this->t('It works!'),
    ];

    return $build;
  }

  public function readCache() {
    $file_system = \Drupal::service('file_system');

    $directory = 'public://midocean';
    $filepath = $directory . '/products.json';
    $realpath = $file_system->realpath($filepath);

    if ($realpath && file_exists($realpath)) {
      $data = Json::decode(file_get_contents($realpath));

      $data = $this->filterData($data, 'product_class', 'T-shirt');
      dpm($data);


            $directory = 'public://t-shirt';
            $file_system->prepareDirectory(
              $directory,
              FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS
            );


            foreach ($data as $key => $item) {
              $filepath = $directory . '/' . $item['master_code'] . '.json';

              file_put_contents(
                $file_system->realpath($filepath),
                Json::encode($item)
              );
            }
    }

    // Get variants.
    $colors = [];
    foreach ($data['2289']['variants'] as $variant) {
      if (!in_array($variant['color_code'], $colors)) {
        $colors[] = $variant['color_code'];
      }
    }
    dpm($colors);
    dpm($data['2289']['variants']);

    $build['content'] = [
      '#type' => 'item',
      '#markup' => $this->t('It works!'),
    ];

    return $build;
  }

  public function filterData($data, $field, $value) {
    foreach ($data as $key => $item) {
      if (!is_null($item[$field])
        && !str_contains($item[$field], $value)) {
        unset($data[$key]);
      }
    }

    return $data;
  }


}
