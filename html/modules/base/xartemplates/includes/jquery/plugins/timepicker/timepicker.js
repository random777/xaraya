/*!
 * jQuery UI Timepicker 0.2.1-xaraya
 *
 * Copyright (c) 2009 Martin Milesich (http://milesich.com/)
 *
 * Some parts are
 *   Copyright (c) 2009 AUTHORS.txt (http://jqueryui.com/about)
 *
 *
 * Additions by Dracos for Xaraya:
 * - Add optional seconds slider
 * - Full localization
 * - Convert $() calls to jQuery()
 *
 * $Id: timepicker.js 28 2009-08-11 20:31:23Z majlo $
 *
 * Depends:
 *  ui.core.js
 *  ui.datepicker.js
 *  ui.slider.js
 */
(function(jQuery) {

/**
 * Extending default values
 */
jQuery.extend(jQuery.datepicker._defaults, {
    stepSeconds: 1, // Number of seconds to step up/down
    stepMinutes: 1, // Number of minutes to step up/down
    stepHours: 1, // Number of hours to step up/down
    time24h: false, // True if 24h time
    showTime: false, // Show timepicker with datepicker
    showSeconds: true, // Show seconds in timepicker
    altTimeField: '', // Selector for an alternate field to store time into
    hourText: 'Hour', // Text label for hour slider
    minuteText: 'Minute', // Text label for minute slider
    secondText: 'Second', // Text label for second slider
    amText: 'am', // Text label for second slider
    pmText: 'pm' // Text label for second slider
});

/**
 * _hideDatepicker must be called with null
 */
jQuery.datepicker._connectDatepickerOverride = jQuery.datepicker._connectDatepicker;
jQuery.datepicker._connectDatepicker = function(target, inst) {
    jQuery.datepicker._connectDatepickerOverride(target, inst);

    // showButtonPanel is required with timepicker
    if (this._get(inst, 'showTime')) {
        inst.settings['showButtonPanel'] = true;
    }

    var showOn = this._get(inst, 'showOn');

    if (showOn == 'button' || showOn == 'both') {
        // Unbind all click events
        inst.trigger.unbind('click');

        // Bind new click event
        inst.trigger.click(function() {
            if (jQuery.datepicker._datepickerShowing && jQuery.datepicker._lastInput == target)
                jQuery.datepicker._hideDatepicker(null); // This override is all about the "null"
            else
                jQuery.datepicker._showDatepicker(target);
            return false;
        });
    }
};

/**
 * Datepicker does not have an onShow event so I need to create it.
 * What I actually doing here is copying original _showDatepicker
 * method to _showDatepickerOverload method.
 */
jQuery.datepicker._showDatepickerOverride = jQuery.datepicker._showDatepicker;
jQuery.datepicker._showDatepicker = function (input) {
    // Call the original method which will show the datepicker
    jQuery.datepicker._showDatepickerOverride(input);

    input = input.target || input;

    // find from button/image trigger
    if (input.nodeName.toLowerCase() != 'input') input = jQuery('input', input.parentNode)[0];

    // Do not show timepicker if datepicker is disabled
    if (jQuery.datepicker._isDisabledDatepicker(input)) return;

    // Get instance to datepicker
    var inst = jQuery.datepicker._getInst(input);

    var showTime = jQuery.datepicker._get(inst, 'showTime');

    // If showTime = True show the timepicker
    if (showTime) jQuery.timepicker.show(input);
};

/**
 * Same as above. Here I need to extend the _checkExternalClick method
 * because I don't want to close the datepicker when the sliders get focus.
 */
jQuery.datepicker._checkExternalClickOverride = jQuery.datepicker._checkExternalClick;
jQuery.datepicker._checkExternalClick = function (event) {
    if (!jQuery.datepicker._curInst) return;
    var jQuerytarget = jQuery(event.target);

    if ((jQuerytarget.parents('#' + jQuery.timepicker._mainDivId).length == 0)) {
        jQuery.datepicker._checkExternalClickOverride(event);
    }
};

/**
 * Datepicker has onHide event but I just want to make it simple for you
 * so I hide the timepicker when datepicker hides.
 */
jQuery.datepicker._hideDatepickerOverride = jQuery.datepicker._hideDatepicker;
jQuery.datepicker._hideDatepicker = function(input, duration) {
    // Some lines from the original method
    var inst = this._curInst;

    if (!inst || (input && inst != jQuery.data(input, PROP_NAME))) return;

    // Get the value of showTime property
    var showTime = this._get(inst, 'showTime');

    if (input === undefined && showTime) {
        if (inst.input) {
            inst.input.val(this._formatDate(inst));
            inst.input.trigger('change'); // fire the change event
        }

        this._updateAlternate(inst);

        if (showTime) jQuery.timepicker.update(this._formatDate(inst));
    }

    // Hide datepicker
    jQuery.datepicker._hideDatepickerOverride(input, duration);

    // Hide the timepicker if enabled
    if (showTime) {
        jQuery.timepicker.hide();
    }
};

/**
 * This is a complete replacement of the _selectDate method.
 * If showed with timepicker do not close when date is selected.
 */
jQuery.datepicker._selectDate = function(id, dateStr) {
    var target = jQuery(id);
    var inst = this._getInst(target[0]);
    var showTime = this._get(inst, 'showTime');
    dateStr = (dateStr != null ? dateStr : this._formatDate(inst));
    if (!showTime) {
        if (inst.input)
            inst.input.val(dateStr);
        this._updateAlternate(inst);
    }
    var onSelect = this._get(inst, 'onSelect');
    if (onSelect)
        onSelect.apply((inst.input ? inst.input[0] : null), [dateStr, inst]);  // trigger custom callback
    else if (inst.input && !showTime)
        inst.input.trigger('change'); // fire the change event
    if (inst.inline)
        this._updateDatepicker(inst);
    else if (!inst.stayOpen) {
        if (showTime) {
            this._updateDatepicker(inst);
        } else {
            this._hideDatepicker(null, this._get(inst, 'duration'));
            this._lastInput = inst.input[0];
            if (typeof(inst.input[0]) != 'object')
                inst.input[0].focus(); // restore focus
            this._lastInput = null;
        }
    }
};

/**
 * We need to resize the timepicker when the datepicker has been changed.
 */
jQuery.datepicker._updateDatepickerOverride = jQuery.datepicker._updateDatepicker;
jQuery.datepicker._updateDatepicker = function(inst) {
    jQuery.datepicker._updateDatepickerOverride(inst);
    jQuery.timepicker.resize();
};

function Timepicker() {}

Timepicker.prototype = {
    init: function()
    {
        this._mainDivId   = 'ui-timepicker-div';
        this._inputId     = null;
        this._orgValue    = null;
        this._orgHour     = null;
        this._orgMinute   = null;
        this._orgSecond   = null;
        this._colonPos    = -1;
        this._visible     = false;
        this._showSeconds = false;
        this._hourText    = 'Hour';
        this._minuteText  = 'Minute';
        this._secondText  = 'Second';
        this._amText      = 'am';
        this._pmText      = 'pm';
        this.tpDiv        = jQuery('<div id="' + this._mainDivId + '" class="ui-datepicker ui-widget ui-widget-content ui-helper-clearfix ui-corner-all ui-helper-hidden-accessible" style="display: none; position: absolute;"></div>');
        this._generateHtml();
    },

    show: function (input)
    {
        // Get instance to datepicker
        var inst = jQuery.datepicker._getInst(input);

        this._time24h = jQuery.datepicker._get(inst, 'time24h');
        this._altTimeField = jQuery.datepicker._get(inst, 'altTimeField');

        this._showSeconds = jQuery.datepicker._get(inst, 'showSeconds');

        this._hourText = jQuery.datepicker._get(inst, 'hourText');
        this._minuteText = jQuery.datepicker._get(inst, 'minuteText');
        this._secondText = jQuery.datepicker._get(inst, 'secondText');
        this._amText = jQuery.datepicker._get(inst, 'amText');
        this._pmText = jQuery.datepicker._get(inst, 'pmText');

        var stepSeconds = parseInt(jQuery.datepicker._get(inst, 'stepSeconds'), 10) || 1;
        var stepMinutes = parseInt(jQuery.datepicker._get(inst, 'stepMinutes'), 10) || 1;
        var stepHours   = parseInt(jQuery.datepicker._get(inst, 'stepHours'), 10)   || 1;

        if (60 % stepSeconds != 0) { stepSeconds = 1; }
        if (60 % stepMinutes != 0) { stepMinutes = 1; }
        if (24 % stepHours != 0)   { stepHours   = 1; }

        jQuery('#tpHourTH').text(this._hourText);
        jQuery('#tpMinuteTH').text(this._minuteText);
        if (this._showSeconds) {
            jQuery('#tpSecondTH').text(this._secondText);
            jQuery('#timePickerSliders tr > *').css('width','33%');
        } else {
            jQuery('#tpSecondTH, #tpSecondTD').hide();
            jQuery('.fragSeconds').prev().andSelf().hide();
            jQuery('#timePickerSliders tr > *').css('width','50%');
        }
        jQuery('#timePickerSliders td').css('padding','.75em 0');
        jQuery('#hourSlider').slider('option', 'max', 24 - stepHours);
        jQuery('#hourSlider').slider('option', 'step', stepHours);

        jQuery('#minuteSlider').slider('option', 'max', 60 - stepMinutes);
        jQuery('#minuteSlider').slider('option', 'step', stepMinutes);

        jQuery('#secondSlider').slider('option', 'max', 60 - stepSeconds);
        jQuery('#secondSlider').slider('option', 'step', stepSeconds);

        this._inputId = input.id;

        if (!this._visible) {
            this._parseTime();
            this._orgValue = jQuery('#' + this._inputId).val();
        }

        this.resize();

        jQuery('#' + this._mainDivId).show();

        this._visible = true;

        var dpDiv     = jQuery('#' + jQuery.datepicker._mainDivId);
        var dpDivPos  = dpDiv.position();

        var viewWidth = (window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth) + jQuery(document).scrollLeft();
        var tpRight   = this.tpDiv.offset().left + this.tpDiv.outerWidth();

        if (tpRight > viewWidth) {
            dpDiv.css('left', dpDivPos.left - (tpRight - viewWidth) - 5);
            this.tpDiv.css('left', dpDiv.offset().left + dpDiv.outerWidth() + 'px');
        }
    },

    update: function (fd)
    {
        var curTime = jQuery('#' + this._mainDivId + ' span.fragHours').text()
                    + ':'
                    + jQuery('#' + this._mainDivId + ' span.fragMinutes').text();
        if (this._showSeconds) {
            curTime += ':' + jQuery('#' + this._mainDivId + ' span.fragSeconds').text();
        }

        if (!this._time24h) {
            curTime += ' ' + jQuery('#' + this._mainDivId + ' span.fragAmpm').text();
        }

        var curDate = jQuery('#' + this._inputId).val();

        jQuery('#' + this._inputId).val(fd + ' ' + curTime);

        if (this._altTimeField) {
            jQuery(this._altTimeField).each(function() { jQuery(this).val(curTime); });
        }
    },

    hide: function ()
    {
        this._visible = false;
        jQuery('#' + this._mainDivId).hide();
    },

    resize: function ()
    {
        var dpDiv = jQuery('#' + jQuery.datepicker._mainDivId);
        var dpDivPos = dpDiv.position();

        var hdrHeight = jQuery('#' + jQuery.datepicker._mainDivId +  ' > div.ui-datepicker-header:first-child').height();

        jQuery('#' + this._mainDivId + ' > div.ui-datepicker-header:first-child').css('height', hdrHeight);

        this.tpDiv.css({
            'height': dpDiv.height(),
            'top'   : dpDivPos.top,
            'left'  : dpDivPos.left + dpDiv.outerWidth() + 'px'
        });

        jQuery('#hourSlider').css('height',   this.tpDiv.height() - (3.5 * hdrHeight));
        jQuery('#minuteSlider').css('height', this.tpDiv.height() - (3.5 * hdrHeight));
        jQuery('#secondSlider').css('height', this.tpDiv.height() - (3.5 * hdrHeight));
    },

    _generateHtml: function ()
    {
        var self = this;
        var html = '';

        html += '<div class="ui-datepicker-header ui-widget-header ui-helper-clearfix ui-corner-all">';
        html += '<div class="ui-datepicker-title" style="margin:0">';
        html += '<span class="fragHours"></span><span class="delim">:</span><span class="fragMinutes"></span>';
        html += '<span class="delim">:</span><span class="fragSeconds"></span>';
        html += ' <span class="fragAmpm"></span></div></div><table id="timePickerSliders">';
        html += '<tr><th id="tpHourTH"></th><th id="tpMinuteTH"></th><th id="tpSecondTH"></th></tr>';
        html += '<tr><td id="tpHourTD" style="text-align:center"><div id="hourSlider" class="slider" style="margin:auto"></div></td><td id="tpMinuteTD" style="text-align:center"><div id="minuteSlider" class="slider" style="margin:auto"></div></td><td id="tpSecondTD" style="text-align:center"><div id="secondSlider" class="slider" style="margin:auto"></div></td>';
        html += '</tr></table>';

        this.tpDiv.empty().append(html);
        jQuery('body').append(this.tpDiv);

        jQuery(this.tpDiv).find('th:eq(0)').text(this._hourText);
        jQuery(this.tpDiv).find('th:eq(1)').text(this._minuteText);
        jQuery(this.tpDiv).find('th:eq(2)').text(this._secondText);

        jQuery('#hourSlider').slider({
            orientation: "vertical",
            range: 'min',
            min: 0,
            max: 23,
            step: 1,
            slide: function(event, ui) {
                self._writeTime('hour', ui.value);
            },
            stop: function(event, ui) {
                jQuery('#' + self._inputId).focus();
            }
        });

        jQuery('#minuteSlider').slider({
            orientation: "vertical",
            range: 'min',
            min: 0,
            max: 59,
            step: 1,
            slide: function(event, ui) {
                self._writeTime('minute', ui.value);
            },
            stop: function(event, ui) {
                jQuery('#' + self._inputId).focus();
            }
        });

        jQuery('#secondSlider').slider({
            orientation: "vertical",
            range: 'min',
            min: 0,
            max: 59,
            step: 1,
            slide: function(event, ui) {
                self._writeTime('second', ui.value);
            },
            stop: function(event, ui) {
                jQuery('#' + self._inputId).focus();
            }
        });
        jQuery('#hourSlider > a').css('padding', 0);
        jQuery('#minuteSlider > a').css('padding', 0);
        jQuery('#secondSlider > a').css('padding', 0);
    },

    _writeTime: function (type, value)
    {
        if (type == 'hour') {
            if (!this._time24h) {
                if (value < 12) {
                    jQuery('#' + this._mainDivId + ' span.fragAmpm').text(this._amText);
                } else {
                    jQuery('#' + this._mainDivId + ' span.fragAmpm').text(this._pmText);
                    value -= 12;
                }

                if (value == 0) value = 12;
            } else {
                jQuery('#' + this._mainDivId + ' span.fragAmpm').text('');
            }

            if (value < 10) value = '0' + value;
            jQuery('#' + this._mainDivId + ' span.fragHours').text(value);
        }

        if (type == 'minute') {
            if (value < 10) value = '0' + value;
            jQuery('#' + this._mainDivId + ' span.fragMinutes').text(value);
        }
        if (type == 'second') {
            if (value < 10) value = '0' + value;
            jQuery('#' + this._mainDivId + ' span.fragSeconds').text(value);
        }
    },

    _parseTime: function ()
    {
        var dt = jQuery('#' + this._inputId).val();

        this._colonPos = dt.search(':');

        var m = 0, h = 0, s = 0, a = '';

        if (this._colonPos != -1) {
            h = parseInt(dt.substr(this._colonPos - 2, 2), 10);
            m = parseInt(dt.substr(this._colonPos + 1, 2), 10);
            s = parseInt(dt.substr(this._colonPos + 4, 2), 10);
            a = jQuery.trim(dt.substr(this._colonPos + 7, 3));
        }

        a = a.toLowerCase();

        if (a != 'am' && a != 'pm') {
            a = '';
        }

        if (h < 0) h = 0;
        if (m < 0) m = 0;
        if (s < 0) s = 0;

        if (h > 23) h = 23;
        if (m > 59) m = 59;
        if (s > 59) s = 59;

        if (a == 'pm' && h  < 12) h += 12;
        if (a == 'am' && h == 12) h  = 0;

        this._setTime('hour',   h);
        this._setTime('minute', m);
        this._setTime('second', s);

        this._orgHour   = h;
        this._orgMinute = m;
        this._orgSecond = s;
    },

    _setTime: function (type, value)
    {
        if (isNaN(value)) value = 0;
        if (value < 0)    value = 0;
        if (value > 23 && type == 'hour')   value = 23;
        if (value > 59 && (type == 'minute' || type == 'second')) value = 59;

        if (type == 'hour') {
            jQuery('#hourSlider').slider('value', value);
        }

        if (type == 'minute') {
            jQuery('#minuteSlider').slider('value', value);
        }

        if (type == 'second') {
            jQuery('#secondSlider').slider('value', value);
        }

        this._writeTime(type, value);
    }
};

jQuery.timepicker = new Timepicker();
jQuery('document').ready(function () {jQuery.timepicker.init();});

})(jQuery);
