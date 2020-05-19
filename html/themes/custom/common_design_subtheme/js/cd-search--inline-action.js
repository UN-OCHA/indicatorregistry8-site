(function (Drupal) {
  'use strict';

  Drupal.behaviors.cdSearchInlineAction = {
    attach: function (context, settings) {

      var searchWrapper = context.querySelector('.block-search');

      if (searchWrapper) {
        if (window.location.pathname.indexOf('/assessments/') === 0 || window.location.pathname.indexOf('/knowledge-management/') === 0) {
          var form = searchWrapper.querySelector('form');
          form.action = '';
        }
      }
    }
  };
})(Drupal);
