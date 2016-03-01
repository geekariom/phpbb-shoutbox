$(document).ready(function() {
    var shoutbox = new Shoutbox(shoutbox_opts);
    shoutbox.init();
});

/**
 * Shoutbox
 * @constructor
 */
var Shoutbox = function(opts) {
    // Vars
    this.opts = opts;
    this.lastMsg = parseInt(getCookie('shoutbox_last_msg', 0));
    this.colorsLoaded = false;
    this.readOnly = false;
    this.smileysLoaded = false;
    this.lockSend = false;
    this.canLoadMore = true;
    this.loadWIP = false;
    this.scrollLimit = 0.75;
    this.timeago = false;
    this.volume = parseInt(getCookie('shoutbox_sound', -1));

    // jquery selector
    this.$div = $('#shoutbox');
    this.$contain = $('#shoutbox-content');
    this.$msg = $('#shoutbox-msg');
    this.$lastMsg = $('#shoutbox-lastmsg');
    this.$btns = $('.shoutbox-btn');
    this.$colors = $('#color-pane');
    this.$smileys = $('#smileys-list');

    // Timer
    this.timer = null;
    this.nbrCheck = 0;
    this.minInterval = opts.min;
    this.maxInterval = opts.max;
    this.interval = this.minInterval;

    var self = this;

    /**
     * Init
     * @returns {Shoutbox}
     */
    this.init = function() {
        // First check without ajax
        var lastMsg = parseInt(self.$lastMsg.val());
        if (self.lastMsg < lastMsg) {
            self.updateLastMsg(lastMsg);
            self.playSound('receive');
        }

        // Lauch timer
        self.startTimer();
        if (self.timeago) {
            $('.timeago').timeago().removeAttr('title');
        }

        // Scroll
        if (self.opts.scroll) {
            self.$contain.scroll(function(e) { self.scroll(e); });
        }

        if (!self.readOnly) {
            // Style's buttons
            $('.btn-bold', self.$btns).click(function() { self.bbcode('[b]', '[/b]'); });
            $('.btn-italic', self.$btns).click(function() { self.bbcode('[i]', '[/i]'); });
            $('.btn-underline', self.$btns).click(function() { self.bbcode('[u]', '[/u]'); });
            $('.btn-strike', self.$btns).click(function() { self.bbcode('[s]', '[/s]'); });
            $('.btn-link', self.$btns).click(function() { self.bbcode('[url]', '[/url]'); });

            // Colors
            $('.btn-colors', self.$btns).click(function() { self.showColors(); });
            self.$btns.on('click', '#color-pane .line div', function() {
                self.bbcode('[color=#' + $(this).attr('data-color') + ']', '[/color]');
            });

            // Smileys
            $('.btn-smiley', self.$btns).click(function() { self.showSmileys(); });
            self.$btns.on('click', '#smileys-list img', function(e) {
                e.preventDefault();
                self.bbcode($(this).attr('alt'), '');
            });

            // hide btns on mouseout except if input has focus
            if (self.opts.fullscreen) {
                self.$btns.show();
                self.$msg.focus();
            } else {
                self.$div.hover(
                    function() {
                        self.$btns.show('fast');
                        $(this).addClass('focus');
                    },
                    function() {
                        if (!self.$msg.is(':focus')) {
                            self.$btns.hide();
                        }
                        $(this).removeClass('focus');
                    }
                );
                self.$msg.focusout(function() {
                    if (!self.$div.hasClass('focus')) {
                        self.$btns.hide();
                    }
                });
            }

            // Click on author
            self.$contain.on('click', 'a.author', function(e) {
                e.preventDefault();
                var msg = self.$msg.val();
                var dest = '@' + $(this).html() + ' : ';
                if (msg == '') {
                    msg = dest;
                } else if (msg[0] == '@') {
                    msg = msg.replace(/^@(.*) : /i, dest);
                } else {
                    msg = dest+msg;
                }
                self.$msg.val(msg);

                return false;
            });

            // Form submit
            $('form', self.$div).submit(function(e) {
                e.preventDefault();

                return false;
            });

            // Suppression
            self.$div.on('click', 'img.delete', function() {
                var id = $(this).parent('div').attr('data-id');
                self.delete(id);
            });

            // Press key in the form input
            self.$msg.keyup(function(e) { self.keyPress(e); });
        } // => readonly

        // Sound button
        $('.btn-mute', self.$btns).click(function() {
            if (!self.volume) {
                self.setVolume(100);
            } else {
                self.setVolume(0);
            }
        });

        return self;
    }; // => init()

    /**
     * Check new message
     * @returns {Shoutbox}
     */
    this.check = function() {
        var silent = (arguments.length === 1 && arguments[0]);
        self.stopTimer();

        $.ajax({
            url: self.opts.ajax,
            type: 'post',
            dataType: 'json',
            data: {
                action: 'check',
                last: self.lastMsg
            },
            success: function(obj) {
                if (obj.length) { // New message
                    $(obj).each(function(i, msg) {
                        $('li', self.$contain).prepend(self.getMessageHTML(msg));
                        if (msg.TIMESTAMP > self.lastMsg) {
                            self.updateLastMsg(msg.TIMESTAMP);
                        }
                        if (msg.TIMEAGO && self.timeago) {
                            $('#shoutbox-msg-'+msg.ID+' time').timeago().removeAttr('title');
                        }
                    });

                    self.$contain.scrollTop(0);
                    self.nbrCheck = 0;
                    self.interval = self.minInterval;

                    if (!silent) {
                        self.playSound('receive');
                    }
                } else {
                    // No msg : every 5 check, we increment the check interval
                    if (self.nbrCheck > 5) {
                        self.nbrCheck = 0;
                        self.interval = Math.min(self.maxInterval, self.interval+1);
                    }
                }

                self.nbrCheck++;
                self.startTimer();
            },
            error: function() {
                self.startTimer();
            }
        });

        return self;
    }; // => check()

    /**
     * Send message
     * @returns {Shoutbox}
     */
    this.send = function() {
        // Lock send
        if (self.lockSend || self.readOnly) {
            return self;
        }

        // Get the message text
        var msg = self.$msg.val();
        if (!msg.length) {
            return self;
        }

        self.stopTimer();
        self.lockSend = true;
        $.ajax({
            url: self.opts.ajax,
            type: 'post',
            dataType: 'json',
            data: {
                action: 'send',
                msg: msg
            },
            success: function(obj) {
                if (obj.success) {
                    self.$msg.val('');
                    self.$colors.hide('slow');
                    self.$smileys.hide('slow');
                    self.playSound('send');
                }
                self.lockSend = false;
                self.check(true);
            },
            error: function() {
                self.lockSend = false;
                self.startTimer();
            }
        });

        return self;
    }; // => send()

    /**
     * Infinit scroll
     * @param {event} e
     * @returns {Shoutbox}
     */
    this.scroll = function(e) {
        // Anymore to find
        if (!self.canLoadMore) {
            return self;
        }

        // WIP
        if (self.loadWIP) {
            return self;
        }

        // After scrollLimit % : loading next messages
        if ((self.$contain.scrollTop() + self.$contain.height()) > self.$contain[0].scrollHeight * self.scrollLimit) {
            self.loadWIP = true;
            $.ajax({
                url: self.opts.ajax,
                type: 'post',
                dataType: 'json',
                data: {
                    action: 'scroll',
                    offset: $('li div', self.$contain).length
                },
                success: function(obj) {
                    if (obj.length == 0) {
                        self.canLoadMore = false;
                    }

                    $(obj).each(function(i, msg) {
                        $('li', self.$contain).append(self.getMessageHTML(msg));
                    });

                    self.loadWIP = false;
                },
                error: function() {
                    self.loadWIP = false;
                }
            });
        }

        return self;
    }; // => scroll()

    /**
     * Delete a message
     * @param id Message id
     */
    this.delete = function(id) {
        $.ajax({
            url: self.opts.ajax,
            type: 'post',
            dataType: 'json',
            data: {
                action: 'delete',
                id: id
            },
            success: function(obj) {
                if (obj.success) {
                    $('#shoutbox-msg-'+id).hide('slow', function() {
                        $(this).remove();
                    });
                }
            }
        });
    }; // => delete()

    /**
     * Press key in the input form
     * @param {event} e
     */
    this.keyPress = function(e) {
        if (e.which == 13) { // Enter
            self.send();
        }
    }; // => keyPress();

    /**
     * Get the HTML code
     * @param msg
     * @returns {HTMLElement}
     */
    this.getMessageHTML = function(msg) {
        var $msg = $('<div><span class="info"><time></time> ' + self.opts.i18n.by + ' <a class="author"></a>&nbsp;:&nbsp;</span>' + msg.TEXT + '</div>');
        $msg.attr({
            id: 'shoutbox-msg-' + msg.ID,
            'data-id': msg.ID
        });
        var iso_date = new Date(msg.TIMESTAMP*1000).toISOString();
        $('time', $msg).attr('datetime', iso_date).text(msg.DATE_USER).removeAttr('title');
        $('.author', $msg).text(msg.USER);
        if (msg.CAN_DELETE) {
            $msg.prepend('<img src="ext/matthieuy/shoutbox/styles/prosilver/theme/images/delete.png" class="delete" title="'+ self.opts.i18n.del +'" alt="Delete">');
        }
        if (opts.quote && msg.QUOTE) {
            $msg.addClass('quote');
        }

        return $msg;
    }; // => getMessageHTML()

    /**
     * Add bbcode
     * @param {String} start balise
     * @param {String|undefined} end balise (optional)
     */
    this.bbcode = function(start, end) {
        if (typeof end == 'undefined') {
            end = '';
        }
        var element = document.getElementById(self.$msg.attr('id'));
        if (document.selection) {
            element.focus();
            sel = document.selection.createRange();
            sel.text = start + sel.text + end;
        } else if (element.selectionStart || element.selectionStart == '0') {
            element.focus();
            var startPos = element.selectionStart;
            var endPos = element.selectionEnd;
            element.value = element.value.substring(0, startPos) + start + element.value.substring(startPos, endPos) + end + element.value.substring(endPos, element.value.length);
        } else {
            element.value += start + end;
        }
    }; //=> bbcode()

    /**
     * Show color panel
     * @returns {Shoutbox}
     */
    this.showColors = function() {
        // Hide
        if (self.$colors.is(':visible')) {
            self.$colors.hide('slow');
            return self;
        }

        // Create panel
        if (!self.colorsLoaded) {
            var r = 0, v = 0, b = 0;
            var numberList = new Array(6);
            var color = '';
            numberList[0] = '00';
            numberList[1] = '40';
            numberList[2] = '80';
            numberList[3] = 'BF';
            numberList[4] = 'FF';

            var div = $('<div class="line"></div>').css({
                width: '375px',
                maxWidth: '100%'
            });
            for (r=0; r<5; r++) {
                for (v=0; v<5; v++) {
                    for (b=0; b<5; b++) {
                        color = String(numberList[r]) + String(numberList[v]) + String(numberList[b]);
                        $('<div></div>')
                            .css('background-color', '#'+color)
                            .attr('data-color', color)
                            .appendTo(div);
                    }
                }
            }
            self.$colors.append(div);
            self.colorsLoaded = true;
        }

        // Show panel
        self.$smileys.hide('slow');
        self.$colors.show('slow');

        return self;
    }; //=> showColors()

    /**
     * Show smileys
     * @returns {Shoutbox}
     */
    this.showSmileys = function() {
        // Hide smileys if they are visible
        if (self.$smileys.is(':visible')) {
            self.$smileys.hide('slow');
            return self;
        }
        self.$colors.hide('slow');

        // Show smileys
        if (self.smileysLoaded) {
            self.$smileys.show('slow');
            return self;
        }

        // Load smiley list from ajax
        $.ajax({
            url: self.opts.ajax,
            type: 'post',
            dataType: 'json',
            data: { action: 'smileys' },
            success: function(obj) {
                self.$smileys.html('<br>');
                $(obj).each(function(i, img) {
                    self.$smileys.append('<img src="'+img.url+'" alt="'+img.code+'" title="'+img.emotion+'" height="'+img.height+'" width="'+img.width+'">&nbsp;');
                });
                self.$smileys.show('slow');
                self.smileysLoaded = true;
            }
        });

        return self;
    }; // => showSmileys()

    /**
     * Lauch the check timer
     */
    this.startTimer = function() {
        self.timer = setTimeout(self.check, self.interval*1000);
    }; // => startTimer()

    /**
     * Stop the check timer
     */
    this.stopTimer = function() {
        clearTimeout(self.timer);
        self.timer = null;
    }; // => stopTimer()

    /**
     * Update the last msg receive
     * @param timestamp Message timestamp
     */
    this.updateLastMsg = function(timestamp) {
        timestamp = parseInt(timestamp);
        self.$lastMsg.val(timestamp);
        setCookie('shoutbox_last_msg', timestamp);
        self.lastMsg = timestamp;
    }; // => updateLastMsg()

    /**
     * Play a sound
     * @param {string} sound Name of the file (in sound dir)
     */
    this.playSound = function(sound) {
        // Sound disabled or mobile device or mute
        if (!self.opts.sound || !self.volume || self.isMobile()) {
            return self;
        }

        // Create sound and autoplay
        if ($('audio#shoutbox-'+sound).length === 0) {
            var audio = $('<audio />').attr({
                id: 'shoutbox-'+sound,
                autoplay: true,
                oncanplaythrough: function() {
                    this.volume = self.volume / 100;
                }
            });
            $('<source />').attr('src', self.opts.theme_path + '/sound/'+sound+'.mp3').appendTo(audio);
            $('<source />').attr('src', self.opts.theme_path + '/sound/'+sound+'.ogg').appendTo(audio);
            $(audio).appendTo('body');
            return;
        }

        // Play sound
        document.getElementById('shoutbox-'+sound).volume = self.volume / 100;
        document.getElementById('shoutbox-'+sound).play();
    }; // => playSound()

    /**
     * Set volume level
     * @param {int} volume Percent level
     */
    this.setVolume = function(volume) {
        if (volume) {
            $('.btn-mute', self.$btns).attr({
                title: self.opts.i18n.sound_disable,
                src: self.opts.theme_path + '/images/mute.png'
            });
        } else {
            $('.btn-mute', self.$btns).attr({
                title: self.opts.i18n.sound_enable,
                src: self.opts.theme_path + '/images/sound.png'
            });

        }
        this.volume = volume;
        setCookie('shoutbox_sound', volume);
    };

    /**
     * Check if is mobile device
     * @returns {boolean}
     */
    this.isMobile = function() {
        if (navigator.userAgent) {
            return (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent));
        }

        return false;
    }; // => isMobile()

    // Read only
    if (!this.$msg.length) {
        this.readOnly = true;
    }

    // Timeago
    if (this.opts.timeago && typeof $.timeago == 'function') {
        this.timeago = true;
        var translate = $.extend($.timeago.settings.strings, self.opts.i18n);
        $.timeago.settings.strings = translate;
    }

    if (getCookie('shoutbox_sound', -1) == -1) {
        self.setVolume(100);
    }
};

/**
 * Set a cookie
 * @param cname Cookie name
 * @param cvalue Cookie value
 */
function setCookie(cname, cvalue) {
    var d = new Date();
    d.setTime(d.getTime() + (30*24*60*60*1000));
    var expires = "expires="+d.toUTCString();
    document.cookie = cname + "=" + cvalue + "; " + expires;
}

/**
 * Get cookie value
 * @param cname cookie name
 * @param def defaut value
 * @returns {String}
 */
function getCookie(cname, def) {
    var name = cname + "=";
    var ca = document.cookie.split(';');
    for(var i=0; i<ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0)==' ') {
            c = c.substring(1);
        }
        if (c.indexOf(name) == 0) {
            return c.substring(name.length,c.length);
        }
    }
    return def;
}
