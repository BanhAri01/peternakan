(function () {
    'use strict';

    var meta = function (name) {
        var el = document.querySelector('meta[name="' + name + '"]');
        return el ? el.getAttribute('content') : '';
    };

    var USER_ID = Number(meta('hefam-user')) || 0;
    var USER_NAME = meta('hefam-user-name');
    var TOKEN_URL = meta('hefam-token-url');
    var SEND_TIMEOUT_MS = 20000;
    var RETRY_EVERY_MS = 30000;
    var DB_NAME = 'hefam';
    var STORE = 'antrian';
    var flushing = false;
    var bar = null;

    if (!USER_ID || !window.indexedDB) {
        return;
    }

    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js', { scope: '/' }).then(function () {
            if (!navigator.serviceWorker.controller && navigator.onLine && document.querySelector('form[data-offline]') && window.caches) {
                Promise.all([caches.open('hefam-pages'), fetch(location.href, { credentials: 'same-origin' })])
                    .then(function (r) { return r[1].ok && !r[1].redirected ? r[0].put(location.href, r[1]) : null; })
                    .catch(function () {});
            }
        }).catch(function () {});
    }

    function openDb() {
        return new Promise(function (resolve, reject) {
            var request = indexedDB.open(DB_NAME, 1);
            request.onupgradeneeded = function () { request.result.createObjectStore(STORE, { keyPath: 'id' }); };
            request.onsuccess = function () { resolve(request.result); };
            request.onerror = function () { reject(request.error); };
        });
    }

    function run(mode, action) {
        return openDb().then(function (db) {
            return new Promise(function (resolve, reject) {
                var tx = db.transaction(STORE, mode);
                var request = action(tx.objectStore(STORE));
                tx.oncomplete = function () { resolve(request ? request.result : undefined); };
                tx.onerror = tx.onabort = function () { reject(tx.error); };
            });
        });
    }

    var queue = {
        all: function () { return run('readonly', function (s) { return s.getAll(); }).then(function (items) { return (items || []).sort(function (a, b) { return a.createdAt - b.createdAt; }); }); },
        put: function (item) { return run('readwrite', function (s) { return s.put(item); }); },
        remove: function (id) { return run('readwrite', function (s) { return s.delete(id); }); }
    };

    function uuid() {
        if (window.crypto && crypto.randomUUID) {
            return crypto.randomUUID();
        }
        var bytes = crypto.getRandomValues(new Uint8Array(16));
        bytes[6] = (bytes[6] & 0x0f) | 0x40;
        bytes[8] = (bytes[8] & 0x3f) | 0x80;
        var hex = Array.prototype.map.call(bytes, function (b) { return ('0' + b.toString(16)).slice(-2); }).join('');
        return hex.slice(0, 8) + '-' + hex.slice(8, 12) + '-' + hex.slice(12, 16) + '-' + hex.slice(16, 20) + '-' + hex.slice(20);
    }

    function fetchWithTimeout(url, options, ms) {
        return new Promise(function (resolve, reject) {
            var timer = setTimeout(function () { reject(new Error('timeout')); }, ms);
            fetch(url, options).then(function (r) { clearTimeout(timer); resolve(r); }, function (e) { clearTimeout(timer); reject(e); });
        });
    }

    function freshToken(item) {
        return fetchWithTimeout(TOKEN_URL, { credentials: 'same-origin', headers: { Accept: 'application/json' }, cache: 'no-store' }, SEND_TIMEOUT_MS)
            .then(function (response) {
                if (response.status === 401 || response.redirected) {
                    throw new Error('login');
                }
                return response.json();
            })
            .then(function (data) {
                if (item && Number(data.user) !== Number(item.userId)) {
                    throw new Error('user');
                }
                return data.token;
            });
    }

    function send(item, token, queued) {
        var body = new FormData();
        item.fields.forEach(function (pair) { body.append(pair[0], pair[0] === '_token' ? token : pair[1]); });

        var headers = { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': token };
        if (queued) {
            headers['X-Hefam-Queue'] = '1';
        }

        return fetchWithTimeout(item.url, { method: 'POST', body: body, credentials: 'same-origin', headers: headers }, SEND_TIMEOUT_MS);
    }

    function firstError(data) {
        if (data && data.errors) {
            var keys = Object.keys(data.errors);
            if (keys.length) {
                return data.errors[keys[0]][0];
            }
        }
        return (data && data.message) || 'Data ditolak server.';
    }

    function describe(form, fields) {
        var get = function (name) {
            var found = fields.filter(function (p) { return p[0] === name; })[0];
            return found ? found[1] : '';
        };
        var kind = form.getAttribute('data-offline');
        var date = get('log_date') || get('sort_date') || get('feed_date');
        var label = kind === 'panen' ? 'Panen' : (kind === 'pakan' ? 'Pakan' : 'Sortir telur');
        var meta = { date: date };

        if (kind === 'pakan') {
            var sesi = form.querySelector('input[name="session"]:checked');
            var kandang = form.querySelector('input[name="coop_id"]:checked');
            label += (sesi && sesi.parentElement.querySelector('b') ? ' ' + sesi.parentElement.querySelector('b').textContent : '') + (kandang && kandang.dataset.name ? ' ' + kandang.dataset.name : '');
            meta.coop = get('coop_id');
            meta.session = get('session');
        }

        if (kind === 'panen') {
            var radio = form.querySelector('input[name="coop_id"]:checked');
            meta.coop = get('coop_id');
            label += radio && radio.dataset.name ? ' ' + radio.dataset.name : '';
        }

        return { kind: kind, label: label + ' · ' + formatDate(date), meta: meta };
    }

    function formatDate(value) {
        if (!value) {
            return '';
        }
        var d = new Date(value + 'T00:00:00');
        return isNaN(d) ? value : d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
    }

    function unlockButtons(form) {
        form.querySelectorAll('button[type="submit"]').forEach(function (btn) {
            btn.disabled = false;
            if (btn.dataset.originalText) {
                btn.innerHTML = btn.dataset.originalText;
            }
        });
    }

    function nativeSubmit(form, token) {
        var input = form.querySelector('input[name="_token"]');
        if (input && token) {
            input.value = token;
        }
        HTMLFormElement.prototype.submit.call(form);
    }

    function saveOnPhone(form, item) {
        return queue.put(item).then(function () {
            unlockButtons(form);
            showSavedNotice(item);
            setTimeout(function () { location.reload(); }, 1800);
        });
    }

    function showSavedNotice(item) {
        var box = document.createElement('div');
        box.className = 'offline-saved';
        box.innerHTML = '<i class="bi bi-phone-fill"></i><div><b>Tersimpan di HP</b><span></span></div>';
        box.querySelector('span').textContent = item.label + '. Akan dikirim otomatis saat ada sinyal.';
        document.body.appendChild(box);
    }

    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (!form.matches || !form.matches('form[data-offline]') || event.defaultPrevented) {
            return;
        }
        event.preventDefault();

        var uuidInput = form.querySelector('input[name="client_uuid"]');
        if (!uuidInput) {
            uuidInput = document.createElement('input');
            uuidInput.type = 'hidden';
            uuidInput.name = 'client_uuid';
            form.appendChild(uuidInput);
        }
        if (!uuidInput.value) {
            uuidInput.value = uuid();
        }

        var fields = Array.from(new FormData(form).entries()).filter(function (p) { return typeof p[1] === 'string'; });
        var info = describe(form, fields);
        var item = {
            id: uuidInput.value, url: form.action, fields: fields, kind: info.kind, label: info.label, meta: info.meta,
            userId: USER_ID, userName: USER_NAME, createdAt: Date.now(), status: 'menunggu', error: null
        };

        if (!navigator.onLine) {
            saveOnPhone(form, item);
            return;
        }

        var token = (form.querySelector('input[name="_token"]') || {}).value || meta('csrf-token');

        send(item, token, false)
            .then(function (response) {
                if (response.status === 419) {
                    return freshToken(null).then(function (newToken) {
                        token = newToken;
                        return send(item, token, false);
                    });
                }
                return response;
            })
            .then(function (response) {
                if (response.status === 201) {
                    return response.json().then(function (data) { location.href = data.redirect || location.href; });
                }
                nativeSubmit(form, token);
            })
            .catch(function (error) {
                if (error && error.message === 'login') {
                    nativeSubmit(form, token);
                    return;
                }
                saveOnPhone(form, item);
            });
    });

    function flush() {
        if (flushing || !navigator.onLine) {
            return Promise.resolve();
        }
        flushing = true;
        var sent = 0;

        return queue.all().then(function (items) {
            var mine = items.filter(function (i) { return i.userId === USER_ID && i.status !== 'gagal'; });
            if (!mine.length) {
                return;
            }

            return freshToken(null).then(function (token) {
                return mine.reduce(function (chain, item) {
                    return chain.then(function (stop) {
                        if (stop) {
                            return true;
                        }
                        return send(item, token, true).then(function (response) {
                            if (response.status === 201 || response.status === 200) {
                                sent++;
                                return queue.remove(item.id).then(function () { return false; });
                            }
                            if (response.status === 422) {
                                return response.json().catch(function () { return {}; }).then(function (data) {
                                    item.status = 'gagal';
                                    item.error = firstError(data);
                                    return queue.put(item).then(function () { return false; });
                                });
                            }
                            return true;
                        }, function () { return true; });
                    });
                }, Promise.resolve(false));
            });
        }).catch(function () {}).then(function () {
            flushing = false;
            if (sent > 0) {
                toast(sent + ' catatan dari HP berhasil terkirim.');
            }
            return render();
        });
    }

    function toast(text) {
        var el = document.createElement('div');
        el.className = 'offline-toast';
        el.textContent = text;
        document.body.appendChild(el);
        setTimeout(function () { el.remove(); }, 5000);
    }

    function markPendingCoops(items) {
        var form = document.querySelector('form[data-offline="panen"]');
        if (!form) {
            return;
        }
        var date = (form.querySelector('input[name="log_date"]') || {}).value;
        items.filter(function (i) { return i.kind === 'panen' && i.meta && i.meta.date === date; }).forEach(function (item) {
            var radio = form.querySelector('input[name="coop_id"][value="' + item.meta.coop + '"]');
            if (!radio || radio.disabled) {
                return;
            }
            radio.disabled = true;
            radio.checked = false;
            var label = radio.closest('label');
            if (label) {
                label.style.opacity = '.6';
                var tag = document.createElement('span');
                tag.className = 'done';
                tag.innerHTML = '<i class="bi bi-hourglass-split"></i> Menunggu sinyal';
                (label.querySelector('span') || label).appendChild(tag);
            }
        });
    }

    function ensureBar() {
        if (bar) {
            return bar;
        }
        bar = document.createElement('div');
        bar.className = 'offline-bar';
        bar.hidden = true;
        document.body.appendChild(bar);
        bar.addEventListener('click', function (event) {
            var button = event.target.closest('button');
            if (!button) {
                return;
            }
            if (button.dataset.action === 'send') {
                flush();
            } else if (button.dataset.action === 'toggle') {
                bar.classList.toggle('open');
            } else if (button.dataset.action === 'remove') {
                if (button.dataset.sure !== '1') {
                    button.dataset.sure = '1';
                    button.textContent = 'Yakin hapus?';
                    return;
                }
                queue.remove(button.dataset.id).then(render);
            }
        });
        return bar;
    }

    function render() {
        return queue.all().then(function (items) {
            var el = ensureBar();
            var mine = items.filter(function (i) { return i.userId === USER_ID; });
            var others = items.filter(function (i) { return i.userId !== USER_ID; });
            var waiting = mine.filter(function (i) { return i.status !== 'gagal'; });
            var failed = mine.filter(function (i) { return i.status === 'gagal'; });
            var offline = !navigator.onLine;

            markPendingCoops(items);

            if (!offline && !items.length && document.body.dataset.fromCache !== '1') {
                el.hidden = true;
                return;
            }

            var title = offline || document.body.dataset.fromCache === '1'
                ? '<i class="bi bi-wifi-off"></i> Tidak ada sinyal. Catatan disimpan di HP.'
                : '<i class="bi bi-cloud-arrow-up-fill"></i> Ada catatan di HP yang belum terkirim.';

            var html = '<div class="offline-bar-head"><span>' + title + '</span>';
            if (items.length) {
                html += '<button type="button" class="btn btn-light btn-sm" data-action="toggle">' + items.length + ' catatan</button>';
            }
            if (waiting.length && !offline) {
                html += '<button type="button" class="btn btn-primary btn-sm" data-action="send"><i class="bi bi-send-fill"></i> Kirim</button>';
            }
            html += '</div><ul class="offline-bar-list">';

            mine.concat(others).forEach(function (item) {
                var mineItem = item.userId === USER_ID;
                var status = item.status === 'gagal'
                    ? '<b class="text-danger">Gagal: ' + escapeHtml(item.error || '') + '</b>'
                    : (mineItem ? 'Menunggu sinyal' : 'Milik ' + escapeHtml(item.userName || 'pekerja lain') + ' (masuk sebagai dia untuk mengirim)');
                html += '<li><div><b>' + escapeHtml(item.label) + '</b><small>' + status + '</small></div>';
                if (item.status === 'gagal' && mineItem) {
                    html += '<button type="button" class="btn btn-ghost-danger btn-sm" data-action="remove" data-id="' + item.id + '">Hapus</button>';
                }
                html += '</li>';
            });

            el.innerHTML = html + '</ul>';
            el.hidden = false;
            document.body.classList.toggle('has-offline-bar', true);

            if (failed.length) {
                el.classList.add('open');
            }
        }).catch(function () {});
    }

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (form.action && /\/logout$/.test(form.action) && navigator.serviceWorker && navigator.serviceWorker.controller) {
            navigator.serviceWorker.controller.postMessage('clear-pages');
        }
    }, true);

    if (document.body.dataset.fromCache === '1') {
        document.querySelectorAll('.notice').forEach(function (n) { n.remove(); });
    }

    window.addEventListener('online', function () { render(); flush(); });
    window.addEventListener('offline', render);
    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'visible') {
            flush();
        }
    });
    setInterval(flush, RETRY_EVERY_MS);

    render().then(function () { setTimeout(flush, 1000); });
})();
