window.CMS = (() => {
    const tokenKey = 'nhua_cms_token';
    const toastKey = 'nhua_cms_toast';
    const api = () => document.body.dataset.api || '/admin/api';
    const esc = value => String(value ?? '').replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]));
    let loggingOut = false;
    let refreshPromise = null;
    let refreshTimer = null;

    async function createRichEditor(textarea) {
        if (!textarea || !window.ClassicEditor) return null;
        try {
            const editor = await window.ClassicEditor.create(textarea, {
                toolbar: {
                    items: ['undo', 'redo', '|', 'heading', '|', 'bold', 'italic', 'link', '|', 'bulletedList', 'numberedList', 'blockQuote'],
                    shouldNotGroupWhenFull: false,
                },
                link: {addTargetToExternalLinks: true},
                placeholder: textarea.placeholder || 'Nhập nội dung',
            });
            textarea._richEditor = editor;
            return editor;
        } catch (error) {
            console.error('Không thể khởi tạo CKEditor:', error);
            return null;
        }
    }

    async function setupRichEditors(container) {
        const textareas = [...container.querySelectorAll('textarea[data-type="richtext"]')];
        await Promise.all(textareas.map(textarea => createRichEditor(textarea)));
        return textareas;
    }

    function expireSession() {
        if (loggingOut) return;
        loggingOut = true;
        localStorage.removeItem(tokenKey);
        const logoutForm = document.querySelector('form[action$="/cms/logout"]');
        if (logoutForm) {
            logoutForm.submit();
            return;
        }
        location.replace(document.body.dataset.login || '/cms/login');
    }

    function toast(message, error = false) {
        const element = document.querySelector('[data-toast]');
        if (!element) return;
        element.innerHTML = `<span class="toast-icon">${error ? '!' : '✓'}</span><span>${esc(message)}</span>`;
        element.className = `toast${error ? ' error' : ''}`;
        element.style.display = 'block';
        clearTimeout(element._timer);
        element._timer = setTimeout(() => element.style.display = 'none', 4000);
    }

    function flashToast(message, error = false) {
        sessionStorage.setItem(toastKey, JSON.stringify({message, error}));
    }

    function scheduleTokenRefresh(expiresAt) {
        clearTimeout(refreshTimer);
        const delay = Math.max(0, Number(expiresAt) * 1000 - Date.now() - 30000);
        refreshTimer = setTimeout(() => {
            refreshAccessToken().catch(expireSession);
        }, delay);
    }

    async function refreshAccessToken() {
        if (refreshPromise) return refreshPromise;
        refreshPromise = (async () => {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            const response = await fetch(document.body.dataset.refreshUrl || '/cms/refresh-token', {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    ...(csrfToken ? {'X-CSRF-TOKEN': csrfToken} : {}),
                },
            });
            let body = {};
            try { body = await response.json(); } catch {}
            if (!response.ok || !body.data?.access_token) {
                throw new Error(body.message || 'Không thể làm mới phiên đăng nhập.');
            }

            const token = body.data.access_token;
            const expiresAt = Number(body.data.access_token_expires_at || 0);
            localStorage.setItem(tokenKey, token);
            document.body.dataset.accessToken = token;
            document.body.dataset.accessTokenExpiresAt = String(expiresAt);
            if (expiresAt) scheduleTokenRefresh(expiresAt);
            return token;
        })().finally(() => { refreshPromise = null; });

        return refreshPromise;
    }

    async function request(path, options = {}, canRefresh = true) {
        const token = localStorage.getItem(tokenKey) || document.body.dataset.accessToken;
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        const customHeaders = {...(options.headers || {})};
        delete customHeaders.Authorization;
        delete customHeaders.authorization;
        const requestOptions = {...options, headers: {
            Accept: 'application/json',
            ...(token ? {Authorization: `Bearer ${token}`} : {}),
            ...(csrfToken ? {'X-CSRF-TOKEN': csrfToken} : {}),
            ...customHeaders,
        }};
        const response = await fetch(api() + path, requestOptions);
        let body = {};
        try { body = await response.json(); } catch {}
        if (response.status === 401 && canRefresh) {
            try {
                await refreshAccessToken();
                return request(path, options, false);
            } catch (error) {
                expireSession();
                throw new Error(error.message || 'Phiên đăng nhập đã hết hạn');
            }
        }
        if (response.status === 401 || response.status === 419) {
            expireSession();
            throw new Error('Phiên đăng nhập đã hết hạn');
        }
        if (!response.ok) {
            const details = body.errors ? Object.values(body.errors).flat().join('\n') : '';
            throw new Error(details || body.message || 'Có lỗi xảy ra');
        }
        return body;
    }

    function common() {
        const token = document.body.dataset.accessToken;
        if (token) localStorage.setItem(tokenKey, token);
        const pendingToast = sessionStorage.getItem(toastKey);
        if (pendingToast) {
            sessionStorage.removeItem(toastKey);
            try {
                const notification = JSON.parse(pendingToast);
                toast(notification.message, !!notification.error);
            } catch {}
        }
        const expiresAt = Number(document.body.dataset.accessTokenExpiresAt || 0) * 1000;
        if (expiresAt) scheduleTokenRefresh(expiresAt / 1000);
        document.querySelector('[data-menu]')?.addEventListener('click', () => document.querySelector('[data-sidebar]')?.classList.toggle('open'));
    }

    function show(value) {
        if (typeof value === 'boolean') return `<span class="badge ${value ? 'on' : ''}">${value ? 'Có' : 'Không'}</span>`;
        if (Array.isArray(value)) return `<span class="badge">${value.length} mục</span>`;
        return esc(value ?? '—');
    }

    function tableValue(key, value) {
        if (key === 'first_image' || key === 'thumbnail_path' || key === 'thumbnail_url') {
            if (!value) return '<span class="table-image-empty">Chưa có ảnh</span>';
            const storage = (document.body.dataset.storage || '/storage').replace(/\/$/, '');
            let url = '';
            if (typeof value === 'object' && value !== null) {
                url = value.external_url || (value.path ? `${storage}/${String(value.path).replace(/^\//, '')}` : '');
            } else if (typeof value === 'string' && value.trim()) {
                url = /^https?:\/\//.test(value) ? value : `${storage}/${value.replace(/^\//, '')}`;
            }
            return url
                ? `<img class="table-image" src="${esc(url)}" alt="" loading="lazy">`
                : '<span class="table-image-empty">Chưa có ảnh</span>';
        }
        if (key === 'price' && value !== null && value !== undefined && value !== '') {
            return `${new Intl.NumberFormat('vi-VN').format(Number(value))} ₫`;
        }
        if (key === 'category_names' || key === 'consultation_content' || key === 'description') {
            const raw = String(value || '');
            const textOnly = raw.replace(/<[^>]*>/g, '').trim();
            const full = textOnly || (raw && raw !== 'null' && raw !== 'undefined' ? raw : '—');
            const short = full.length > 45 ? `${full.slice(0, 45).trim()}...` : full;
            return `<span class="table-ellipsis" title="${esc(full)}">${esc(short || '—')}</span>`;
        }
        return show(value);
    }

    function fileKind(file) {
        const name = String(file.name || file.file_name || file.path || '').toLowerCase();
        const mime = String(file.type || file.mime_type || '').toLowerCase();
        if (mime.startsWith('image/') || /\.(png|jpe?g|gif|webp|svg)$/.test(name)) return 'image';
        if (mime.includes('pdf') || name.endsWith('.pdf')) return 'PDF';
        if (mime.includes('word') || /\.docx?$/.test(name)) return 'WORD';
        if (mime.includes('excel') || mime.includes('spreadsheet') || /\.(xlsx?|csv)$/.test(name)) return 'EXCEL';
        if (mime.includes('powerpoint') || mime.includes('presentation') || /\.pptx?$/.test(name)) return 'PPT';
        if (/\.(zip|rar|7z)$/.test(name)) return 'ZIP';
        if (mime.startsWith('video/')) return 'VIDEO';
        return (name.split('.').pop() || 'FILE').toUpperCase().slice(0, 5);
    }

    function fileSize(bytes) {
        if (!Number(bytes)) return '';
        const units = ['B', 'KB', 'MB', 'GB']; let size = Number(bytes), index = 0;
        while (size >= 1024 && index < units.length - 1) { size /= 1024; index++; }
        return `${size.toFixed(index ? 1 : 0)} ${units[index]}`;
    }

    function renderUploadItem(file, source, remove) {
        const item = document.createElement('div'); item.className = 'upload-file-item';
        const kind = fileKind(file); const name = file.name || file.file_name || file.path?.split('/').pop() || 'File';
        const path = file.path ? `${document.body.dataset.storage || '/storage'}/${String(file.path).replace(/^\//, '')}` : '';
        if (kind === 'image') {
            const url = source === 'new' ? URL.createObjectURL(file) : path;
            item.innerHTML = `<div class="upload-thumb"><img src="${esc(url)}" alt=""></div><div class="upload-file-info"><strong title="${esc(name)}">${esc(name)}</strong><small>${fileSize(file.size)}</small></div>`;
            if (source === 'new') item._objectUrl = url;
        } else item.innerHTML = `<div class="upload-doc-icon type-${kind.toLowerCase()}">${esc(kind)}</div><div class="upload-file-info"><strong title="${esc(name)}">${esc(name)}</strong><small>${fileSize(file.size)}</small></div>`;
        if (source === 'new') {
            const button = document.createElement('button'); button.type = 'button'; button.className = 'upload-remove'; button.title = 'Bỏ file'; button.textContent = '×';
            button.onclick = () => { if (item._objectUrl) URL.revokeObjectURL(item._objectUrl); remove(); }; item.appendChild(button);
        } else {
            item.classList.add('existing');
            if (file.id) {
                const button = document.createElement('button');
                button.type = 'button'; button.className = 'upload-remove stored-file-remove'; button.title = 'Xóa file đã lưu'; button.setAttribute('aria-label', 'Xóa file đã lưu');
                button.innerHTML = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v5M14 11v5"/></svg>';
                button.onclick = async () => {
                    if (!await confirmRemoveStoredFile(name)) return;
                    button.disabled = true;
                    try {
                        const result = await request(`/files/${file.id}`, {method: 'DELETE'});
                        item.remove();
                        toast(result.message || 'Đã xóa file');
                    } catch (error) {
                        button.disabled = false;
                        toast(error.message, true);
                    }
                };
                item.appendChild(button);
            }
        }
        return item;
    }

    function setupMultiUploads(form) {
        form.querySelectorAll('[data-multi-upload]').forEach(upload => {
            const input = upload.querySelector('[data-upload-input]'), zone = upload.querySelector('[data-upload-dropzone]'), preview = upload.querySelector('[data-upload-preview]');
            let files = [];
            const sync = () => {
                const transfer = new DataTransfer(); files.forEach(file => transfer.items.add(file)); input.files = transfer.files;
                preview.querySelectorAll('[data-new-file]').forEach(item => item.remove());
                files.forEach((file, index) => { const item = renderUploadItem(file, 'new', () => { files.splice(index, 1); sync(); }); item.dataset.newFile = '1'; preview.appendChild(item); });
            };
            const add = incoming => { files = upload.dataset.singleUpload ? incoming.slice(0, 1) : [...files, ...incoming]; sync(); };
            input.addEventListener('change', () => add([...input.files]));
            ['dragenter', 'dragover'].forEach(name => zone.addEventListener(name, event => { event.preventDefault(); zone.classList.add('dragover'); }));
            ['dragleave', 'drop'].forEach(name => zone.addEventListener(name, event => { event.preventDefault(); zone.classList.remove('dragover'); }));
            zone.addEventListener('drop', event => add([...event.dataTransfer.files]));
            upload._showExisting = existing => { preview.querySelectorAll('.existing').forEach(item => item.remove()); (existing || []).forEach(file => preview.appendChild(renderUploadItem(file, 'existing'))); };
            upload._clearNewFiles = () => { files = []; sync(); input.value = ''; };
        });
    }

    function indexPage() {
        const root = document.querySelector('.module-table');
        const endpoint = root.dataset.endpoint;
        const editUrl = root.dataset.editUrl;
        const fixedParams = JSON.parse(root.dataset.fixedParams || '{}');
        const columns = JSON.parse(root.querySelector('[data-columns]').textContent);
        const tbody = root.querySelector('[data-table-body]');
        const filters = [...root.querySelectorAll('[data-filter-name]')];
        let timer;
        async function load(search = '') {
            tbody.innerHTML = `<tr><td class="empty loading-state" colspan="${columns.length + 1}">Đang tải dữ liệu...</td></tr>`;
            try {
                const params = new URLSearchParams({per_page: '50', search});
                Object.entries(fixedParams).forEach(([name, value]) => params.set(name, value));
                filters.forEach(filter => {
                    const explicitQueryName = filter.dataset.filterQueryName;
                    if (filter.dataset.filterMultiple) {
                        const queryName = explicitQueryName || `${filter.dataset.filterName}[]`;
                        (filter._selected || []).forEach(item => params.append(queryName, item.id));
                    } else if (filter.value) {
                        if (explicitQueryName) params.append(explicitQueryName, filter.value);
                        else params.set(filter.dataset.filterName, filter.value);
                    }
                });
                const result = await request(`/${endpoint}?${params.toString()}`);
                const rows = result.data || [];
                tbody.innerHTML = rows.length ? rows.map(row => {
                    const variantsAction = endpoint === 'products'
                        ? `<a class="btn icon-button variants-link" href="/cms/products/${row.id}/variants" title="Quản lý biến thể" aria-label="Quản lý biến thể"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg></a>`
                        : '';
                    return `<tr>${columns.map(key => `<td>${tableValue(key, row[key])}</td>`).join('')}<td><div class="actions">${variantsAction}<a class="btn icon-button" href="${editUrl}/${row.id}/edit" title="Sửa" aria-label="Sửa"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L8 18l-4 1 1-4Z"/></svg></a><button class="btn icon-button danger-icon delete" data-id="${row.id}" title="Xóa" aria-label="Xóa"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v5M14 11v5"/></svg></button></div></td></tr>`;
                }).join('') : `<tr><td class="empty" colspan="${columns.length + 1}">Chưa có dữ liệu</td></tr>`;
                tbody.querySelectorAll('.delete').forEach(button => button.onclick = async () => {
                    if (!confirm('Bạn chắc chắn muốn xóa?')) return;
                    try { const result = await request(`/${endpoint}/${button.dataset.id}`, {method: 'DELETE'}); toast(result.message || 'Đã xóa'); load(); }
                    catch (error) { toast(error.message, true); }
                });
            } catch (error) { tbody.innerHTML = `<tr><td class="empty" colspan="${columns.length + 1}">${esc(error.message)}</td></tr>`; }
        }
        const searchInput = root.querySelector('[data-search]');
        Promise.all(filters.map(async filter => {
            const items = filter.dataset.inlineItems
                ? JSON.parse(filter.dataset.inlineItems)
                : ((await request(`/${filter.dataset.source}?per_page=100`)).data || []);
            if (filter.dataset.filterMultiple) {
                filter._selected = [];
                const chips = filter.closest('[data-filter-wrap]').querySelector('[data-filter-chips]');
                const filterWrap = filter.closest('[data-filter-wrap]');
                const filterToggle = filterWrap.querySelector('[data-filter-toggle]');
                const filterSummary = filterWrap.querySelector('[data-filter-summary]');
                const filterMenu = filterWrap.querySelector('[data-filter-menu]');
                const renderCheckboxMenu = () => {
                    const selectedIds = new Set(filter._selected.map(item => String(item.id)));
                    filterSummary.textContent = selectedIds.size
                        ? `Đã chọn ${selectedIds.size} giá trị`
                        : (filter.dataset.filterLabel || 'Chọn giá trị để lọc');
                    filterMenu.innerHTML = items.length ? items.map(item => {
                        const id = String(item[filter.dataset.value]);
                        return `<label class="checkbox-filter-option"><input type="checkbox" value="${esc(id)}" ${selectedIds.has(id) ? 'checked' : ''}><span>${esc(item[filter.dataset.text])}</span></label>`;
                    }).join('') : '<div class="checkbox-filter-empty">Chưa có giá trị phù hợp</div>';
                    filterMenu.querySelectorAll('input[type="checkbox"]').forEach(checkbox => checkbox.onchange = () => {
                        const item = items.find(value => String(value[filter.dataset.value]) === checkbox.value);
                        if (checkbox.checked && item && !filter._selected.some(value => String(value.id) === checkbox.value)) {
                            filter._selected.push({id: item[filter.dataset.value], name: item[filter.dataset.text]});
                        } else if (!checkbox.checked) {
                            filter._selected = filter._selected.filter(value => String(value.id) !== checkbox.value);
                        }
                        render();
                        renderCheckboxMenu();
                        filterMenu.hidden = false;
                        filterWrap.classList.add('open');
                        load(searchInput.value);
                    });
                };
                const render = () => {
                    const selectedIds = new Set(filter._selected.map(item => String(item.id)));
                    filter.innerHTML = `<option value="">${esc(filter.dataset.filterLabel || 'Chọn giá trị để lọc')}</option>` + items.filter(item => !selectedIds.has(String(item[filter.dataset.value]))).map(item => `<option value="${item[filter.dataset.value]}">${esc(item[filter.dataset.text])}</option>`).join('');
                    chips.innerHTML = filter._selected.map(item => `<span class="filter-chip">${esc(item.name)}<button type="button" data-remove-filter="${item.id}" aria-label="Bỏ bộ lọc">×</button></span>`).join('');
                    chips.querySelectorAll('[data-remove-filter]').forEach(button => button.onclick = () => {
                        filter._selected = filter._selected.filter(item => String(item.id) !== button.dataset.removeFilter);
                        render(); load(searchInput.value);
                    });
                };
                filter.onchange = () => {
                    if (!filter.value) return;
                    const item = items.find(value => String(value[filter.dataset.value]) === filter.value);
                    if (item) filter._selected.push({id: item[filter.dataset.value], name: item[filter.dataset.text]});
                    render(); load(searchInput.value);
                };
                filterToggle.onclick = event => {
                    event.stopPropagation();
                    const willOpen = filterMenu.hidden;
                    document.querySelectorAll('[data-filter-menu]').forEach(menu => menu.hidden = true);
                    document.querySelectorAll('[data-filter-wrap].open').forEach(wrap => wrap.classList.remove('open'));
                    filterMenu.hidden = !willOpen;
                    filterWrap.classList.toggle('open', willOpen);
                };
                filterMenu.onclick = event => event.stopPropagation();
                filter._resetFilter = () => {
                    filter._selected = [];
                    filter.value = '';
                    render();
                    renderCheckboxMenu();
                    filterMenu.hidden = true;
                    filterWrap.classList.remove('open');
                };
                render();
                renderCheckboxMenu();
            } else {
                items.forEach(item => filter.add(new Option(item[filter.dataset.text], item[filter.dataset.value])));
                filter.onchange = () => load(searchInput.value);
                filter._resetFilter = () => { filter.value = ''; };
            }
        })).catch(error => toast(error.message, true));
        document.addEventListener('click', event => {
            if (event.target.closest('[data-filter-wrap]')) return;
            root.querySelectorAll('[data-filter-menu]').forEach(menu => menu.hidden = true);
            root.querySelectorAll('[data-filter-wrap].open').forEach(wrap => wrap.classList.remove('open'));
        });
        searchInput.oninput = event => { clearTimeout(timer); timer = setTimeout(() => load(event.target.value), 350); };
        root.querySelector('[data-reload]').onclick = () => {
            clearTimeout(timer);
            searchInput.value = '';
            filters.forEach(filter => {
                if (filter._resetFilter) filter._resetFilter();
                else {
                    filter.value = '';
                    if (filter.dataset.filterMultiple) filter._selected = [];
                }
            });
            load('');
        };
        load();
    }

    function modalShell(content) {
        const overlay = document.createElement('div');
        overlay.className = 'cms-modal-overlay';
        overlay.innerHTML = `<div class="cms-modal">${content}</div>`;
        document.body.appendChild(overlay);
        return overlay;
    }

    function confirmGroupEdit(group, productsCount) {
        return new Promise(resolve => {
            const overlay = modalShell(`<div class="cms-modal-head"><div class="warning-icon">!</div><div><h3>Cảnh báo thay đổi nhóm biến thể</h3><p>Nhóm <strong>${esc(group.group_name)}</strong> đang được sử dụng bởi <strong>${productsCount} sản phẩm</strong>.</p></div></div><div class="cms-alert">Việc đổi tên hoặc mã nhóm sẽ hiển thị trên tất cả sản phẩm đang sử dụng nhóm này. Bạn có muốn tiếp tục?</div><div class="cms-modal-actions"><button type="button" class="btn" data-cancel>Hủy</button><button type="button" class="btn primary" data-confirm>Tiếp tục sửa</button></div>`);
            const close = result => { overlay.remove(); resolve(result); };
            overlay.querySelector('[data-cancel]').onclick = () => close(false);
            overlay.querySelector('[data-confirm]').onclick = () => close(true);
            overlay.onclick = event => { if (event.target === overlay) close(false); };
        });
    }

    function confirmRemoveProductGroup(group) {
        return new Promise(resolve => {
            const overlay = modalShell(`<div class="cms-modal-head"><div class="warning-icon">!</div><div><h3>Xóa nhóm biến thể khỏi sản phẩm?</h3><p>Nhóm <strong>${esc(group.group_name)}</strong> và các giá trị riêng sẽ bị xóa khỏi sản phẩm này.</p></div></div><div class="cms-alert">Thao tác được thực hiện ngay, không cần nhấn “Lưu thay đổi”.</div><div class="cms-modal-actions"><button type="button" class="btn" data-cancel>Hủy</button><button type="button" class="btn danger" data-confirm>Xóa</button></div>`);
            const close = result => { overlay.remove(); resolve(result); };
            overlay.querySelector('[data-cancel]').onclick = () => close(false);
            overlay.querySelector('[data-confirm]').onclick = () => close(true);
            overlay.onclick = event => { if (event.target === overlay) close(false); };
        });
    }

    function confirmRemoveVariantOption(optionName) {
        return new Promise(resolve => {
            const overlay = modalShell(`<div class="cms-modal-head"><div class="warning-icon">!</div><div><h3>Xóa giá trị biến thể?</h3><p>Giá trị <strong>${esc(optionName || 'này')}</strong> sẽ bị xóa khỏi sản phẩm.</p></div></div><div class="cms-alert">Thao tác được thực hiện ngay, không cần nhấn “Lưu thay đổi”.</div><div class="cms-modal-actions"><button type="button" class="btn" data-cancel>Hủy</button><button type="button" class="btn danger" data-confirm>Xóa</button></div>`);
            const close = result => { overlay.remove(); resolve(result); };
            overlay.querySelector('[data-cancel]').onclick = () => close(false);
            overlay.querySelector('[data-confirm]').onclick = () => close(true);
            overlay.onclick = event => { if (event.target === overlay) close(false); };
        });
    }

    function confirmRemoveStoredFile(fileName) {
        return new Promise(resolve => {
            const overlay = modalShell(`<div class="cms-modal-head"><div class="warning-icon">!</div><div><h3>Xóa file đã lưu?</h3><p>File <strong>${esc(fileName)}</strong> sẽ bị xóa khỏi dữ liệu và bộ nhớ lưu trữ.</p></div></div><div class="cms-alert">Thao tác này được thực hiện ngay và không cần lưu lại form.</div><div class="cms-modal-actions"><button type="button" class="btn" data-cancel>Hủy</button><button type="button" class="btn danger" data-confirm>Xóa</button></div>`);
            const close = result => { overlay.remove(); resolve(result); };
            overlay.querySelector('[data-cancel]').onclick = () => close(false);
            overlay.querySelector('[data-confirm]').onclick = () => close(true);
            overlay.onclick = event => { if (event.target === overlay) close(false); };
        });
    }

    function groupFormModal(group = null) {
        return new Promise(resolve => {
            const editing = !!group;
            const overlay = modalShell(`<form class="cms-group-form"><div class="cms-modal-title"><h3>${editing ? 'Sửa' : 'Thêm mới'} nhóm biến thể</h3><button type="button" class="modal-close" data-cancel>×</button></div><div class="cms-modal-body"><div class="field"><label>Mã nhóm biến thể</label><input class="input" name="group_code" value="${esc(group?.group_code || '')}" placeholder="Ví dụ: color" required></div><div class="field"><label>Tên nhóm biến thể</label><input class="input" name="group_name" value="${esc(group?.group_name || '')}" placeholder="Ví dụ: Màu sắc" required></div><div class="form-error" data-modal-error></div></div><div class="cms-modal-actions"><button type="button" class="btn" data-cancel>Hủy</button><button type="submit" class="btn primary">${editing ? 'Lưu thay đổi' : 'Thêm nhóm'}</button></div></form>`);
            const form = overlay.querySelector('form');
            const close = result => { overlay.remove(); resolve(result); };
            overlay.querySelectorAll('[data-cancel]').forEach(button => button.onclick = () => close(null));
            overlay.onclick = event => { if (event.target === overlay) close(null); };
            form.onsubmit = async event => {
                event.preventDefault();
                const button = form.querySelector('button[type="submit"]');
                const error = form.querySelector('[data-modal-error]');
                button.disabled = true;
                error.textContent = '';
                try {
                    const payload = Object.fromEntries(new FormData(form));
                    const result = await request(`/variant-groups${editing ? '/' + group.id : ''}`, {
                        method: editing ? 'PUT' : 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify(payload),
                    });
                    close({...result.data, _message: result.message});
                } catch (exception) {
                    error.textContent = exception.message;
                    button.disabled = false;
                }
            };
        });
    }

    function selectedGroupState(picker) {
        const state = new Map();
        picker.querySelectorAll('.relation-row').forEach(row => {
            const checkbox = row.querySelector('[data-group-check]');
            if (checkbox?.checked) state.set(Number(checkbox.value), {
                configuration_id: row.dataset.configurationId || null,
                is_required: row.querySelector('[data-required]').checked,
                sort_order: row.querySelector('.relation-order').value,
                options: [...row.querySelectorAll('.product-option-row')].map(optionRow => ({
                    id: optionRow.querySelector('[data-option-field="id"]').value || null,
                    option_code: optionRow.querySelector('[data-option-field="option_code"]').value,
                    option_name: optionRow.querySelector('[data-option-field="option_name"]').value,
                    sort_order: optionRow.querySelector('[data-option-field="sort_order"]').value,
                    is_active: optionRow.querySelector('[data-option-field="is_active"]').checked,
                })),
            });
        });
        return state;
    }

    function renderGroupPicker(picker, groups, state = new Map()) {
        picker._groups = groups;
        picker.innerHTML = groups.length ? `<div class="relation-toolbar"><div class="relation-search-wrap"><span>⌕</span><input class="input relation-search" type="search" placeholder="Nhập tên hoặc mã nhóm để tìm..." data-group-search autocomplete="off"></div><div class="relation-toolbar-actions"><span class="selected-count" data-selected-count>Đã chọn 0 nhóm</span><button type="button" class="btn primary compact" data-add-group>＋ Thêm nhóm</button></div></div><div class="group-search-results" data-group-search-results hidden><div class="group-list-title">Kết quả tìm kiếm</div><div data-group-list>${groups.map(group => `<div class="relation-row" data-group-id="${group.id}" data-search-text="${esc(`${group.group_name} ${group.group_code}`.toLowerCase())}"><div class="relation-main"><button type="button" class="group-drag" draggable="true" title="Kéo để đổi thứ tự nhóm" aria-label="Kéo để đổi thứ tự nhóm">⠿</button><input type="checkbox" data-group-check value="${group.id}" hidden><span><strong data-group-name>${esc(group.group_name)}</strong><small data-group-code>${esc(group.group_code)}</small></span></div><label class="required-control">Bắt buộc <input type="checkbox" data-required disabled></label><label class="group-order-control">Thứ tự trong sản phẩm <input class="input relation-order" type="number" min="0" value="" placeholder="Tự động" disabled></label><div class="group-row-actions"><button type="button" class="btn compact select-group" data-select-group>＋ Chọn</button><button type="button" class="btn compact edit-group" data-edit-group="${group.id}">Sửa</button><button type="button" class="btn compact remove-group" data-remove-group title="Gỡ nhóm khỏi sản phẩm" aria-label="Gỡ nhóm khỏi sản phẩm">🗑</button></div><div class="product-options" data-product-options hidden><div class="product-options-head"><strong>Giá trị riêng của sản phẩm</strong><button type="button" class="btn compact" data-add-option>＋ Thêm giá trị</button></div><div data-option-rows></div></div></div>`).join('')}</div><div class="relation-no-result" data-no-result hidden>Không tìm thấy nhóm biến thể phù hợp.</div></div><div class="selected-groups-section"><div class="group-list-title">Nhóm biến thể đã chọn</div><div data-selected-group-list></div><div class="selected-groups-empty" data-selected-empty>Chưa chọn nhóm biến thể nào. Hãy tìm kiếm và chọn nhóm muốn sử dụng.</div></div>` : `<div class="relation-toolbar"><span>Chưa có nhóm biến thể.</span><button type="button" class="btn primary compact" data-add-group>＋ Thêm nhóm đầu tiên</button></div>`;
        setupGroupPicker(picker);
        picker.querySelectorAll('.product-options-head strong').forEach(title => title.textContent = 'Giá trị của biến thể');
        picker.querySelectorAll('[data-edit-group]').forEach(button => {
            button.classList.add('icon-button');
            button.title = 'Sửa nhóm biến thể';
            button.setAttribute('aria-label', 'Sửa nhóm biến thể');
            button.innerHTML = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L8 18l-4 1 1-4Z"/></svg>';
        });
        picker.querySelectorAll('[data-remove-group]').forEach(button => {
            button.classList.add('icon-button', 'danger-icon');
            button.title = 'Xóa nhóm khỏi sản phẩm';
            button.setAttribute('aria-label', 'Xóa nhóm khỏi sản phẩm');
            button.innerHTML = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v5M14 11v5"/></svg>';
        });
        state.forEach((value, id) => {
            const row = picker.querySelector(`[data-group-id="${id}"]`);
            if (!row) return;
            row.querySelector('[data-group-check]').checked = true;
            row.dataset.configurationId = value.configuration_id || '';
            row.querySelector('[data-required]').checked = !!value.is_required;
            row.querySelector('.relation-order').value = value.sort_order;
            setProductOptions(row, value.options || []);
        });
        picker._updateSelection?.();
        picker.querySelector('[data-add-group]').onclick = async () => {
            const currentState = selectedGroupState(picker);
            const created = await groupFormModal();
            if (!created) return;
            const selectedOrders = [...currentState.values()].map(item => Number(item.sort_order)).filter(Number.isFinite);
            currentState.set(Number(created.id), {is_required: false, sort_order: selectedOrders.length ? Math.max(...selectedOrders) + 1 : 0});
            renderGroupPicker(picker, [...picker._groups, created], currentState);
            toast(created._message || 'Đã thêm và chọn nhóm biến thể');
        };
        picker.querySelectorAll('[data-edit-group]').forEach(button => button.onclick = async () => {
            const group = picker._groups.find(item => Number(item.id) === Number(button.dataset.editGroup));
            try {
                const usage = await request(`/variant-groups/${group.id}/usage`);
                if (!await confirmGroupEdit(group, usage.data.products_count)) return;
                const updated = await groupFormModal(group);
                if (!updated) return;
                const currentState = selectedGroupState(picker);
                renderGroupPicker(picker, picker._groups.map(item => Number(item.id) === Number(updated.id) ? {...item, ...updated} : item), currentState);
                toast(updated._message || 'Đã cập nhật nhóm biến thể');
            } catch (error) { toast(error.message, true); }
        });
        picker.querySelectorAll('[data-add-option]').forEach(button => button.onclick = () => addProductOptionRow(button.closest('.relation-row')));
    }

    function addProductOptionRow(row, option = {}) {
        const container = row.querySelector('[data-option-rows]');
        const element = document.createElement('div');
        element.className = 'product-option-row';
        element.innerHTML = `<button type="button" class="option-drag" draggable="true" title="Kéo để thay đổi thứ tự" aria-label="Kéo để thay đổi thứ tự">⠿</button><input type="hidden" data-option-field="id" value="${esc(option.id || '')}"><input type="hidden" data-option-field="sort_order" value="${option.sort_order ?? container.children.length}"><input class="input" data-option-field="option_code" value="${esc(option.option_code || '')}" placeholder="Mã, ví dụ: s" required><input class="input" data-option-field="option_name" value="${esc(option.option_name || '')}" placeholder="Tên, ví dụ: S" required><label class="option-active"><input type="checkbox" data-option-field="is_active" ${option.is_active === false ? '' : 'checked'}> Hoạt động</label><div class="option-row-actions"><button type="button" class="btn compact save-option">Lưu</button><button type="button" class="btn compact remove-option">Xóa</button></div>`;
        const removeButton = element.querySelector('.remove-option');
        removeButton.classList.add('icon-button', 'danger-icon');
        removeButton.title = 'Xóa giá trị biến thể';
        removeButton.setAttribute('aria-label', 'Xóa giá trị biến thể');
        removeButton.innerHTML = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v5M14 11v5"/></svg>';
        element.querySelector('.save-option').onclick = async event => {
            const configurationId = row.dataset.configurationId;
            if (!configurationId) {
                toast('Hãy lưu sản phẩm trước để tạo nhóm biến thể, sau đó mới lưu từng giá trị.', true);
                return;
            }
            const codeInput = element.querySelector('[data-option-field="option_code"]');
            const nameInput = element.querySelector('[data-option-field="option_name"]');
            if (!codeInput.reportValidity() || !nameInput.reportValidity()) return;
            normalizeProductOptionOrder(container);
            const idInput = element.querySelector('[data-option-field="id"]');
            const optionId = idInput.value;
            const payload = {
                product_variant_group_id: Number(configurationId),
                option_code: codeInput.value.trim(),
                option_name: nameInput.value.trim(),
                sort_order: Number(element.querySelector('[data-option-field="sort_order"]').value),
                is_active: element.querySelector('[data-option-field="is_active"]').checked,
            };
            const button = event.currentTarget;
            button.disabled = true;
            button.textContent = 'Đang lưu...';
            element.classList.remove('has-error');
            element.removeAttribute('data-error');
            try {
                const result = await request(`/variant-options${optionId ? '/' + optionId : ''}`, {
                    method: optionId ? 'PUT' : 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(payload),
                });
                idInput.value = result.data.id;
                toast(result.message || (optionId ? 'Đã cập nhật giá trị biến thể' : 'Đã tạo giá trị biến thể'));
            } catch (error) {
                element.classList.add('has-error');
                element.dataset.error = error.message;
                toast(error.message, true);
            } finally {
                button.disabled = false;
                button.textContent = 'Lưu';
            }
        };
        element.querySelector('.remove-option').onclick = async event => {
            const button = event.currentTarget;
            const id = element.querySelector('[data-option-field="id"]').value;
            const optionName = element.querySelector('[data-option-field="option_name"]').value;
            if (!id) {
                element.remove();
                normalizeProductOptionOrder(container);
                return;
            }
            if (!await confirmRemoveVariantOption(optionName)) return;
            button.disabled = true;
            try {
                const result = await request(`/variant-options/${id}`, {method: 'DELETE'});
                element.remove();
                normalizeProductOptionOrder(container);
                toast(result.message || 'Đã xóa giá trị biến thể');
            } catch (error) {
                button.disabled = false;
                toast(error.message, true);
            }
        };
        const dragHandle = element.querySelector('.option-drag');
        dragHandle.addEventListener('dragstart', event => {
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', 'option');
            const preview = element.cloneNode(true);
            const rect = element.getBoundingClientRect();
            preview.classList.add('option-drag-preview');
            preview.style.width = `${rect.width}px`;
            preview.style.height = `${rect.height}px`;
            document.body.appendChild(preview);
            event.dataTransfer.setDragImage(preview, 18, rect.height / 2);
            element._dragPreview = preview;
            requestAnimationFrame(() => element.classList.add('dragging'));
        });
        dragHandle.addEventListener('dragend', () => {
            element.classList.remove('dragging');
            element._dragPreview?.remove();
            element._dragPreview = null;
            normalizeProductOptionOrder(container);
        });
        setupProductOptionDropZone(container);
        container.appendChild(element);
        normalizeProductOptionOrder(container);
    }

    function setupProductOptionDropZone(container) {
        if (container.dataset.sortableReady) return;
        container.dataset.sortableReady = '1';
        container.addEventListener('dragover', event => {
            event.preventDefault();
            const dragging = container.querySelector('.product-option-row.dragging');
            if (!dragging) return;
            const target = [...container.querySelectorAll('.product-option-row:not(.dragging)')]
                .find(item => event.clientY < item.getBoundingClientRect().top + item.offsetHeight / 2);
            target ? container.insertBefore(dragging, target) : container.appendChild(dragging);
        });
    }

    function normalizeProductOptionOrder(container) {
        [...container.querySelectorAll('.product-option-row')].forEach((item, index) => {
            item.querySelector('[data-option-field="sort_order"]').value = index;
        });
    }

    function setProductOptions(row, options) {
        row.querySelector('[data-option-rows]').innerHTML = '';
        [...options].sort((first, second) => Number(first.sort_order || 0) - Number(second.sort_order || 0)).forEach(option => addProductOptionRow(row, option));
    }

    function setupCategoryPicker(picker, categories) {
        const choice = picker.querySelector('[data-category-choice]');
        const list = picker.querySelector('[data-category-selected]');
        const inputs = picker.querySelector('[data-category-inputs]');
        let selected = [];

        const render = () => {
            const selectedIds = new Set(selected.map(item => String(item.id)));
            choice.innerHTML = `<option value="">${esc(choice.dataset.placeholder || '-- Chọn danh mục để thêm --')}</option>` + categories
                .filter(item => !selectedIds.has(String(item[picker.dataset.value])))
                .map(item => `<option value="${item[picker.dataset.value]}">${esc(item[picker.dataset.text])}</option>`).join('');
            list.innerHTML = selected.length ? selected.map(item => `<div class="category-chip" data-category-id="${item.id}"><span>${esc(item.category_name)}</span><button type="button" class="category-chip-remove" data-remove-category="${item.id}" title="Gỡ danh mục" aria-label="Gỡ danh mục"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg></button></div>`).join('') : '<div class="category-empty">Chưa chọn danh mục nào.</div>';
            inputs.innerHTML = selected.map(item => `<input type="hidden" name="${esc(picker.dataset.name)}[]" value="${item.id}">`).join('');
            list.querySelectorAll('[data-remove-category]').forEach(button => button.onclick = () => {
                selected = selected.filter(item => String(item.id) !== button.dataset.removeCategory);
                render();
            });
        };

        choice.onchange = () => {
            if (!choice.value) return;
            const category = categories.find(item => String(item[picker.dataset.value]) === choice.value);
            if (category) selected.push({id: category[picker.dataset.value], category_name: category[picker.dataset.text]});
            render();
        };
        picker._setSelected = items => {
            selected = items.map(item => ({id: item.id, category_name: item.category_name}));
            render();
        };
        render();
    }

    function setupSearchableSelect(picker) {
        const input = picker.querySelector('[data-searchable-input]');
        const hidden = picker.querySelector('input[type="hidden"]');
        const results = picker.querySelector('[data-searchable-results]');
        let timer;
        let sequence = 0;

        const close = () => { results.hidden = true; };
        const select = (id, label, notify = true) => {
            hidden.value = id ?? '';
            input.value = label ?? '';
            input.setCustomValidity(hidden.value ? '' : 'Vui lòng chọn sản phẩm trong danh sách.');
            close();
            if (notify) hidden.dispatchEvent(new Event('change', {bubbles: true}));
        };
        const render = items => {
            results.innerHTML = items.length
                ? items.map(item => {
                    const image = Array.isArray(item.images) ? item.images[0] : null;
                    const storage = (document.body.dataset.storage || '/storage').replace(/\/$/, '');
                    const imageUrl = image?.external_url || (image?.path ? `${storage}/${String(image.path).replace(/^\//, '')}` : '');
                    const categories = item.category_names || (item.categories || []).map(category => category.category_name).join(', ') || 'Chưa có danh mục';
                    return `<button type="button" class="searchable-select-option product-search-option" data-id="${item[picker.dataset.value]}">
                        <span class="product-search-thumb">${imageUrl ? `<img src="${esc(imageUrl)}" alt="">` : '<span>Không có ảnh</span>'}</span>
                        <span class="product-search-info">
                            <strong>${esc(item[picker.dataset.text])}</strong>
                            <span class="product-search-meta"><span>SKU: ${esc(item.sku || '—')}</span><span title="${esc(categories)}">Danh mục: ${esc(categories)}</span></span>
                        </span>
                        <span class="product-search-status">
                            <span class="badge ${item.is_active ? 'on' : ''}">${item.is_active ? 'Đang hoạt động' : 'Ngừng hoạt động'}</span>
                            <span class="badge ${item.is_featured ? 'featured' : ''}">${item.is_featured ? 'Nổi bật' : 'Không nổi bật'}</span>
                        </span>
                    </button>`;
                }).join('')
                : '<div class="searchable-select-empty">Không tìm thấy sản phẩm phù hợp.</div>';
            results.hidden = false;
            results.querySelectorAll('[data-id]').forEach((button, index) => {
                button.onclick = () => select(items[index][picker.dataset.value], items[index][picker.dataset.text]);
            });
        };
        const search = async keyword => {
            const current = ++sequence;
            results.innerHTML = '<div class="searchable-select-empty">Đang tìm sản phẩm...</div>';
            results.hidden = false;
            try {
                const query = new URLSearchParams({search: keyword, per_page: '20'});
                const response = await request(`/${picker.dataset.source}?${query}`);
                if (current === sequence) render(response.data || []);
            } catch (error) {
                if (current === sequence) close();
                throw error;
            }
        };

        input.addEventListener('input', () => {
            hidden.value = '';
            input.setCustomValidity('Vui lòng chọn sản phẩm trong danh sách.');
            clearTimeout(timer);
            const keyword = input.value.trim();
            if (!keyword) return close();
            timer = setTimeout(() => search(keyword).catch(error => toast(error.message, true)), 250);
        });
        input.addEventListener('focus', () => {
            if (input.value.trim() && !hidden.value) search(input.value.trim()).catch(error => toast(error.message, true));
        });
        document.addEventListener('click', event => { if (!picker.contains(event.target)) close(); });
        picker._setValue = (id, label) => select(id, label, false);
    }

    async function loadSources(form) {
        await Promise.all([...form.querySelectorAll('[data-type="select_api"]')].map(async select => {
            const result = await request(`/${select.dataset.source}?per_page=50`);
            (result.data || []).forEach(item => select.add(new Option(item[select.dataset.text], item[select.dataset.value])));
        }));
        await Promise.all([...form.querySelectorAll('[data-type="multi_select_api"]')].map(async picker => {
            const result = await request(`/${picker.dataset.source}?per_page=50`);
            setupCategoryPicker(picker, result.data || []);
        }));
        form.querySelectorAll('[data-type="searchable_select_api"]').forEach(setupSearchableSelect);
        await Promise.all([...form.querySelectorAll('.relation-picker')].map(async picker => {
            if (picker.dataset.type === 'variant_options') {
                picker.innerHTML = '<div class="relation-loading">Chọn sản phẩm để tải các giá trị biến thể.</div>';
                return;
            }
            const result = await request(`/${picker.dataset.source}?per_page=50`);
            const groups = result.data || [];
            if (picker.dataset.type === 'product_variant_groups') {
                renderGroupPicker(picker, groups);
            }
        }));
        const productSelect = form.querySelector('[name="product_id"]');
        if (productSelect) productSelect.addEventListener('change', () => loadProductContext(form, productSelect.value));
    }

    async function loadProductContext(form, productId) {
        const groupSelect = form.querySelector('[data-type="product_group_select"]');
        const optionPicker = form.querySelector('[data-type="variant_options"]');
        if (!productId) {
            if (groupSelect) groupSelect.innerHTML = '<option value="">-- Chọn sản phẩm trước --</option>';
            if (optionPicker) optionPicker.innerHTML = '<div class="relation-loading">Chọn sản phẩm để tải các giá trị biến thể.</div>';
            return;
        }
        const result = await request(`/products/${productId}`);
        form._variantProduct = result.data || null;
        const configurations = result.data?.variant_groups || [];
        if (groupSelect) {
            const oldValue = groupSelect.value;
            groupSelect.innerHTML = '<option value="">-- Chọn nhóm biến thể --</option>' + configurations.map(configuration => `<option value="${configuration.id}">${esc(configuration.group_name)}</option>`).join('');
            groupSelect.value = oldValue;
        }
        if (optionPicker) {
            const groupsHtml = configurations.length ? configurations.map(configuration => {
                const options = (configuration.options || []).filter(option => option.is_active !== false);
                const requiredBadge = configuration.is_required ? '<span class="required-badge">Bắt buộc</span>' : '<span class="optional-badge">Không bắt buộc</span>';
                const choices = options.map(option => `<label class="option-choice"><input type="radio" name="_variant_group_${configuration.id}" value="${option.id}" data-option-id data-option-code="${esc(option.option_code)}" ${configuration.is_required ? 'required' : ''}> <span>${esc(option.option_name)} <small>(${esc(option.option_code)})</small></span></label>`).join('');
                const emptyChoice = configuration.is_required ? '' : `<label class="option-choice"><input type="radio" name="_variant_group_${configuration.id}" value="" checked> <span>Không chọn</span></label>`;
                return `<fieldset class="option-group" data-required="${configuration.is_required ? '1' : '0'}"><legend><span>${esc(configuration.group_name)}</span>${requiredBadge}</legend>${options.length ? emptyChoice + choices : '<div class="option-group-empty">Nhóm này chưa có giá trị hoạt động.</div>'}</fieldset>`;
            }).join('') : '<div class="relation-loading">Sản phẩm chưa cấu hình nhóm biến thể.</div>';
            optionPicker.innerHTML = configurations.length
                ? `<div class="variant-option-toolbar"><span>Chọn giá trị cho từng nhóm biến thể</span><button type="button" class="btn compact" data-reset-variant-options>↻ Đặt lại giá trị</button></div>${groupsHtml}${form.dataset.recordId ? '' : '<label class="generate-combinations"><input type="checkbox" name="generate_all_combinations" value="1" data-generate-combinations> <span><strong>Tạo tất cả tổ hợp biến thể</strong><small>Tự động tạo một biến thể cho mỗi tổ hợp từ các giá trị đang hoạt động.</small></span></label>'}`
                : groupsHtml;
            optionPicker.onchange = event => {
                if (event.target.matches('input[type="radio"]')) suggestVariantSku(form);
            };
            optionPicker.querySelector('[data-reset-variant-options]')?.addEventListener('click', () => {
                optionPicker.querySelectorAll('.option-group').forEach(group => {
                    group.querySelectorAll('input[type="radio"]').forEach(input => { input.checked = false; });
                    if (group.dataset.required !== '1') {
                        const emptyChoice = group.querySelector('input[type="radio"]:not([data-option-id])');
                        if (emptyChoice) emptyChoice.checked = true;
                    }
                });
                const skuInput = form.querySelector('[name="sku"]');
                if (skuInput?.dataset.autoSuggested === '1') {
                    skuInput.value = '';
                    delete skuInput.dataset.autoSuggested;
                }
            });
            optionPicker.querySelector('[data-generate-combinations]')?.addEventListener('change', event => {
                const enabled = event.target.checked;
                optionPicker.querySelectorAll('.option-group input[type="radio"]').forEach(input => { input.disabled = enabled; });
                optionPicker.querySelectorAll('.option-group').forEach(group => group.classList.toggle('bulk-disabled', enabled));
                const skuInput = form.querySelector('[name="sku"]');
                if (skuInput) {
                    skuInput.required = !enabled;
                    if (enabled && skuInput.dataset.autoSuggested === '1') {
                        skuInput.value = '';
                        delete skuInput.dataset.autoSuggested;
                    }
                    skuInput.placeholder = enabled ? 'SKU sẽ được tự động tạo cho từng tổ hợp' : 'Nhập mã SKU';
                }
                const imageField = form.querySelector('[data-field-name="images"]');
                if (imageField) {
                    imageField.hidden = enabled;
                    if (enabled) imageField.querySelector('[data-multi-upload]')?._clearNewFiles?.();
                }
            });
            suggestVariantSku(form);
        }
    }

    function skuPart(value) {
        return String(value || '')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/đ/gi, 'd')
            .toUpperCase()
            .replace(/[^A-Z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');
    }

    function suggestVariantSku(form) {
        const skuInput = form.querySelector('[name="sku"]');
        const optionPicker = form.querySelector('[data-type="variant_options"]');
        const product = form._variantProduct;
        if (!skuInput || !optionPicker || !product) return;
        if (skuInput.value.trim() && skuInput.dataset.autoSuggested !== '1') return;

        const groups = [...optionPicker.querySelectorAll('.option-group')];
        if (!groups.length || groups.some(group => !group.querySelector('input[type="radio"]:checked'))) return;

        const optionCodes = groups
            .map(group => group.querySelector('[data-option-id]:checked')?.dataset.optionCode)
            .filter(Boolean)
            .map(skuPart);
        if (!optionCodes.length) return;

        const productCode = skuPart(product.sku || product.product_name || `SP-${product.id}`);
        skuInput.value = [productCode, ...optionCodes].filter(Boolean).join('-').slice(0, 100);
        skuInput.dataset.autoSuggested = '1';
    }

    function setupGroupPicker(picker) {
        const rows = [...picker.querySelectorAll('.relation-row')];
        const count = picker.querySelector('[data-selected-count]');
        const noResult = picker.querySelector('[data-no-result]');
        const searchInput = picker.querySelector('[data-group-search]');
        const searchResults = picker.querySelector('[data-group-search-results]');
        const searchToolbar = picker.querySelector('.relation-toolbar');
        const availableList = picker.querySelector('[data-group-list]');
        const selectedList = picker.querySelector('[data-selected-group-list]');
        const selectedEmpty = picker.querySelector('[data-selected-empty]');
        rows.forEach(row => {
            const orderInput = row.querySelector('.relation-order');
            orderInput.type = 'hidden';
        });
        const update = () => {
            const selected = rows.filter(row => row.querySelector('[data-group-check]').checked);
            if (count) count.textContent = `Đã chọn ${selected.length} nhóm`;
            rows.forEach(row => {
                const checked = row.querySelector('[data-group-check]').checked;
                if (!checked) availableList?.appendChild(row);
                row.classList.toggle('selected', checked);
                row.querySelector('[data-required]').disabled = !checked;
                row.querySelector('.relation-order').disabled = !checked;
                row.querySelector('[data-product-options]').hidden = !checked;
                if (!checked) row.querySelector('[data-required]').checked = false;
            });
            selected
                .sort((first, second) => Number(first.querySelector('.relation-order').value || 0) - Number(second.querySelector('.relation-order').value || 0))
                .forEach(row => selectedList?.appendChild(row));
            normalizeSelectedGroupOrder(selectedList);
            if (selectedEmpty) selectedEmpty.hidden = selected.length > 0;
            filterGroupSearch();
        };
        rows.forEach(row => row.querySelector('[data-group-check]').addEventListener('change', event => {
            const row = event.target.closest('.relation-row');
            const order = row.querySelector('.relation-order');
            if (event.target.checked && order.value === '') {
                const selectedOrders = rows
                    .filter(item => item !== row && item.querySelector('[data-group-check]').checked)
                    .map(item => Number(item.querySelector('.relation-order').value))
                    .filter(Number.isFinite);
                order.value = selectedOrders.length ? Math.max(...selectedOrders) + 1 : 0;
            }
            if (!event.target.checked) order.value = '';
            update();
        }));
        rows.forEach(row => {
            row.querySelector('[data-select-group]')?.addEventListener('click', () => {
                const checkbox = row.querySelector('[data-group-check]');
                checkbox.checked = true;
                checkbox.dispatchEvent(new Event('change', {bubbles: true}));
            });
            row.querySelector('[data-remove-group]')?.addEventListener('click', async event => {
                const removeButton = event.currentTarget;
                const checkbox = row.querySelector('[data-group-check]');
                const group = picker._groups.find(item => Number(item.id) === Number(checkbox.value));
                if (!await confirmRemoveProductGroup(group)) return;
                const configurationId = row.dataset.configurationId;
                const productId = document.querySelector('.module-form')?.dataset.recordId;
                if (configurationId && productId) {
                    removeButton.disabled = true;
                    try {
                        const result = await request(`/products/${productId}/variant-groups/${configurationId}`, {method: 'DELETE'});
                        row.dataset.configurationId = '';
                        toast(result.message || 'Đã xóa nhóm biến thể khỏi sản phẩm');
                    } catch (error) {
                        removeButton.disabled = false;
                        toast(error.message, true);
                        return;
                    }
                }
                checkbox.checked = false;
                checkbox.dispatchEvent(new Event('change', {bubbles: true}));
            });
            row.querySelector('[data-required]')?.addEventListener('change', async event => {
                const input = event.currentTarget;
                const configurationId = row.dataset.configurationId;
                const productId = document.querySelector('.module-form')?.dataset.recordId;
                if (!configurationId || !productId) return;
                const nextValue = input.checked;
                input.disabled = true;
                try {
                    const result = await request(`/products/${productId}/variant-groups/${configurationId}`, {
                        method: 'PATCH',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({is_required: nextValue}),
                    });
                    toast(result.message || 'Đã cập nhật trạng thái bắt buộc');
                } catch (error) {
                    input.checked = !nextValue;
                    toast(error.message, true);
                } finally {
                    input.disabled = false;
                }
            });
            const dragHandle = row.querySelector('.group-drag');
            dragHandle?.addEventListener('dragstart', event => {
                if (!row.querySelector('[data-group-check]').checked) {
                    event.preventDefault();
                    return;
                }
                event.dataTransfer.effectAllowed = 'move';
                event.dataTransfer.setData('text/plain', 'variant-group');
                const preview = row.cloneNode(true);
                const rect = row.getBoundingClientRect();
                preview.classList.add('group-drag-preview');
                preview.style.width = `${rect.width}px`;
                document.body.appendChild(preview);
                event.dataTransfer.setDragImage(preview, 18, 30);
                row._dragPreview = preview;
                requestAnimationFrame(() => row.classList.add('group-dragging'));
            });
            dragHandle?.addEventListener('dragend', () => {
                row.classList.remove('group-dragging');
                row._dragPreview?.remove();
                row._dragPreview = null;
                normalizeSelectedGroupOrder(selectedList);
            });
        });
        selectedList?.addEventListener('dragover', event => {
            event.preventDefault();
            const dragging = selectedList.querySelector('.relation-row.group-dragging');
            if (!dragging) return;
            const target = [...selectedList.querySelectorAll('.relation-row:not(.group-dragging)')]
                .find(item => event.clientY < item.getBoundingClientRect().top + item.offsetHeight / 2);
            target ? selectedList.insertBefore(dragging, target) : selectedList.appendChild(dragging);
        });
        const filterGroupSearch = () => {
            const keyword = searchInput?.value.trim().toLowerCase() || '';
            let visible = 0;
            rows.forEach(row => {
                if (row.querySelector('[data-group-check]').checked) {
                    row.hidden = false;
                    return;
                }
                const showRow = keyword !== '' && row.dataset.searchText.includes(keyword);
                row.hidden = !showRow;
                if (showRow) visible++;
            });
            if (searchResults) searchResults.hidden = keyword === '';
            if (searchResults && searchToolbar) searchResults.style.top = `${searchToolbar.offsetHeight + 6}px`;
            if (noResult) noResult.hidden = keyword === '' || visible !== 0;
        };
        searchInput?.addEventListener('input', filterGroupSearch);
        document.addEventListener('click', event => {
            if (!picker.contains(event.target) && searchResults) searchResults.hidden = true;
        });
        searchInput?.addEventListener('focus', filterGroupSearch);
        picker._updateSelection = update;
        update();
    }

    function normalizeSelectedGroupOrder(container) {
        if (!container) return;
        [...container.querySelectorAll('.relation-row')].forEach((row, index) => {
            row.querySelector('.relation-order').value = index + 1;
        });
    }

    function setupKeyValueEditors(form) {
        form.querySelectorAll('[data-type="key_value"]').forEach(editor => {
            const rows = editor.querySelector('[data-key-value-rows]');
            const empty = editor.querySelector('[data-key-value-empty]');
            const refreshEmpty = () => { empty.hidden = rows.children.length > 0; };
            const addRow = (key = '', value = '') => {
                const row = document.createElement('div');
                row.className = 'key-value-row';
                row.innerHTML = `<input class="input" data-key-value-key placeholder="${esc(editor.dataset.keyPlaceholder)}" value="${esc(key)}"><input class="input" type="url" data-key-value-value placeholder="${esc(editor.dataset.valuePlaceholder)}" value="${esc(value)}"><button type="button" class="btn icon-button danger-icon" data-remove-key-value title="Xóa" aria-label="Xóa"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v5M14 11v5"/></svg></button>`;
                row.querySelector('[data-remove-key-value]').onclick = () => { row.remove(); refreshEmpty(); };
                rows.appendChild(row);
                refreshEmpty();
                return row;
            };
            editor.querySelector('[data-add-key-value]').onclick = () => addRow().querySelector('[data-key-value-key]').focus();
            editor._setValue = values => {
                rows.innerHTML = '';
                Object.entries(values || {}).forEach(([key, value]) => addRow(key, value));
                refreshEmpty();
            };
            editor._appendTo = formData => {
                const name = editor.dataset.name;
                const seen = new Set();
                [...rows.children].forEach(row => {
                    const key = row.querySelector('[data-key-value-key]').value.trim();
                    const value = row.querySelector('[data-key-value-value]').value.trim();
                    if (!key && !value) return;
                    if (!key || !value) throw new Error('Vui lòng nhập đủ tên nền tảng và URL.');
                    if (seen.has(key)) throw new Error(`Tên nền tảng "${key}" đang bị trùng.`);
                    seen.add(key);
                    formData.append(`${name}[${key}]`, value);
                });
            };
            refreshEmpty();
        });
    }

    function setupRepeatableEditors(form) {
        form.querySelectorAll('[data-type="repeatable_values"]').forEach(editor => {
            const rows = editor.querySelector('[data-repeatable-rows]');
            const empty = editor.querySelector('[data-repeatable-empty]');
            const refreshEmpty = () => { empty.hidden = rows.children.length > 0; };
            const addRow = (value = '') => {
                const row = document.createElement('div');
                row.className = 'repeatable-row';
                row.innerHTML = `<input class="input" data-repeatable-value maxlength="255" placeholder="${esc(editor.dataset.placeholder)}" value="${esc(value)}"><button type="button" class="btn icon-button danger-icon" data-remove-repeatable title="Xóa" aria-label="Xóa"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v5M14 11v5"/></svg></button>`;
                row.querySelector('[data-remove-repeatable]').onclick = () => { row.remove(); refreshEmpty(); };
                rows.appendChild(row);
                refreshEmpty();
                return row;
            };
            editor.querySelector('[data-add-repeatable]').onclick = () => addRow().querySelector('[data-repeatable-value]').focus();
            editor._setValue = values => {
                rows.innerHTML = '';
                (Array.isArray(values) ? values : []).forEach(value => addRow(value));
                refreshEmpty();
            };
            editor._appendTo = formData => {
                const name = editor.dataset.name;
                [...rows.querySelectorAll('[data-repeatable-value]')].forEach(input => {
                    const value = input.value.trim();
                    if (value) formData.append(`${name}[]`, value);
                });
            };
            refreshEmpty();
        });
    }

    async function fill(form, row) {
        form.querySelectorAll('[name]').forEach(input => {
            const name = input.name.replace(/\[\]$/, '');
            const value = row[name];
            if (input.type === 'file' || value == null) return;
            if (input.type === 'checkbox') input.checked = !!value;
            else if (input.dataset.type === 'json') input.value = JSON.stringify(value, null, 2);
            else if (input.dataset.type === 'lines' && Array.isArray(value)) input.value = value.join('\n');
            else input.value = value;
        });
        if (row.product_id) {
            form.querySelector('[data-type="searchable_select_api"]')?._setValue?.(row.product_id, row.product_name || `#${row.product_id}`);
            await loadProductContext(form, row.product_id);
            const groupSelect = form.querySelector('[data-type="product_group_select"]');
            if (groupSelect && row.product_variant_group_id) groupSelect.value = row.product_variant_group_id;
        }
        const categoryPicker = form.querySelector('[data-type="multi_select_api"]');
        if (categoryPicker) categoryPicker._setSelected?.(row.categories || []);
        const groupPicker = form.querySelector('[data-type="product_variant_groups"]');
        if (groupPicker) (row.variant_groups || []).forEach(selected => {
            const checkbox = groupPicker.querySelector(`[data-group-check][value="${selected.variant_group_id}"]`);
            if (!checkbox) return;
            const line = checkbox.closest('.relation-row');
            line.dataset.configurationId = selected.id || '';
            checkbox.checked = true;
            line.querySelector('[data-required]').checked = !!selected.is_required;
            line.querySelector('.relation-order').value = selected.sort_order ?? 0;
            setProductOptions(line, selected.options || []);
        });
        if (groupPicker?._updateSelection) groupPicker._updateSelection();
        form.querySelectorAll('[data-multi-upload]').forEach(upload => {
            const fieldName = upload.dataset.fieldName;
            let existing = row[fieldName] || [];
            if (!Array.isArray(existing) && existing) {
                existing = typeof existing === 'string' ? [{path: existing}] : [existing];
            }
            if (!existing.length && upload.dataset.singleUpload && row[`${fieldName}_path`]) {
                existing = [{path: row[`${fieldName}_path`]}];
            }
            upload._showExisting?.(existing);
        });
        form.querySelectorAll('[data-type="key_value"]').forEach(editor => editor._setValue?.(row[editor.dataset.name] || {}));
        form.querySelectorAll('[data-type="repeatable_values"]').forEach(editor => editor._setValue?.(row[editor.dataset.name] || []));
        const optionPicker = form.querySelector('[data-type="variant_options"]');
        if (optionPicker) (row.options || []).forEach(option => {
            const checkbox = optionPicker.querySelector(`[data-option-id][value="${option.id}"]`);
            if (checkbox) checkbox.checked = true;
        });
    }

    function appendJson(formData, name, value) {
        if (Array.isArray(value)) {
            value.forEach((item, index) => appendJson(formData, `${name}[${index}]`, item));
            return;
        }
        if (value && typeof value === 'object') {
            Object.entries(value).forEach(([key, item]) => appendJson(formData, `${name}[${key}]`, item));
            return;
        }
        formData.append(name, value ?? '');
    }

    function serializeRelations(form, formData) {
        const groupPicker = form.querySelector('[data-type="product_variant_groups"]');
        if (groupPicker) {
            formData.delete('variant_groups');
            [...groupPicker.querySelectorAll('[data-group-check]:checked')].forEach((checkbox, index) => {
                const line = checkbox.closest('.relation-row');
                formData.append(`variant_groups[${index}][variant_group_id]`, checkbox.value);
                formData.append(`variant_groups[${index}][is_required]`, line.querySelector('[data-required]').checked ? '1' : '0');
                formData.append(`variant_groups[${index}][sort_order]`, line.querySelector('.relation-order').value === '' ? index : line.querySelector('.relation-order').value);
                formData.append(`variant_groups[${index}][options_present]`, '1');
                normalizeProductOptionOrder(line.querySelector('[data-option-rows]'));
                line.querySelectorAll('.product-option-row').forEach((optionRow, optionIndex) => {
                    ['id', 'option_code', 'option_name', 'sort_order'].forEach(field => {
                        const value = optionRow.querySelector(`[data-option-field="${field}"]`).value;
                        if (field !== 'id' || value) formData.append(`variant_groups[${index}][options][${optionIndex}][${field}]`, value);
                    });
                    formData.append(`variant_groups[${index}][options][${optionIndex}][is_active]`, optionRow.querySelector('[data-option-field="is_active"]').checked ? '1' : '0');
                });
            });
        }
        const optionPicker = form.querySelector('[data-type="variant_options"]');
        if (optionPicker) {
            formData.delete('option_ids');
            if (optionPicker.querySelector('[data-generate-combinations]:checked')) return;
            const missingRequired = [...optionPicker.querySelectorAll('.option-group[data-required="1"]')]
                .find(group => !group.querySelector('[data-option-id]:checked'));
            if (missingRequired) {
                const groupName = missingRequired.querySelector('legend span')?.textContent || 'bắt buộc';
                throw new Error(`Vui lòng chọn một giá trị cho nhóm ${groupName}.`);
            }
            const selectedOptions = [...optionPicker.querySelectorAll('[data-option-id]:checked')];
            if (!selectedOptions.length) throw new Error('Vui lòng chọn ít nhất một giá trị biến thể.');
            selectedOptions.forEach(option => formData.append('option_ids[]', option.value));
        }
    }

    function pageContentCards() {
        const root = document.querySelector('[data-content-pages]');
        if (!root) return;
        const endpoint = root.dataset.endpoint;
        const editUrl = root.dataset.editUrl;
        const sectionsUrl = root.dataset.sectionsUrl;
        const grid = root.querySelector('[data-page-grid]');
        const count = root.querySelector('[data-page-count]');
        const searchInput = root.querySelector('[data-page-search]');
        let timer;
        const documentIcon = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 2h8l4 4v16H6z"/><path d="M14 2v5h5M9 12h6M9 16h6"/></svg>';
        const editIcon = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L8 18l-4 1 1-4Z"/></svg>';
        const deleteIcon = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v5M14 11v5"/></svg>';

        async function load(search = '') {
            grid.innerHTML = '<div class="content-pages-state loading-state">Đang tải dữ liệu...</div>';
            try {
                const params = new URLSearchParams({per_page: '50', search});
                const result = await request(`/${endpoint}?${params.toString()}`);
                const rows = result.data || [];
                count.textContent = `${result.meta?.total ?? rows.length} trang`;
                grid.innerHTML = rows.length ? rows.map(row => {
                    const slug = String(row.slug || '').replace(/^\/+/, '');
                    const sectionCount = Array.isArray(row.sections) ? row.sections.length : 0;
                    return `<article class="content-page-card" data-page-href="${sectionsUrl}/${encodeURIComponent(slug)}" tabindex="0" role="link" aria-label="Mở các section của ${esc(row.title)}">
                        <div class="content-page-card-top"><span class="content-page-icon">${documentIcon}</span><span class="content-page-tools">
                            <a class="content-page-tool" href="${editUrl}/${encodeURIComponent(slug)}/edit" title="Sửa trang" aria-label="Sửa trang">${editIcon}</a>
                            <button type="button" class="content-page-tool danger delete-page" data-id="${row.id}" title="Xóa trang" aria-label="Xóa trang">${deleteIcon}</button>
                        </span></div>
                        <h2>${esc(row.title)}</h2><p class="content-page-slug">/${esc(slug)}</p><span class="content-page-key">${esc(slug || 'trang')}</span>
                        <div class="content-page-card-bottom"><span>${sectionCount} section</span><b aria-hidden="true">→</b></div>
                    </article>`;
                }).join('') : '<div class="content-pages-state">Chưa có trang nội dung phù hợp.</div>';

                grid.querySelectorAll('[data-page-href]').forEach(card => {
                    const open = event => {
                        if (event.target.closest('a,button')) return;
                        if (event.type === 'keydown' && !['Enter', ' '].includes(event.key)) return;
                        if (event.type === 'keydown') event.preventDefault();
                        location.href = card.dataset.pageHref;
                    };
                    card.addEventListener('click', open);
                    card.addEventListener('keydown', open);
                });
                grid.querySelectorAll('.delete-page').forEach(button => button.onclick = async () => {
                    if (!confirm('Bạn chắc chắn muốn xóa trang và toàn bộ nội dung liên quan?')) return;
                    try {
                        const result = await request(`/${endpoint}/${button.dataset.id}`, {method: 'DELETE'});
                        toast(result.message || 'Đã xóa trang');
                        await load(searchInput.value.trim());
                    } catch (error) { toast(error.message, true); }
                });
            } catch (error) {
                count.textContent = '0 trang';
                grid.innerHTML = `<div class="content-pages-state">${esc(error.message)}</div>`;
            }
        }

        searchInput.addEventListener('input', () => {
            clearTimeout(timer);
            timer = setTimeout(() => load(searchInput.value.trim()), 300);
        });
        load();
    }

    function sectionManager() {
        const root = document.querySelector('[data-section-manager]');
        if (!root) return;
        const endpoint = root.dataset.endpoint;
        const pageId = root.dataset.pageId;
        const pageUrl = root.dataset.pageUrl;
        const list = root.querySelector('[data-section-list]');
        const count = root.querySelector('[data-section-count]');
        const searchInput = root.querySelector('[data-section-search]');
        const storage = (document.body.dataset.storage || '/storage').replace(/\/$/, '');
        let timer;
        let currentSections = [];

        const editIcon = '<svg viewBox="0 0 24 24"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L8 18l-4 1 1-4Z"/></svg>';
        const deleteIcon = '<svg viewBox="0 0 24 24"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v5M14 11v5"/></svg>';
        const textOnly = value => {
            const element = document.createElement('div');
            element.innerHTML = String(value || '');
            return element.textContent || '';
        };
        const fileUrl = file => file.external_url || (file.path ? `${storage}/${String(file.path).replace(/^\//, '')}` : '');
        async function openSectionModal(section, settings = {}) {
            const itemMode = !!settings.item;
            const creating = !!settings.create;
            const resourceEndpoint = itemMode ? 'section-items' : endpoint;
            const overlay = modalShell(`<form class="cms-section-form">
                <div class="cms-modal-title"><h3>${creating ? 'Thêm' : 'Chỉnh sửa'} ${itemMode ? 'item' : 'section'}</h3><button type="button" class="modal-close" data-cancel aria-label="Đóng">×</button></div>
                <div class="cms-modal-body section-modal-body">
                    <div class="section-modal-grid">
                        <div class="field"><label>Tiêu đề</label><input class="input" name="title" value="${esc(section.title || '')}" placeholder="Nhập tiêu đề"></div>
                        <div class="field"><label>Tiêu đề phụ</label><input class="input" name="subtitle" value="${esc(section.subtitle || '')}" placeholder="Nhập tiêu đề phụ"></div>
                        <div class="field full"><label>Nội dung</label><textarea class="input" data-type="richtext" name="content" placeholder="Nhập nội dung section">${esc(section.content || '')}</textarea></div>
                        <div class="field"><label>Thứ tự</label><input class="input" type="number" name="sort_order" value="${Number(section.sort_order || 0)}" min="0"></div>
                    </div>
                    <div class="section-modal-media">
                        <div class="section-modal-media-head"><div><strong>Media</strong><small>Ảnh JPG, PNG, GIF, WEBP; video sử dụng đường dẫn URL.</small></div><button type="button" class="btn primary" data-add-media>＋ Thêm media</button></div>
                        <div class="section-modal-files sortable-media" data-modal-files></div>
                    </div>
                    <div class="form-error" data-modal-error></div>
                </div>
                <div class="cms-modal-actions"><button type="button" class="btn" data-cancel>Đóng</button><button type="submit" class="btn primary">Lưu</button></div>
            </form>`);
            overlay.querySelector('.cms-modal').classList.add('section-edit-modal');
            const form = overlay.querySelector('form');
            const contentEditor = await createRichEditor(form.elements.content);
            const filesBox = form.querySelector('[data-modal-files]');
            const originalIds = new Set((section.files || []).map(file => String(file.id)));
            let mediaRows = (section.files || []).map(file => ({
                key: `stored-${file.id}`, id: file.id, originalKind: file.external_url ? 'video' : 'image',
                kind: file.external_url ? 'video' : 'image', value: file.external_url || file.file_name || file.title || '',
                preview: fileUrl(file), file: null, stored: file,
            }));
            let draggedKey = null;

            const chooseImage = row => new Promise(resolve => {
                const input = document.createElement('input'); input.type = 'file'; input.accept = 'image/jpeg,image/png,image/gif,image/webp';
                input.onchange = () => {
                    if (!input.files[0]) return resolve(false);
                    if (input.files[0].size > 5 * 1024 * 1024) { toast('Hình ảnh không được vượt quá 5MB.', true); return resolve(false); }
                    row.file = input.files[0]; row.value = row.file.name; row.preview = URL.createObjectURL(row.file); resolve(true);
                };
                input.click();
            });
            const renderRows = () => {
                filesBox.innerHTML = mediaRows.length ? mediaRows.map(row => `<div class="section-modal-file media-sort-row" draggable="true" data-media-key="${esc(row.key)}">
                    <button type="button" class="media-drag" title="Kéo để thay đổi thứ tự">⠿</button>
                    <span class="media-row-preview">${row.kind === 'image' && row.preview ? `<img src="${esc(row.preview)}" alt="">` : '<b>VIDEO</b>'}</span>
                    <select class="input media-kind"><option value="image" ${row.kind === 'image' ? 'selected' : ''}>Hình ảnh</option><option value="video" ${row.kind === 'video' ? 'selected' : ''}>Video</option></select>
                    ${row.kind === 'image' ? `<button type="button" class="input media-value choose-row-image" title="Chọn hình ảnh">${esc(row.value || 'Chọn hình ảnh')}</button>` : `<input class="input media-value media-video-url" type="url" value="${esc(row.value || '')}" placeholder="Nhập URL video">`}
                    <button type="button" class="section-modal-file-delete" title="Xóa media">${deleteIcon}</button>
                </div>`).join('') : '<div class="section-modal-media-empty">Chưa có media.</div>';
                filesBox.querySelectorAll('[data-media-key]').forEach(element => {
                    const row = mediaRows.find(item => item.key === element.dataset.mediaKey);
                    element.querySelector('.media-kind').onchange = async event => {
                        const previous = row.kind; row.kind = event.target.value;
                        if (row.kind === 'image' && !row.file && row.originalKind !== 'image') {
                            if (!await chooseImage(row)) row.kind = previous;
                        } else if (row.kind === 'video' && row.originalKind !== 'video') row.value = '';
                        renderRows();
                    };
                    element.querySelector('.choose-row-image')?.addEventListener('click', async () => { await chooseImage(row); renderRows(); });
                    element.querySelector('.media-video-url')?.addEventListener('input', event => { row.value = event.target.value; });
                    element.querySelector('.section-modal-file-delete').onclick = async () => {
                        if (row.id && !await confirmRemoveStoredFile(row.value || 'media này')) return;
                        try {
                            if (row.id) await request(`/files/${row.id}`, {method: 'DELETE'});
                            mediaRows = mediaRows.filter(item => item.key !== row.key); renderRows();
                            if (row.id) toast('Đã xóa media');
                        } catch (error) { toast(error.message, true); }
                    };
                    element.ondragstart = event => { draggedKey = row.key; element.classList.add('dragging'); event.dataTransfer.effectAllowed = 'move'; };
                    element.ondragend = () => {
                        const byKey = new Map(mediaRows.map(item => [item.key, item]));
                        mediaRows = [...filesBox.querySelectorAll('[data-media-key]')].map(item => byKey.get(item.dataset.mediaKey)).filter(Boolean);
                        draggedKey = null; element.classList.remove('dragging');
                    };
                    element.ondragover = event => {
                        event.preventDefault();
                        const draggedElement = [...filesBox.querySelectorAll('[data-media-key]')].find(item => item.dataset.mediaKey === draggedKey);
                        if (!draggedElement || draggedElement === element) return;
                        const after = event.clientY > element.getBoundingClientRect().top + element.offsetHeight / 2;
                        filesBox.insertBefore(draggedElement, after ? element.nextSibling : element);
                    };
                });
            };
            renderRows();
            form.querySelector('[data-add-media]').onclick = () => {
                mediaRows.push({key: `new-${Date.now()}-${Math.random()}`, kind: 'image', originalKind: null, value: '', preview: '', file: null});
                renderRows();
            };

            const close = async () => {
                if (contentEditor) await contentEditor.destroy();
                overlay.remove();
            };
            overlay.querySelectorAll('[data-cancel]').forEach(button => button.onclick = close);
            form.onsubmit = async event => {
                event.preventDefault();
                const errorBox = form.querySelector('[data-modal-error]'); errorBox.textContent = '';
                try {
                    const invalid = mediaRows.find(row => row.kind === 'video' && !row.value.trim() || row.kind === 'image' && !row.id && !row.file);
                    if (invalid) throw new Error('Vui lòng nhập đầy đủ URL video hoặc chọn hình ảnh.');
                    const data = new FormData();
                    ['title', 'subtitle', 'sort_order'].forEach(name => data.append(name, form.elements[name].value));
                    data.append('content', contentEditor ? contentEditor.getData() : form.elements.content.value);
                    const newImages = mediaRows.filter(row => !row.id && row.kind === 'image');
                    const newVideos = mediaRows.filter(row => !row.id && row.kind === 'video');
                    newImages.forEach(row => data.append('files[]', row.file));
                    newVideos.forEach(row => data.append('video_urls[]', row.value.trim()));
                    if (itemMode && creating) data.append('page_section_id', settings.sectionId);
                    if (!itemMode && creating) data.append('page_content_id', pageId);
                    if (!creating) data.append('_method', 'PUT');
                    const result = await request(`/${resourceEndpoint}${creating ? '' : '/' + section.id}`, {method: 'POST', body: data});

                    for (const row of mediaRows.filter(row => row.id)) {
                        if (row.kind === 'video') await request(`/files/${row.id}`, {method: 'PATCH', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({external_url: row.value.trim(), type: 'video'})});
                        else if (row.file) { const replacement = new FormData(); replacement.append('file', row.file); replacement.append('type', 'image'); await request(`/files/${row.id}/replace`, {method: 'POST', body: replacement}); }
                    }
                    const returned = result.data?.files || [];
                    const added = returned.filter(file => !originalIds.has(String(file.id)));
                    const addedImages = added.filter(file => !file.external_url);
                    const addedVideos = added.filter(file => file.external_url);
                    newImages.forEach((row, index) => { row.id = addedImages[index]?.id; });
                    newVideos.forEach((row, index) => { row.id = addedVideos[index]?.id; });
                    await Promise.all(mediaRows.filter(row => row.id).map((row, index) => request(`/files/${row.id}`, {method: 'PATCH', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({sort_order: index})})));
                    await close(); toast(result.message || `Đã ${creating ? 'thêm' : 'cập nhật'} ${itemMode ? 'item' : 'section'}`); await load(searchInput.value.trim());
                } catch (error) { errorBox.textContent = error.message; }
            };
        }
        const renderMedia = files => (files || []).map(file => {
            const url = fileUrl(file);
            if (!url) return '';
            const image = String(file.mime_type || '').startsWith('image/') || /\.(png|jpe?g|gif|webp|svg)$/i.test(file.path || file.file_name || '');
            return `<a class="section-media-card" href="${esc(url)}" target="_blank" rel="noopener">
                ${image ? `<img src="${esc(url)}" alt="${esc(file.title || file.file_name || 'Media')}" loading="lazy">` : '<span class="section-media-file">FILE</span>'}
                <span>↗ ${esc(file.type || (image ? 'image' : 'file'))}: ${esc(file.title || file.file_name || url)}</span>
            </a>`;
        }).join('');
        const renderItem = item => `<article class="section-item-card" data-item-id="${item.id}">
            <div class="section-item-top"><div><span class="visibility-badge">● Hiển thị</span><small>Thứ tự: ${Number(item.sort_order || 0)}</small></div><div class="section-row-tools">
                <button class="section-icon-button edit-item" type="button" data-id="${item.id}" data-section-id="${item.page_section_id}" title="Sửa item">${editIcon}</button>
                <button class="section-icon-button danger delete-item" type="button" data-id="${item.id}" title="Xóa item">${deleteIcon}</button>
            </div></div>
            <h3>${esc(item.title || 'Item chưa có tiêu đề')}</h3>
            ${item.subtitle ? `<p class="section-item-subtitle">${esc(item.subtitle)}</p>` : ''}
            ${item.content ? `<div class="section-item-content">${esc(textOnly(item.content))}</div>` : ''}
            ${(item.files || []).length ? `<div class="section-media-grid item-media">${renderMedia(item.files)}</div>` : ''}
        </article>`;
        const renderSection = (section, index) => {
            const items = section.items || [];
            return `<article class="section-card" id="section-${section.id}" data-section-id="${section.id}">
                <header class="section-card-header">
                    <span class="section-number">${index + 1}</span>
                    <div class="section-card-heading"><div><h2>${esc(section.title || 'Section chưa có tiêu đề')}</h2><span class="visibility-badge">● Hiển thị</span></div><small>Thứ tự: ${Number(section.sort_order || 0)}</small>${section.subtitle ? `<p>${esc(section.subtitle)}</p>` : ''}</div>
                    <div class="section-card-actions"><span class="section-item-count">${items.length} item</span><button class="section-icon-button edit-section" type="button" data-id="${section.id}" title="Sửa section">${editIcon}</button><button class="section-icon-button danger delete-section" type="button" data-id="${section.id}" title="Xóa section">${deleteIcon}</button><button class="section-icon-button section-toggle" type="button" title="Thu gọn section"><span>⌃</span></button></div>
                </header>
                <div class="section-card-body"><div class="section-add-item"><button class="btn add-item" type="button" data-section-id="${section.id}">＋ Thêm item</button></div>
                    ${(section.files || []).length ? `<div class="section-media-grid">${renderMedia(section.files)}</div>` : ''}
                    <div class="section-items">${items.length ? items.map(renderItem).join('') : '<div class="section-items-empty">Section này chưa có item.</div>'}</div>
                </div>
            </article>`;
        };

        async function load(search = '') {
            list.innerHTML = '<div class="section-manager-state loading-state">Đang tải dữ liệu...</div>';
            try {
                const params = new URLSearchParams({per_page: '50', page_content_id: pageId, search});
                const result = await request(`/${endpoint}?${params.toString()}`);
                const rows = result.data || [];
                currentSections = rows;
                count.textContent = `${result.meta?.total ?? rows.length} section`;
                list.innerHTML = rows.length ? rows.map(renderSection).join('') : '<div class="section-manager-state card">Chưa có section phù hợp.</div>';
                bindActions(rows);
                if (location.hash) document.querySelector(location.hash)?.scrollIntoView({behavior: 'smooth', block: 'start'});
            } catch (error) { list.innerHTML = `<div class="section-manager-state card">${esc(error.message)}</div>`; }
        }

        function bindActions(rows) {
            const sections = new Map(rows.map(section => [String(section.id), section]));
            const items = new Map(rows.flatMap(section => (section.items || []).map(item => [String(item.id), item])));
            list.querySelectorAll('.edit-section').forEach(button => button.onclick = () => openSectionModal(sections.get(button.dataset.id)));
            list.querySelectorAll('.add-item').forEach(button => button.onclick = () => {
                const section = sections.get(button.dataset.sectionId);
                openSectionModal({title: '', subtitle: '', content: '', sort_order: (section?.items?.length || 0) + 1, files: []}, {item: true, create: true, sectionId: button.dataset.sectionId});
            });
            list.querySelectorAll('.edit-item').forEach(button => button.onclick = () => openSectionModal(items.get(button.dataset.id), {item: true, sectionId: button.dataset.sectionId}));
            list.querySelectorAll('.section-toggle').forEach(button => button.onclick = () => {
                const card = button.closest('.section-card');
                card.classList.toggle('collapsed');
                button.querySelector('span').textContent = card.classList.contains('collapsed') ? '⌄' : '⌃';
            });
            list.querySelectorAll('.delete-section').forEach(button => button.onclick = async () => {
                if (!confirm('Xóa section này sẽ xóa toàn bộ item bên trong. Bạn có chắc chắn?')) return;
                try { const result = await request(`/${endpoint}/${button.dataset.id}`, {method: 'DELETE'}); toast(result.message || 'Đã xóa section'); load(searchInput.value.trim()); }
                catch (error) { toast(error.message, true); }
            });
            list.querySelectorAll('.delete-item').forEach(button => button.onclick = async () => {
                if (!confirm('Bạn chắc chắn muốn xóa item này?')) return;
                try { const result = await request(`/section-items/${button.dataset.id}`, {method: 'DELETE'}); toast(result.message || 'Đã xóa item'); load(searchInput.value.trim()); }
                catch (error) { toast(error.message, true); }
            });
        }

        searchInput.addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(() => load(searchInput.value.trim()), 300); });
        root.querySelector('[data-add-section]').onclick = () => openSectionModal({
            title: '', subtitle: '', content: '', sort_order: currentSections.length + 1, files: [],
        }, {create: true});
        load();
    }

    async function formPage() {
        const form = document.querySelector('.module-form');
        const endpoint = form.dataset.endpoint;
        const id = form.dataset.recordId;
        const indexUrl = form.dataset.indexUrl;
        const skuInput = form.querySelector('[name="sku"]');
        skuInput?.addEventListener('input', () => { skuInput.dataset.autoSuggested = '0'; });
        try {
            setupMultiUploads(form);
            setupKeyValueEditors(form);
            setupRepeatableEditors(form);
            await loadSources(form);
            if (id) {
                const result = await request(`/${endpoint}/${id}`);
                await fill(form, result.data || {});
                form.querySelectorAll('[data-lock-on-edit]').forEach(input => input.disabled = true);
            } else {
                const query = new URLSearchParams(location.search);
                form.querySelectorAll('[name]').forEach(input => {
                    if (query.has(input.name) && !input.value && !['file', 'checkbox', 'radio'].includes(input.type)) input.value = query.get(input.name);
                });
                const productId = query.get('product_id');
                const productPicker = form.querySelector('[data-type="searchable_select_api"]');
                if (productId && productPicker) {
                    const result = await request(`/products/${productId}`);
                    const product = result.data || {};
                    productPicker._setValue?.(product.id, product.product_name || `#${productId}`);
                    productPicker.querySelector('[data-searchable-input]').disabled = true;
                    await loadProductContext(form, productId);
                }
            }
            await setupRichEditors(form);
        } catch (error) { toast(error.message, true); }
        form.onsubmit = async event => {
            event.preventDefault();
            try {
                const formData = new FormData(form);
                form.querySelectorAll('textarea[data-type="richtext"]').forEach(input => {
                    formData.set(input.name, input._richEditor ? input._richEditor.getData() : input.value);
                });
                form.querySelectorAll('.field > .check input[type="checkbox"]').forEach(input => formData.set(input.name, input.checked ? '1' : '0'));
                form.querySelectorAll('[data-type="json"]').forEach(input => { formData.delete(input.name); if (input.value.trim()) appendJson(formData, input.name, JSON.parse(input.value)); });
                form.querySelectorAll('[data-type="lines"]').forEach(input => { formData.delete(input.name); input.value.split('\n').map(v => v.trim()).filter(Boolean).forEach(v => formData.append(`${input.name}[]`, v)); });
                form.querySelectorAll('[data-type="key_value"]').forEach(editor => editor._appendTo?.(formData));
                form.querySelectorAll('[data-type="repeatable_values"]').forEach(editor => editor._appendTo?.(formData));
                serializeRelations(form, formData);
                if (id) formData.append('_method', 'PUT');
                const result = await request(`/${endpoint}${id ? '/' + id : ''}`, {method: 'POST', body: formData});
                flashToast(result.message || (id ? 'Đã cập nhật dữ liệu' : 'Đã tạo dữ liệu'));
                location.href = indexUrl;
            } catch (error) { toast(error.message, true); }
        };
    }

    function dashboard() {
        document.querySelectorAll('[data-dashboard] [data-endpoint]').forEach(async card => {
            try { const result = await request(`/${card.dataset.endpoint}?per_page=1`); card.querySelector('strong').textContent = result.meta?.total ?? result.data?.length ?? 0; }
            catch { card.querySelector('strong').textContent = '—'; }
        });
    }

    document.addEventListener('DOMContentLoaded', common);
    return {request, indexPage, pageContentCards, sectionManager, formPage, dashboard};
})();
