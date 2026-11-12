(function () {
    'use strict';

    var SELECTOR = '[data-cm-fm][data-cm-fm-config]';
    var loadedSnippets = new Set();

    var DEFAULT_LABELS = {
        loading: 'Loading …',
        busy: 'Please wait, an action is still in progress.',
        networkError: 'No connection to the server. Please check your connection and try again.',
        timeout: 'The server did not respond in time. Please try again.',
        invalidResponse: 'The server sent an unexpected response. Please try again.',
        sessionExpired: 'Your session has expired. Please log in again.',
        csrf: 'Your session has expired. Please try again.',
        accessDenied: 'You are not allowed to perform this action.',
        recordDenied: 'The record does not exist or you are not allowed to access it.',
        formErrors: 'Please correct the marked fields.',
        error: 'An unexpected error occurred. Please try again.',
        databaseError: 'The data could not be saved. Please try again later.',
        badRequest: 'Invalid request.',
        dismiss: 'Close message'
    };

    var CODE_LABELS = {
        not_authenticated: 'sessionExpired',
        csrf_invalid: 'csrf',
        forbidden: 'accessDenied',
        not_found: 'recordDenied',
        validation_failed: 'formErrors',
        bad_request: 'badRequest',
        database_error: 'databaseError',
        server_error: 'error',
        network_error: 'networkError',
        timeout: 'timeout',
        invalid_response: 'invalidResponse'
    };

    function FrontendManager(root) {
        this.root = root;
        this.config = parseConfig(root.getAttribute('data-cm-fm-config'));
        this.labels = Object.assign({}, DEFAULT_LABELS, this.config.labels || {});
        this.moduleId = String(this.config.moduleId || root.getAttribute('data-cm-fm'));
        this.param = this.config.param || '_cmfm_ajax';
        this.token = this.config.token || '';
        this.timeout = Number(this.config.timeout) || 30000;
        this.view = root.querySelector('[data-cm-fm-view]');
        this.statusRegion = root.querySelector('[data-cm-fm-status]');
        this.alertRegion = root.querySelector('[data-cm-fm-alert]');
        this.pending = null;
        this.lastSubmitter = null;

        if (!this.view) {
            return;
        }

        root.addEventListener('click', this.guard(this.onClick));
        root.addEventListener('submit', this.guard(this.onSubmit));
        root.addEventListener('change', this.guard(this.onChange));
        window.addEventListener('popstate', this.guard(this.onPopState));

        // Aktuellen Eintrag markieren, damit "Zurück" zu dieser Ansicht per AJAX funktioniert
        this.replaceHistory(window.location.href);
        this.enhance(true);
        this.initEditors();
    }

    FrontendManager.prototype = {

        guard: function (handler) {
            var self = this;

            return function (event) {
                try {
                    return handler.call(self, event);
                } catch (error) {
                    self.reportClientError(error);
                }
            };
        },

        // ---------------------------------------------------------------
        // Ereignisse
        // ---------------------------------------------------------------

        onClick: function (event) {
            // Übermittelnde Schaltfläche merken (Fallback für Browser ohne SubmitEvent.submitter)
            var submit = event.target.closest('button[type="submit"], input[type="submit"]');
            if (submit && this.view.contains(submit)) {
                this.lastSubmitter = submit;

                if (this.isLocked()) {
                    event.preventDefault();
                    this.announceBusy();
                }

                return;
            }

            // Label bzw. Link der Fehlerzusammenfassung zu einem Rich-Text-Feld: Editor fokussieren
            // (das Textarea selbst ist ausgeblendet und kann den Fokus nicht erhalten)
            var labelOrAnchor = event.target.closest('label[for], a[href^="#"]');
            if (labelOrAnchor && this.view.contains(labelOrAnchor)) {
                var targetId = labelOrAnchor.tagName === 'LABEL' ? labelOrAnchor.getAttribute('for') : labelOrAnchor.getAttribute('href').slice(1);
                var editor = this.getEditor(targetId);

                if (editor) {
                    event.preventDefault();
                    editor.focus();
                    return;
                }
            }

            var link = event.target.closest('a[href]');

            if (!link || !this.view.contains(link) || event.defaultPrevented || event.button !== 0
                || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                return;
            }

            var url = this.resolveOwnUrl(link);
            if (!url) {
                return;
            }

            event.preventDefault();

            if (this.isLocked()) {
                this.announceBusy();
                return;
            }

            this.navigate(url, {push: true, trigger: link, focus: [link.getAttribute('data-cm-fm-focus')]});
        },

        onSubmit: function (event) {
            var form = event.target;

            if (!(form instanceof HTMLFormElement) || !this.view.contains(form) || event.defaultPrevented) {
                return;
            }

            event.preventDefault();

            var submitter = event.submitter || (this.lastSubmitter && this.lastSubmitter.form === form ? this.lastSubmitter : null);
            this.lastSubmitter = null;

            if ((form.getAttribute('method') || 'get').toLowerCase() === 'get') {
                this.navigate(this.buildGetUrl(form), {push: true, trigger: submitter, focus: [submitter && submitter.getAttribute('data-cm-fm-focus')]});
                return;
            }

            this.submitForm(form, submitter);
        },

        onChange: function (event) {
            // Selektorfelder: Palette ändert sich → Formular mit den eingegebenen Werten neu laden
            var field = event.target.closest('[data-cm-fm-reload]');

            if (!field || !field.form || !this.view.contains(field)) {
                return;
            }

            var wrapper = field.closest('.cm-fm__field');
            var button = wrapper ? wrapper.querySelector('.cm-fm__reload') : null;

            if (button) {
                this.submitForm(field.form, button, {focus: [field.id ? '#' + field.id : null]});
            }
        },

        onPopState: function (event) {
            var state = event.state;

            if (state && state.cmfm && String(state.cmfm) === this.moduleId) {
                this.navigate(new URL(window.location.href), {push: false});
            }
        },

        // ---------------------------------------------------------------
        // Aktionen
        // ---------------------------------------------------------------

        navigate: function (url, options) {
            options = options || {};

            if (this.pending && this.pending.write) {
                this.announceBusy();
                return Promise.resolve();
            }

            if (this.pending) {
                this.pending.controller.abort();
            }

            var requestUrl = new URL(url.href);
            requestUrl.searchParams.set(this.param, this.moduleId);

            return this.send('GET', requestUrl.href, null, false, options.trigger, null).then(this.guardResult(function (result) {
                if (result.aborted) {
                    return;
                }

                if (!result.ok) {
                    this.handleFailure(result);
                    return;
                }

                this.applyView(result.data, {
                    history: options.push ? 'push' : 'none',
                    fallbackUrl: url.href,
                    focus: options.focus
                });
            }));
        },

        submitForm: function (form, submitter, options, isRetry) {
            options = options || {};

            if (this.pending && this.pending.write) {
                this.announceBusy();
                return Promise.resolve();
            }

            if (this.pending) {
                this.pending.controller.abort();
            }

            // Inhalte der Editoren in die Textareas schreiben, bevor FormData sie liest
            this.saveEditors();

            var data = new FormData(form);

            if (submitter && submitter.name) {
                data.append(submitter.name, submitter.value);
            }

            data.set('REQUEST_TOKEN', this.token);
            data.set(this.param, this.moduleId);

            var recordInput = form.querySelector('input[name="_cmfm_id"]');
            var recordId = recordInput ? recordInput.value : '';
            var isReload = !!(submitter && submitter.classList.contains('cm-fm__reload'));
            var focus = (options.focus || []).concat([
                submitter && submitter.getAttribute('data-cm-fm-focus'),
                recordId && recordId !== '0' ? 'edit-' + recordId : null
            ]);

            return this.send('POST', this.formAction(form), data, true, submitter, form).then(this.guardResult(function (result) {
                if (result.aborted) {
                    return;
                }

                // Abgelaufenes Token: Die Antwort enthält bereits ein neues → einmal wiederholen.
                // Unbedenklich, weil der Server die erste Anfrage vor jeder Verarbeitung abgewiesen hat.
                if (!result.ok && result.code === 'csrf_invalid' && !isRetry && result.data && result.data.token) {
                    return this.submitForm(form, submitter, options, true);
                }

                if (!result.ok && result.code === 'validation_failed' && result.data && typeof result.data.html === 'string') {
                    // Server hat das Formular mit Fehlern und den eingegebenen Werten neu gerendert
                    this.applyView(result.data, {history: 'none', focus: ['[data-cm-fm-error-summary]'], validation: true});
                    return;
                }

                if (!result.ok) {
                    this.handleFailure(result);
                    return;
                }

                this.applyView(result.data, {history: isReload ? 'none' : 'replace', focus: focus});
            }));
        },

        // ---------------------------------------------------------------
        // Transport
        // ---------------------------------------------------------------

        send: function (method, url, body, write, trigger, form) {
            var self = this;
            var controller = new AbortController();
            var timedOut = false;
            var timer = window.setTimeout(function () {
                timedOut = true;
                controller.abort();
            }, this.timeout);
            var pending = {controller: controller, write: write};

            this.pending = pending;
            this.clearMessages(); // alte Meldungen gehören nicht zur neuen Aktion
            this.setBusy(true, trigger, form);

            return fetch(url, {
                method: method,
                body: body,
                credentials: 'same-origin',
                cache: 'no-store',
                redirect: 'follow',
                signal: controller.signal,
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }).then(function (response) {
                return self.parseResponse(response);
            }, function () {
                if (timedOut) {
                    return {ok: false, code: 'timeout', status: 0};
                }

                return controller.signal.aborted ? {aborted: true} : {ok: false, code: 'network_error', status: 0};
            }).then(function (result) {
                return result;
            }, function () {
                // Fehler beim Lesen des Antwortkörpers (Verbindung abgebrochen)
                return timedOut ? {ok: false, code: 'timeout', status: 0} : {ok: false, code: 'network_error', status: 0};
            }).finally(function () {
                window.clearTimeout(timer);

                if (self.pending === pending) {
                    self.pending = null;
                    self.setBusy(false, trigger, form);
                }
            });
        },

        parseResponse: function (response) {
            var self = this;
            var type = response.headers.get('Content-Type') || '';

            if (type.indexOf('application/json') === -1) {
                return {ok: false, code: this.codeForNonJson(response), status: response.status};
            }

            return response.json().then(function (data) {
                if (!data || typeof data !== 'object' || typeof data.status !== 'string' || typeof data.code !== 'string') {
                    return {ok: false, code: 'invalid_response', status: response.status};
                }

                if (typeof data.token === 'string' && data.token) {
                    self.setToken(data.token);
                }

                if (response.ok && data.status === 'success') {
                    return {ok: true, data: data, status: response.status};
                }

                return {
                    ok: false,
                    code: data.code,
                    message: typeof data.message === 'string' ? data.message : '',
                    status: response.status,
                    data: data
                };
            }, function () {
                return {ok: false, code: 'invalid_response', status: response.status};
            });
        },

        codeForNonJson: function (response) {
            if (response.redirected || response.status === 401) {
                return 'not_authenticated';
            }

            if (response.status === 403) {
                return 'forbidden';
            }

            if (response.status === 400) {
                return 'bad_request';
            }

            if (response.status >= 500) {
                return 'server_error';
            }

            return 'invalid_response';
        },

        guardResult: function (callback) {
            var self = this;

            return function (result) {
                try {
                    return callback.call(self, result);
                } catch (error) {
                    self.reportClientError(error);
                }
            };
        },

        // ---------------------------------------------------------------
        // Fehlerbehandlung (zentral)
        // ---------------------------------------------------------------

        handleFailure: function (result) {
            var code = result.code || 'server_error';
            var label = this.labels[CODE_LABELS[code] || 'error'] || this.labels.error;

            // Meldung des Servers nur für fachliche Codes übernehmen; sonst die eigene, einheitliche
            var useServerMessage = ['validation_failed', 'not_found', 'forbidden', 'not_authenticated', 'csrf_invalid', 'database_error'].indexOf(code) !== -1;
            var message = useServerMessage && result.message ? result.message : label;

            this.showAlert(message);

            if (window.console && console.warn) {
                console.warn('[cm-fm] request failed:', code, result.status);
            }
        },

        reportClientError: function (error) {
            if (window.console && console.error) {
                console.error('[cm-fm]', error);
            }

            try {
                this.showAlert(this.labels.error);
            } catch (ignore) {
                // Anzeige selbst fehlgeschlagen: nichts weiter tun, aber keinen Fehler werfen
            }
        },

        // ---------------------------------------------------------------
        // Ansicht
        // ---------------------------------------------------------------

        applyView: function (data, options) {
            options = options || {};

            if (typeof data.html !== 'string') {
                this.handleFailure({code: 'invalid_response'});
                return;
            }

            this.destroyEditors();
            this.view.innerHTML = data.html;
            this.applyToken();
            this.runScripts(this.view);

            // Erst wenn nachgeladene Skripte (z. B. tinymce.min.js) bereit sind, Editoren starten
            var self = this;
            this.loadAssets(data.assets || {}).then(function () {
                return self.initEditors();
            }).then(function () {
                self.root.dispatchEvent(new CustomEvent('cm-fm:editors-ready', {bubbles: true}));
            }, function (error) {
                self.reportClientError(error);
            });

            // Meldungen: Bei Validierungsfehlern liest die Fehlerzusammenfassung (role=alert) vor
            this.clearMessages();
            if (!options.validation) {
                this.showMessages(Array.isArray(data.messages) ? data.messages : []);
            } else if (!this.view.querySelector('[data-cm-fm-error-summary]') && data.message) {
                this.showAlert(data.message);
            }

            var url = typeof data.url === 'string' && data.url ? data.url : options.fallbackUrl;
            if (url && options.history === 'push') {
                this.pushHistory(url);
            } else if (url && options.history === 'replace') {
                this.replaceHistory(url);
            }

            this.enhance(false);
            this.restoreFocus(options.focus || []);

            this.root.dispatchEvent(new CustomEvent('cm-fm:updated', {
                bubbles: true,
                detail: {view: data.view, record: data.record, response: data}
            }));
        },

        enhance: function (initial) {
            this.view.querySelectorAll('.cm-fm__reload').forEach(function (button) {
                button.hidden = true;
            });

            if (initial) {
                var summary = this.view.querySelector('[data-cm-fm-error-summary]');
                if (summary) {
                    summary.focus();
                }
            }
        },

        restoreFocus: function (keys) {
            var view = this.view;
            var target = null;

            keys.concat(['[data-cm-fm-error-summary]', '[data-cm-fm-heading]']).some(function (key) {
                if (!key) {
                    return false;
                }

                try {
                    target = key.charAt(0) === '#' || key.charAt(0) === '['
                        ? view.querySelector(key.charAt(0) === '#' ? '#' + cssEscape(key.slice(1)) : key)
                        : view.querySelector('[data-cm-fm-focus="' + cssEscape(key) + '"]');
                } catch (ignore) {
                    target = null;
                }

                return !!target;
            });

            (target || view).focus({preventScroll: false});
        },

        runScripts: function (container) {
            container.querySelectorAll('script').forEach(function (old) {
                var script = document.createElement('script');

                Array.prototype.forEach.call(old.attributes, function (attribute) {
                    script.setAttribute(attribute.name, attribute.value);
                });

                script.text = old.text;
                old.parentNode.replaceChild(script, old);
            });
        },

        loadAssets: function (assets) {
            var pending = [];

            (assets.css || []).forEach(function (href) {
                if (!document.querySelector('link[rel="stylesheet"][href="' + cssEscape(href) + '"]')) {
                    var link = document.createElement('link');
                    link.rel = 'stylesheet';
                    link.href = href;
                    document.head.appendChild(link);
                }
            });

            (assets.js || []).forEach(function (src) {
                var existing = document.querySelector('script[src="' + cssEscape(src) + '"]');

                if (existing) {
                    // Bereits eingefügt, aber evtl. noch nicht geladen (schnelle Folgeanfragen)
                    if (existing.getAttribute('data-cm-fm-loading') === '1') {
                        pending.push(waitForScript(existing));
                    }
                    return;
                }

                var script = document.createElement('script');
                script.src = src;
                script.async = false; // Reihenfolge beibehalten
                script.setAttribute('data-cm-fm-loading', '1');
                pending.push(waitForScript(script));
                document.body.appendChild(script);
            });

            (assets.html || []).forEach(function (snippet) {
                if (loadedSnippets.has(snippet)) {
                    return;
                }

                loadedSnippets.add(snippet);
                var template = document.createElement('template');
                template.innerHTML = snippet;
                var self = this;

                Array.prototype.slice.call(template.content.childNodes).forEach(function (node) {
                    document.body.appendChild(node);

                    // Nur die neu eingefügten Skripte ausführen (innerHTML führt sie nicht aus)
                    if (node.nodeType === Node.ELEMENT_NODE) {
                        if (node.tagName === 'SCRIPT') {
                            var wrapper = document.createElement('div');
                            node.replaceWith(wrapper);
                            wrapper.appendChild(node);
                            self.runScripts(wrapper);
                            wrapper.replaceWith(wrapper.firstChild);
                        } else {
                            self.runScripts(node);
                        }
                    }
                });
            }, this);

            return Promise.all(pending);
        },

        // ---------------------------------------------------------------
        // Rich Text (TinyMCE)
        // ---------------------------------------------------------------

        initEditors: function () {
            var self = this;
            var targets = Array.prototype.filter.call(this.view.querySelectorAll('textarea'), function (element) {
                return element.tinymceConfig && !element.hasAttribute('data-cm-fm-rte');
            });

            if (!targets.length) {
                return Promise.resolve();
            }

            if (!window.tinymce || typeof window.tinymce.init !== 'function') {
                if (window.console && console.warn) {
                    console.warn('[cm-fm] TinyMCE is not loaded, falling back to plain textareas.');
                }

                return Promise.resolve();
            }

            return Promise.all(targets.map(function (element) {
                return self.initEditor(element);
            }));
        },

        initEditor: function (element) {
            var self = this;
            var config = Object.assign({}, element.tinymceConfig, {target: element});
            var backendSetup = config.setup;
            var label = this.view.querySelector('label[for="' + cssEscape(element.id) + '"]');

            element.setAttribute('data-cm-fm-rte', 'pending');

            // Stimulus-Verweise des Backend-Templates sind im Frontend wirkungslos
            element.removeAttribute('data-controller');
            element.removeAttribute('data-action');

            // Kein Datei-/Link-Picker im Frontend (braucht einen Backend-Login, Backend.* fehlt hier)
            if (!config.file_picker_types) {
                delete config.file_picker_callback;
                delete config.file_picker_types;
            }

            // Zugänglicher Name des Editorbereichs = Feldlabel
            if (label && !config.iframe_aria_text) {
                config.iframe_aria_text = label.textContent.replace(/\s+/g, ' ').replace(/\*\s*$/, '').trim();
            }

            config.setup = function (editor) {
                if (typeof backendSetup === 'function') {
                    backendSetup(editor);
                }

                // Textarea aktuell halten (wie im Backend über keyup), damit auch andere Skripte den Wert sehen
                editor.on('change input undo redo', function () {
                    editor.save();
                    element.dispatchEvent(new Event('input', {bubbles: true}));
                });

                // Pflicht-/Fehlerzustand des Feldes an den Editorrahmen weitergeben
                editor.on('init', function () {
                    var container = editor.getContainer();

                    // Zugänglicher Name des iframes (TinyMCE setzt nur aria-label am Inhalt)
                    if (editor.iframeElement && config.iframe_aria_text && !editor.iframeElement.title) {
                        editor.iframeElement.title = config.iframe_aria_text;
                    }

                    if (container && element.closest('.error, .cm-fm__field--error')) {
                        container.classList.add('cm-fm__rte--error');
                        editor.getBody().setAttribute('aria-invalid', 'true');
                    }
                });
            };

            return window.tinymce.init(config).then(function (editors) {
                var editor = editors && editors[0];

                element.setAttribute('data-cm-fm-rte', editor ? 'ready' : 'failed');
                self.root.dispatchEvent(new CustomEvent('cm-fm:editor-loaded', {bubbles: true, detail: {editor: editor, element: element}}));
            }, function (error) {
                element.setAttribute('data-cm-fm-rte', 'failed');

                if (window.console && console.warn) {
                    console.warn('[cm-fm] TinyMCE could not be initialized:', error);
                }
            });
        },

        destroyEditors: function () {
            var view = this.view;

            this.eachEditor(function (editor) {
                var element = editor.getElement();

                if (element && view.contains(element)) {
                    editor.remove();
                }
            });
        },

        saveEditors: function () {
            var view = this.view;

            this.eachEditor(function (editor) {
                var element = editor.getElement();

                if (element && view.contains(element)) {
                    editor.save();
                }
            });
        },

        getEditor: function (id) {
            if (!id || !window.tinymce || typeof window.tinymce.get !== 'function') {
                return null;
            }

            var editor = window.tinymce.get(id);

            return editor && this.view.contains(editor.getElement()) ? editor : null;
        },

        eachEditor: function (callback) {
            if (!window.tinymce || typeof window.tinymce.get !== 'function') {
                return;
            }

            var editors = window.tinymce.get();

            (Array.isArray(editors) ? editors : (window.tinymce.editors || [])).slice().forEach(callback);
        },

        // ---------------------------------------------------------------
        // Zustände
        // ---------------------------------------------------------------

        setBusy: function (busy, trigger, form) {
            var self = this;

            this.root.classList.toggle('is-busy', busy);

            if (busy) {
                this.root.setAttribute('aria-busy', 'true');
                this.locked = [];

                // Schreibende Anfrage: alle Aktionen sperren (aria-disabled statt disabled, damit der Fokus
                // bleibt). Lesende Anfragen sperren nichts – eine neue Aktion bricht sie einfach ab.
                if (this.pending && this.pending.write) {
                    this.view.querySelectorAll('button[type="submit"], input[type="submit"], a[href]').forEach(function (element) {
                        if (element.getAttribute('aria-disabled') !== 'true') {
                            element.setAttribute('aria-disabled', 'true');
                            self.locked.push(element);
                        }
                    });
                }

                if (trigger) {
                    trigger.classList.add('is-loading');
                    this.loadingTrigger = trigger;
                }

                // Lange Anfragen ansagen (kurze nicht, sonst wird jede Aktion doppelt vorgelesen)
                window.clearTimeout(this.loadingTimer);
                this.loadingTimer = window.setTimeout(function () {
                    self.setStatusText(self.labels.loading);
                }, 1000);

                return;
            }

            this.root.removeAttribute('aria-busy');
            window.clearTimeout(this.loadingTimer);

            (this.locked || []).forEach(function (element) {
                element.removeAttribute('aria-disabled');
            });
            this.locked = [];

            if (this.loadingTrigger) {
                this.loadingTrigger.classList.remove('is-loading');
                this.loadingTrigger = null;
            }

            if (this.statusRegion && this.statusRegion.getAttribute('data-cm-fm-loading') === '1') {
                this.statusRegion.textContent = '';
                this.statusRegion.removeAttribute('data-cm-fm-loading');
            }
        },

        isLocked: function () {
            return !!this.pending && this.pending.write;
        },

        announceBusy: function () {
            this.setStatusText(this.labels.busy);
        },

        setToken: function (token) {
            this.token = token;
            this.applyToken();
        },

        applyToken: function () {
            var token = this.token;

            this.root.querySelectorAll('input[name="REQUEST_TOKEN"]').forEach(function (input) {
                input.value = token;
            });
        },

        // ---------------------------------------------------------------
        // Meldungen
        // ---------------------------------------------------------------

        clearMessages: function () {
            if (this.statusRegion) {
                this.statusRegion.textContent = '';
                this.statusRegion.removeAttribute('data-cm-fm-loading');
            }

            if (this.alertRegion) {
                this.alertRegion.textContent = '';
            }
        },

        showMessages: function (messages) {
            var self = this;

            messages.forEach(function (message) {
                if (message && typeof message.text === 'string') {
                    if (message.type === 'error') {
                        self.showAlert(message.text);
                    } else {
                        self.appendMessage(self.statusRegion, 'confirm', message.text, false);
                    }
                }
            });
        },

        showAlert: function (text) {
            if (this.alertRegion) {
                // Inhalt ersetzen (nicht anhängen): Screenreader lesen die neue Meldung sicher vor
                this.alertRegion.textContent = '';
                this.appendMessage(this.alertRegion, 'error', text, true);
            }

            if (this.statusRegion && this.statusRegion.getAttribute('data-cm-fm-loading') === '1') {
                this.statusRegion.textContent = '';
            }
        },

        setStatusText: function (text) {
            if (this.statusRegion) {
                this.statusRegion.textContent = '';
                this.appendMessage(this.statusRegion, 'info', text, false);
                this.statusRegion.setAttribute('data-cm-fm-loading', text === this.labels.loading ? '1' : '0');
            }
        },

        appendMessage: function (region, type, text, dismissible) {
            if (!region) {
                return;
            }

            var paragraph = document.createElement('p');
            var span = document.createElement('span');

            paragraph.className = 'cm-fm__message cm-fm__message--' + type;
            span.textContent = text; // nie als HTML
            paragraph.appendChild(span);

            if (dismissible) {
                var button = document.createElement('button');
                button.type = 'button';
                button.className = 'cm-fm__dismiss';
                button.setAttribute('aria-label', this.labels.dismiss);
                button.textContent = '×';
                button.addEventListener('click', function () {
                    paragraph.remove();
                });
                paragraph.appendChild(button);
            }

            region.appendChild(paragraph);
        },

        // ---------------------------------------------------------------
        // URLs und History
        // ---------------------------------------------------------------

        resolveOwnUrl: function (link) {
            var href = link.getAttribute('href') || '';

            if (href.charAt(0) === '#' || link.hasAttribute('download') || link.hasAttribute('data-cm-fm-native')
                || (link.target && link.target !== '_self')) {
                return null;
            }

            var url;
            try {
                url = new URL(link.href, window.location.href);
            } catch (ignore) {
                return null;
            }

            if (url.origin !== window.location.origin || url.pathname !== window.location.pathname) {
                return null;
            }

            url.searchParams.delete(this.param);

            return url;
        },

        buildGetUrl: function (form) {
            var url = new URL(this.formAction(form));
            var params = new URLSearchParams();

            new FormData(form).forEach(function (value, name) {
                if (typeof value === 'string' && value !== '') {
                    params.append(name, value);
                }
            });

            url.search = params.toString();

            return url;
        },

        formAction: function (form) {
            return new URL(form.getAttribute('action') || window.location.href, window.location.href).href;
        },

        pushHistory: function (url) {
            if (url !== window.location.href) {
                window.history.pushState({cmfm: this.moduleId}, '', url);
            } else {
                this.replaceHistory(url);
            }
        },

        replaceHistory: function (url) {
            window.history.replaceState(Object.assign({}, window.history.state || {}, {cmfm: this.moduleId}), '', url);
        }
    };

    function parseConfig(value) {
        try {
            var config = JSON.parse(value || '{}');
            return config && typeof config === 'object' ? config : {};
        } catch (ignore) {
            return {};
        }
    }

    function waitForScript(script) {
        return new Promise(function (resolve) {
            var done = function () {
                script.removeAttribute('data-cm-fm-loading');
                resolve();
            };

            // Bei Ladefehlern weitermachen: Ohne TinyMCE bleibt das Textarea nutzbar
            script.addEventListener('load', done, {once: true});
            script.addEventListener('error', done, {once: true});
        });
    }

    function cssEscape(value) {
        return window.CSS && CSS.escape ? CSS.escape(value) : String(value).replace(/["\\]/g, '\\$&');
    }

    function init() {
        if (!window.fetch || !window.FormData || !window.URL || !window.AbortController || !window.history.pushState) {
            return; // ältere Browser: Verwaltung funktioniert ohne JavaScript weiter
        }

        document.querySelectorAll(SELECTOR).forEach(function (root) {
            if (!root.cmFrontendManager) {
                root.cmFrontendManager = new FrontendManager(root);
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
