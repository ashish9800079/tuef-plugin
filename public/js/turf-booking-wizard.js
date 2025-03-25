/**
 * Multi-step booking wizard functionality
 */
(function($, window, document, undefined) {
    'use strict';

    // Check if jQuery is available
    if (typeof $ !== 'function') {
        console.error('Turf Booking Wizard requires jQuery');
        return;
    }

    // Check if the wizard container exists
    if (!$('#tb-booking-wizard').length) {
        return;
    }

    // Global booking variables
    var bookingData = {
        courtId: $('#tb-booking-wizard').data('court-id'),
        date: '',
        timeFrom: '',
        timeTo: '',
        addons: [],
        duration: 0,
        basePrice: 0,
        totalPrice: 0
    };

    // Initialize the booking wizard
    function initBookingWizard() {
        initDatePicker();
        initWizardNavigation();
        initAddonSelection();
        initBookingSubmission();
    }

    // Initialize date picker
    function initDatePicker() {
        if (!$.fn.datepicker) {
            console.error('jQuery UI Datepicker is required for the booking wizard');
            return;
        }

        // Get today's date
        var today = new Date();
        
        // Get settings for maximum booking days in advance
        var maxDays = 30; // Default to 30 days
        
        // Initialize the date picker
        $('#tb-date-picker').datepicker({
            minDate: 0,
            maxDate: '+' + maxDays + 'd',
            dateFormat: 'yy-mm-dd',
            firstDay: 1,
            showOtherMonths: true,
            selectOtherMonths: true,
            onSelect: function(dateText) {
                // Store selected date
                bookingData.date = dateText;
                
                // Load time slots for the selected date
                loadTimeSlots(dateText);
            }
        });
    }

    // Load time slots for selected date
    function loadTimeSlots(date) {
        var $timeSlots = $('#tb-time-slots');
        
        // Show loading
        $timeSlots.html('<div class="tb-loading"><div class="tb-spinner"></div><p>Loading available time slots...</p></div>');
        
        // Disable next button
        $('#tb-to-step-2').prop('disabled', true);
        
        // Reset time selection
        bookingData.timeFrom = '';
        bookingData.timeTo = '';
        
        // Get time slots via AJAX
        $.ajax({
            url: tb_public_params.ajax_url,
            type: 'POST',
            data: {
                action: 'get_court_availability',
                court_id: bookingData.courtId,
                date: date,
                nonce: tb_public_params.availability_nonce
            },
            success: function(response) {
                if (response.success && response.data && response.data.slots) {
                    var slots = response.data.slots;
                    var slotsHtml = '';
                    
                    if (slots.length === 0) {
                        $timeSlots.html('<div class="tb-select-date-message">No time slots available for this date.</div>');
                        return;
                    }
                    
                    // Render time slots
                    for (var i = 0; i < slots.length; i++) {
                        var slot = slots[i];
                        var slotClass = slot.available ? 'tb-time-slot' : 'tb-time-slot disabled';
                        
                        slotsHtml += '<div class="' + slotClass + '" ' + 
                            (slot.available ? 'data-from="' + slot.from + '" data-to="' + slot.to + '" data-price="' + slot.price + '"' : '') + '>' +
                            '<span class="tb-slot-time">' + slot.from + ' - ' + slot.to + '</span>' +
                            (slot.available ? '<span class="tb-slot-price">' + tb_public_params.currency_symbol + parseFloat(slot.price).toFixed(2) + '</span>' : 
                            '<span class="tb-slot-status">' + tb_public_params.booked_text + '</span>') +
                            '</div>';
                    }
                    
                    $timeSlots.html(slotsHtml);
                    
                    // Time slot selection
                    $('.tb-time-slot:not(.disabled)').on('click', function() {
                        $('.tb-time-slot').removeClass('selected');
                        $(this).addClass('selected');
                        
                        // Store selected time slot
                        bookingData.timeFrom = $(this).data('from');
                        bookingData.timeTo = $(this).data('to');
                        bookingData.basePrice = $(this).data('price');
                        
                        // Calculate duration in hours
                        var timeFrom = bookingData.timeFrom.split(':');
                        var timeTo = bookingData.timeTo.split(':');
                        var dateFrom = new Date(0, 0, 0, timeFrom[0], timeFrom[1]);
                        var dateTo = new Date(0, 0, 0, timeTo[0], timeTo[1]);
                        
                        // Handle crossing midnight
                        if (dateTo < dateFrom) {
                            dateTo.setDate(dateTo.getDate() + 1);
                        }
                        
                        // Get difference in hours
                        var diff = (dateTo - dateFrom) / (1000 * 60 * 60);
                        bookingData.duration = diff;
                        
                        // Enable next button
                        $('#tb-to-step-2').prop('disabled', false);
                    });
                } else {
                    $timeSlots.html('<div class="tb-select-date-message">Error loading time slots. Please try again.</div>');
                }
            },
            error: function() {
                $timeSlots.html('<div class="tb-select-date-message">Error loading time slots. Please try again.</div>');
            }
        });
    }

    // Initialize wizard navigation
    function initWizardNavigation() {
        // Step 1 to Step 2
        $('#tb-to-step-2').on('click', function() {
            if (!bookingData.date || !bookingData.timeFrom || !bookingData.timeTo) {
                showError('Please select a date and time slot.');
                return;
            }
            
            // Hide error message
            hideError();
            
            // Set summary data
            updateSummary();
            
            // Go to step 2
            goToStep(2);
        });
        
        // Step 2 to Step 1
        $('#tb-back-to-1').on('click', function() {
            goToStep(1);
        });
        
        // Step 2 to Step 3 (with addons)
        $('#tb-to-step-3').on('click', function() {
            // Update summary with addons
            updateSummary();
            
            // Go to step 3
            goToStep(3);
        });
        
        // Step 3 to Step 2
        $('#tb-back-to-2').on('click', function() {
            goToStep(2);
        });
        
        // Step 3 to Step 4
        $('#tb-to-step-4').on('click', function() {
            // Validate contact info
            if (!validateContactInfo()) {
                return;
            }
            
            // Go to step 4
            goToStep(4);
            
            // Prepare the booking
            prepareBooking();
        });
        
        // Step 4 to Step 3
        $('#tb-back-to-3').on('click', function() {
            goToStep(3);
        });
    }

    // Go to specific step
    function goToStep(step) {
        // Hide all steps
        $('.tb-booking-step').hide();
        
        // Show the requested step
        $('#tb-step-' + step).show();
        
        // Update progress bar
        updateProgressBar(step);
    }

    // Update progress bar
    function updateProgressBar(currentStep) {
        $('.tb-progress-step').removeClass('active completed');
        
        for (var i = 1; i <= currentStep; i++) {
            if (i < currentStep) {
                $('.tb-progress-step[data-step="' + i + '"]').addClass('completed');
            } else {
                $('.tb-progress-step[data-step="' + i + '"]').addClass('active');
            }
        }
    }

    // Initialize addon selection
    function initAddonSelection() {
        $('.tb-addon-item input[type="checkbox"]').on('change', function() {
            var $addonItem = $(this).closest('.tb-addon-item');
            var addonId = $addonItem.data('addon-id');
            var addonPrice = parseFloat($addonItem.data('addon-price'));
            var addonType = $addonItem.data('addon-type');
            
            if ($(this).is(':checked')) {
                // Add addon to selection
                bookingData.addons.push({
                    id: addonId,
                    price: addonPrice,
                    type: addonType
                });
            } else {
                // Remove addon from selection
                bookingData.addons = bookingData.addons.filter(function(addon) {
                    return addon.id !== addonId;
                });
            }
        });
    }

    // Update booking summary
    function updateSummary() {
        // Update date and time
        var formattedDate = formatDate(bookingData.date);
        $('#tb-summary-date').text(formattedDate);
        $('#tb-summary-time').text(bookingData.timeFrom + ' - ' + bookingData.timeTo);
        $('#tb-summary-duration').text(bookingData.duration.toFixed(1) + ' hours');
        
        // Calculate court price
        var courtPrice = bookingData.basePrice * bookingData.duration;
        $('#tb-price-court').text(tb_public_params.currency_symbol + courtPrice.toFixed(2));
        
        // Calculate total price
        var totalPrice = courtPrice;
        var addonsHtml = '';
        
        // Add addons to the breakdown
        if (bookingData.addons.length > 0) {
            bookingData.addons.forEach(function(addon) {
                var addonTotal = (addon.type === 'per_hour') 
                    ? (addon.price * bookingData.duration) 
                    : addon.price;
                
                totalPrice += addonTotal;
                
                // Add to addons breakdown
                addonsHtml += '<div class="tb-price-item">' +
                    '<span class="tb-price-label">' + $('#tb-addon-' + addon.id).closest('.tb-addon-item').find('h4').text() + ':</span>' +
                    '<span class="tb-price-value">' + tb_public_params.currency_symbol + addonTotal.toFixed(2) + '</span>' +
                    '</div>';
            });
        }
        
        $('#tb-addons-breakdown').html(addonsHtml);
        $('#tb-price-total').text(tb_public_params.currency_symbol + totalPrice.toFixed(2));
        
        // Store total price
        bookingData.totalPrice = totalPrice;
    }

    // Format date nicely
    function formatDate(dateString) {
        var date = new Date(dateString);
        var options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        return date.toLocaleDateString(navigator.language || 'en-US', options);
    }

    // Validate contact information
    function validateContactInfo() {
        var name = $('#tb-booking-name').val();
        var email = $('#tb-booking-email').val();
        var phone = $('#tb-booking-phone').val();
        
        if (!name || !email || !phone) {
            showError('Please fill in all contact information.');
            return false;
        }
        
        // Basic email validation
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            showError('Please enter a valid email address.');
            return false;
        }
        
        // Hide error message
        hideError();
        
        return true;
    }

    // Prepare booking for payment
    function prepareBooking() {
        // Show payment processing
        $('#tb-payment-container').html('<div class="tb-payment-loading"><div class="tb-spinner"></div><p>Processing your booking...</p></div>');
        
        // Set hidden form values
        $('#hidden-booking-date').val(bookingData.date);
        $('#hidden-booking-time-from').val(bookingData.timeFrom);
        $('#hidden-booking-time-to').val(bookingData.timeTo);
        
        // Set addons
        var addonIds = bookingData.addons.map(function(addon) {
            return addon.id;
        });
        $('#hidden-booking-addons').val(JSON.stringify(addonIds));
        
        // Submit booking
        $.ajax({
            url: tb_public_params.ajax_url,
            type: 'POST',
            data: {
                action: 'create_booking',
                court_id: bookingData.courtId,
                date: bookingData.date,
                time_from: bookingData.timeFrom,
                time_to: bookingData.timeTo,
                name: $('#tb-booking-name').val(),
                email: $('#tb-booking-email').val(),
                phone: $('#tb-booking-phone').val(),
                addons: addonIds,
                nonce: tb_public_params.booking_nonce
            },
            success: function(response) {
                if (response.success) {
                    // Redirect to the appropriate page
                    window.location.href = response.data.redirect_url;
                } else {
                    showError(response.data.message || 'Error creating booking. Please try again.');
                    goToStep(3); // Go back to details step
                }
            },
            error: function() {
                showError('An error occurred while processing your booking. Please try again.');
                goToStep(3); // Go back to details step
            }
        });
    }

    // Show error message
    function showError(message) {
        $('#tb-booking-error').html(message).show();
    }

    // Hide error message
    function hideError() {
        $('#tb-booking-error').hide();
    }

    // Initialize when document is ready
    $(function() {
        initBookingWizard();
    });

})(jQuery, window, document);
