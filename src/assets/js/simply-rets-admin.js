/*
 *
 * simply-rets-admin.js
 * Javascript for the admin functionality of the Simple Rets plugin.
 * Copyright (c) 2014-2024 SimplyRETS
 *
 */


// Listing-page editor filters use the existing labels and control IDs.
jQuery(function($) {
  var filterInputs = {
    'Minimum Price': '#sr-min-price-span',
    'Maximum Price': '#sr-max-price-span',
    'Minimum Beds': '#sr-min-bed-span',
    'Maximum Beds': '#sr-max-bed-span',
    'Minimum Bathrooms': '#sr-min-bath-span',
    'Maximum Bathrooms': '#sr-max-bath-span',
    'Listing Agent': '#sr-listing-agent-span',
    'Listing Type': '#sr-listing-type-span',
    'Amount of listings': '#sr-limit-span'
  };

  $('#sr-filter-select').on('change', function() {
    var selector = filterInputs[$(this).val()];
    if (!selector) return;
    var input = $(selector);
    $('.current-filters').append(input);
    input.show();
    $(this).find('option:selected').remove();
  });

  $('.sr-remove-filter').on('click', function() {
    $(this).parent('.sr-filter-input').hide();
    $(this).prev('input').val('');
  });
});

/* Enhance only the settings page. Every section remains available without JS,
 * and hidden panels keep their controls in the single WordPress settings form. */
jQuery(function($) {
  var root = $('.sr-admin-wrap');
  var form = root.find('#sr-settings-form');
  if (!form.length) return;

  var panels = form.find('[data-sr-panel]');
  var nav = root.find('.sr-settings-nav');
  var search = root.find('#sr-settings-search');
  var clear = root.find('.sr-clear-search');
  var empty = form.find('.sr-settings-empty');
  var searchStatus = root.find('.sr-search-status');
  var savebar = form.find('.sr-settings-savebar');
  var saveStatus = savebar.find('.sr-save-status');

  // Keep custom-form visibility and submission behavior together with the
  // other settings handlers, instead of an inline script in the PHP view.
  form.find('#sr_enable_custom_lead_form_toggle').on('change', function() {
    form.find('#sr_custom_lead_form_row').toggle(this.checked);
    form.find('#sr_leadcapture_custom_form').prop('disabled', !this.checked);
  });
  var active = 'account';
  var storageKey = 'simplyrets-settings-section';
  var submitting = false;
  var firstInvalid = null;
  var initialValues = form.serialize();
  var searchIndex = [];

  // Index help text and option names once, never stored field values.
  function searchText(element) {
    var text = element.clone();
    text.find('input, textarea, select, button').remove();
    var names = element.find('[name]').map(function() { return this.name; }).get().join(' ');
    return (text.text() + ' ' + names).toLowerCase();
  }
  panels.each(function() {
    var panel = $(this);
    var rows = panel.find('tr').map(function() {
      var row = $(this);
      return { row: row, text: searchText(row) };
    }).get();
    searchIndex.push({ panel: panel, text: searchText(panel), rows: rows });
  });

  function showSection(section, remember) {
    if (section !== 'all' && !panels.filter(function() { return $(this).attr('data-sr-panel') === section; }).length) section = 'account';
    active = section;
    search.val('');
    clear.prop('hidden', true);
    panels.each(function() { this.hidden = section !== 'all' && $(this).attr('data-sr-panel') !== section; });
    empty.prop('hidden', true);
    searchStatus.text('');
    nav.find('a').removeAttr('aria-current').filter(function() { return $(this).attr('data-sr-section') === section; }).attr('aria-current', 'true');
    form.find('.sr-search-match').removeClass('sr-search-match');
    if (remember) {
      try { window.sessionStorage.setItem(storageKey, section); } catch (error) { /* Storage may be unavailable. */ }
    }
  }

  try { active = window.sessionStorage.getItem(storageKey) || active; } catch (error) { /* Use the account section. */ }
  root.addClass('sr-settings-enhanced');
  root.find('.sr-settings-toolbar, .sr-settings-nav, .sr-settings-savebar-info, .sr-reveal-credentials').prop('hidden', false);
  showSection(active, false);

  nav.on('click', 'a', function(event) {
    event.preventDefault();
    showSection($(this).attr('data-sr-section'), true);
    var visiblePanel = panels.filter(function() { return !this.hidden; }).first()[0];
    var position = visiblePanel.getBoundingClientRect();
    if (window.innerWidth <= 782 || position.top < 52 || position.top > window.innerHeight - 100) visiblePanel.scrollIntoView();
  });

  search.on('input', function() {
    var query = search.val().trim().toLowerCase();
    if (!query) { showSection(active, false); return; }
    var matches = 0;
    nav.find('a').removeAttr('aria-current');
    clear.prop('hidden', false);
    form.find('.sr-search-match').removeClass('sr-search-match');
    $.each(searchIndex, function(index, entry) {
      var match = entry.text.indexOf(query) !== -1;
      entry.panel.prop('hidden', !match);
      if (match) {
        matches++;
        $.each(entry.rows, function(index, entryRow) {
          entryRow.row.toggleClass('sr-search-match', entryRow.text.indexOf(query) !== -1);
        });
      }
    });
    empty.prop('hidden', matches !== 0);
    searchStatus.text(matches + (matches === 1 ? ' section matches' : ' sections match') + ' “' + search.val() + '”.');
  });
  clear.on('click', function() { showSection(active, false); search.trigger('focus'); });
  search.on('keydown', function(event) { if (event.key === 'Escape') { showSection(active, false); } });

  root.find('.sr-reveal-credentials').on('click', function() {
    var reveal = $(this).attr('aria-pressed') !== 'true';
    form.find('[name="sr_api_name"], [name="sr_api_key"]').attr('type', reveal ? 'text' : 'password');
    $(this).attr('aria-pressed', reveal ? 'true' : 'false').text(reveal ? 'Hide credentials' : 'Show credentials');
  });

  // Give legacy text fields accessible names without replacing existing IDs.
  form.find('input:not([type="hidden"]):not([type="submit"]), select, textarea').each(function() {
    var field = $(this);
    if (field.attr('aria-label') || field.closest('label').length) return;
    var label = field.closest('tr').find('strong').first().text() || field.closest('tr').find('p').first().text();
    if (!label) label = field.closest('[data-sr-panel]').find('h3').first().text();
    if (this.name === 'sr_custom_no_results_message') label = 'No search results message';
    if (label) field.attr('aria-label', label.trim());
  });

  form.on('input change', ':input', function() {
    var dirty = form.serialize() !== initialValues;
    saveStatus.text(dirty ? 'You have unsaved changes' : 'No unsaved changes');
    savebar.toggleClass('sr-is-dirty', dirty);
  });
  // Browser validation must be able to focus an invalid field on a hidden panel.
  form[0].addEventListener('invalid', function(event) {
    if (firstInvalid) return;
    firstInvalid = event.target;
    var panel = $(event.target).closest('[data-sr-panel]');
    if (panel.length) showSection(panel.attr('data-sr-panel'), true);
    window.setTimeout(function() { firstInvalid = null; }, 0);
  }, true);
  form.on('submit', function() { submitting = true; });
  $(window).on('beforeunload', function(event) {
    if (!submitting && form.serialize() !== initialValues) {
      event.preventDefault();
      event.originalEvent.returnValue = '';
      return '';
    }
  });
});
