(function (Drupal, $) {
  Drupal.behaviors.colorSwatches = {
    attach(context) {
      once('colorSelect2', context.querySelectorAll('select'))
        .forEach((select) => {
          const $select = $(select);

          console.log('loaded.');

        if ($select.data('select2')) {
          $select.select2('destroy');
        }

        console.log($select);
        const colors = drupalSettings.colorSelect2;
        $select.select2({
          width: '100%',
          placeholder: "Select color",
          templateResult: formatColorOption,
          templateSelection: formatColorOption,
          escapeMarkup: (m) => m,
        });

        function formatColorOption(data) {
          const dot = `<span class="color-dot" style="background:${colors[data.id]}"></span>`;
          return `<span class="color-option">${dot}${data.text}</span>`;
        }
      });
    }
  };
})(Drupal, jQuery, drupalSettings);
