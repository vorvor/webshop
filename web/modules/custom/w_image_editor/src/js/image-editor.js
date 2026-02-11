(function (Drupal, $) {
  Drupal.behaviors.imageEditor = {
    attach(context) {
      once('imageEditor', '.after-image-area', context).forEach((element) => {

        let type = $(element).data('type');

        console.log(`editor ${type} loaded`);

        let src = $(`#field_image-media-library-wrapper-field_image_placement_${type}-0-subform .field--name-thumbnail img`).attr('src');
        if (src !== undefined) {



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

             */

            document.getElementById(`rotation-${type}`).value = angle;
            document.getElementById(`top-${type}`).value = top;
            document.getElementById(`left-${type}`).value = left;
            document.getElementById(`scale-${type}`).value = scaleX;

            $(`input[data-drupal-selector="edit-field-image-placement-${type}-0-subform-field-rotation-0-value"]`).val(angle);

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
          canvas.on('selection:created', updateFromActive);
          canvas.on('selection:updated', updateFromActive);
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
          console.log('Loading FOREGROUND:', url);

          const htmlImg = new Image();
          htmlImg.crossOrigin = 'anonymous';

          htmlImg.onload = () => {
            console.log('✅ Foreground loaded:', htmlImg.naturalWidth, htmlImg.naturalHeight);

            const fabImg = new fabric.Image(htmlImg, {
              left: 100,
              top: 100,
              cornerStyle: 'circle',
              cornerStrokeColor: 'blue',
              cornerColor: 'lightblue',
              padding: 10,
              transparentCorners: false,
              cornerDashArray: [2, 2],
              borderColor: 'orange',
              borderDashArray: [3, 1, 3],
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
            canvas.setActiveObject(fabImg);
            canvas.requestRenderAll();

            // Initialize HUD immediately
            captureStartState(fabImg);
            updateHUD(fabImg);
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
