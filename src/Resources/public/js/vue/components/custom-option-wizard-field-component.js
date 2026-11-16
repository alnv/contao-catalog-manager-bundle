/**
 * Custom-Option-Wizard (Backend-Widget "customOptionWizard")
 *
 * Mehrfachauswahl als Chips mit der Möglichkeit, neue Optionen anzulegen und
 * in dieser Sitzung angelegte Optionen wieder zu löschen.
 *
 * Barrierefreiheit:
 * - echte Checkboxen (per Tastatur bedienbar, sichtbarer Fokusring)
 * - Gruppierung per <fieldset>/<legend>
 * - Buttons mit eindeutigen Namen („Rot“ löschen)
 * - Statusmeldungen in einer Live-Region (role="status")
 * - kein verschachteltes <form> im Contao-Bearbeitungsformular
 *
 * Benötigt Vue 2.7 (wird von alnv/widget-collection-bundle geladen).
 */
(function () {
    'use strict';

    var uid = 0;

    var DEFAULT_LABELS = {
        add: 'Hinzufügen',
        newOption: 'Neuen Eintrag hinzufügen',
        placeholder: 'Neuen Eintrag eingeben …',
        empty: 'Noch keine Einträge vorhanden.',
        remove: '„%s“ löschen',
        confirmRemove: 'Soll „%s“ endgültig gelöscht werden?',
        added: '„%s“ wurde hinzugefügt und ausgewählt.',
        removed: '„%s“ wurde gelöscht.',
        exists: '„%s“ ist bereits vorhanden und wurde ausgewählt.',
        emptyInput: 'Bitte einen Namen eingeben.',
        saving: 'Wird gespeichert …',
        forbidden: 'Keine Berechtigung für diese Aktion.',
        error: 'Die Aktion ist fehlgeschlagen. Bitte die Seite neu laden und erneut versuchen.'
    };

    /**
     * HTML-Entities dekodieren und Tags entfernen (Labels werden kodiert gespeichert).
     * DOMParser führt dabei keine Skripte aus; ausgegeben wird nur Text (v-text).
     */
    function toText(value) {
        if (value === null || value === undefined) {
            return '';
        }
        var doc = new DOMParser().parseFromString('<!doctype html><body>' + String(value), 'text/html');
        return (doc.body.textContent || '').trim();
    }

    function format(pattern, value) {
        return String(pattern || '').replace('%s', value);
    }

    function normalizeValue(value) {
        if (Array.isArray(value)) {
            return value
                .filter(function (v) { return v !== '' && v !== null && v !== undefined; })
                .map(String);
        }
        if (typeof value === 'string' && value !== '') {
            return value.split(',').filter(Boolean);
        }
        return [];
    }

    Vue.component('custom-option-wizard-field', {
        props: {
            eval: {
                type: Object,
                required: true,
                default: function () { return {}; }
            },
            name: {
                type: String,
                required: true
            },
            value: {
                type: [Array, String],
                default: function () { return []; }
            },
            idPrefix: {
                type: String,
                default: ''
            },
            noLabel: {
                type: Boolean,
                default: false
            }
        },

        data: function () {
            // Reihenfolge: Standard → alte eval-Labels (addButtonLabel1/2) → übersetzte Labels aus TL_LANG
            var legacy = {};

            if (this.eval.addButtonLabel2) {
                legacy.add = toText(this.eval.addButtonLabel2);
            }
            if (this.eval.addButtonLabel1) {
                legacy.newOption = toText(this.eval.addButtonLabel1);
            }

            var labels = Object.assign({}, DEFAULT_LABELS, legacy, this.eval._labels || {});
            return {
                uid: ++uid,
                labels: labels,
                options: [],
                selected: normalizeValue(this.value),
                newValue: '',
                busy: false,
                status: '',
                error: false
            };
        },

        computed: {
            baseId: function () {
                return this.idPrefix || ('cm-ow-' + this.uid);
            },
            // Contao rendert <label for="ctrl_<feld>"> – ein Klick darauf fokussiert das Eingabefeld
            inputId: function () {
                return this.baseId;
            },
            statusId: function () {
                return this.baseId + '_status';
            },
            descriptionId: function () {
                return this.eval.description ? this.baseId + '_description' : null;
            },
            legendText: function () {
                return toText(this.eval.label) || this.name;
            },
            descriptionText: function () {
                return toText(this.eval.description);
            },
            messages: function () {
                return !this.eval.validate && Array.isArray(this.eval.messages) ? this.eval.messages.map(toText) : [];
            },
            serializedValue: function () {
                return this.selected.join(',');
            },
            rootClass: function () {
                return {
                    'cm-ow': true,
                    'is-busy': this.busy,
                    'is-mandatory': !!this.eval.mandatory
                };
            }
        },

        watch: {
            selected: function (value) {
                this.$emit('input', value);
            }
        },

        mounted: function () {
            this.options = (this.eval.options || []).map(function (option) {
                return {
                    value: String(option.value),
                    text: toText(option.label),
                    delete: !!option.delete
                };
            });
        },

        methods: {
            format: format,

            optionId: function (index) {
                return this.baseId + '_opt_' + index;
            },

            isChecked: function (value) {
                return this.selected.indexOf(String(value)) !== -1;
            },

            announce: function (message, isError) {
                // Kurz leeren, damit identische Meldungen erneut vorgelesen werden
                this.status = '';
                this.error = !!isError;
                this.$nextTick(function () {
                    this.status = message;
                });
            },

            focusInput: function () {
                this.$nextTick(function () {
                    if (this.$refs.input) {
                        this.$refs.input.focus();
                    }
                });
            },

            request: function (url, payload) {
                var body = new FormData();

                Object.keys(payload).forEach(function (key) {
                    body.append(key, payload[key]);
                });
                body.append('REQUEST_TOKEN', this.eval._requestToken || '');

                return fetch(url, {
                    method: 'POST',
                    body: body,
                    credentials: 'same-origin',
                    headers: {'Accept': 'application/json'}
                }).then(function (response) {
                    return response.json().catch(function () { return {}; }).then(function (data) {
                        if (!response.ok) {
                            var error = new Error(data.error || response.statusText);
                            error.status = response.status;
                            throw error;
                        }
                        return data;
                    });
                });
            },

            handleError: function (error) {
                var forbidden = error && (error.status === 401 || error.status === 403);
                this.announce(forbidden ? this.labels.forbidden : this.labels.error, true);
            },

            addOption: function () {
                var text = toText(this.newValue).replace(/\s+/g, ' ');

                if (this.busy) {
                    return;
                }

                if (!text) {
                    this.announce(this.labels.emptyInput, true);
                    this.focusInput();
                    return;
                }

                // Schon vorhanden? Dann nur auswählen statt doppelt anzulegen.
                var existing = this.options.find(function (option) {
                    return option.text.toLocaleLowerCase() === text.toLocaleLowerCase();
                });

                if (existing) {
                    if (!this.isChecked(existing.value)) {
                        this.selected.push(existing.value);
                    }
                    this.newValue = '';
                    this.announce(format(this.labels.exists, existing.text));
                    this.focusInput();
                    return;
                }

                this.busy = true;
                this.announce(this.labels.saving);

                this.request(this.eval._addUrl, {
                    table: this.eval._table,
                    name: this.name,
                    option: text
                }).then(function (data) {
                    var option = {
                        value: String(data.value),
                        text: toText(data.label) || text,
                        delete: true
                    };

                    this.options.push(option);
                    this.selected.push(option.value);
                    this.newValue = '';
                    this.announce(format(this.labels.added, option.text));
                }.bind(this)).catch(this.handleError.bind(this)).then(function () {
                    this.busy = false;
                    this.focusInput();
                }.bind(this));
            },

            removeOption: function (option) {
                if (this.busy || !window.confirm(format(this.labels.confirmRemove, option.text))) {
                    return;
                }

                this.busy = true;
                this.announce(this.labels.saving);

                this.request(this.eval._deleteUrl, {
                    table: this.eval._table,
                    name: this.name,
                    option: option.value
                }).then(function () {
                    this.options = this.options.filter(function (item) { return item !== option; });
                    this.selected = this.selected.filter(function (value) { return value !== option.value; });
                    this.announce(format(this.labels.removed, option.text));
                }.bind(this)).catch(this.handleError.bind(this)).then(function () {
                    this.busy = false;
                    this.focusInput();
                }.bind(this));
            }
        },

        template:
            '<fieldset :class="rootClass" :aria-describedby="descriptionId" :aria-busy="busy ? \'true\' : \'false\'">' +
                '<legend class="cm-ow__sr" v-text="legendText"></legend>' +
                '<input type="hidden" :name="name" :value="serializedValue">' +

                '<ul v-if="options.length" class="cm-ow__list">' +
                    '<li v-for="(option, index) in options" :key="option.value" class="cm-ow__item" ' +
                        ':class="{\'is-checked\': isChecked(option.value), \'has-delete\': option.delete}">' +
                        '<input type="checkbox" class="cm-ow__checkbox" :id="optionId(index)" :value="option.value" v-model="selected" :disabled="busy">' +
                        '<label class="cm-ow__chip" :for="optionId(index)">' +
                            '<span class="cm-ow__check" aria-hidden="true"></span>' +
                            '<span class="cm-ow__text" v-text="option.text"></span>' +
                        '</label>' +
                        '<button v-if="option.delete" type="button" class="cm-ow__delete" :disabled="busy" ' +
                            ':aria-label="format(labels.remove, option.text)" :title="format(labels.remove, option.text)" ' +
                            '@click="removeOption(option)">' +
                            '<span class="cm-ow__delete-icon" aria-hidden="true"></span>' +
                        '</button>' +
                    '</li>' +
                '</ul>' +
                '<p v-else class="cm-ow__empty" v-text="labels.empty"></p>' +

                '<div class="cm-ow__add">' +
                    '<label class="cm-ow__sr" :for="inputId" v-text="labels.newOption"></label>' +
                    '<input ref="input" type="text" class="tl_text cm-ow__input" :id="inputId" v-model="newValue" maxlength="255" autocomplete="off" ' +
                        ':placeholder="labels.placeholder" :disabled="busy" :aria-invalid="error ? \'true\' : \'false\'" :aria-describedby="statusId" ' +
                        '@keydown.enter.prevent="addOption">' +
                    '<button type="button" class="tl_submit cm-ow__add-button" :disabled="busy" @click="addOption">' +
                        '<span class="cm-ow__add-icon" aria-hidden="true"></span>' +
                        '<span v-text="labels.add"></span>' +
                    '</button>' +
                '</div>' +

                '<p :id="statusId" class="cm-ow__status" :class="{\'is-error\': error}" role="status" v-text="status"></p>' +
                '<p v-for="message in messages" class="cm-ow__error tl_error" v-text="message"></p>' +
                '<p v-if="eval.description" :id="descriptionId" class="cm-ow__description" v-text="descriptionText"></p>' +
            '</fieldset>'
    });
})();
