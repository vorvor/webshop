(function (Drupal, $) {
  Drupal.behaviors.imagePrintArea = {
    attach(context) {
      once('imagePrintArea', '#edit-print-area-controls', context).forEach((e) => {

        console.log('printarea js.');
       $('#edit-print-area-controls').append('<div id="add-print-area-paragraph">Add</div>');

        $('#add-print-area-paragraph').click(function () {

          const widget = document.querySelector('.field--name-field-image-placement');
          if (!widget) return;

          const inputs = widget.querySelectorAll('table tbody tr');
          let last = inputs.length ? inputs[inputs.length - 1] : null;

          console.log('LAST' + inputs.length);

          if (last !== null && inputs.length === 1) {
            last = inputs[0];
            $('.field--name-field-print-position input', last).val('left');
          } else {
            const addMore = document.querySelector(
              'input[name="field_image_placement_image_placement_wrapper_add_more"]'
            );
            addMore.dispatchEvent(new MouseEvent('mousedown', {bubbles: true}));
          }

          $(document).on('ajaxComplete', function () {

            // Find the LAST paragraph item within the widget and set its field.
            // Adjust selectors based on your field type:
            console.log('ajaxed.');

            $('.field--name-field-print-position input', last).val('left');
          });
        });
      })
    }
  };
})(Drupal, jQuery, drupalSettings);
