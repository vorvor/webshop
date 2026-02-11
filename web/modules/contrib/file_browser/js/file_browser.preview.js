/**
 * @file
 * file_browser.preview.js
 */

(($, Drupal) => {
  /**
   * Contains logic for the view widget.
   */
  Drupal.behaviors.fileBrowserPreview = {
    attach(context, settings) {
      const wrapper = once(
        'file-browser-preview',
        '#file-browser-preview-wrapper',
      );
      if (wrapper.length) {
        $(wrapper)
          .find('select')
          .on('change', () => {
            Drupal.ajax({
              url: `${settings.file_browser.preview_path} / ${this.val()}`,
              wrapper: 'file-browser-preview-wrapper',
            }).execute();
          });
      }
    },
  };
})(jQuery, Drupal);
