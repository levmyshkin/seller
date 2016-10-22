/**
 * @file
 * Attaches behaviors for Drupal's color field.
 */

(function (Drupal, drupalSettings, $) {

    "use strict";

    Drupal.behaviors.color_field = {
        attach: function (context, settings) {

            var $context = $(context);

            var default_colors = settings.color_field.color_field_widget_box.settings.default_colors;

            $context.find('.color-field-widget-box-form').each(function (index, element) {
                var $element = $(element);
                var $input = $element.prev().find('input');
                $element.empty().addColorPicker({
                    currentColor: $input.val(),
                    colors: default_colors,
                    blotchClass:'color_field_widget_box__square',
                    blotchTransparentClass:'color_field_widget_box__square--transparent',
                    clickCallback: function(color) {
                        $input.val(color).trigger('change');
                    }
                });
            });

        },
        detach: function (context, settings, trigger) {
        }
    };

})(Drupal, drupalSettings, jQuery);
