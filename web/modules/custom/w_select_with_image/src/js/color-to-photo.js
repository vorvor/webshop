(function (Drupal, $) {
  Drupal.behaviors.colorPhoto = {
    attach(context) {
      once('colorPhoto', '.block-w-select-with-image-colorselection', context).forEach((element) => {

        console.log('colorphoto loaded.');
        $('.color-dot').each(function (e) {
          $(this).click(function () {
            console.log( $(this).data('image-0-url'));
            $('#shirt-image').attr('src', $(this).data('image-0-url'));

            for (let c = 0; c < 20; c++) {
              let url = $(this).data('image-' + c + '-url');
              if (url === undefined) {
                break;
              }

              console.log(c + ': ' + url);
              $('#shirt-image-wrapper .thumb-' + c + ' img').attr('src', url);
            }

            $('#shirt-image-alt').text($(this).attr('title'));

            /*
            $('#shirt-image-front-thumb').attr('src', $(this).data('image-front-url'));
            $('#shirt-image-back-thumb').attr('src', $(this).data('image-back-url'));
            $('#shirt-image-side-thumb').attr('src', $(this).data('image-side-url')); */

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
