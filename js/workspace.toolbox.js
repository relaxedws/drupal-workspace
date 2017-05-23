/**
 * @file
 * Drupal's Settings Tray library.
 */

(function ($, Drupal) {
    Drupal.behaviors.workspaceToolbox = {
        attach: function (context, settings) {
            $(context).find('a.toolbar-icon-workspace').once('workspaceToolbox').click(function (event) {
                $('body').toggleClass('transform-active').css('padding-top', 0);
                $('div.toolbox').toggleClass('toolbox-active');
                event.stopPropagation();
                event.preventDefault();
                return false;
            });
            $(context).find('button.toolbox-close').once('workspaceToolbox').click(function (event) {
                $('body').toggleClass('transform-active').css('padding-top', '79px');
                $('div.toolbox').toggleClass('toolbox-active');
                event.stopPropagation();
                event.preventDefault();
                return false;
            });
        }
    };
})(jQuery, Drupal);
