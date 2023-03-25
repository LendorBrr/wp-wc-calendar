jQuery(document).ready(function($) {
  // Initialize date and time pickers
  let minDate = parseInt($("#wc-date-picker").data("min-date"), 10);
  let minTime = parseInt($("#wc-time-picker").data("min-time"), 10);
  var availableDates = wcDateTimePickerData.available_dates;
  var availableTimeSlots = wcDateTimePickerData.available_time_slots;
  var allowed_products = JSON.parse(wc_datetime_picker_params.allowed_products); // Parse the JSON string
  var current_product_id = parseInt(wc_datetime_picker_params.current_product_id, 10);

  if (allowed_products.includes(current_product_id)) {

  function isAvailableDate(date) {
    var dateString = jQuery.datepicker.formatDate('yy-mm-dd', date);
    return (availableDates.indexOf(dateString) > -1);
  }

  $("#wc-date-picker").datepicker({
    minDate: minDate,
    dateFormat: "yy-mm-dd",
    beforeShowDay: function(date) {
      if (availableDates.length > 0) {
        return [isAvailableDate(date)];
      }
      return [true];
    },
  });

  $("#wc-time-picker").timepicker({
    minTime: minTime,
    timeFormat: "H:i",
    disableTimeRanges: availableTimeSlots.length > 0 ? [] : [['12am', '11:59pm']],
  });

  if (availableTimeSlots.length > 0) {
    $('#wc-time-picker').on('show.timepicker', function() {
      var instance = $(this).data('timepicker');
      var date = $('#wc-date-picker').datepicker('getDate');

      if (isAvailableDate(date)) {
        instance.option('disableTimeRanges', []);
        availableTimeSlots.forEach(function(timeSlot) {
          var timeRange = timeSlot.split('-');
          instance.option('disableTimeRanges').push(timeRange);
        });
      } else {
        instance.option('disableTimeRanges', [['12am', '11:59pm']]);
      }
    });
  }
  }
};
