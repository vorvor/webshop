<?php

/**
 * @file
 * Post-update hooks for the File Browser module.
 *
 * This file contains update hooks that run after module updates,
 * such as switching the default icon from PNG to SVG.
 */

use Drupal\Core\File\FileExists;

/**
 * @addtogroup updates-8.x-1.x
 * @{
 */

/**
 * Update default config with default uuid.
 */
function file_browser_post_update_default_uuid() {
  $configuration = \Drupal::configFactory()->getEditable('embed.button.file_browser');
  // Default uuid in the config.
  $uuid = 'db2cad05-1e3b-4b35-b163-99d7d036130c';
  // Set file uuid in the config.
  $configuration->set('icon_uuid', $uuid);
  $configuration->save();
  // Load the file_browser_icon form the storage.
  $files = \Drupal::entityTypeManager()
    ->getStorage('file')
    ->loadByProperties(['uri' => 'public://file_browser_icon.svg']);
  if (!empty($files)) {
    $file = reset($files);
    // Set file uuid same as default config.
    $file->set('uuid', $uuid);
    $file->save();
  }
}

/**
 * @} End of "addtogroup updates-8.x-1.x".
 */

/**
 * Recursively walk through config and replace PNG with SVG.
 */
function _file_browser_replace_icon(&$item, &$changed) {
  if (is_array($item)) {
    foreach ($item as $key => &$value) {
      if ($key === 'value' && $value === 'file_browser_icon.png') {
        $value = 'file_browser_icon.svg';
        $changed = TRUE;
      }
      else {
        _file_browser_replace_icon($value, $changed);
      }
    }
  }
}

/**
 * Switch default icon from PNG to SVG in configuration.
 */
function file_browser_post_update_switch_icon_to_svg(array &$sandbox): void {
  $config_factory = \Drupal::configFactory();

  $configs = [
    'views.view.file_entity_browser',
    'embed.button.file_browser',
  ];

  $changed_any = FALSE;

  foreach ($configs as $config_name) {
    $editable = $config_factory->getEditable($config_name);

    if (!$editable) {
      continue;
    }

    // Get the full config array safely.
    $data = $editable->get();

    $changed = FALSE;

    // Use the helper function to replace PNG with SVG recursively.
    _file_browser_replace_icon($data, $changed);

    if ($changed) {
      // Use setData() instead of setRawData().
      $editable->setData($data)->save();

      $changed_any = TRUE;
      \Drupal::logger('file_browser')->notice(
        "Updated icon reference from PNG to SVG in @config.",
        ['@config' => $config_name]
      );
    }
  }

  // Fallback - if the SVG file doesn’t exist in public://, copy it over.
  $public_svg = 'public://file_browser_icon.svg';

  if (!file_exists($public_svg)) {
    $module_path = \Drupal::service('extension.list.module')->getPath('file_browser');
    $source = $module_path . '/file_browser_icon.svg';

    if (file_exists($source)) {
      \Drupal::service('file_system')->copy($source, $public_svg, FileExists::Replace);
      \Drupal::logger('file_browser')->notice('Restored missing file_browser_icon.svg in public://');
    }
  }
}
