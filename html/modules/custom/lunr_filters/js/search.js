/**
 * @file
 * Provides behaviors for Lunr search pages.
 */

(function ($, Drupal, drupalSettings) {

  /**
   * Stores globals needed for search.
   *
   * @type {object}
   */
  Drupal.lunr = Drupal.lunr || {
    worker: null,
    pages: {},
  };

  /**
   * Constructs an object representing a search page.
   *
   * @constructor
   *
   * @param {object} settings
   *   Settings for this search page.
   * @param {string} settings.indexPath
   *   The path to the index file.
   * @param {number} settings.id
   *   The ID for the lunr search entity.
   * @param {string} settings.documentPathPattern
   *   The path pattern for document files.
   * @param {string} settings.displayField
   *   The field to display for documents.
   * @param {number} settings.resultsPerPage
   *   The number of search results to display per page.
   * @param {object} $form
   *   The jQuery object for the form.
   */
  Drupal.lunrSearchPage = function(settings, $form) {
    this.settings = settings;
    this.documents = {};
    this.results = [];
    this.activeFacets = {};
    this.facetDropdowns = {};
    this.$progressElement = false;
    this.$form = $form;

    // Activate select2.
    for (var facet = 0; facet < this.settings.facetFields.length; facet++) {
      var dropdown = document.querySelector('select[data-lunr-search-field="' + this.settings.facetFields[facet] + '"]');

      var $dropdown = $(dropdown).select2();
      $dropdown.on("select2:select", function (e) {
        $(this).select2('close');
        $(this).closest('form').submit();
      });
      $dropdown.on("select2:unselect", function (e) {
        var self = $(this);
        setTimeout(function() {
            self.select2('close');
        }, 1);

        $(this).closest('form').submit();
      });

      this.facetDropdowns[this.settings.facetFields[facet]] = $dropdown;
    }
  };

  /**
   * Shows progress while AJAX requests are made.
   */
  Drupal.lunrSearchPage.prototype.showProgress = function() {
    this.hideProgress();
    this.$form.addClass('lunr-loading');
    this.$form.removeClass('lunr-ready');
    this.$progressElement = $(Drupal.theme.lunrSearchProgress());
    $('body').after(this.$progressElement);
  };

  /**
   * Hides the progress element.
   */
  Drupal.lunrSearchPage.prototype.hideProgress = function() {
    this.$form.removeClass('lunr-loading');
    this.$form.addClass('lunr-ready');
    if (this.$progressElement) {
      this.$progressElement.remove();
    }
  };

  /**
   * Initializes the search page.
   */
  Drupal.lunrSearchPage.prototype.init = function() {
    this.$form.on('submit', function(e) {
      e.preventDefault();
      var value = this.$form.find('.js-lunr-search-input').val();
      var parameters = {
        search: value,
        page: 1
      };
      this.$form.find('[data-lunr-search-field]').each(function () {
        parameters[$(this).attr('data-lunr-search-field')] = $(this).val();
      });
      this.setParameters(parameters);
      this.searchByQuery();
    }.bind(this));
    this.hideProgress();
    this.searchByQuery();
  };

  /**
   * Executes a search based on the current query params.
   */
  Drupal.lunrSearchPage.prototype.searchByQuery = function() {
    var parameters = this.getParameters();
    var fields = {};
    for (var key in parameters) {
      if (key !== 'search' && key !== 'page') {
        // Track fields with a value.
        if (parameters[key].length > 0) {
          fields[key] = parameters[key];
        }

        if (parameters[key].indexOf(',')) {
          this.$form.find('[data-lunr-search-field="' + key + '"]').val(parameters[key].split(',')).trigger('change.select2');
        }
        else {
          this.$form.find('[data-lunr-search-field="' + key + '"]').val(parameters[key]).trigger('change.select2');
        }
      }
    }

    // Make sure unspecified fields are empty.
    for (var facet = 0; facet < this.settings.facetFields.length; facet++) {
      if (!parameters[this.settings.facetFields[facet]]) {
        var dropdown = this.facetDropdowns[this.settings.facetFields[facet]];
        dropdown.val(null).trigger('change.select2');
      }
    }

    var search = parameters['search'] ? parameters['search'] : '';
    this.$form.find('.js-lunr-search-input').val(search);
    this.showProgress();
    Drupal.lunr.worker.postMessage({type: 'search', search: search, fields: fields, id: this.settings.id});
  };

  /**
   * Displays a given page of search results.
   *
   * @param {number} page
   *   The page.
   */
  Drupal.lunrSearchPage.prototype.showPage = function(page) {
    this.showProgress();
    var requests = {};

    this.results.forEach(function(result) {
      var documentPage = result.ref.split(':')[0];
      if (!(documentPage in this.documents) && !(documentPage in requests)) {
        requests[documentPage] = $.ajax({
          url: this.settings.documentPathPattern.replace('PAGE', documentPage),
          type : 'GET',
          success: function(data) {
            this.documents[documentPage] = data;
          }.bind(this),
          error: function(request, error) {
            console.log('Error'); //@todo
          }
        });
      }
    }.bind(this));
    $.when.apply($, Object.values(requests)).then(function () {
      // Build facets.
      this.activeFacets = {};
      this.results.forEach(function(result) {
        this.trackFacets(result.ref);
      }.bind(this));

      // Slice for html output.
      var currentResults = this.results.slice(page * this.settings.resultsPerPage, (page * this.settings.resultsPerPage) + this.settings.resultsPerPage);

      var $results = this.$form.siblings('.js-lunr-search-results');
      $results.empty();
      $results.append(Drupal.theme.lunrSearchResultCount({
        count: this.results.length,
        page: page
      }));

      if (this.results.length > 0) {
        // Table output.
        if (this.settings.displayField.indexOf(',') >= 0) {
          var $table = $results.append('<table>');
          var $thead = $table.append('<thead>');
          this.settings.displayField.split(',').forEach(function(field) {
            $thead.append('<td>' + this.settings.tableHeaders[field.trim()] + '</td>');
          }.bind(this));

          var $tbody = $table.append('<tbody>');
          currentResults.forEach(function(result) {
            $tbody.append(this.getTableRowElement(result.ref));
          }.bind(this));

        }
        else {
          currentResults.forEach(function(result) {
            $results.append(this.getRowElement(result.ref));
          }.bind(this));
        }

        var $pager = $(Drupal.theme.lunrSearchPager({
          count: this.results.length,
          resultsPerPage: this.settings.resultsPerPage,
          page: page
        }));

        $pager.find('[data-page]').on('click', function(e) {
          e.preventDefault();
          var newPage = parseInt($(e.currentTarget).attr('data-page'));
          this.setParameter('page', newPage + 1);
          this.showPage(newPage);
        }.bind(this));

        $results.append($pager);

        this.disableUnusedFacets();
      }
      else {
        this.disableAllFacets();
      }

      this.scrollToForm();
      this.hideProgress();
      this.updateSummary();
    }.bind(this));
  };

  /**
   * Disable all facets.
   */
   Drupal.lunrSearchPage.prototype.updateSummary = function() {
    var summaryLinks = [];

    // Get active facets.
    for (var facet = 0; facet < this.settings.facetFields.length; facet++) {
      var dropdown = this.facetDropdowns[this.settings.facetFields[facet]];
      summaryLinks = summaryLinks.concat(dropdown.select2('data'));
    }

    // Build summary.
    var $summary = this.$form.siblings('.js-lunr-summary');
    $summary.empty();
    var $summaryList = $summary.append('<ul>');

    // Add free text search.
    var parameters = this.getParameters();
    var search = parameters['search'] ? parameters['search'] : '';

    if (search.length > 0) {
      var searchParams = new URLSearchParams(window.location.search);
      searchParams.delete('search');
      var newUrl = window.location.pathname + '?' + searchParams.toString();
      $summaryList.append('<li><a href="' + newUrl + '">x ' + search + '</a></li>');
    }

    // Add facets.
    summaryLinks.forEach(function(link) {
      var id = link.element.parentNode.getAttribute('data-lunr-search-field');
      var searchParams = new URLSearchParams(window.location.search);
      var activeSelection = searchParams.get(id);
      if (activeSelection && activeSelection.indexOf(',') >= 0) {
        var values = activeSelection.split(',');
        var values = values.filter(function(value, index, arr){
          return value != link.id;
        });
        searchParams.set(id, values.join(','));
      }
      else {
        searchParams.delete(id);
      }
      var newUrl = window.location.pathname + '?' + searchParams.toString();
      $summaryList.append('<li><a href="' + newUrl + '">x ' + link.text + '</a></li>');
    });

    // Add reset all.
    if ($summaryList.find('li').length > 0) {
      var newUrl = window.location.pathname;
      $summaryList.append('<li><a href="' + newUrl + '">x Reset all filters</a></li>');
    }

    // Intercept clicks.
    $('.js-lunr-summary a').on('click', function (e) {
      e.preventDefault();
      history.pushState(null, '', e.currentTarget.href);
      this.searchByQuery();
    }.bind(this));
  };

  /**
   * Disable all facets.
   */
   Drupal.lunrSearchPage.prototype.disableAllFacets = function() {
    for (var facet = 0; facet < this.settings.facetFields.length; facet++) {
      var dropdown = this.facetDropdowns[this.settings.facetFields[facet]];
      dropdown.prop('disabled', true);
      dropdown.trigger('change.select2');
    }
  };

  /**
   * Disable unused facets.
   */
  Drupal.lunrSearchPage.prototype.disableUnusedFacets = function() {
    var that = this;
    for (var facet = 0; facet < this.settings.facetFields.length; facet++) {
      if (that.activeFacets[that.settings.facetFields[facet]]) {
        var dropdown = this.facetDropdowns[this.settings.facetFields[facet]];
        dropdown.prop('disabled', true);
        dropdown.find('option').each(function (index, element) {
          var option = $(element);
          if (that.activeFacets[that.settings.facetFields[facet]].indexOf(option.val()) >= 0) {
            option.removeAttr('disabled');
            dropdown.prop('disabled', false);
          }
          else {
            option.attr('disabled', 'disabled');
          }
        });
        dropdown.trigger('change.select2');
      }
    }
  };

  /**
   * Scrolls to the top of the form.
   */
  Drupal.lunrSearchPage.prototype.scrollToForm = function() {
    $('html,body').animate({
      scrollTop: this.$form.offset().top - ($('#toolbar-bar').find('.toolbar-tab').outerHeight() || 0)
    }, 200);
  };

  /**
   * Gets the row element for a given reference ID.
   *
   * @param {string} ref
   *   The document reference ID in the format page:index
   *
   * @returns {object}
   *   A jQuery object representing the row.
   */
  Drupal.lunrSearchPage.prototype.getRowElement = function(ref) {
    var parts = ref.split(':');
    var document = this.documents[parts[0]][parts[1]];

    return $(Drupal.theme.lunrSearchResultWrapper()).append(document[this.settings.displayField]);
  };

  /**
   * Gets the row element for a given reference ID.
   *
   * @param {string} ref
   *   The document reference ID in the format page:index
   *
   * @returns {object}
   *   A jQuery object representing the row.
   */
   Drupal.lunrSearchPage.prototype.getTableRowElement = function(ref) {
    var parts = ref.split(':');
    var document = this.documents[parts[0]][parts[1]];

    var $row = $('<tr>');
    this.settings.displayField.split(',').forEach(function(field) {
      $row.append('<td>' + document[field.trim()] + '</td>');
    });

    return $row;
  };

  /**
   * Track facets.
   *
   * @param {object} document
   *   The document
   */
  Drupal.lunrSearchPage.prototype.trackFacets = function(ref) {
    var parts = ref.split(':');
    var document = this.documents[parts[0]][parts[1]];

    // Track facets.
    for (var facet = 0; facet < this.settings.facetFields.length; facet++) {
      var property = this.settings.facetFields[facet];
      if (document.hasOwnProperty(property)) {
        this.activeFacets[property] = this.activeFacets[property] || [];
        var facets = document[property].split(',');
        for (var f = 0; f < facets.length; f++) {
          if (facets[f].length > 0 && this.activeFacets[property].indexOf(facets[f]) < 0) {
            this.activeFacets[property].push(facets[f]);
          }
        }
      }
    }
  }

  /**
   * Utility function for getting multiple query param values.
   *
   * @returns {object}
   *   An object mapping .
   */
  Drupal.lunrSearchPage.prototype.getParameters = function() {
    if ('URLSearchParams' in window) {
      var values = {};
      var searchParams = new URLSearchParams(window.location.search);
      searchParams.forEach(function(value, key) {
        values[key] = value;
      });
      return values;
    }
    return false;
  };

  /**
   * Utility function for getting query param values.
   *
   * @param {string} name
   *   The query param name.
   *
   * @returns {*}
   *   The value for the query param.
   */
  Drupal.lunrSearchPage.prototype.getParameter = function(name) {
    if ('URLSearchParams' in window) {
      var searchParams = new URLSearchParams(window.location.search);
      return searchParams.get(name);
    }
    return false;
  };

  /**
   * Utility function for setting query param values.
   *
   * Credit to Anthony Manning-Franklin https://stackoverflow.com/a/41542008
   *
   * @param {string} name
   *   The query param name.
   * @param {*} value
   *   The query param value.
   */
  Drupal.lunrSearchPage.prototype.setParameter = function(name, value) {
    if ('URLSearchParams' in window) {
      var searchParams = new URLSearchParams(window.location.search);
      searchParams.set(name, value);
      var newRelativePathQuery = window.location.pathname + '?' + searchParams.toString();
      history.pushState(null, '', newRelativePathQuery);
    }
    return;
  };

  /**
   * Utility function for setting multiple query param values.
   *
   * Credit to Anthony Manning-Franklin https://stackoverflow.com/a/41542008
   *
   * @param {object} parameters
   *   An object mapping parameter keys to values.
   */
  Drupal.lunrSearchPage.prototype.setParameters = function(parameters) {
    if ('URLSearchParams' in window) {
      var searchParams = new URLSearchParams(window.location.search);
      for (var key in parameters) {
        searchParams.set(key, parameters[key]);
      }
      var newRelativePathQuery = window.location.pathname + '?' + searchParams.toString();
      history.pushState(null, '', newRelativePathQuery);
    }
    return;
  };

  /**
   * Displays search results.
   *
   * @param {array} results
   *   The search results.
   */
  Drupal.lunrSearchPage.prototype.showResults = function(results) {
    this.hideProgress();
    this.results = results;
    var initPage = parseInt(this.getParameter('page'));
    if (initPage && initPage > 0) {
      this.showPage(initPage - 1);
    }
    else {
      this.showPage(0);
    }
  };

  /**
   * Listens to messages from our worker.
   *
   * @param {MessageEvent} event
   *   The event.
   */
  function onWorkerMessage(event) {
    switch (event.data.type) {
      case 'loadIndexComplete':
        Drupal.lunr.pages[event.data.id].init();
        break;
      case 'searchComplete':
        Drupal.lunr.pages[event.data.id].showResults(event.data.results);
        break;
      default:
        throw new Error('Unknown message sent from lunr search worker.');
    }
  }

  /**
   * A Drupal behavior that initializes Lunr search pages.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.lunrIndexForm = {
    attach: function(context) {
      $('form.js-lunr-search-page-form', context).once('lunr-search').each(function() {
        var settings = drupalSettings.lunr.searchSettings[$(this).attr('data-lunr-search')];
        if (!Drupal.lunr.worker) {
          Drupal.lunr.worker = new Worker(drupalSettings.lunr.workerPath);
          Drupal.lunr.worker.onmessage = onWorkerMessage;
        }
        Drupal.lunr.worker.postMessage({
          type: 'loadIndex',
          id: settings.id,
          indexPath: settings.indexPath,
          lunrPath: drupalSettings.lunr.lunrPath
        });
        Drupal.lunr.pages[settings.id] = new Drupal.lunrSearchPage(settings, $(this));
        Drupal.lunr.pages[settings.id].showProgress();
        window.onpopstate = function() {
          Drupal.lunr.pages[settings.id].searchByQuery();
        };
      });
    }
  };

})(jQuery, Drupal, drupalSettings);
