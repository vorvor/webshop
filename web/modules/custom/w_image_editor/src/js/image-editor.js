(function (Drupal, $) {
  Drupal.behaviors.imageEditor = {
    attach(context) {
      once('imageEditorPopup', '#open-popup', context).forEach((e) => {
        $('.horizontal-tab-button').hide();
        // Image placing on product print areas popup opener.
        $(e).click(function() {
          if ($(this).hasClass('active')) {
            $(this).removeClass('active').html('Place image on product');
            $('.horizontal-tabs').hide();
          } else {
            $(this).addClass('active').html('Close image place');
            $('.horizontal-tabs').show();
          }
        })

      })

      once('imageEditorBackgrounds', '.after-image-area-list', context).forEach((element, index) => {
        let printAreaName = $(element).data('print-area-name');
        let imageUrl = $(element).val();

        // There are 7 tabs default, show only adecvate number depending on number of print areas.
        $('.horizontal-tab-button-' + index).show();
        // Set tabs labels depending on print area names.
        $('.horizontal-tab-button-' + index + ' strong').html(printAreaName);
      })

      once('imageEditor', '.after-image-area', context).forEach((element, index) => {
        let parent = $(element).data('parent');
        let parentHyphen = parent.replace(/_/g, '-');


        const bgData = $('.after-image-area-list-' + parent);
        const bgImageUrl = bgData.val();
        const bgImageLeft = bgData.attr('data-left');
        const bgImageTop = bgData.attr('data-top');
        const bgImageLeftWidth = bgData.attr('data-left-width');
        const bgImageTopHeight = bgData.attr('data-top-height');

        const bgImageWidth = bgImageLeftWidth - bgImageLeft;
        const bgImageHeight = bgImageTopHeight - bgImageTop;

        // Background scale.
        const generalScale = 0.6;

        // Init fabric on canvases.
        const canvas = new fabric.Canvas(`editor-${parent}`, {
          centeredScaling: false,
          centeredRotation: true,
          uniformScaling: true,
          lockUniScaling: true
        });

        // Set canvas background coming from midocean api print area source.
        fabric.Image.fromURL(bgImageUrl).then((img) => {
          img.canvas = canvas;

          img.set({
            scaleX: generalScale,
            scaleY: generalScale,
            originX: 'left',
            originY: 'top',
            left: 0,
            top: 0
          });

          canvas.backgroundImage = img;
          canvas.requestRenderAll();
        });

        let src = $(`#field_image-media-library-wrapper-${parent}-0-subform .field--name-thumbnail img`).attr('src');

        // Trigger: image selected from Media selector and thumbnail appeared.
        if (src !== undefined) {
          // We’ll store the "start" state of the active object when user begins an action
          let startState = null;

          function fmt(n, digits = 2) {
            return Number(n).toFixed(digits);
          }

          function captureStartState(obj) {
            if (!obj) return;
            startState = {
              left: obj.left ?? 0,
              top: obj.top ?? 0,
              scaleX: obj.scaleX ?? 1,
              scaleY: obj.scaleY ?? 1,
              angle: obj.angle ?? 0
            };
          }


          // const hudEl = document.getElementById(`hud-${type}`);
          function updateHUD(obj) {
            /*
            if (!obj) {
              hudEl.textContent = 'No active object';
              return;
            }
            */

            // Ensure Fabric has updated coords (useful during interactions)
            obj.setCoords();

            const left = obj.left ?? 0;
            const top = obj.top ?? 0;
            const angle = obj.angle ?? 0;
            const scaleX = obj.scaleX ?? 1;
            const scaleY = obj.scaleY ?? 1;

            let dx = 0, dy = 0, dAngle = 0, dScaleX = 0, dScaleY = 0;
            if (startState) {
              dx = left - startState.left;
              dy = top - startState.top;
              dAngle = angle - startState.angle;
              dScaleX = scaleX - startState.scaleX;
              dScaleY = scaleY - startState.scaleY;
            }

            /*
            hudEl.textContent =
              `x (left):   ${fmt(left, 1)}
                y (top):    ${fmt(top, 1)}
                rotation:   ${fmt(angle, 1)}°
                scaleX:     ${fmt(scaleX, 3)}
                scaleY:     ${fmt(scaleY, 3)}

                Δx:         ${fmt(dx, 1)}
                Δy:         ${fmt(dy, 1)}
                Δrot:       ${fmt(dAngle, 1)}°
                ΔscaleX:    ${fmt(dScaleX, 3)}
                ΔscaleY:    ${fmt(dScaleY, 3)}`;



            document.getElementById(`rotation-${type}`).value = angle;
            document.getElementById(`top-${type}`).value = top;
            document.getElementById(`left-${type}`).value = left;
            document.getElementById(`scale-${type}`).value = scaleX;
*/

            // Update paragraph fields with current transform data.
            $(`input[data-drupal-selector="edit-${parentHyphen}-0-subform-field-rotation-0-value"]`).val(Math.round(angle));
            $(`input[data-drupal-selector="edit-${parentHyphen}-0-subform-field-top-0-value"]`).val(Math.round(top));
            $(`input[data-drupal-selector="edit-${parentHyphen}-0-subform-field-left-0-value"]`).val(Math.round(left));
            $(`input[data-drupal-selector="edit-${parentHyphen}-0-subform-field-scale-0-value"]`).val(parseFloat(scaleX).toFixed(2));

          }

          // Update HUD for whichever object is active
          function updateFromActive() {
            updateHUD(canvas.getActiveObject());
          }

          // Capture start state when user presses mouse down on an object
          canvas.on('mouse:down', (e) => {
            if (e.target) {
              captureStartState(e.target);
              updateHUD(e.target);
            }
          });

          // Live updates while transforming
          canvas.on('object:moving',   (e) => updateHUD(e.target));
          canvas.on('object:scaling',  (e) => updateHUD(e.target));
          canvas.on('object:rotating', (e) => updateHUD(e.target));

          // Also update when selection changes
          //canvas.on('selection:created', updateFromActive);
          //canvas.on('selection:updated', updateFromActive);
          canvas.on('selection:cleared', () => updateHUD(null));

          // Optional: when user releases mouse, reset startState (so Δ values are per-gesture)
          canvas.on('mouse:up', () => {
            // keep the HUD showing final values, but clear deltas for the next gesture
            startState = null;
            updateFromActive();
          });

          // ----------------------------
          // 2) FOREGROUND (your existing functionality)
          // ----------------------------
          const htmlImg = new Image();
          htmlImg.crossOrigin = 'anonymous';

          htmlImg.onload = () => {


            // Depending on bg image w/h ratio and uploaded image w/h ratio, decided width or height should be scaled to fit boundaries.
            let htmlImgScale;
            let bgRatio = bgImageWidth / bgImageHeight;
            let imRatio = htmlImg.naturalWidth / htmlImg.naturalHeight;
            if (bgRatio >= imRatio) {
              htmlImgScale = ((bgImageHeight * generalScale) / htmlImg.naturalHeight) * 0.8;
            } else {
              htmlImgScale = ((bgImageWidth * generalScale) / htmlImg.naturalWidth) * 0.8;
            }

            let startingWidth = htmlImg.naturalWidth * htmlImgScale;
            let startingHeight = htmlImg.naturalHeight * htmlImgScale;

            // Grab back info from node fields if already changed.
            let angle = $(`input[data-drupal-selector="edit-${parentHyphen}-0-subform-field-rotation-0-value"]`).val();
            let topC = $(`input[data-drupal-selector="edit-${parentHyphen}-0-subform-field-top-0-value"]`).val();
            let leftC = $(`input[data-drupal-selector="edit-${parentHyphen}-0-subform-field-left-0-value"]`).val();
            let scale = $(`input[data-drupal-selector="edit-${parentHyphen}-0-subform-field-scale-0-value"]`).val();

            console.log('top:' + bgImageTop + ' left:' + bgImageLeft)
            console.log('height:' + bgImageHeight + ' width:' + bgImageWidth);

            console.log(`Xscale: ${htmlImgScale}`);
            console.log('Left' + bgImageLeft + ':' + (223 + parseInt(bgImageWidth) * generalScale) + ':'  + ': ' + ((bgImageLeft + (parseInt(bgImageWidth) / 2)) * generalScale));

            console.log('bgImageLeft' + bgImageLeft);
            console.log('bgImageLeft scaled' + (bgImageLeft * generalScale));
            console.log('imageWidth' + htmlImg.naturalWidth);
            console.log('xScale' + htmlImgScale);
            console.log('bgImageWidth scaled' + (htmlImg.naturalWidth * htmlImgScale));

            // Uploaded image (thumbnail) added to canvas with fabric.
            const fabImg = new fabric.Image(htmlImg, {
              left: !leftC ? (bgImageLeft * generalScale + htmlImg.naturalWidth * htmlImgScale / 2) : Math.round(leftC),
              top: !topC ? (bgImageTop * generalScale + htmlImg.naturalHeight * htmlImgScale / 2) : Math.round(topC),
              scaleX: !scale ? htmlImgScale : scale,
              scaleY: !scale ? htmlImgScale : scale,
              cornerStyle: 'circle',
              cornerStrokeColor: 'orange',
              cornerColor: 'red',
              padding: 0,
              transparentCorners: false,
              cornerDashArray: [2, 2],
              borderColor: 'orange',
              borderDashArray: [3, 1, 3, 1],
              borderScaleFactor: 2,
            });

            fabImg.setControlsVisibility({
              mt: false,
              mb: false,
              ml: false,
              mr: false,
              // mtr: false
            });

            canvas.add(fabImg);
            console.log('image added to canvas');
            canvas.setActiveObject(fabImg);
            canvas.requestRenderAll();

            var maxScaleX = bgImageWidth * generalScale / htmlImg.naturalWidth;

            // Limit scaling depending on print area boundaries.
            canvas.on('object:scaling', function(e) {
              let obj = e.target;
              console.log('scaling' + obj.scaleX + ':' + maxScaleX);
              if(obj.scaleX > maxScaleX) {
                obj.scaleX = maxScaleX;
                obj.left = obj.lastGoodLeft;
                obj.top = obj.lastGoodTop;
                console.log('stop');
              }
              if(obj.scaleY > maxScaleX) {
                obj.scaleY = maxScaleX;
                obj.left = obj.lastGoodLeft;
                obj.top = obj.lastGoodTop;
              }
              obj.lastGoodTop = obj.top;
              obj.lastGoodLeft = obj.left;
            })

            // Limit moving depending on print area boundaries.
            canvas.on('object:moving', function(e) {
              let obj = e.target;

              let widthNow = htmlImg.naturalWidth * obj.scaleX;
              let heightNow = htmlImg.naturalHeight * obj.scaleX;
              let diffx = (startingWidth - widthNow) / 2;
              let diffy = (startingHeight - heightNow) / 2;

              var minLeft = (bgImageLeft * generalScale + widthNow * 0.5);
              var minTop = (bgImageTop * generalScale + heightNow * 0.5);
              var maxRight = (bgImageLeftWidth * generalScale + widthNow * 0.5);
              var maxTop = (bgImageTopHeight * generalScale + heightNow / 2);

              if(obj.left < minLeft - diffx) {
                obj.left = obj.lastGoodLeft;
              }

              if(obj.left > maxRight - widthNow - diffx) {
                obj.left = obj.lastGoodLeft;
              }

              if(obj.top < minTop - diffy) {
                obj.top = obj.lastGoodTop;
              }

              if(obj.top > maxTop - heightNow - diffy) {
                obj.top = obj.lastGoodTop;
              }

              obj.lastGoodTop = obj.top;
              obj.lastGoodLeft = obj.left;
            })

            // Initialize HUD immediately
            captureStartState(fabImg);
            //updateHUD(fabImg);
            startState = null; // so Δ starts at 0 until first gesture
          };

          htmlImg.onerror = (e) => {
            console.error('❌ Foreground failed to load:', url, e);
            alert('Foreground failed to load: ' + url + '\nCheck Network tab for 404 / blocked request.');
          };

          htmlImg.src = src;
        }
      });
    }
  };
})(Drupal, jQuery, drupalSettings);
