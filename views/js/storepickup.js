/**
 * DISCLAIMER
 *
 * Do not edit or add to this file.
 * You are not authorized to modify, copy or redistribute this file.
 * Permissions are reserved by FME Modules.
 *
 *  @author    FMM Modules
 *  @copyright FME Modules 2020
 *  @license   Single domain
 */

var STOREPICKUP = STOREPICKUP || {};
    STOREPICKUP.gMap = null;
    STOREPICKUP.gInfoWindow = null;
    STOREPICKUP.gMarkers = null || [];

// load script
setGScript( protocol_link + 'maps.googleapis.com/maps/api/js?key=' + STORE_KEY + '&region=' + region );

$(function() {
    STOREPICKUP.init();
})

STOREPICKUP.init = function() {
    if (psNew) {
        STOREPICKUP.psOneSeven();
    } else {
        STOREPICKUP.psOneSix();
    }

    $(document).on('change', '#store-pickup-select', function(e) {
        STOREPICKUP.setStore($(this).val(), true);
    });
}

STOREPICKUP.destroy = function() {
    STOREPICKUP.gMap = null;
    STOREPICKUP.gInfoWindow = null;
    STOREPICKUP.gMarkers = null || [];
}

STOREPICKUP.initGoogleMap = function() {
    var mapElement = $('#store-pickup-map').get(0);
    if (typeof mapElement !== 'undefined' && typeof mapElement !== 'null' && mapElement) {
        var mapOptions = {
            center: new google.maps.LatLng(defaultLat, defaultLong),
            zoom: pickp_zoom,
            mapTypeId: 'roadmap',
            mapTypeControlOptions: {style: google.maps.MapTypeControlStyle.DROPDOWN_MENU},
            styles: (typeof pickup_map_theme !== 'undefined') ? JSON.parse(pickup_map_theme) : '',
        }

        if (fixed_view) {
            mapOptions.gestureHandling = 'none';
            mapOptions.keyboardShortcuts = false;
        }

        STOREPICKUP.gMap = new google.maps.Map(mapElement, mapOptions);
        STOREPICKUP.gInfoWindow = new google.maps.InfoWindow();

        google.maps.event.addListenerOnce(STOREPICKUP.gMap, 'tilesloaded', function () {
            //IF autolocation is enabled ask user's Permission
            if (STOREPICKUP_USER) {
                navigator.geolocation.getCurrentPosition(
                    STOREPICKUP.findUserLocation,
                    STOREPICKUP.positionErrors
                );
            }
            STOREPICKUP.setStore();
        });
        STOREPICKUP.initMarkers();
    }
}

STOREPICKUP.initMarkers = function() {
    STOREPICKUP.getXmlStores(pickupURL, function(data) {
        var xml = STOREPICKUP.parseXml(data);
        var markerNodes = xml.documentElement.getElementsByTagName('marker');
        var bounds = new google.maps.LatLngBounds();
        var marker = {};
        for (var i = 0; i < markerNodes.length; i++) {
            marker.name = markerNodes[i].getAttribute('name');
            marker.address = markerNodes[i].getAttribute('address');
            marker.other = markerNodes[i].getAttribute('other');
            marker.id_store = markerNodes[i].getAttribute('id_store');
            marker.email = markerNodes[i].getAttribute('email');
            marker.fax = markerNodes[i].getAttribute('fax');
            marker.note = markerNodes[i].getAttribute('note');
            marker.has_store_picture = markerNodes[i].getAttribute('has_store_picture');
            marker.phone = markerNodes[i].getAttribute('phone');
            marker.link = markerNodes[i].getAttribute('link');
            marker.addressNoHtml = markerNodes[i].getAttribute('addressNoHtml');
            marker.latlng = new google.maps.LatLng(
                parseFloat(markerNodes[i].getAttribute('lat')),
                parseFloat(markerNodes[i].getAttribute('lng'))
            );
            STOREPICKUP.createMarker(marker);
            bounds.extend(marker.latlng);
        }

        storeSelect = document.getElementById('store-pickup-select');
        storeSelect.onchange = function () {
            var markerNum = storeSelect.options[storeSelect.selectedIndex].value;
            if (markerNum !== 'none') {
                google.maps.event.trigger(STOREPICKUP.gMarkers[markerNum], 'click');
            }
        };
    });
}

STOREPICKUP.setStore = function(id, swal) {
    var id_store = $('#store-pickup-select option:selected').attr('data-value');
    if (typeof id === 'undefined' || !id) {
        id = $('#store-pickup-select option:selected').val();
    }
    STOREPICKUP.getStoreDates(id_store, swal);
    google.maps.event.trigger(STOREPICKUP.gMarkers[id], 'click');
}

