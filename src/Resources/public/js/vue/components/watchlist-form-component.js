/**
 * Merkliste: „Zur Merkliste hinzufügen / entfernen“, optional mit Mengenauswahl.
 *
 * Barrierefreiheit:
 * - echte Buttons; der Hauptbutton wechselt seinen Namen (hinzufügen/entfernen), optional mit Titel des Eintrags
 * - Mengenfeld mit sichtbarem Label, Plus/Minus-Buttons mit aria-label, vollständig per Tastatur bedienbar
 * - Statusmeldungen in einer Live-Region (role="status"), Fehler als role="alert"
 * - aria-busy während des Speicherns, sichtbarer Fokus (CSS), reduzierte Bewegung, Forced Colors
 * - Labels werden nur als Text ausgegeben (kein v-html)
 *
 * Nach jeder erfolgreichen Änderung wird `cm:watchlist-updated` auf document ausgelöst
 * (detail: {data, added, units}), z. B. für einen Zähler im Seitenkopf.
 *
 * Benötigt Vue 2.7 (geladen über alnv/widget-collection-bundle) und fetch/FormData.
 */
(function () {
    'use strict';

    var uid = 0;
    var MAX_UNITS = 9999;

    var DEFAULT_LABELS = {
        add: 'Zur Merkliste hinzufügen',
        remove: 'Von der Merkliste entfernen',
        units: 'Menge',
        decrease: 'Menge verringern',
        increase: 'Menge erhöhen',
        added: 'Zur Merkliste hinzugefügt.',
        removed: 'Von der Merkliste entfernt.',
        unitsUpdated: 'Menge auf %s geändert.',
        saving: 'Wird gespeichert …',
        tooMany: 'Zu viele Anfragen. Bitte kurz warten und erneut versuchen.',
        invalidToken: 'Die Sitzung ist abgelaufen. Bitte die Seite neu laden.',
        full: 'Die Merkliste ist voll.',
        error: 'Die Merkliste konnte nicht gespeichert werden. Bitte erneut versuchen.'
    };

    function format(pattern, value) {
        return String(pattern || '').replace('%s', value);
    }

    function clampUnits(value) {
        var number = parseInt(value, 10);
        if (isNaN(number) || number < 1) {
            return 1;
        }
        return Math.min(number, MAX_UNITS);
    }

    Vue.component('watchlist-form', {
        props: {
            data: {type: String, required: true},
            units: {type: [Number, String], default: 1},
            added: {type: Boolean, default: false},
            useUnits: {type: Boolean, default: true},
            requestToken: {type: String, default: ''},
            itemTitle: {type: String, default: ''},
            url: {type: String, default: '/catalog-manager/watchlist/update'},
            labels: {type: Object, default: function () { return {}; }},
            // Ältere Props, bleiben für bestehende Templates erhalten
            buttonAddLabel: {type: String, default: ''},
            buttonRemoveLabel: {type: String, default: ''},
            buttonUnitsLabel: {type: String, default: ''}
        },

        data: function () {
            var legacy = {};
            if (this.buttonAddLabel) { legacy.add = this.buttonAddLabel; }
            if (this.buttonRemoveLabel) { legacy.remove = this.buttonRemoveLabel; }

            return {
                uid: ++uid,
                text: Object.assign({}, DEFAULT_LABELS, legacy, this.labels || {}),
                amount: clampUnits(this.units),
                savedAmount: clampUnits(this.units),
                isAdded: this.added,
                token: this.requestToken,
                busy: false,
                status: '',
                error: '',
                timer: null
            };
        },

        computed: {
            baseId: function () {
                return 'cm-watchlist-' + this.uid;
            },
            buttonLabel: function () {
                return this.isAdded ? this.text.remove : this.text.add;
            }
        },

        beforeDestroy: function () {
            clearTimeout(this.timer);
        },

        methods: {
            announce: function (message, isError) {
                // Kurz leeren, damit gleiche Meldungen erneut vorgelesen werden
                this.status = '';
                this.error = '';
                this.$nextTick(function () {
                    if (isError) {
                        this.error = message;
                    } else {
                        this.status = message;
                    }
                });
            },

            toggle: function () {
                if (this.busy) {
                    return;
                }
                clearTimeout(this.timer);
                this.save(this.isAdded ? 0 : clampUnits(this.amount));
            },

            step: function (delta) {
                var next = clampUnits(this.amount + delta);
                if (this.busy || next === clampUnits(this.amount)) {
                    return; // Grenze erreicht: Button ist aria-disabled, behält aber den Fokus
                }
                this.amount = clampUnits(this.amount + delta);
                this.scheduleUnitsSave();
            },

            onAmountBlur: function () {
                this.amount = clampUnits(this.amount);
                this.scheduleUnitsSave(0);
            },

            scheduleUnitsSave: function (delay) {
                if (!this.isAdded) {
                    return; // Ohne Eintrag wird die Menge erst beim Hinzufügen gespeichert
                }
                clearTimeout(this.timer);
                this.timer = setTimeout(function () {
                    var units = clampUnits(this.amount);
                    if (units !== this.savedAmount) {
                        this.save(units);
                    }
                }.bind(this), typeof delay === 'number' ? delay : 600);
            },

            save: function (units) {
                var wasAdded = this.isAdded;
                var body = new FormData();

                body.append('data', this.data);
                body.append('items', String(units));
                body.append('REQUEST_TOKEN', this.token || '');

                this.busy = true;
                this.announce(this.text.saving);

                fetch(this.url, {
                    method: 'POST',
                    body: body,
                    credentials: 'same-origin',
                    headers: {'Accept': 'application/json'}
                }).then(function (response) {
                    return response.json().catch(function () { return {}; }).then(function (json) {
                        if (!response.ok) {
                            var error = new Error(json.error || response.statusText);
                            error.status = response.status;
                            throw error;
                        }
                        return json;
                    });
                }).then(function (json) {
                    if (json.requestToken) {
                        this.token = json.requestToken;
                    }

                    this.isAdded = !!json.id;

                    if (this.isAdded) {
                        this.savedAmount = clampUnits(json.units);
                        this.amount = this.savedAmount;
                        this.announce(wasAdded ? format(this.text.unitsUpdated, this.savedAmount) : this.text.added);
                    } else {
                        this.savedAmount = 1;
                        this.amount = 1;
                        this.announce(this.text.removed);
                    }

                    document.dispatchEvent(new CustomEvent('cm:watchlist-updated', {
                        detail: {data: this.data, added: this.isAdded, units: this.isAdded ? this.savedAmount : 0}
                    }));
                }.bind(this)).catch(function (error) {
                    var status = error && error.status;
                    var message = this.text.error;

                    if (status === 429) {
                        message = this.text.tooMany;
                    } else if (status === 400 || status === 403) {
                        message = this.text.invalidToken;
                    } else if (status === 422) {
                        message = this.text.full;
                    }

                    this.amount = this.savedAmount;
                    this.announce(message, true);
                }.bind(this)).then(function () {
                    this.busy = false;
                }.bind(this));
            }
        },

        template:
            '<div class="cm-watchlist ce_watchlist-form" :class="{\'is-added\': isAdded, added: isAdded, \'is-busy\': busy}" :aria-busy="busy ? \'true\' : \'false\'">' +
                '<div class="cm-watchlist__controls watchlist-form">' +
                    '<div v-if="useUnits" class="cm-watchlist__units watchlist-units">' +
                        '<label class="cm-watchlist__units-label" :for="baseId + \'-units\'" v-text="text.units"></label>' +
                        '<div class="cm-watchlist__stepper">' +
                            '<button type="button" class="cm-watchlist__step" :aria-disabled="(busy || amount <= 1) ? \'true\' : null" ' +
                                ':aria-label="text.decrease" :aria-controls="baseId + \'-units\'" @click="step(-1)">' +
                                '<svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" width="16" height="16"><path d="M5 12h14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/></svg>' +
                            '</button>' +
                            '<input :id="baseId + \'-units\'" class="cm-watchlist__amount" type="number" inputmode="numeric" min="1" max="9999" step="1" ' +
                                'v-model.number="amount" :aria-disabled="busy ? \'true\' : null" :readonly="busy" ' +
                                '@input="scheduleUnitsSave()" @blur="onAmountBlur" @keydown.enter.prevent="onAmountBlur">' +
                            '<button type="button" class="cm-watchlist__step" :aria-disabled="(busy || amount >= 9999) ? \'true\' : null" ' +
                                ':aria-label="text.increase" :aria-controls="baseId + \'-units\'" @click="step(1)">' +
                                '<svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" width="16" height="16"><path d="M12 5v14M5 12h14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/></svg>' +
                            '</button>' +
                        '</div>' +
                    '</div>' +

                    '<button type="button" class="cm-watchlist__toggle watchlist-button" ' +
                        ':class="{\'remove-button\': isAdded, \'add-button\': !isAdded}" ' +
                        ':aria-disabled="busy ? \'true\' : null" @click="toggle">' +
                        '<svg class="cm-watchlist__icon" aria-hidden="true" focusable="false" viewBox="0 0 24 24" width="20" height="20">' +
                            '<path d="M6 3.75h12a.75.75 0 0 1 .75.75v16.19a.5.5 0 0 1-.8.4L12 16.5l-5.95 4.59a.5.5 0 0 1-.8-.4V4.5A.75.75 0 0 1 6 3.75Z" ' +
                                ':fill="isAdded ? \'currentColor\' : \'none\'" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/>' +
                        '</svg>' +
                        '<span class="cm-watchlist__label" v-text="buttonLabel"></span>' +
                        '<span v-if="itemTitle" class="cm-watchlist__sr" v-text="\': \' + itemTitle"></span>' +
                    '</button>' +
                '</div>' +

                '<p class="cm-watchlist__status" role="status" v-text="status"></p>' +
                '<p class="cm-watchlist__error" role="alert" v-text="error"></p>' +
            '</div>'
    });
})();
