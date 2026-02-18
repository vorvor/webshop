(function (Drupal, $) {
  Drupal.behaviors.colorPhoto = {
    attach(context) {
      once('colorPhoto', '.view-available-colors', context).forEach((element) => {
        const nodeTitle = $('h1.page-title, h1').first().text().trim();
        const code = nodeTitle
          .split('-')[0]
          .trim()
          .toLowerCase();
        console.log(code);

        $('.view-available-colors .views-row').each(function () {
          if ($('#shirt-image').attr('src') === '') {
            $('#shirt-image').attr('src', `https://cdn1.midocean.com/image/700X700/${code}-` + $('.color-dot', this).data('color-code') + '.jpg');
          }

          $('.color-dot', this)
            .attr('style', 'background:' + $('.color-dot', this).data('color-hex'))
            .click(function () {
              $('#shirt-image').attr('src', `https://cdn1.midocean.com/image/700X700/${code}-` + $(this).data('color-code') + '.jpg');
              $('#shirt-image-alt').text( $('.color-dot', this).data('color-name') + ' shirt')
            });
        })
      });
    }
  };
})(Drupal, jQuery, drupalSettings);
