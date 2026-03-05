<?php

declare(strict_types=1);

namespace Drupal\w_solo_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Component\Serialization\Json;
use Drupal\Core\File\FileSystemInterface;
use Drupal\taxonomy\Entity\Term;

/**
 * Returns responses for W solo api routes.
 */
final class WSoloApiController extends ControllerBase {

  /**
   * Builds the response.
   */
  public function __invoke(): array {

    $data = \Drupal::service('w_solo_api.midocean_client')->getProducts();
    //dpm($data);
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


  public function printData(): array {

    $data = \Drupal::service('w_solo_api.midocean_client')->getPrintData();

    /** @var \Drupal\Core\File\FileSystemInterface $file_system */
    $file_system = \Drupal::service('file_system');


    $directory = 'public://midocean';
    $file_system->prepareDirectory(
      $directory,
      FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS
    );

    $filepath = $directory . '/printdata.json';

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
    $filepath = $directory . '/printdata.json';
    $realpath = $file_system->realpath($filepath);

    if ($realpath && file_exists($realpath)) {
      $data = Json::decode(file_get_contents($realpath));
      dpm($data['products'][0]);

     // $data = $this->filterData($data, 'product_class', 'T-shirt');
      //dpm($data);

/*
 * write t-shirt products data separately to files
 * /
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
*/
    }

    /*

    $file_system = \Drupal::service('file_system');

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

//dpm($flatCPM);

    $colorCodes = [];
    $missing = [];
    foreach ($data as $key => $item) {
      //dpm($item['master_code'] . ':' . $key);
      foreach ($item['variants'] as $variant) {
        $hexV = '???';
        if (isset($variant['color_group'])) {
          $hexV = $flatCPM[strtolower(preg_replace('/[^0-9]/', '', $variant['color_group']))];
        } elseif (isset($variant['pms_color'])) {
          $hexV = $flatCPM[strtolower(preg_replace('/[^0-9]/', '', $variant['pms_color']))];
        }

        if (empty($hexV)) {
          $hexV = $flatCPM[strtolower(preg_replace('/[^0-9]/', '', $variant['color_group']))];
        }

        if ($hexV == '???' || empty($hexV)) {
          $missing[] = $variant['color_code'] . ':' . $variant['color_group'] . ':' . $variant['pms_color'];
        }

        $vals = $variant['color_code'] . ':' . $variant['color_group'] . ':' . $variant['pms_color'] . ':' . $hexV;
        if (!in_array($vals, $colorCodes)) {
          $colorCodes[] = $vals;
        }
      }
    }

    dpm('missing.');
    dpm($missing);

    //dpm($colorCodes);

    shuffle($data);              // randomize order
    $data = array_slice($data, 0, 50);

    */




// Write one product to file cache
/*
 *
 * foreach ($data as $key => $item) {
      if ($item['master_code'] == 'MO8422') {
        break;
      }
    }

    $directory = 'public://other';
    $file_system->prepareDirectory(
      $directory,
      FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS
    );


      $item = $data[$key];
      $filepath = $directory . '/' . $item['master_code'] . '.json';

      file_put_contents(
        $file_system->realpath($filepath),
        Json::encode($item)
      );

*/
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

  public function colorsToDb() {
    $file_system = \Drupal::service('file_system');

    $directory = 'public://midocean';
    $filepath = $directory . '/products.json';
    $realpath = $file_system->realpath($filepath);

    $data = Json::decode(file_get_contents($realpath));

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

    $colorCodes = [];
    $missing = [];

    $tids = \Drupal::entityQuery('taxonomy_term')
      ->condition('vid', 'colors')
      ->accessCheck(FALSE)
      ->execute();

    if (!empty($tids)) {
      $terms = Term::loadMultiple($tids);
      foreach ($terms as $term) {
        $term->delete();
      }
    }

    foreach ($data as $key => $item) {
      //dpm($item['master_code'] . ':' . $key);
      foreach ($item['variants'] as $variant) {
        $hexV = '???';
        if (isset($variant['pms_color'])) {
          $hexV = $flatCPM[strtolower(preg_replace('/[^0-9]/', '', $variant['pms_color']))];
        } elseif (isset($variant['color_group'])) {
          $hexV = $flatCPM[strtolower($variant['color_group'])];
        }

        if (empty($hexV)) {
          $hexV = $flatCPM[strtolower(preg_replace('/[^0-9]/', '', $variant['color_group']))];
        }

        if ($hexV == '???' || empty($hexV)) {
          $missing[] = $variant['color_code'] . ':' . $variant['color_group'] . ':' . $variant['pms_color'];
        }

        $vals = $variant['color_code'] . ':' . $variant['color_group'] . ':' . $variant['pms_color'] . ':' . $hexV;
        if (!in_array($vals, $colorCodes)) {
          $colorCodes[] = $vals;

          $storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
          $existing = $storage->loadByProperties([
            'vid' => 'colors',
            'name' => $variant['color_code'],
          ]);

          $term = $existing ? reset($existing) : FALSE;

          if (!$term) {
            Term::create([
              'vid' => 'colors',
              'name' => $variant['color_description'],
              'field_color_group' => $variant['color_group'],
              'field_color_pms' => $variant['pms_color'],
              'field_color_hex' => $hexV,
              'field_color_code' => $variant['color_code'],
            ])->save();
          }
        }
      }
    }

    $build['content'] = [
      '#type' => 'item',
      '#markup' => $this->t('It works!'),
    ];

    return $build;
  }
}
