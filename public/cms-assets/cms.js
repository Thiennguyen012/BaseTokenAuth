window.CMS = (() => {
    const tokenKey = 'nhua_cms_token';
    const toastKey = 'nhua_cms_toast';
    const api = () => document.body.dataset.api || '/admin/api';
    const esc = value => String(value ?? '').replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]));
    const loadingMarkup = () => '<span class="cms-loading-content"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span><span>Đang tải dữ liệu...</span></span>';
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

    function tableValue(key, value, row = null) {
        if (typeof value === 'boolean' || key === 'is_active' || key === 'is_featured' || key === 'featured' || key === 'is_required') {
            const isChecked = Boolean(value === true || value === 1 || value === '1' || value === 'true' || value === 'Active');
            if (row && row.id && (key === 'is_active' || key === 'is_featured' || key === 'featured' || key === 'is_required' || typeof value === 'boolean')) {
                return `<label class="switcher mb-0" title="Bật / Tắt">
                    <input type="checkbox" class="switcher_input toggle-row-boolean" data-field="${esc(key)}" data-id="${row.id}" ${isChecked ? 'checked' : ''}>
                    <span class="switcher_control"></span>
                </label>`;
            }
            return `<span class="badge ${isChecked ? 'on' : ''}">${isChecked ? 'Có' : 'Không'}</span>`;
        }
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
            item.innerHTML = `<div class="upload-thumb"><img src="${esc(url)}" alt=""></div>`;
            if (source === 'new') item._objectUrl = url;
        } else item.innerHTML = `<div class="upload-doc-icon type-${kind.toLowerCase()}">${esc(kind)}</div><div class="upload-file-info"><strong title="${esc(name)}">${esc(name)}</strong></div>`;
        if (source === 'new') {
            const downloadLink = document.createElement('a');
            downloadLink.className = 'upload-download'; downloadLink.href = item._objectUrl || '#'; downloadLink.download = name; downloadLink.target = '_blank'; downloadLink.title = 'Tải xuống file';
            downloadLink.innerHTML = '<i class="ri-download-2-line"></i>';
            item.appendChild(downloadLink);
            const button = document.createElement('button'); button.type = 'button'; button.className = 'upload-remove'; button.title = 'Xóa file'; button.innerHTML = '<i class="ri-close-line"></i>';
            button.onclick = () => { if (item._objectUrl) URL.revokeObjectURL(item._objectUrl); remove(); }; item.appendChild(button);
        } else {
            item.classList.add('existing');
            if (path) {
                const downloadLink = document.createElement('a');
                downloadLink.className = 'upload-download'; downloadLink.href = path; downloadLink.download = name; downloadLink.target = '_blank'; downloadLink.title = 'Tải xuống file';
                downloadLink.innerHTML = '<i class="ri-download-2-line"></i>';
                item.appendChild(downloadLink);
            }
            if (file.id) {
                const button = document.createElement('button');
                button.type = 'button'; button.className = 'upload-remove-existing'; button.title = 'Xóa file đã lưu'; button.setAttribute('aria-label', 'Xóa file đã lưu');
                button.innerHTML = '<i class="ri-close-line"></i>';
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
            const input = upload.querySelector('[data-upload-input]'), zone = upload.querySelector('[data-upload-dropzone]'), preview = upload.querySelector('[data-upload-preview]'), filenameLabel = upload.querySelector('[data-upload-filename]');
            let files = [];
            const sync = () => {
                const transfer = new DataTransfer(); files.forEach(file => transfer.items.add(file)); input.files = transfer.files;
                preview.querySelectorAll('[data-new-file]').forEach(item => item.remove());
                files.forEach((file, index) => { const item = renderUploadItem(file, 'new', () => { files.splice(index, 1); sync(); }); item.dataset.newFile = '1'; preview.appendChild(item); });
                if (filenameLabel) {
                    filenameLabel.textContent = files.length ? `${files.length} file đã chọn` : 'Chọn File';
                }
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

    function renderStandalonePagination(container, meta, onPage, fallbackPerPage = 20) {
        if (!container) return;
        const page = Number(meta?.current_page) || 1;
        const lastPage = Math.max(1, Number(meta?.last_page) || 1);
        const perPage = Number(meta?.per_page) || fallbackPerPage;
        const total = Number(meta?.total) || 0;
        const info = container.querySelector('[data-pagination-info]');
        const list = container.querySelector('[data-pagination-list]');
        const from = total ? ((page - 1) * perPage) + 1 : 0;
        const to = total ? Math.min(page * perPage, total) : 0;
        if (info) info.textContent = `Hiển thị ${from}–${to} trong ${total} bản ghi`;
        if (!list) return;
        const candidates = lastPage <= 7
            ? Array.from({length: lastPage}, (_, index) => index + 1)
            : [...new Set([1, lastPage, page - 1, page, page + 1])].filter(item => item >= 1 && item <= lastPage).sort((a, b) => a - b);
        const pages = [];
        candidates.forEach((item, index) => {
            if (index && item - candidates[index - 1] > 1) pages.push('…');
            pages.push(item);
        });
        container.hidden = false;
        list.innerHTML = `<li class="page-item ${page <= 1 ? 'disabled' : ''}"><button type="button" class="page-link" data-page="${page - 1}" aria-label="Trang trước">‹</button></li>
            ${pages.map(item => item === '…' ? '<li class="page-item disabled"><span class="page-link">…</span></li>' : `<li class="page-item ${item === page ? 'active' : ''}"><button type="button" class="page-link" data-page="${item}">${item}</button></li>`).join('')}
            <li class="page-item ${page >= lastPage ? 'disabled' : ''}"><button type="button" class="page-link" data-page="${page + 1}" aria-label="Trang sau">›</button></li>`;
        list.querySelectorAll('[data-page]').forEach(button => button.onclick = () => {
            const nextPage = Number(button.dataset.page);
            if (nextPage < 1 || nextPage > lastPage || nextPage === page) return;
            onPage(nextPage);
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
        const perPage = Number(root.dataset.perPage) || 20;
        const pagination = root.querySelector('[data-pagination]');
        const paginationInfo = root.querySelector('[data-pagination-info]');
        const paginationList = root.querySelector('[data-pagination-list]');
        let currentPage = 1;
        let timer;
        const paginationPages = (page, lastPage) => {
            if (lastPage <= 7) return Array.from({length: lastPage}, (_, index) => index + 1);
            const pages = new Set([1, lastPage, page - 1, page, page + 1]);
            const sorted = [...pages].filter(item => item >= 1 && item <= lastPage).sort((a, b) => a - b);
            const result = [];
            sorted.forEach((item, index) => {
                if (index && item - sorted[index - 1] > 1) result.push('…');
                result.push(item);
            });
            return result;
        };
        const renderPagination = meta => {
            const page = Number(meta?.current_page) || 1;
            const lastPage = Math.max(1, Number(meta?.last_page) || 1);
            const total = Number(meta?.total) || 0;
            const pageSize = Number(meta?.per_page) || perPage;
            currentPage = page;
            if (paginationInfo) {
                const from = total ? ((page - 1) * pageSize) + 1 : 0;
                const to = total ? Math.min(page * pageSize, total) : 0;
                paginationInfo.textContent = `Hiển thị ${from}–${to} trong ${total} bản ghi`;
            }
            if (!pagination || !paginationList) return;
            pagination.hidden = false;
            paginationList.innerHTML = `
                <li class="page-item ${page <= 1 ? 'disabled' : ''}"><button type="button" class="page-link" data-page="${page - 1}" aria-label="Trang trước">‹</button></li>
                ${paginationPages(page, lastPage).map(item => item === '…'
                    ? '<li class="page-item disabled"><span class="page-link">…</span></li>'
                    : `<li class="page-item ${item === page ? 'active' : ''}"><button type="button" class="page-link" data-page="${item}">${item}</button></li>`).join('')}
                <li class="page-item ${page >= lastPage ? 'disabled' : ''}"><button type="button" class="page-link" data-page="${page + 1}" aria-label="Trang sau">›</button></li>`;
            paginationList.querySelectorAll('[data-page]').forEach(button => button.onclick = () => {
                const nextPage = Number(button.dataset.page);
                if (nextPage < 1 || nextPage > lastPage || nextPage === currentPage) return;
                load(searchInput.value, nextPage);
            });
        };
        async function load(search = '', page = 1) {
            tbody.innerHTML = `<tr><td class="empty loading-state" colspan="${columns.length + 1}">${loadingMarkup()}</td></tr>`;
            try {
                const params = new URLSearchParams({per_page: String(perPage), page: String(page), search});
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
                const lastPage = Math.max(1, Number(result.meta?.last_page) || 1);
                if (page > lastPage) return load(search, lastPage);
                tbody.innerHTML = rows.length ? rows.map(row => {
                    const variantsAction = endpoint === 'products'
                        ? `<a class="btn btn-outline-success btn-sm square-btn mr-1" href="/cms/products/${row.id}/variants" title="Quản lý biến thể" aria-label="Quản lý biến thể"><i class="tio-layers-outlined"></i></a>`
                        : '';
                    return `<tr>${columns.map(key => `<td>${tableValue(key, row[key], row)}</td>`).join('')}<td><div class="d-flex justify-content-end align-items-center gap-1">${variantsAction}<a class="btn btn-outline-info btn-sm square-btn mr-1" href="${editUrl}/${row.id}/edit" title="Sửa" aria-label="Sửa"><i class="tio-edit"></i></a><button class="btn btn-outline-danger btn-sm square-btn delete" data-id="${row.id}" title="Xóa" aria-label="Xóa"><i class="tio-delete"></i></button></div></td></tr>`;
                }).join('') : `<tr><td class="empty" colspan="${columns.length + 1}">Chưa có dữ liệu</td></tr>`;

                tbody.querySelectorAll('.toggle-row-boolean').forEach(checkbox => checkbox.onchange = async () => {
                    const id = checkbox.dataset.id;
                    const field = checkbox.dataset.field;
                    const isChecked = checkbox.checked;
                    checkbox.disabled = true;
                    try {
                        const payload = {};
                        payload[field] = isChecked;
                        const result = await request(`/${endpoint}/${id}`, {
                            method: 'PUT',
                            headers: {'Content-Type': 'application/json'},
                            body: JSON.stringify(payload),
                        });
                        toast(result.message || 'Đã cập nhật trạng thái');
                    } catch (error) {
                        checkbox.checked = !isChecked;
                        toast(error.message, true);
                    } finally {
                        checkbox.disabled = false;
                    }
                });

                tbody.querySelectorAll('.delete').forEach(button => button.onclick = async () => {
                    const confirmed = await confirmSwal({
                        title: 'Bạn có chắc chắn muốn xóa?',
                        text: 'Dữ liệu này sẽ bị xóa và không thể hoàn tác!',
                        icon: 'warning',
                        confirmButtonText: 'Đồng ý xóa',
                        cancelButtonText: 'Hủy',
                        confirmButtonColor: '#ed4c78'
                    });
                    if (!confirmed) return;
                    try {
                        const result = await request(`/${endpoint}/${button.dataset.id}`, {method: 'DELETE'});
                        toast(result.message || 'Đã xóa thành công');
                        load(searchInput.value, currentPage);
                    } catch (error) {
                        toast(error.message, true);
                    }
                });
                renderPagination(result.meta || {current_page: page, last_page: 1, per_page: perPage, total: rows.length});
            } catch (error) { tbody.innerHTML = `<tr><td class="empty" colspan="${columns.length + 1}">${esc(error.message)}</td></tr>`; }
        }
        const searchInput = root.querySelector('[data-search]');
        Promise.all(filters.map(async filter => {
            let items;
            if (filter.dataset.inlineItems) {
                items = JSON.parse(filter.dataset.inlineItems);
            } else {
                const firstPage = await request(`/${filter.dataset.source}?per_page=100&page=1`);
                items = firstPage.data || [];
                const lastPage = Math.max(1, Number(firstPage.meta?.last_page) || 1);
                if (lastPage > 1) {
                    const remainingPages = await Promise.all(Array.from(
                        {length: lastPage - 1},
                        (_, index) => request(`/${filter.dataset.source}?per_page=100&page=${index + 2}`),
                    ));
                    remainingPages.forEach(response => items.push(...(response.data || [])));
                }
            }
            if (filter.dataset.filterMultiple) {
                filter._selected = [];
                const chips = filter.closest('[data-filter-wrap]').querySelector('[data-filter-chips]');
                const filterWrap = filter.closest('[data-filter-wrap]');
                const filterToggle = filterWrap.querySelector('[data-filter-toggle]');
                const filterSummary = filterWrap.querySelector('[data-filter-summary]');
                const filterMenu = filterWrap.querySelector('[data-filter-menu]');
                const renderCheckboxMenu = () => {
                    const selectedIds = new Set(filter._selected.map(item => String(item.id)));
                    if (selectedIds.size === 0) {
                        filterSummary.textContent = filter.dataset.filterLabel || 'Chọn giá trị để lọc';
                    } else if (selectedIds.size === 1) {
                        filterSummary.textContent = filter._selected[0].name;
                    } else {
                        filterSummary.textContent = `Đã chọn ${selectedIds.size} giá trị`;
                    }
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
                    if (chips) chips.innerHTML = '';
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

    function confirmSwal({ title, text, icon = 'warning', confirmButtonText = 'Đồng ý', cancelButtonText = 'Hủy', confirmButtonColor = '#377dff', cancelButtonColor = '#677788' }) {
        const swalObj = window.Swal || window.swal || window.Sweetalert2;
        if (swalObj && typeof swalObj.fire === 'function') {
            return swalObj.fire({
                title: title,
                text: text,
                icon: icon,
                type: icon,
                showCancelButton: true,
                confirmButtonColor: confirmButtonColor,
                cancelButtonColor: cancelButtonColor,
                confirmButtonText: confirmButtonText,
                cancelButtonText: cancelButtonText,
                reverseButtons: true
            }).then(result => Boolean(result.value || result.isConfirmed));
        }
        if (typeof swalObj === 'function') {
            return new Promise(resolve => {
                swalObj({
                    title: title,
                    text: text,
                    type: icon,
                    showCancelButton: true,
                    confirmButtonColor: confirmButtonColor,
                    cancelButtonColor: cancelButtonColor,
                    confirmButtonText: confirmButtonText,
                    cancelButtonText: cancelButtonText,
                    reverseButtons: true
                }, function(isConfirm) {
                    resolve(Boolean(isConfirm));
                });
            });
        }
        return Promise.resolve(window.confirm(`${title}\n${text}`));
    }

    function confirmGroupEdit(group, productsCount) {
        return confirmSwal({
            title: 'Cảnh báo thay đổi nhóm biến thể',
            text: `Nhóm "${group.group_name}" đang được sử dụng bởi ${productsCount} sản phẩm. Việc đổi tên hoặc mã nhóm sẽ hiển thị trên tất cả sản phẩm đang sử dụng nhóm này. Bạn có muốn tiếp tục?`,
            icon: 'warning',
            confirmButtonText: 'Tiếp tục sửa',
            cancelButtonText: 'Hủy',
            confirmButtonColor: '#377dff'
        });
    }

    function confirmRemoveProductGroup(group) {
        return confirmSwal({
            title: 'Xóa nhóm biến thể khỏi sản phẩm?',
            text: `Nhóm "${group.group_name}" và các giá trị riêng sẽ bị xóa khỏi sản phẩm này. Thao tác được thực hiện ngay lập tức.`,
            icon: 'warning',
            confirmButtonText: 'Đồng ý xóa',
            cancelButtonText: 'Hủy',
            confirmButtonColor: '#ed4c78'
        });
    }

    function confirmRemoveVariantOption(optionName) {
        return confirmSwal({
            title: 'Xóa giá trị biến thể?',
            text: `Giá trị "${optionName || 'này'}" sẽ bị xóa khỏi sản phẩm này. Thao tác được thực hiện ngay lập tức.`,
            icon: 'warning',
            confirmButtonText: 'Đồng ý xóa',
            cancelButtonText: 'Hủy',
            confirmButtonColor: '#ed4c78'
        });
    }

    function confirmRemoveStoredFile(fileName) {
        return confirmSwal({
            title: 'Xóa file đã lưu?',
            text: `File "${fileName}" sẽ bị xóa vĩnh viễn khỏi dữ liệu và bộ nhớ lưu trữ. Thao tác này không thể khôi phục.`,
            icon: 'warning',
            confirmButtonText: 'Đồng ý xóa',
            cancelButtonText: 'Hủy',
            confirmButtonColor: '#ed4c78'
        });
    }

    function groupFormModal(group = null) {
        return new Promise(resolve => {
            const editing = !!group;
            const overlay = modalShell(`<form class="cms-group-form"><div class="cms-modal-title border-bottom bg-light p-3 d-flex align-items-center justify-content-between"><h3 class="font-weight-bold text-dark mb-0 h4"><i class="tio-edit text-primary mr-2"></i>${editing ? 'Sửa' : 'Thêm mới'} nhóm biến thể</h3><button type="button" class="modal-close btn btn-xs btn-ghost-secondary" data-cancel aria-label="Đóng"><i class="tio-clear" style="font-size: 20px;"></i></button></div><div class="cms-modal-body p-4"><div class="form-group mb-3"><label class="title-color font-weight-bold mb-2">Mã nhóm biến thể</label><input class="form-control" name="group_code" value="${esc(group?.group_code || '')}" placeholder="Ví dụ: color, size" required></div><div class="form-group mb-3"><label class="title-color font-weight-bold mb-2">Tên nhóm biến thể</label><input class="form-control" name="group_name" value="${esc(group?.group_name || '')}" placeholder="Ví dụ: Màu sắc, Kích thước" required></div><div class="form-error text-danger font-weight-bold small" data-modal-error></div></div><div class="cms-modal-actions bg-light border-top p-3 d-flex justify-content-end"><button type="button" class="btn btn-secondary btn-sm px-4 mr-2 action-cancel" data-cancel>Hủy</button><button type="submit" class="btn btn-primary btn-sm px-4 shadow-sm action-save">${editing ? 'Lưu thay đổi' : 'Thêm nhóm'}</button></div></form>`);
            const form = overlay.querySelector('form');
            const close = result => { overlay.remove(); resolve(result); };
            overlay.querySelectorAll('[data-cancel]').forEach(button => button.onclick = () => close(null));
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
        picker.innerHTML = groups.length ? `<div class="card border mb-3 shadow-none"><div class="card-header bg-light p-3 d-flex flex-wrap align-items-center justify-content-between gap-2"><div class="input-group input-group-merge input-group-flush border rounded bg-white" style="max-width: 380px;"><div class="input-group-prepend"><div class="input-group-text"><i class="tio-search"></i></div></div><input class="form-control form-control-sm relation-search border-0" type="search" placeholder="Nhập tên hoặc mã nhóm để tìm..." data-group-search autocomplete="off"></div><div class="d-flex align-items-center gap-2"><span class="badge badge-soft-info p-2 font-weight-bold" data-selected-count>Đã chọn 0 nhóm</span><button type="button" class="btn btn-outline-primary btn-sm px-3 shadow-sm" data-add-group><i class="tio-add mr-1"></i> Thêm nhóm mới</button></div></div><div class="card-body p-3"><div class="group-search-results border rounded bg-white p-3 mb-3" data-group-search-results hidden><div class="font-weight-bold text-muted text-uppercase small mb-2">Kết quả tìm kiếm nhóm biến thể</div><div data-group-list>${groups.map(group => `<div class="relation-row card border p-3 mb-2" data-group-id="${group.id}" data-search-text="${esc(`${group.group_name} ${group.group_code}`.toLowerCase())}"><div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2"><div class="relation-main d-flex align-items-center gap-2"><button type="button" class="btn btn-xs btn-ghost-secondary group-drag p-0 mr-1" draggable="true" title="Kéo để đổi thứ tự" aria-label="Kéo để đổi thứ tự"><i class="tio-move-page" style="font-size:16px;"></i></button><input type="checkbox" data-group-check value="${group.id}" hidden><div><strong class="h5 mb-0 font-weight-bold text-dark" data-group-name>${esc(group.group_name)}</strong><span class="badge badge-soft-secondary ml-2" data-group-code>${esc(group.group_code)}</span></div></div><div class="d-flex align-items-center gap-3"><input class="relation-order" type="hidden" value=""><label class="form-check-label d-inline-flex align-items-center small font-weight-bold text-muted cursor-pointer mr-3 mb-0"><input type="checkbox" class="form-check-input mr-1" data-required disabled style="width:16px;height:16px;cursor:pointer;"> Bắt buộc</label><div class="group-row-actions d-flex gap-1 ml-2"><button type="button" class="btn btn-soft-primary btn-sm px-3 select-group" data-select-group><i class="tio-add mr-1"></i> Chọn</button><button type="button" class="btn btn-outline-info btn-sm square-btn edit-group" data-edit-group="${group.id}" title="Sửa"><i class="tio-edit"></i></button><button type="button" class="btn btn-outline-danger btn-sm square-btn remove-group" data-remove-group title="Xóa"><i class="tio-delete"></i></button></div></div></div><div class="product-options border-top pt-3 mt-2" data-product-options hidden><div class="product-options-head d-flex align-items-center justify-content-between mb-3"><strong class="font-weight-bold text-primary"><i class="tio-layers-outlined mr-1"></i> Giá trị của biến thể</strong><button type="button" class="btn btn-soft-primary btn-xs px-3" data-add-option><i class="tio-add mr-1"></i> Thêm giá trị</button></div><div class="d-flex flex-column gap-2" data-option-rows></div></div></div>`).join('')}</div><div class="relation-no-result text-muted text-center py-3" data-no-result hidden>Không tìm thấy nhóm biến thể phù hợp.</div></div><div class="selected-groups-section"><div class="font-weight-bold text-dark mb-2"><i class="tio-folder-special-outlined text-primary mr-1"></i> Nhóm biến thể đã chọn cho sản phẩm</div><div data-selected-group-list></div><div class="selected-groups-empty text-muted text-center py-4 border rounded bg-light" data-selected-empty><i class="tio-info-outined mr-1"></i> Chưa chọn nhóm biến thể nào. Hãy gõ tìm kiếm và nhấn "+ Chọn" nhóm muốn áp dụng.</div></div></div></div>` : `<div class="card border p-4 text-center"><p class="text-muted mb-3">Chưa có nhóm biến thể nào trong hệ thống.</p><div><button type="button" class="btn btn-primary btn-sm px-4 shadow-sm" data-add-group><i class="tio-add mr-1"></i> Thêm nhóm đầu tiên</button></div></div>`;
        setupGroupPicker(picker);
        picker.querySelectorAll('.product-options-head strong').forEach(title => title.textContent = 'Giá trị của biến thể');
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
        const uniqueId = `opt-${Date.now()}-${Math.floor(Math.random()*1000)}`;
        const element = document.createElement('div');
        element.className = 'product-option-row card p-2 mb-2 bg-white border';
        element.innerHTML = `<div class="d-flex flex-wrap align-items-center justify-content-between gap-2"><div class="d-flex align-items-center gap-2 flex-grow-1"><button type="button" class="btn btn-xs btn-ghost-secondary option-drag p-0 mr-1" draggable="true" title="Kéo đổi thứ tự" aria-label="Kéo đổi thứ tự"><i class="tio-move-page" style="font-size:14px;"></i></button><input type="hidden" data-option-field="id" value="${esc(option.id || '')}"><input type="hidden" data-option-field="sort_order" value="${option.sort_order ?? container.children.length}"><div class="row g-2 flex-grow-1"><div class="col-sm-5"><input class="form-control form-control-sm" data-option-field="option_code" value="${esc(option.option_code || '')}" placeholder="Mã biến thể (VD: S, Red)" required></div><div class="col-sm-6"><input class="form-control form-control-sm" data-option-field="option_name" value="${esc(option.option_name || '')}" placeholder="Tên biến thể (VD: Size S, Màu Đỏ)" required></div></div></div><div class="d-flex align-items-center gap-2"><label class="form-check-label d-inline-flex align-items-center small font-weight-bold text-dark cursor-pointer mr-3 mb-0"><input type="checkbox" class="form-check-input mr-1" id="${uniqueId}" data-option-field="is_active" ${option.is_active === false ? '' : 'checked'} style="width:16px;height:16px;cursor:pointer;"> Hoạt động</label><div class="option-row-actions d-flex gap-1"><button type="button" class="btn btn-soft-primary btn-sm px-3 save-option action-save">Lưu</button><button type="button" class="btn btn-outline-danger btn-sm square-btn remove-option" title="Xóa giá trị"><i class="tio-delete"></i></button></div></div></div>`;
        const removeButton = element.querySelector('.remove-option');
        removeButton.className = 'btn btn-outline-danger btn-sm square-btn remove-option';
        removeButton.title = 'Xóa giá trị biến thể';
        removeButton.setAttribute('aria-label', 'Xóa giá trị biến thể');
        removeButton.innerHTML = '<i class="tio-delete"></i>';
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
        const searchInput = picker.querySelector('[data-category-search]');
        const results = picker.querySelector('[data-category-results]');
        const list = picker.querySelector('[data-category-selected]');
        const inputs = picker.querySelector('[data-category-inputs]');
        let selected = [];

        const close = () => { results.hidden = true; };
        const renderSelected = () => {
            list.innerHTML = selected.length ? selected.map(item => `<div class="category-chip" data-category-id="${item.id}"><span>${esc(item.label)}</span><button type="button" class="category-chip-remove" data-remove-category="${item.id}" title="Gỡ mục đã chọn" aria-label="Gỡ ${esc(item.label)}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg></button></div>`).join('') : '<div class="category-empty">Chưa chọn mục nào.</div>';
            inputs.innerHTML = selected.map(item => `<input type="hidden" name="${esc(picker.dataset.name)}[]" value="${item.id}">`).join('');
            list.querySelectorAll('[data-remove-category]').forEach(button => button.onclick = () => {
                selected = selected.filter(item => String(item.id) !== button.dataset.removeCategory);
                renderSelected();
                filter();
            });
        };
        const filter = () => {
            const keyword = searchInput.value.trim().toLowerCase();
            const selectedIds = new Set(selected.map(item => String(item.id)));
            const matches = categories.filter(item => {
                const id = String(item[picker.dataset.value]);
                const label = String(item[picker.dataset.text] ?? '');
                return !selectedIds.has(id) && (!keyword || label.toLowerCase().includes(keyword));
            });
            results.innerHTML = matches.length
                ? matches.map(item => `<button type="button" class="category-search-option" data-category-option="${esc(item[picker.dataset.value])}">${esc(item[picker.dataset.text])}</button>`).join('')
                : '<div class="category-search-empty">Không tìm thấy kết quả phù hợp.</div>';
            results.hidden = false;
            results.querySelectorAll('[data-category-option]').forEach(button => button.onclick = () => {
                const item = categories.find(candidate => String(candidate[picker.dataset.value]) === button.dataset.categoryOption);
                if (!item) return;
                selected.push({id: item[picker.dataset.value], label: item[picker.dataset.text]});
                searchInput.value = '';
                renderSelected();
                searchInput.focus();
                close();
            });
        };
        searchInput.addEventListener('input', filter);
        searchInput.addEventListener('focus', filter);
        searchInput.addEventListener('click', () => { if (results.hidden) filter(); });
        document.addEventListener('click', event => { if (!picker.contains(event.target)) close(); });
        picker._setSelected = items => {
            selected = items.map(item => ({
                id: item[picker.dataset.value] ?? item.id,
                label: item[picker.dataset.text] ?? item.category_name ?? item.name ?? '',
            }));
            renderSelected();
        };
        renderSelected();
    }

    function setupSearchableSelect(picker) {
        const input = picker.querySelector('[data-searchable-input]');
        const hidden = picker.querySelector('input[type="hidden"]');
        const results = picker.querySelector('[data-searchable-results]');
        const simple = picker.dataset.searchVariant === 'simple';
        const fieldLabel = (picker.dataset.label || 'mục').toLowerCase();
        let timer;
        let sequence = 0;

        const close = () => { results.hidden = true; };
        const select = (id, label, notify = true) => {
            hidden.value = id ?? '';
            input.value = label ?? '';
            input.setCustomValidity(!input.required || hidden.value ? '' : `Vui lòng chọn ${fieldLabel} trong danh sách.`);
            close();
            if (notify) hidden.dispatchEvent(new Event('change', {bubbles: true}));
        };
        const render = items => {
            if (simple) {
                results.innerHTML = items.length
                    ? items.map(item => `<button type="button" class="searchable-select-option simple-search-option" data-id="${esc(item[picker.dataset.value])}">${esc(item[picker.dataset.text])}</button>`).join('')
                    : `<div class="searchable-select-empty">Không tìm thấy ${esc(fieldLabel)} phù hợp.</div>`;
            } else {
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
            }
            results.hidden = false;
            results.querySelectorAll('[data-id]').forEach((button, index) => {
                button.onclick = () => select(items[index][picker.dataset.value], items[index][picker.dataset.text]);
            });
        };
        const search = async keyword => {
            const current = ++sequence;
            results.innerHTML = `<div class="searchable-select-empty">Đang tìm ${esc(fieldLabel)}...</div>`;
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
            input.setCustomValidity(input.required ? `Vui lòng chọn ${fieldLabel} trong danh sách.` : '');
            clearTimeout(timer);
            const keyword = input.value.trim();
            timer = setTimeout(() => search(keyword).catch(error => toast(error.message, true)), 250);
        });
        input.addEventListener('focus', () => {
            const keyword = hidden.value ? '' : input.value.trim();
            search(keyword).catch(error => toast(error.message, true));
        });
        input.addEventListener('click', () => {
            if (!results.hidden) return;
            const keyword = hidden.value ? '' : input.value.trim();
            search(keyword).catch(error => toast(error.message, true));
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
                const requiredBadge = configuration.is_required
                    ? '<span class="badge badge-soft-danger ml-2 font-weight-bold"><i class="tio-asterisk mr-1"></i>Bắt buộc</span>'
                    : '<span class="badge badge-soft-secondary ml-2">Không bắt buộc</span>';
                
                const choices = options.map(option => `
                    <label class="btn btn-outline-primary btn-sm option-choice mb-2 mr-2 border rounded p-2 d-inline-flex align-items-center cursor-pointer shadow-none">
                        <input type="radio" class="mr-2" name="_variant_group_${configuration.id}" value="${option.id}" data-option-id data-option-code="${esc(option.option_code)}" ${configuration.is_required ? 'required' : ''}>
                        <span class="font-weight-bold text-dark">${esc(option.option_name)}</span>
                        <span class="text-muted small ml-1">(${esc(option.option_code)})</span>
                    </label>`).join('');
                
                const emptyChoice = configuration.is_required ? '' : `
                    <label class="btn btn-outline-secondary btn-sm option-choice mb-2 mr-2 border rounded p-2 d-inline-flex align-items-center cursor-pointer shadow-none">
                        <input type="radio" class="mr-2" name="_variant_group_${configuration.id}" value="" checked>
                        <span class="text-muted">Không chọn</span>
                    </label>`;
                
                return `
                    <div class="card mb-3 border shadow-none option-group" data-required="${configuration.is_required ? '1' : '0'}">
                        <div class="card-header bg-light py-2 px-3 d-flex align-items-center">
                            <h5 class="mb-0 font-weight-bold text-dark d-flex align-items-center">
                                <i class="tio-layers-outlined text-primary mr-2"></i> ${esc(configuration.group_name)}
                                ${requiredBadge}
                            </h5>
                        </div>
                        <div class="card-body p-3">
                            <div class="d-flex flex-wrap align-items-center">
                                ${options.length ? emptyChoice + choices : '<div class="text-muted small py-2">Nhóm này chưa có giá trị hoạt động.</div>'}
                            </div>
                        </div>
                    </div>`;
            }).join('') : '<div class="relation-loading text-muted text-center py-4">Sản phẩm chưa cấu hình nhóm biến thể.</div>';
            
            optionPicker.innerHTML = configurations.length
                ? `<div class="card border mb-3 shadow-none">
                    <div class="card-header bg-light p-3 d-flex align-items-center justify-content-between">
                        <span class="font-weight-bold text-dark"><i class="tio-tune mr-1 text-primary"></i> Chọn giá trị cho từng nhóm biến thể</span>
                        <button type="button" class="btn btn-outline-secondary btn-xs px-3" data-reset-variant-options>
                            <i class="tio-refresh mr-1"></i> Đặt lại giá trị
                        </button>
                    </div>
                   </div>
                   ${groupsHtml}
                   ${form.dataset.recordId ? '' : `
                    <div class="card border border-info bg-soft-info p-3 mb-3">
                        <label class="custom-control custom-checkbox d-flex align-items-center mb-0 cursor-pointer">
                            <input type="checkbox" class="custom-control-input" name="generate_all_combinations" value="1" data-generate-combinations>
                            <span class="custom-control-label font-weight-bold text-dark" style="padding-top: 2px;">
                                <strong>Tạo tất cả tổ hợp biến thể</strong>
                                <small class="text-muted d-block font-weight-normal mt-1">Tự động tạo một biến thể cho mỗi tổ hợp từ các giá trị đang hoạt động.</small>
                            </span>
                        </label>
                    </div>`}`
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
        const searchToolbar = picker.querySelector('.card-header');
        const availableList = picker.querySelector('[data-group-list]');
        const selectedList = picker.querySelector('[data-selected-group-list]');
        const selectedEmpty = picker.querySelector('[data-selected-empty]');
        rows.forEach(row => {
            row.setAttribute('role', 'option');
            row.setAttribute('tabindex', '0');
            const orderInput = row.querySelector('.relation-order');
            if (orderInput) {
                orderInput.type = 'hidden';
                orderInput.closest('.d-flex')?.classList.add('d-none');
            }
        });
        const update = () => {
            const selected = rows.filter(row => row.querySelector('[data-group-check]').checked);
            if (count) count.textContent = `Đã chọn ${selected.length} nhóm`;
            rows.forEach(row => {
                const checked = row.querySelector('[data-group-check]').checked;
                if (!checked) availableList?.appendChild(row);
                row.classList.toggle('selected', checked);
                row.setAttribute('aria-selected', checked ? 'true' : 'false');
                row.tabIndex = checked ? -1 : 0;
                row.querySelector('[data-required]').disabled = !checked;
                row.querySelector('.relation-order').disabled = !checked;
                row.querySelector('[data-product-options]').hidden = !checked;
                const selectBtn = row.querySelector('[data-select-group]');
                if (selectBtn) selectBtn.hidden = checked;
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
                if (searchInput) searchInput.value = '';
                if (searchResults) searchResults.hidden = true;
            });
            const selectFromDropdown = event => {
                if (row.parentElement !== availableList || row.querySelector('[data-group-check]').checked) return;
                if (event.type === 'keydown' && !['Enter', ' '].includes(event.key)) return;
                if (event.target.closest('button, input, label')) return;
                event.preventDefault();
                row.querySelector('[data-select-group]')?.click();
                searchInput?.focus();
            };
            row.addEventListener('click', selectFromDropdown);
            row.addEventListener('keydown', selectFromDropdown);
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
            if (!searchResults || searchResults.hidden) return;
            if (event.target === searchInput || searchResults.contains(event.target)) return;
            searchResults.hidden = true;
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
                row.innerHTML = `<input class="form-control" data-key-value-key placeholder="${esc(editor.dataset.keyPlaceholder)}" value="${esc(key)}"><input class="form-control" type="url" data-key-value-value placeholder="${esc(editor.dataset.valuePlaceholder)}" value="${esc(value)}"><button type="button" class="btn btn-outline-danger border btn-sm d-flex align-items-center justify-content-center" style="width:42px;height:42px;border-radius:8px;flex-shrink:0;" data-remove-key-value title="Xóa" aria-label="Xóa"><i class="ri-delete-bin-line font-weight-bold" style="font-size:18px;"></i></button>`;
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
                row.innerHTML = `<input class="form-control" data-repeatable-value maxlength="255" placeholder="${esc(editor.dataset.placeholder)}" value="${esc(value)}"><button type="button" class="btn btn-outline-danger border btn-sm d-flex align-items-center justify-content-center" style="width:42px;height:42px;border-radius:8px;flex-shrink:0;" data-remove-repeatable title="Xóa" aria-label="Xóa"><i class="ri-delete-bin-line font-weight-bold" style="font-size:18px;"></i></button>`;
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
        form.querySelectorAll('[data-type="searchable_select_api"]').forEach(picker => {
            const hidden = picker.querySelector('input[type="hidden"][name]');
            if (!hidden || row[hidden.name] == null) return;
            const selectedText = picker.dataset.selectedText;
            const label = selectedText
                ? row[selectedText]
                : hidden.name === 'product_id'
                    ? row.product_name
                    : row[picker.dataset.text];
            picker._setValue?.(row[hidden.name], label || `#${row[hidden.name]}`);
        });
        if (row.product_id) {
            await loadProductContext(form, row.product_id);
            const groupSelect = form.querySelector('[data-type="product_group_select"]');
            if (groupSelect && row.product_variant_group_id) groupSelect.value = row.product_variant_group_id;
        }
        form.querySelectorAll('[data-type="multi_select_api"]').forEach(picker => {
            const items = picker.dataset.name === 'category_ids'
                ? row.categories
                : picker.dataset.name === 'tag_ids'
                    ? row.tags
                    : row[picker.dataset.name];
            picker._setSelected?.(items || []);
        });
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
        const perPage = Number(root.dataset.perPage) || 20;
        const pagination = root.querySelector('[data-pagination]');
        let currentPage = 1;
        let timer;
        const documentIcon = '<i class="ri-file-text-line"></i>';
        const editIcon = '<i class="ri-edit-line"></i>';
        const deleteIcon = '<i class="ri-delete-bin-line"></i>';

        async function load(search = '', page = 1) {
            grid.innerHTML = `<div class="content-pages-state loading-state">${loadingMarkup()}</div>`;
            try {
                const params = new URLSearchParams({per_page: String(perPage), page: String(page), search});
                const result = await request(`/${endpoint}?${params.toString()}`);
                const rows = result.data || [];
                const lastPage = Math.max(1, Number(result.meta?.last_page) || 1);
                if (page > lastPage) return load(search, lastPage);
                currentPage = Number(result.meta?.current_page) || page;
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
                        await load(searchInput.value.trim(), currentPage);
                    } catch (error) { toast(error.message, true); }
                });
                renderStandalonePagination(pagination, result.meta, nextPage => load(searchInput.value.trim(), nextPage), perPage);
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
        const perPage = Number(root.dataset.perPage) || 20;
        const pagination = root.querySelector('[data-pagination]');
        const storage = (document.body.dataset.storage || '/storage').replace(/\/$/, '');
        let timer;
        let currentPage = 1;
        let currentSections = [];

        const editIcon = '<i class="tio-edit"></i>';
        const deleteIcon = '<i class="tio-delete"></i>';
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
                <div class="cms-modal-title p-3 border-bottom d-flex align-items-center justify-content-between">
                    <h5 class="font-weight-bold mb-0">${creating ? 'Thêm' : 'Chỉnh sửa'} ${itemMode ? 'nội dung item' : 'section'}</h5>
                    <button type="button" class="modal-close border-0 bg-transparent text-muted h4 mb-0" data-cancel aria-label="Đóng"><i class="ri-close-line"></i></button>
                </div>
                <div class="cms-modal-body section-modal-body p-4">
                    <div class="section-modal-grid">
                        <div class="field"><label class="font-weight-semibold">Tiêu đề</label><input class="input form-control" name="title" value="${esc(section.title || '')}" placeholder="Nhập tiêu đề"></div>
                        <div class="field"><label class="font-weight-semibold">Tiêu đề phụ</label><input class="input form-control" name="subtitle" value="${esc(section.subtitle || '')}" placeholder="Nhập tiêu đề phụ"></div>
                        <div class="field full"><label class="font-weight-semibold">Nội dung</label><textarea class="input form-control" data-type="richtext" name="content" placeholder="Nhập nội dung section">${esc(section.content || '')}</textarea></div>
                        <div class="field"><label class="font-weight-semibold">Thứ tự hiển thị</label><input class="input form-control" type="number" name="sort_order" value="${Number(section.sort_order || 0)}" min="0"></div>
                    </div>
                    <div class="section-modal-media mt-4 p-3 border rounded bg-light">
                        <div class="section-modal-media-head d-flex align-items-center justify-content-between mb-3">
                            <div><strong>Media</strong><small class="text-muted d-block">Ảnh JPG, PNG, GIF, WEBP; video sử dụng đường dẫn URL.</small></div>
                            <button type="button" class="btn btn-primary btn-sm px-3 shadow-sm" data-add-media><i class="ri-add-line mr-1"></i> Thêm media</button>
                        </div>
                        <div class="section-modal-files sortable-media" data-modal-files></div>
                    </div>
                    <div class="form-error text-danger font-weight-bold mt-2" data-modal-error></div>
                </div>
                <div class="cms-modal-actions p-3 border-top bg-light d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-secondary btn-sm px-4 mr-2" data-cancel>Đóng</button>
                    <button type="submit" class="btn btn-primary btn-sm px-4 shadow-sm">Lưu thay đổi</button>
                </div>
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
                    <select class="input form-control media-kind"><option value="image" ${row.kind === 'image' ? 'selected' : ''}>Hình ảnh</option><option value="video" ${row.kind === 'video' ? 'selected' : ''}>Video</option></select>
                    ${row.kind === 'image' ? `<button type="button" class="input form-control media-value choose-row-image text-left" title="Chọn hình ảnh">${esc(row.value || 'Chọn hình ảnh')}</button>` : `<input class="input form-control media-value media-video-url" type="url" value="${esc(row.value || '')}" placeholder="Nhập URL video">`}
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
                    await close(); toast(result.message || `Đã ${creating ? 'thêm' : 'cập nhật'} ${itemMode ? 'item' : 'section'}`); await load(searchInput.value.trim(), currentPage);
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

        async function load(search = '', page = 1) {
            list.innerHTML = `<div class="section-manager-state loading-state">${loadingMarkup()}</div>`;
            try {
                const params = new URLSearchParams({per_page: String(perPage), page: String(page), page_content_id: pageId, search});
                const result = await request(`/${endpoint}?${params.toString()}`);
                const rows = result.data || [];
                const lastPage = Math.max(1, Number(result.meta?.last_page) || 1);
                if (page > lastPage) return load(search, lastPage);
                currentPage = Number(result.meta?.current_page) || page;
                currentSections = rows;
                count.textContent = `${result.meta?.total ?? rows.length} section`;
                list.innerHTML = rows.length ? rows.map((section, index) => renderSection(section, ((currentPage - 1) * perPage) + index)).join('') : '<div class="section-manager-state card">Chưa có section phù hợp.</div>';
                bindActions(rows);
                renderStandalonePagination(pagination, result.meta, nextPage => load(searchInput.value.trim(), nextPage), perPage);
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
                try { const result = await request(`/${endpoint}/${button.dataset.id}`, {method: 'DELETE'}); toast(result.message || 'Đã xóa section'); load(searchInput.value.trim(), currentPage); }
                catch (error) { toast(error.message, true); }
            });
            list.querySelectorAll('.delete-item').forEach(button => button.onclick = async () => {
                if (!confirm('Bạn chắc chắn muốn xóa item này?')) return;
                try { const result = await request(`/section-items/${button.dataset.id}`, {method: 'DELETE'}); toast(result.message || 'Đã xóa item'); load(searchInput.value.trim(), currentPage); }
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
