(function ($, Drupal) {
    Drupal.behaviors.workspaceDeployment = {
        attach: function (context, settings) {
            $("#tabs").tabs({
                active: -1
            });
        }
    };
})(jQuery, Drupal);