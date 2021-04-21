(function (Drupal) {
  'use strict';

  Drupal.behaviors.exportButton = {
    attach: function (context, settings) {

      var exportButton = context.querySelector('.cd-button--export');

      if (exportButton) {
        exportButton.addEventListener('click', function (e) {
          var location = window.location.toString();
          location = location.replace('/indicators', '/export/indicators');
          window.location = location;
          if (e) {
            e.preventDefault();
          }
        });
      }
    }
  };
})(Drupal);
