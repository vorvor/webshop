(function (Drupal, $) {
  Drupal.behaviors.colorPhoto = {
    attach(context) {
      once('colorPhoto', '.block-w-select-with-image-colorselection', context).forEach((element) => {

        console.log('colorphoto loaded.');
        $('.color-dot').each(function (e) {
          $(this).click(function () {
            $('#shirt-image').attr('src', $(this).data('image-front-url'));
            $('#shirt-image-front-thumb').attr('src', $(this).data('image-front-url'));
            $('#shirt-image-back-thumb').attr('src', $(this).data('image-back-url'));
            $('#shirt-image-side-thumb').attr('src', $(this).data('image-side-url'));
            $('#shirt-image-alt').text($(this).attr('title'));
          });
        })

        $('.thumb').each(function (e) {
          console.log('thumb clicked.');

          $(this).click(function () {
            console.log($('img', this).attr('src'));

            $('#shirt-image').attr('src', $('img', this).attr('src'));
          })
        });
      });
    }
  }
})(Drupal, jQuery, drupalSettings);