STOREPICKUP.getStoreDates = function(id_store) {
    var request = {
        url: pickupURL,
        method: 'get',
        dataType: 'json',
        data: {
            id_store: id_store,
            action: 'getPickupStoreDates'
        },
        success: function(response) {
            if (response.success) {
                var dateOptions = {
                    locale: iso_lang,
                    minuteIncrement: 1,
                    noCalendar: false,
                    minDate: "today",
                    maxDate: maxDate,
                    dateFormat: "Y-m-d",
                    minuteIncrement: 5,
                    monthSelectorType: 'static',
                    "disable": [
                        function(date) {
                            return (($.inArray(moment(date).format('YYYY-MM-DD') , response.disabled.split(',')) >= 0));
                        }
                    ],
                };

                var pickuptime = null;
                $('.pickuptime').each(function(e) {
                    switch ($(this).attr('data-type')) {
                        case 'date':
                            dateOptions.defaultDate = preselectedPickupDate;
                            dateOptions.enableTime = false;
                            dateOptions.onChange = function(selectedDates, dateStr, instance) {
                                if ($('#storepickup_time_wrapper').length) {
                                    if (typeof dateStr === 'undefined' || !dateStr) {
                                        $('#storepickup_time_wrapper').hide();
                                    } else {
                                        $('#storepickup_time_wrapper').show();
                                        var weekday = moment(dateStr).format('d');
                                        if (typeof response.timeslot !== 'undefined' && response.timeslot.length > 1) {
                                            // set opening hours
                                            if (false !== response.timeslot[weekday].minTime) {
                                                pickuptime.config.minTime = response.timeslot[weekday].minTime;
                                                pickuptime.set("minTime" , response.timeslot[weekday].minTime);
                                                defaultHour = response.timeslot[weekday].minDate;
                                            }
                                            // set closing hours
                                            if (false !== response.timeslot[weekday].maxTime) {
                                                pickuptime.config.maxTime = response.timeslot[weekday].maxTime;
                                                pickuptime.set("maxTime" , response.timeslot[weekday].maxTime);
                                            }

                                            // set default pickup hours
                                            if (false !== response.timeslot[weekday].defaultHour) {
                                                pickuptime.config.defaultHour = response.timeslot[weekday].defaultHour;
                                                pickuptime.set("defaultHour" , response.timeslot[weekday].defaultHour);
                                            }

                                            // set default pickup minutes
                                            if (false !== response.timeslot[weekday].defaultMinute) {
                                                pickuptime.config.defaultMinute = response.timeslot[weekday].defaultMinute;
                                                pickuptime.set("defaultMinute" , response.timeslot[weekday].defaultMinute);
                                            }
                                        }
                                    }
                                }
                            };
                            flatpickr($(this), dateOptions);
                            break;
                        case 'time':
                            dateOptions.defaultDate = preselectedPickupTime;
                            dateOptions.enableTime = true;
                            dateOptions.noCalendar = true;
                            dateOptions.dateFormat = 'H:i';
                            dateOptions.onChange = [];
                            dateOptions.onReady = function() {
                                if (!$.trim($('input[name=storepickup_pickup_date').val())) {
                                    $('#storepickup_time_wrapper').hide();
                                }
                            };
                            pickuptime = flatpickr($(this), dateOptions);
                            break;
                    }
                });
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            alert('Error: ' + textStatus + '<br>' + errorThrown);
        }
    }
    STOREPICKUP.sendRequest(request);
}

STOREPICKUP.getMapStores = function(object) {
    var jsonData = {
        url: pickupURL,
        method: 'get',
        dataType: 'json',
        data: {
            action: 'getMapStores'
        },
        success: function(response) {
            if (response.success) {
                var html = (typeof response.html !== 'undefined')? $.trim(response.html.replace(/<\!--.*?-->/g, "")) : '';
                if ($('#pickup-stores').length) {
                    $('#pickup-stores').remove();
                }

                if (psNew) {
                    $('#js-delivery').after(html);
                } else {
                    object.closest('table.table').after(html);
                }

                STOREPICKUP.initGoogleMap();
                STOREPICKUP.getStoreDates(default_store);
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            alert('Error: ' + textStatus + '<br>' + errorThrown);
        }
    }
    STOREPICKUP.sendRequest(jsonData);
}

STOREPICKUP.changeCarrier = function(id_carrier, object) {
    $('#pickup-stores').remove();
    $('#storepickup-delivery-button').remove();
    if (typeof id_carrier !== 'undefined' && id_carrier && (typeof default_carrier !== 'undefined' || typeof default_carrier !== 'null')) {
        //id_carrier = id_carrier.replace(/,\s*$/, '');
        if (id_carrier === default_carrier) {
            STOREPICKUP.getMapStores(object);
            if (psNew) {
                STOREPICKUP.moveShippingSubmitButton();
            }
        }  else {
            STOREPICKUP.destroy();
        }
    }

    if ($('#store-pickup-select').length) {
        STOREPICKUP.selectStore(0);
    }
}


STOREPICKUP.getXmlStores = function(url, callback) {
    var request = window.ActiveXObject ? new ActiveXObject('Microsoft.XMLHTTP') : new XMLHttpRequest();
    request.onreadystatechange = function() {
        if (request.readyState === 4) {
            //request.onreadystatechange = doNothing;
            callback(request.responseText, request.status);
        }
    };
    request.open('GET', url, true);
    request.send(null);
}

STOREPICKUP.parseXml = function(xml) {
    if (window.ActiveXObject) {
        var doc = new ActiveXObject('Microsoft.XMLDOM');
        doc.loadXML(xml);
        return doc;
    } else if (window.DOMParser) {
        return (new DOMParser()).parseFromString(xml, 'text/xml');
    }
}

STOREPICKUP.createMarker = function(marker) {
    var html = '<b> ' + marker.name + '</b><br/>';
    html += marker.address;
    html += (STOREPICKUP_STORE_EMAIL && marker.email !== '' ? '<br />' + store_translations.translation_7 + ' ' + marker.email : '');
    html += (STOREPICKUP_STORE_FAX && marker.fax !== '' ? '<br />' + store_translations.translation_8 + ' ' + marker.fax : '');
    html += (STOREPICKUP_STORE_NOTE && marker.note !== '' ? '<br />' + store_translations.translation_9 + ' ' + marker.note : '');
    html += (marker.has_store_picture > 0 ? '<br /><br /><img src="' + img_store_dir + parseInt(marker.id_store) + '.jpg" alt="" style="max-width:125px" />' : '');
    html += '<br />' + marker.other;
    html += '<br /><a class="store_direction" href="https://maps.google.com/maps?saddr=&daddr=' + marker.latlng + '" target="_blank">' + store_translations.translation_5 + '<\/a>';
    html += '<a class="store_selection" href="javascript:;" onclick="STOREPICKUP.selectStore(' + marker.id_store + ', true)">' + store_translations.translation_store_sel + '<\/a>';


    var markerOptions = {
        map: STOREPICKUP.gMap,
        position: marker.latlng
    }

    var img_path = img_ps_dir + logo_store;
    if (STOREPICKUP_GLOBAL_ICON > 0) {
        var image = new google.maps.MarkerImage(img_path);
        markerOptions.icon = image;
    }

    var mapMarker = new google.maps.Marker(markerOptions);
    google.maps.event.addListener(mapMarker, 'click', function() {
        STOREPICKUP.gInfoWindow.setContent(html);
        STOREPICKUP.gInfoWindow.open(STOREPICKUP.gMap, mapMarker);
    });

    STOREPICKUP.gMarkers.push(mapMarker);
    if (default_store > 0) {
        google.maps.event.addListenerOnce(STOREPICKUP.gMap, 'tilesloaded', function() {
            $('select#store-pickup-select > option[label="' + default_store + '"]').prop('selected', true);
            $('select#store-pickup-select').trigger('change');
        });
    }
}

STOREPICKUP.findUserLocation = function(position) {
    // Centre the map on the new location
    var coords = position.coords || position.coordinate || position;
    var LtLnPos = new google.maps.LatLng(coords.latitude, coords.longitude);
    STOREPICKUP.gMap.setCenter(LtLnPos);
    STOREPICKUP.gMap.setZoom(10);
    var marker = new google.maps.Marker({
        map: STOREPICKUP.gMap,
        position: LtLnPos,
        title: store_translations.translation_06
    });
    STOREPICKUP.gMarkers.push(marker);

    // And reverse geocode.
    (new google.maps.Geocoder()).geocode({latLng: LtLnPos}, function(resp) {
        //var place = translation_07; //You're around here somewhere!
        if (resp[0]) {
            var bits = [];
            for (var i = 0, I = resp[0].address_components.length; i < I; ++i) {
                var component = resp[0].address_components[i];
                if (jQuery.inArray(component.types, 'political') >= 0) {
                    bits.push(component.long_name);
                }
            }
            // if (bits.length) {
            //     place = bits;
            // }
            marker.setTitle(resp[0].formatted_address);
        }
        //document.getElementById('addressInput').value = place;
        STOREPICKUP.gMap.setZoom(5);
    });
}

STOREPICKUP.positionErrors = function(issue) {
    var message;
    switch(issue.code) {
      case issue.UNKNOWN_ERROR:
        message = store_translations.translation_01; // Unable to find your location
        break;
      case issue.PERMISSION_DENINED:
        message = store_translations.translation_02; //Permission denied
        break;
      case issue.POSITION_UNAVAILABLE:
        message = store_translations.translation_03; //Your location unknown
        break;
      case issue.BREAK:
        message = store_translations.translation_04; //Timeout error
        break;
      default:
        message = store_translations.translation_05; //Location detection not supported in browser
    }
    return message
}
/**
* move shipping form buuton to end
*/
STOREPICKUP.moveShippingSubmitButton = function() {
   $('#storepickup-delivery-button').remove();
     $('#extra_carrier').after('<div id="storepickup-delivery-button" class="clearfix"></div>')
     $('#js-delivery').find('button').hide().clone().attr({
        id:'pickupConfirmDeliveryOption',
        name:'pickupConfirmDeliveryOption'
     }).appendTo('#storepickup-delivery-button').show();
}

/**
* on store dropdown change, get store pickup time
* @param {int} id_store
*/
STOREPICKUP.selectStore = function(id_store, swal) {
   var jsonData = {
       url: pickupURL,
       method: 'post',
       dataType: 'json',
       data: {
           id_store: id_store,
           action: 'selectStore'
       },
       success: function(response) {
           if (typeof response !== 'undefined') {
               var type = (response.hasError)? 'error' : 'success';
                if (typeof swal !== 'undefined' && swal) {
                    Swal.fire({
                        position: 'top-end',
                        type: type,
                        title: response.msg,
                        showConfirmButton: false,
                        timer: 1500
                    });
                }
            }
       },
       error: function(jqXHR, textStatus, errorThrown) {
           alert('Error: ' + textStatus + '<br>' + errorThrown);
       }
   }
   STOREPICKUP.sendRequest(jsonData);
}

/**
 * store pickup for 1.7.x.x
*/
STOREPICKUP.psOneSeven = function() {
    STOREPICKUP.changeCarrier(
        parseInt($('.delivery-options').find('input[type=radio]:checked').val()),
        $('.delivery-options').find('input[type=radio]:checked')
    );

    prestashop.on('updatedDeliveryForm', function(event) {
        STOREPICKUP.changeCarrier(parseInt(event.dataForm[0].value), event.deliveryOption);
    });

    $(document).on('click', "#pickupConfirmDeliveryOption", function(event) {
        event.preventDefault();
        event.stopImmediatePropagation();

        var pickupTime = null;
        var pickupDate = $.trim($('#storepickup_pickup_date').val());
        var id_store = parseInt($('select#store-pickup-select option:selected').attr('data-value'));

        if (id_store === -1) {
            Swal.fire({
                position: 'top-end',
                type: 'error',
                title: store_translations.store_page_error_label,
                showConfirmButton: false,
                timer: 1500
            });
        } else {
            var proceed = true;
            var errorMessage = '';
            $('#fmeStorePage-error').remove();
            if (!moment( pickupDate, 'YYYY-MM-DD' ).isValid()) {
                errorMessage = store_translations.invalid_pickupdate_label;
                $('#storepickup_pickup_date').attr('placeholder', store_translations.invalid_pickupdate_label);
                proceed = false;
            } else {
                proceed = true;
                $('#storepickup_pickup_date').removeAttr('placeholder');
                if ($('#storepickup_pickup_time').length) {
                    pickupTime = $.trim($('#storepickup_pickup_time').val());
                    if (!moment(pickupTime, 'H:i' ).isValid()) {
                        proceed = false;
                        errorMessage = store_translations.invalid_pickuptime_label;
                        $('#storepickup_pickup_time').attr('placeholder', store_translations.invalid_pickuptime_label);
                    } else {
                        proceed = true;
                        $('#storepickup_pickup_time').removeAttr('placeholder');
                    }
                }
            }

            if (!proceed) {
                $('html, body').animate({
                    scrollTop: $("#pickup-stores").offset().top
                }, 300);
                Swal.fire({
                    position: 'top-end',
                    type: 'error',
                    title: errorMessage,
                    showConfirmButton: false,
                    timer: 1500
                });
            } else {
                var resquest = {
                    url: pickupURL,
                    type: 'get',
                    dataType: 'json',
                    async: false,
                    data: {
                        action: 'savePickup',
                        id_store: id_store,
                        pickupTime: pickupTime,
                        pickupDate: pickupDate,
                    },
                    success: function(response) {
                        if (typeof response !== 'undefined') {
                            var type = (response.hasError)? 'error' : 'success';
                            Swal.fire({
                                position: 'top-end',
                                type: type,
                                title: response.msg,
                                showConfirmButton: false,
                                timer: 1500
                            });

                            if (!response.hasError) {
                                $('#js-delivery').find('button[name=confirmDeliveryOption]').click();
                            }
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        alert('Error: ' + textStatus + '<br>' + errorThrown);
                    }
                };
                STOREPICKUP.sendRequest(resquest);
            }
        }
    });
}

/**
* store pickup for 1.6.x.x
*/
STOREPICKUP.psOneSix = function() {
    STOREPICKUP.changeCarrier(
        parseInt($('.delivery_options').find('input[type=radio]:checked').val()),
        $('.delivery_options').find('input[type=radio]:checked')
    );

    $(document).on('change', 'input.delivery_option_radio', function() {
        var id_carrier = parseInt($(this).val());
        STOREPICKUP.changeCarrier(id_carrier, $(this));
    });

    $(document).on('submit', 'form[name=carrier_area]', function(event) {
        if ($('#pickup-stores').length) {
            var pickupTime = null;
            var pickupDate = $.trim($('#storepickup_pickup_date').val());
            var id_store = parseInt($('select#store-pickup-select option:selected').attr('data-value'));

            if (id_store === -1) {
                Swal.fire({
                    position: 'top-end',
                    type: 'error',
                    title: store_translations.store_page_error_label,
                    showConfirmButton: false,
                    timer: 1500
                });
                return false;
            } else {
                var proceed = true;
                var errorMessage = '';
                $('#fme-pickup-stores-page-error').remove();
                if ($('#storepickup_pickup_date').length) {
                    if (!moment( pickupDate, 'YYYY-MM-DD' ).isValid()) {
                        errorMessage = store_translations.invalid_pickupdate_label;
                        $('#storepickup_pickup_date').attr('placeholder', store_translations.invalid_pickupdate_label);
                        proceed = false;
                    } else {
                        proceed = true;
                        $('#storepickup_pickup_date').removeAttr('placeholder');
                        if ($('#storepickup_pickup_time').length) {
                            pickupTime = $.trim($('#storepickup_pickup_time').val());
                            if (!moment(pickupTime, 'H:i' ).isValid()) {
                                proceed = false;
                                errorMessage = store_translations.invalid_pickuptime_label;
                                $('#storepickup_pickup_time').attr('placeholder', store_translations.invalid_pickuptime_label);
                            } else {
                                proceed = true;
                                $('#storepickup_pickup_time').removeAttr('placeholder');
                            }
                        }
                    }
                }

                if (!proceed) {
                    $('html, body').animate({
                        scrollTop: $("#pickup-stores").offset().top
                    }, 300);
                    Swal.fire({
                        position: 'top-end',
                        type: 'error',
                        title: errorMessage,
                        showConfirmButton: false,
                        timer: 1500
                    });
                } else {
                    var resquest = {
                        url: pickupURL,
                        type: 'get',
                        dataType: 'json',
                        async: false,
                        data: {
                            ajax: 1,
                            action: 'savePickup',
                            id_store: id_store,
                            pickupTime: pickupTime,
                            pickupDate: pickupDate,
                        },
                        success: function(response) {
                            if (typeof response !== 'undefined') {
                                var type = (response.hasError)? 'error' : 'success';
                                Swal.fire({
                                    position: 'top-end',
                                    type: type,
                                    title: response.msg,
                                    showConfirmButton: false,
                                    timer: 1500
                                });
                            }
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            alert('Error: ' + textStatus + '<br>' + errorThrown);
                        }
                    };
                    STOREPICKUP.sendRequest(resquest);
                }
                return proceed;
            }
        }
    });
}

STOREPICKUP.sendRequest = function(requestData) {
    $.ajax(requestData);
}

// append script to body
function setGScript(src) {
    document.write('<' + 'script src="' + src + '"><' + '/script>');
}