(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.behaviors.setDocIds = {
    attach: function attach(context, settings) {
      var uuids_references = drupalSettings.uuids_references;
      var uuids = $.map(uuids_references, function(value, key) {
        return key;
      });
      $('#field-doc-ids').val(uuids);
    }
  };

  Drupal.behaviors.changesListTableSelect = {
    attach: function attach(context, settings) {
      $(context).find('th.select-all').closest('table').once('table-select').each(Drupal.changesListTableSelect);
    }
  };

  Drupal.changesListTableSelect = function () {
    if ($('#edit-changes-list').find('td input[type="checkbox"]').length === 0) {
      return;
    }

    var uuids_references = drupalSettings.uuids_references;
    var table = $('#edit-changes-list');
    var checkboxes = void 0;
    var lastChecked = void 0;
    var $table = $(table);
    var strings = {
      selectAll: Drupal.t('Select all rows in this table'),
      selectNone: Drupal.t('Deselect all rows in this table')
    };
    var updateSelectAll = function updateSelectAll(state) {
      $table.prev('table.sticky-header').addBack().find('th.select-all input[type="checkbox"]').each(function () {
        var $checkbox = $(this);
        var stateChanged = $checkbox.prop('checked') !== state;

        $checkbox.attr('title', state ? strings.selectNone : strings.selectAll);

        if (stateChanged) {
          $checkbox.prop('checked', state).trigger('change');
        }
      });
    };

    $table.find('th.select-all').prepend($('<input type="checkbox" class="form-checkbox" />').attr('title', strings.selectAll)).on('click', function (event) {
      if ($(event.target).is('input[type="checkbox"]')) {
        checkboxes.each(function () {
          var $checkbox = $(this);
          var stateChanged = $checkbox.prop('checked') !== event.target.checked;

          if (stateChanged) {
            $checkbox.prop('checked', event.target.checked).trigger('change');

            var checkbox_value = $checkbox[0].value;
            if (uuids_references[checkbox_value] && uuids_references[checkbox_value].length !== 0) {
              var references = uuids_references[checkbox_value];
              for (var i = 0; i < references.length; i++) {
                var uuid = references[i];
                $('#' + uuid).toggleClass('selected', event.target.checked).find('input[type="checkbox"]').prop('disabled', event.target.checked);
              }
            }
          }

          $checkbox.closest('tr').toggleClass('selected', this.checked);
        });

        updateSelectAll(event.target.checked);
      }
    });

    checkboxes = $table.find('td input[type="checkbox"]:enabled').on('click', function (e) {
      var $row = $(this).closest('tr');
      $row.toggleClass('selected', this.checked);

      $('#' + $row[0].id + '-references').toggleClass('selected', e.target.checked);
      if (uuids_references[$row[0].id] && uuids_references[$row[0].id].length !== 0) {
        var references = uuids_references[$row[0].id];
        for (var i = 0; i < references.length; i++) {
          var uuid = references[i];
          $('#' + uuid).toggleClass('selected', e.target.checked).find('input[type="checkbox"]').prop('checked', e.target.checked).prop('disabled', e.target.checked);
        }
      }

      if (e.shiftKey && lastChecked && lastChecked !== e.target) {
        Drupal.tableSelectRange($(e.target).closest('tr')[0], $(lastChecked).closest('tr')[0], e.target.checked);
      }

      updateSelectAll(checkboxes.length === checkboxes.filter(':checked').length);

      lastChecked = e.target;
    });

    updateSelectAll(checkboxes.length === checkboxes.filter(':checked').length);
  };

  Drupal.tableSelectRange = function (from, to, state) {
    var mode = from.rowIndex > to.rowIndex ? 'previousSibling' : 'nextSibling';

    for (var i = from[mode]; i; i = i[mode]) {
      var $i = $(i);

      if (i.nodeType !== 1) {
        continue;
      }

      $i.toggleClass('selected', state);
      $i.find('input[type="checkbox"]').prop('checked', state);

      if (to.nodeType) {
        if (i === to) {
          break;
        }
      } else if ($.filter(to, [i]).r.length) {
        break;
      }
    }
  };
})(jQuery, Drupal, drupalSettings);
