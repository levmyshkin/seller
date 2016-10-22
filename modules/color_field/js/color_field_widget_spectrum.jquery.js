/**
 * @file
 * Javascript for Color Field.
 */
(function ($) {

  "use strict";

  Drupal.behaviors.color_field_spectrum = {
    attach: function (context, settings) {

      var $context = $(context);

      var settings = settings.color_field.color_field_widget_spectrum;

      $context.find('.js-color-field-widget-spectrum').each(function (index, element) {
        var $element = $(element);
        var $element_color = $element.find('.js-color-field-widget-spectrum__color');
        var $element_opacity = $element.find('.js-color-field-widget-spectrum__opacity');

        $element_color.spectrum({
          showInitial: true,
          preferredFormat: "hex",
          showInput: settings.show_input,
          showAlpha: settings.show_alpha,
          showPalette: settings.show_palette,
          showPaletteOnly: !!settings.show_palette_only,
          palette:[settings.palette],
          showButtons: settings.show_buttons,
          allowEmpty: settings.allow_empty,

          change: function(tinycolor) {
            $element_color.val(tinycolor.toHexString());
            $element_opacity.val(tinycolor._roundA);
          }

        });

        // Set alpha value on load.
        if (!!settings.show_alpha) {
          var tinycolor = $element_color.spectrum("get");
          var alpha = $element_opacity.val();
          if (alpha > 0) {
            tinycolor.setAlpha(alpha);
            $element_color.spectrum("set", tinycolor);
          }
        }

      });
    }
  };
})(jQuery);
