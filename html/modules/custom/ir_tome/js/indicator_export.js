(function (Drupal) {
  'use strict';

  Drupal.behaviors.exportButton = {
    attach: function (context, settings) {

      var lunrSearchPage = context.querySelector('.lunr-search-page-form');
      if (!lunrSearchPage) {
        return;
      }

      var exportButton = document.createElement('button');
      exportButton.classList.add('cd-button', 'cd-button--export', 'cd-button--small');
      exportButton.innerText = 'Export to CSV';
      exportButton.addEventListener('click', function (e) {
        var location = window.location.toString();
        location = location.replace('/indicators', '/export/indicators/index.csv');
        window.location = location;
        if (e) {
          e.preventDefault();
        }
      });

      var exportWrapper = document.createElement('div');
      exportWrapper.classList.add('cd-export-button--wrapper');
      exportWrapper.appendChild(exportButton);

      document.querySelector('.lunr-filters--wrapper-filters').prepend(exportWrapper);
    }
  };
})(Drupal);
