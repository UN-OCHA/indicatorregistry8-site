(function (Drupal) {
  'use strict';

  /**
   * Theme function for the table.
   *
   * @param {object} settings
   *   Settings object used to construct the markup.
   *
   * @return {string}
   *   The corresponding HTML.
   */
   Drupal.theme.lunrSearchResultTable = function(settings) {
    var cols = settings.displayField.split(',').length;
    return '<table class="cd-table cd-table--responsive cd-table--striped lunr-search-result-table cols-' + cols + '"></table>';
  };

  /**
   * Theme function for the header cell.
   *
   * @param {object} settings
   *   Settings object used to construct the markup.
   *
   * @return {string}
   *   The corresponding HTML.
   */
  Drupal.theme.lunrSearchResultHeader = function(data, settings) {
    return '<th scope="col" class="lunr-search-result-header">' + data + '</th>';
  };

  /**
   * Theme function for the cell.
   *
   * @param {string} field
   *   Field id.
   * @param {object} settings
   *   Settings object used to construct the markup.
   *
   * @return {string}
   *   The corresponding HTML.
   */
  Drupal.theme.lunrSearchResultCell = function(data, field, settings) {
    return '<td class="lunr-search-result-cell" data-content="' + settings.tableHeaders[field.trim()] + '">' + data + '</td>';
  };

})(Drupal);
