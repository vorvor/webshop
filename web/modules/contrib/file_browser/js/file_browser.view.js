/**
 * @file
 * file_browser.view.js
 */

(($, _, Backbone, Drupal) => {
  /**
   * Renders the file counter based on our internally tracked count.
   */
  function renderFileCounter() {
    document
      .querySelectorAll('.file-browser-file-counter')
      .forEach((el) => el.remove());

    const counter = {};

    document
      .querySelectorAll('.entities-list [data-entity-id]')
      .forEach((element) => {
        const entityId = element.dataset.entityId;

        if (counter[entityId]) {
          counter[entityId] += 1;
        } else {
          counter[entityId] = 1;
        }
      });

    Object.keys(counter).forEach((id) => {
      const count = counter[id];

      if (count > 0) {
        const text = Drupal.formatPlural(
          count,
          'Selected one time',
          'Selected @count times',
        );

        const counterElement = document.createElement('div');

        counterElement.className = 'file-browser-file-counter';
        counterElement.textContent = text;

        const gridItemInfo = document
          .querySelector(`[name = "entity_browser_select[file:${id}]"]`)
          .closest('.grid-item')
          .querySelector('.grid-item-info');

        if (gridItemInfo) {
          gridItemInfo.insertAdjacentElement('afterbegin', counterElement);
        }
      }
    });
  }

  /**
   * Adjusts the padding on the body to account for the fixed actions bar.
   */
  function adjustBodyPadding() {
    setTimeout(() => {
      const bodyElement = document.querySelector('body');
      const actionsElement = document.querySelector('.file-browser-actions');
      const actionsHeight = actionsElement ? actionsElement.offsetHeight : 0;

      bodyElement.style.paddingBottom = `${actionsHeight}px`;
    }, 2000);
  }

  /**
   * Initializes Masonry for the view widget.
   */
  Drupal.behaviors.fileBrowserMasonry = {
    attach(context) {
      const $item = $('.grid-item', context);
      const view = once('file-browser-init', $item.parent());

      if (view.length) {
        const $view = $(view);

        $view.prepend(
          '<div class="grid-sizer"></div><div class="gutter-sizer"></div>',
        );

        // Indicate that images are loading.
        $view.append(
          '<div class="ajax-progress ajax-progress-fullscreen">&nbsp;</div>',
        );

        $view.imagesLoaded(() => {
          // Save the scroll position.
          const scroll = document.body.scrollTop;

          // Remove old Masonry object if it exists. This allows modules like
          // Views Infinite Scroll to function with File Browser.
          if ($view.data('masonry')) {
            $view.masonry('destroy');
          }

          $view.masonry({
            columnWidth: '.grid-sizer',
            gutter: '.gutter-sizer',
            itemSelector: '.grid-item',
            percentPosition: true,
            isFitWidth: true,
          });

          // Jump to the old scroll position.
          document.body.scrollTop = scroll;

          // Add a class to reveal the loaded images, which avoids FOUC.
          $item.addClass('item-style');
          $view.find('.ajax-progress').remove();
        });
      }

      // add thumbnail if file not image
      if ($('.file-browser-actions .entities-list')) {
        const thumbnailWrapper = $('.file-browser-actions .entities-list div');

        for (let i = 0; i < thumbnailWrapper.length; i++) {
          if (
            typeof $(thumbnailWrapper[i]).find('img')[0] === 'undefined' ||
            $(thumbnailWrapper[i]).find('img')[0] === undefined
          ) {
            $(thumbnailWrapper[i]).prepend(
              '<img·style="width:100px;·height:100px;"·src="/modules/contrib/file_browser/images/document_placeholder.svg"/>',
            );
          }
        }
      }
    },
  };

  /**
   * Tracks when entities have been added or removed in the multi-step form,
   * and displays that information on each grid item.
   */
  Drupal.behaviors.fileBrowserEntityCount = {
    attach(context) {
      adjustBodyPadding();
      renderFileCounter();

      // Indicate when files have been selected.
      const entities = once(
        'file-browser-add-count',
        '.entities-list',
        context,
      );

      if (entities.length) {
        entities.forEach((entity) => {
          entity.addEventListener('add-entities', () => {
            adjustBodyPadding();
            renderFileCounter();
          });
        });

        entities.forEach((entity) => {
          entity.addEventListener('remove-entities', () => {
            adjustBodyPadding();
            renderFileCounter();
          });
        });
      }
    },
  };

  const Selection = Backbone.View.extend({
    events: {
      'click .grid-item': 'onClick',
      'dblclick .grid-item': 'onClick',
    },

    initialize() {
      // This view must be created on an element which has this attribute.
      // Otherwise, things will blow up and rightfully so.
      this.uuid = this.el.getAttribute('data-entity-browser-uuid');

      // If we're in an iFrame, reach into the parent window context to get the
      // settings for this entity browser.
      const settings = (window.frameElement ? window.parent : window)
        .drupalSettings.entity_browser[this.uuid];

      // Assume a single-cardinality field with no existing selection.
      this.count = settings.count || 0;
      this.cardinality = settings.cardinality || 1;
    },

    deselect(item) {
      this.$(item)
        .removeClass('checked')
        .find('input[name ^= "entity_browser_select"]')
        .prop('checked', false);
    },

    /**
     * Deselects all items in the entity browser.
     */
    deselectAll() {
      // Create a version of deselect() that can be called within each() with
      // this as its context.
      const _deselect = this.deselect.bind(this);

      this.$('.grid-item').each(function (undef, item) {
        _deselect(item);
      });
    },

    select(item) {
      this.$(item)
        .addClass('checked')
        .find('input[name ^= "entity_browser_select"]')
        .prop('checked', true);
    },

    /**
     * Marks unselected items in the entity browser as disabled.
     */
    lock() {
      this.$('.grid-item:not(.checked)').addClass('disabled');
    },

    /**
     * Marks all items in the entity browser as enabled.
     */
    unlock() {
      this.$('.grid-item').removeClass('disabled');
    },

    /**
     * Handles click events for any item in the entity browser.
     *
     * @param {jQuery.Event} event
     */
    onClick(event) {
      const chosenOne = this.$(event.currentTarget);

      if (chosenOne.hasClass('disabled')) {
        return false;
      }

      if (this.cardinality === 1) {
        this.deselectAll();
        this.select(chosenOne);

        if (event.type === 'dblclick') {
          this.$('.form-actions input').click().prop('disabled', true);
        }
      } else if (chosenOne.hasClass('checked')) {
        this.deselect(chosenOne);
        this.count--;
        this.unlock();
      } else {
        this.select(chosenOne);

        // If cardinality is unlimited, this will never be fulfilled. Good.
        if (++this.count === this.cardinality) {
          this.lock();
        }
      }
    },
  });

  Drupal.behaviors.fileBrowserSelection = {
    getElement(context) {
      // If we're in a document context, search for the first available entity
      // browser form. Otherwise, ensure that the context is itself an entity
      // browser form.
      return $[context === document ? 'find' : 'filter'](
        'form[data-entity-browser-uuid]',
      );
    },

    attach(context) {
      const element = this.getElement(context);

      if (element) {
        $(element).data('view', new Selection({ el: element }));
      }
    },

    detach(context) {
      const element = this.getElement(context);

      if (element) {
        const view = $(element).data('view');

        if (view instanceof Selection) {
          view.undelegateEvents();
        }
      }
    },
  };

  Drupal.behaviors.changeOnKeyUp = {
    onKeyUp: _.debounce(function () {
      $(this).trigger('change');
    }, 600),

    attach(context) {
      $('.keyup-change', context).on('keyup', this.onKeyUp);
    },

    detach(context) {
      $('.keyup-change', context).off('keyup', this.onKeyUp);
    },
  };
})(jQuery, _, Backbone, Drupal);
