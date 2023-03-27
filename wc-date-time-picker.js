jQuery(document).ready(function($) {
  let minDate = parseInt($("#wc-date-picker").data("min-date"), 10);
let minTime = parseInt($("#wc-time-picker").data("min-time"), 10);
var availableDates = wcDateTimePickerData.available_dates;
var availableTimeSlots = wcDateTimePickerData.available_time_slots;
var allowed_products = JSON.parse(wc_datetime_picker_params.allowed_products); // Parse the JSON string
var current_product_id = parseInt(wc_datetime_picker_params.current_product_id, 10);
if ($(".wc-date-time-picker").length > 0) {
var product_id = wc_date_time_picker_vars.product_id;
var allowed_products = wc_date_time_picker_vars.allowed_products;

if (allowed_products.includes(product_id)) {
  $(".wc-date-time-picker").each(function() {
    var $dateTimePicker = $(this);

    // Initialize datepicker
    $dateTimePicker.find(".wc-date-picker").datepicker({
      dateFormat: "yy-mm-dd",
      minDate: 0
    });

    // Initialize timepicker
    $dateTimePicker.find(".wc-time-picker").slider({
      range: "min",
      min: 0,
      max: 1440,
      step: 15,
      slide: function(event, ui) {
        var minutes = ui.value % 60;
        var hours = (ui.value - minutes) / 60;
        $dateTimePicker.find(".wc-time-picker-display").text(("0" + hours).slice(-2) + ":" + ("0" + minutes).slice(-2));
      }
    });

    // Set default time value
    $dateTimePicker.find(".wc-time-picker-display").text("00:00");
  });

  $("form.cart").on("submit", function(e) {
    var $form = $(this);
    var $dateTimePicker = $form.find(".wc-date-time-picker");
    if ($dateTimePicker.length > 0) {
      var date = $dateTimePicker.find(".wc-date-picker").val();
      var time = $dateTimePicker.find(".wc-time-picker-display").text();

      if (!date.trim() || !time.trim()) {
        e.preventDefault();
        alert("Please select a date and time before adding to the cart.");
        return false;
      }

      $("<input>")
        .attr("type", "hidden")
        .attr("name", "wc_date_time")
        .val(date + " " + time)
        .appendTo($form);
    }
  });
}
}
});
