(function (Drupal, $) {
  Drupal.behaviors.imageEditor = {
    attach(context) {
      once('imageEditorPopup', '#open-popup', context).forEach((e) => {
        // popup opener
        $(e).click(function() {
          if ($(this).hasClass('active')) {
            $(this).removeClass('active').html('Place image on shirt');
            $('.horizontal-tabs').hide();
          } else {
            $(this).addClass('active').html('Close image place');
            $('.horizontal-tabs').show();
          }
        })

      })
      once('imageEditor', '.after-image-area', context).forEach((element) => {


        let type = $(element).data('type');
        console.log(`editor ${type} loaded`);
        let src = $(`#field_image-media-library-wrapper-field_image_placement_${type}-0-subform .field--name-thumbnail img`).attr('src');
        if (src !== undefined) {

          console.log(src);

          const canvas = new fabric.Canvas(`editor-${type}`, {
            centeredScaling: false,
            centeredRotation: true,
            uniformScaling: true,
            lockUniScaling: true
          });

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

            console.log('updated.');
            $(`input[data-drupal-selector="edit-field-image-placement-${type}-0-subform-field-rotation-0-value"]`).val(Math.round(angle));
            $(`input[data-drupal-selector="edit-field-image-placement-${type}-0-subform-field-top-0-value"]`).val(Math.round(top));
            $(`input[data-drupal-selector="edit-field-image-placement-${type}-0-subform-field-left-0-value"]`).val(Math.round(left));
            $(`input[data-drupal-selector="edit-field-image-placement-${type}-0-subform-field-scale-0-value"]`).val(parseFloat(scaleX).toFixed(2));

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
          const url = src;
          console.log('Loading ' + type, url);

          const htmlImg = new Image();
          htmlImg.crossOrigin = 'anonymous';

          htmlImg.onload = () => {
            console.log(`✅ ${type}:`, htmlImg.naturalWidth, htmlImg.naturalHeight);
            let ratio = htmlImg.naturalWidth / htmlImg.naturalHeight;
            let xScale = 116 / htmlImg.naturalWidth;

            let angle = $(`input[data-drupal-selector="edit-field-image-placement-${type}-0-subform-field-rotation-0-value"]`).val();
            let topC = $(`input[data-drupal-selector="edit-field-image-placement-${type}-0-subform-field-top-0-value"]`).val();
            let leftC = $(`input[data-drupal-selector="edit-field-image-placement-${type}-0-subform-field-left-0-value"]`).val();
            let scale = $(`input[data-drupal-selector="edit-field-image-placement-${type}-0-subform-field-scale-0-value"]`).val();

            console.log(`Xscale: ${xScale}`);
            console.log(angle, topC, Math.round(leftC), scale);

            const fabImg = new fabric.Image(htmlImg, {
              left: !leftC ? 100 : Math.round(leftC),
              top: !topC ? 100 : Math.round(topC),
              scaleX: !scale ? xScale : scale,
              scaleY: !scale ? xScale : scale,
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

            var maxScaleX = 116 / htmlImg.naturalWidth;
            var maxScaleY = 116 / htmlImg.naturalHeight;

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

            // Initialize HUD immediately
            captureStartState(fabImg);
            //updateHUD(fabImg);
            startState = null; // so Δ starts at 0 until first gesture
          };

          htmlImg.onerror = (e) => {
            console.error('❌ Foreground failed to load:', url, e);
            alert('Foreground failed to load: ' + url + '\nCheck Network tab for 404 / blocked request.');
          };

          htmlImg.src = url;
        }
      });
    }
  };
})(Drupal, jQuery, drupalSettings);
