(function (Drupal, $, drupalSettings, once) {
  'use strict';

  const SUBFORM_SELECTOR = '.paragraphs-subform';
  const SELECT_SELECTOR = `${SUBFORM_SELECTOR} select`;

  const AMOUNT_CLASS_RE = /^field--name-field-(.+)-amount$/;

  function getAmountFields($scope) {
    return $scope
      .find('[class*="field--name-field-"]')
      .filter(function () {
        // Match any element that has a class like field--name-field-<size>-amount
        return this.className
          .split(/\s+/)
          .some((cls) => AMOUNT_CLASS_RE.test(cls));
      });
  }

  function extractSizeFromEl(el) {
    const cls = el.className
      .split(/\s+/)
      .find((c) => AMOUNT_CLASS_RE.test(c));
    if (!cls) return null;

    const match = cls.match(AMOUNT_CLASS_RE);
    return match ? match[1] : null; // xxs/xs/s...
  }

  function isEmptyValue(val) {
    return val === '' || val === '0' || val == null;
  }

  function getSizesForColor(availableColors, colorId) {
    if (isEmptyValue(colorId)) return [];

    const entry = availableColors?.[colorId];
    const sizes = entry?.sizes;

    return Array.isArray(sizes) ? sizes : [];
  }

  function applyAmountVisibility($subform, sizes) {
    const sizesUpper = new Set((sizes || []).map((s) => String(s).toUpperCase()));

    getAmountFields($subform).each(function () {
      const size = extractSizeFromEl(this);
      const shouldShow = size && sizesUpper.has(String(size).toUpperCase());
      $(this).toggle(!!shouldShow);
    });
  }

  function initSelect2($select, colors, availableColors) {
    if ($select.data('select2')) {
      $select.select2('destroy');
    }

    function formatColorOption(data) {
      const idStr = String(data.id);
      const idNum = Number(data.id);

      const isAvailable = Array.isArray(availableColors)
        ? (availableColors.includes(idStr) || availableColors.includes(idNum))
        : Object.prototype.hasOwnProperty.call(availableColors ?? {}, idStr);

      // Keep behavior identical: don't filter out unavailable options (same as your commented return null)
      if (!isAvailable && data.id !== '') {
        // return null;
      }

      const dotColor = colors?.[data.id] ?? '';
      const dot = `<span class="color-dot" style="background:${dotColor}"></span>`;
      return `<span class="color-option">${dot}${data.text}</span>`;
    }

    $select.select2({
      width: '100%',
      placeholder: 'Select color',
      templateResult: formatColorOption,
      templateSelection: formatColorOption,
      escapeMarkup: (m) => m,
    });
  }

  Drupal.behaviors.colorSwatches = {
    attach(context) {
      const colors = drupalSettings.colorSelect2;
      const availableColors = drupalSettings.availableColors;

      once('colorSelect2', context.querySelectorAll(SELECT_SELECTOR)).forEach((select) => {
        const $select = $(select);
        const $subform = $select.closest(SUBFORM_SELECTOR);

        console.log('Color select loaded.');

        initSelect2($select, colors, availableColors);

        // Initial visibility based on current select value
        applyAmountVisibility($subform, getSizesForColor(availableColors, $select.val()));

        // Update on change
        $select.on('change', function () {
          const val = $(this).val();
          const sizes = getSizesForColor(availableColors, val);

          console.log(sizes);
          applyAmountVisibility($subform, sizes);
        });
      });
    }
  };
})(Drupal, jQuery, drupalSettings, once);
