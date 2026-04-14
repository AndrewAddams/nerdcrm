<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nerd - Управление заказами</title>
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
</head>
<body>
    <div class="header">
        <div class="logo"><h2>Nerd Заказы</h2></div>
        <div class="user-info">
    <span class="user-name"><?php echo htmlspecialchars($user['name']); ?></span>
    <span class="role-badge"><?php echo $user['role'] === 'admin' ? 'Администратор' : 'Менеджер'; ?></span>
    <a href="/profile" class="btn">👤 Профиль</a>
    <?php if ($user['role'] === 'admin'): ?>
        <a href="/admin" class="btn">⚙️ Админка</a>
    <?php endif; ?>
    <a href="/reports" class="btn">📊 Отчёты</a>
    <a href="/references" class="btn">📚 Справочники</a>
    <button class="btn btn-secondary btn-with-badge" id="tildaBtn">
    🎨 Заказы с сайта
    <span id="tildaBadge" class="tilda-badge" style="display: none;">0</span>
    </button>
    <button class="btn" id="logoutBtn">Выйти</button>
</div>
    </div>
    
    <div class="app-container">
        <div class="sidebar">
            <div class="sidebar-section">
                <div class="sidebar-title">Фильтр по дате</div>
                <div id="folderTree" class="folder-tree"><div class="loading">Загрузка...</div></div>
            </div>
        </div>
        
        <div class="main-content">
            <div class="toolbar">
                
                <div class="filter-group">
                    <input type="checkbox" id="selectAllCheckbox">
                    <label for="selectAllCheckbox">Выбрать все</label>
                </div>
                
                <div class="filter-group" id="bulkActions" style="display: none;">
                    <select id="bulkActionSelect">
                        <option value="">Массовое действие</option>
                        <option value="change-status">Изменить статус</option>
                        <option value="delete">Удалить выбранные</option>
                    </select>
                    <button class="btn btn-primary" id="applyBulkActionBtn">Применить</button>
                </div>                
                
                <button class="btn btn-primary" id="createOrderBtn">+ Создать заказ</button>
                <button class="btn btn-secondary" id="productionListBtn">📋 Список для производства</button>
                
                <div class="filter-group">
                    <label>Дата с:</label><input type="date" id="dateFrom">
                    <label>по:</label><input type="date" id="dateTo">
                </div>
                
                <div class="filter-group">
                    <label>Статус заказа:</label>
                    <select id="statusFilter">
                        <option value="">Все</option>
                        <option value="1">В работе</option>
                        <option value="2">Упакован</option>
                        <option value="3">Отправлен</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label>🔍 Поиск:</label>
                    <input type="text" id="globalSearch" placeholder="Поиск по всем полям..." style="min-width: 250px;">
                </div>
                
                <div class="filter-group">
                    <button class="btn btn-secondary" id="applyFiltersBtn">Применить</button>
                    <button class="btn" id="resetFiltersBtn">Сбросить</button>
                </div>
                    
                <div class="columns-menu">
                    <button class="columns-menu-btn" id="columnsMenuBtn"><i class="fas fa-columns"></i> Столбцы</button>
                    <div class="columns-menu-content" id="columnsMenuContent"><div id="columnsList"></div></div>
                </div>
                
            </div>
            
            <div class="orders-container" id="ordersContainer">
                <div class="loading">Загрузка заказов...</div>
            </div>
        </div>
    </div>
    
    <?php include __DIR__ . '/partials/order_modal.php'; ?>
    
    <script>
        let currentUser = <?php echo json_encode($user); ?>;
        let currentFilters = { 
            year: null, 
            month: null, 
            dateFrom: null, 
            dateTo: null, 
            statusId: null,
            search: null
        };
        
        const allColumns = [
            { key: 'order_number', label: 'Номер заказа', default: true },
            { key: 'date_created', label: 'Дата создания', default: true },
            { key: 'status_order', label: 'Статус заказа', default: true },
            { key: 'status_payment', label: 'Статус оплаты', default: true },
            { key: 'sale_label', label: 'Метка продажи', default: false },
            { key: 'source', label: 'Источник заказа', default: false },
            { key: 'link', label: 'Ссылка', default: false },
            { key: 'comments', label: 'Комментарии', default: false },
            { key: 'shipping_method', label: 'Способ доставки', default: false },
            { key: 'tracking_number', label: 'Трек номер', default: false },
            { key: 'recipient_name', label: 'Имя получателя', default: false },
            { key: 'recipient_phone', label: 'Телефон получателя', default: false },
            { key: 'recipient_email', label: 'E-mail получателя', default: false },
            { key: 'shipping_cost', label: 'Стоимость доставки', default: false },
            { key: 'total_items_cost', label: 'Общая стоимость товаров', default: false },
            { key: 'total_cost', label: 'Итого', default: false },
            { key: 'user_name', label: 'Постановщик', default: false }
        ];
        
        let userSettings = {
            columnsOrder: allColumns.filter(c => c.default).map(c => c.key),
            columnsVisibility: Object.fromEntries(allColumns.map(c => [c.key, c.default])),
            collapsedOrders: []
        };
        
        let isLoading = false;
        
        const folderTree = document.getElementById('folderTree');
        const ordersContainer = document.getElementById('ordersContainer');
        const createOrderBtn = document.getElementById('createOrderBtn');
        const productionListBtn = document.getElementById('productionListBtn');
        const logoutBtn = document.getElementById('logoutBtn');
        const applyFiltersBtn = document.getElementById('applyFiltersBtn');
        const resetFiltersBtn = document.getElementById('resetFiltersBtn');
        const dateFromInput = document.getElementById('dateFrom');
        const dateToInput = document.getElementById('dateTo');
        const statusFilter = document.getElementById('statusFilter');
        const globalSearch = document.getElementById('globalSearch');
        const selectAllCheckbox = document.getElementById('selectAllCheckbox');
        const bulkActionsDiv = document.getElementById('bulkActions');
        const bulkActionSelect = document.getElementById('bulkActionSelect');
        const applyBulkActionBtn = document.getElementById('applyBulkActionBtn');
        const columnsMenuBtn = document.getElementById('columnsMenuBtn');
        const columnsMenuContent = document.getElementById('columnsMenuContent');
        const columnsList = document.getElementById('columnsList');
        
        async function loadUserSettings() {
            try {
                const response = await fetch('/api/user-settings');
                const result = await response.json();
                if (result.success && result.data) {
                    if (result.data.orders_columns_order) userSettings.columnsOrder = result.data.orders_columns_order;
                    if (result.data.orders_columns_visibility) userSettings.columnsVisibility = result.data.orders_columns_visibility;
                    if (result.data.collapsed_orders) userSettings.collapsedOrders = result.data.collapsed_orders;
                }
            } catch(e) { console.error(e); }
        }
        
        async function saveUserSettings() {
            try {
                await fetch('/api/user-settings', {
                    method: 'POST', headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        orders_columns_order: userSettings.columnsOrder, 
                        orders_columns_visibility: userSettings.columnsVisibility, 
                        collapsed_orders: userSettings.collapsedOrders 
                    })
                });
            } catch(e) { console.error(e); }
        }
        
        async function toggleOrderCollapsed(orderId) {
            const index = userSettings.collapsedOrders.indexOf(orderId);
            if (index === -1) {
                userSettings.collapsedOrders.push(orderId);
            } else {
                userSettings.collapsedOrders.splice(index, 1);
            }
            await saveUserSettings();
            loadOrders();
        }
        
        function renderColumnsMenu() {
            let html = '';
            allColumns.forEach(column => {
                const isVisible = userSettings.columnsVisibility[column.key] !== undefined ? userSettings.columnsVisibility[column.key] : column.default;
                html += '<div class="columns-menu-item" data-column="' + column.key + '"><input type="checkbox" ' + (isVisible ? 'checked' : '') + '><span>' + column.label + '</span></div>';
            });
            columnsList.innerHTML = html;
            columnsList.querySelectorAll('.columns-menu-item').forEach(item => {
                const checkbox = item.querySelector('input');
                const columnKey = item.dataset.column;
                checkbox.addEventListener('change', () => {
                    userSettings.columnsVisibility[columnKey] = checkbox.checked;
                    if (checkbox.checked && !userSettings.columnsOrder.includes(columnKey)) {
                        userSettings.columnsOrder.push(columnKey);
                    }
                    saveUserSettings();
                    loadOrders();
                });
            });
        }
        
        document.addEventListener('click', (e) => {
            if (!columnsMenuBtn.contains(e.target) && !columnsMenuContent.contains(e.target)) columnsMenuContent.classList.remove('show');
        });
        columnsMenuBtn.addEventListener('click', (e) => { e.stopPropagation(); columnsMenuContent.classList.toggle('show'); renderColumnsMenu(); });
        
        async function loadFolders() {
            try {
                const response = await fetch('/api/orders/folders');
                const result = await response.json();
                if (result.success) renderFolders(result.data);
            } catch(e) { folderTree.innerHTML = '<div class="loading">Ошибка</div>'; }
        }
        
        function renderFolders(folders) {
            if (!folders || folders.length === 0) { folderTree.innerHTML = '<div class="loading">Нет заказов</div>'; return; }
            let html = '<ul class="folder-tree">';
            for (const year of folders) {
                const yearActive = currentFilters.year == year.year ? 'active' : '';
                html += '<li class="folder-year"><div class="folder-link ' + yearActive + '" data-year="' + year.year + '">📁 ' + year.year + '</div>';
                if (year.months && year.months.length > 0) {
                    html += '<ul class="folder-months">';
                    for (const month of year.months) {
                        const monthActive = (currentFilters.year == year.year && currentFilters.month == month.month) ? 'active' : '';
                        html += '<li class="folder-month"><div class="folder-link ' + monthActive + '" data-year="' + year.year + '" data-month="' + month.month + '">📄 ' + month.month_name + '</div></li>';
                    }
                    html += '</ul>';
                }
                html += '</li>';
            }
            html += '</ul>';
            folderTree.innerHTML = html;
            document.querySelectorAll('.folder-link[data-year]').forEach(el => {
                el.addEventListener('click', () => {
                    const year = el.dataset.year, month = el.dataset.month;
                    if (month) { currentFilters.year = year; currentFilters.month = month; }
                    else { currentFilters.year = year; currentFilters.month = null; }
                    loadOrders(); loadFolders();
                });
            });
        }
        
                // Загрузка количества необработанных заказов Tilda
async function loadTildaUnreadCount() {
    try {
        const response = await fetch('/api/tilda/unread-count');
        const result = await response.json();
        if (result.success && result.data.count > 0) {
            const badge = document.getElementById('tildaBadge');
            badge.textContent = result.data.count;
            badge.style.display = 'inline-block';
        } else {
            const badge = document.getElementById('tildaBadge');
            badge.style.display = 'none';
        }
    } catch(e) {
        console.error('Ошибка загрузки счётчика Tilda:', e);
    }
}
        
        async function loadOrders() {
            if (isLoading) return;
            isLoading = true;
            try {
                ordersContainer.innerHTML = '<div class="loading">Загрузка...</div>';
                let url = '/api/orders?t=' + Date.now() + '&';
                if (currentFilters.year) url += 'year=' + currentFilters.year + '&';
                if (currentFilters.month) url += 'month=' + currentFilters.month + '&';
                if (currentFilters.dateFrom) url += 'date_from=' + currentFilters.dateFrom + '&';
                if (currentFilters.dateTo) url += 'date_to=' + currentFilters.dateTo + '&';
                if (currentFilters.statusId) url += 'status_id=' + currentFilters.statusId + '&';
                if (currentFilters.search) url += 'search=' + encodeURIComponent(currentFilters.search) + '&';
                const response = await fetch(url);
                const result = await response.json();
                if (result.success) {
                    renderOrders(result.data);
                } else {
                    ordersContainer.innerHTML = '<div class="loading">Ошибка загрузки</div>';
                }
            } catch(e) {
                ordersContainer.innerHTML = '<div class="loading">Ошибка загрузки</div>';
            } finally {
                isLoading = false;
            }
        }
        
        function getFieldValue(order, field) {
            const map = {
                'order_number': escapeHtml(order.order_number),
                'date_created': escapeHtml(order.date_created),
                'status_order': '<select class="status-select order-status-select" data-order-id="' + order.id + '"><option value="1" ' + (order.status_order_id == 1 ? 'selected' : '') + '>В работе</option><option value="2" ' + (order.status_order_id == 2 ? 'selected' : '') + '>Упакован</option><option value="3" ' + (order.status_order_id == 3 ? 'selected' : '') + '>Отправлен</option></select>',
                'status_payment': '<select class="status-select payment-status-select" data-order-id="' + order.id + '"><option value="4" ' + (order.status_payment_id == 4 ? 'selected' : '') + '>Не оплачен</option><option value="5" ' + (order.status_payment_id == 5 ? 'selected' : '') + '>Оплачен</option></select>',
                'sale_label': escapeHtml(order.sale_label || ''),
                'source': escapeHtml(order.source_name || ''),
                'link': order.link ? '<a href="' + escapeHtml(order.link) + '" target="_blank">Ссылка</a>' : '',
                'comments': order.comments ? '<div class="comments-cell">' + order.comments + '</div>' : '',
                'shipping_method': escapeHtml(order.shipping_method_name || ''),
                'tracking_number': escapeHtml(order.tracking_number || ''),
                'recipient_name': escapeHtml(order.recipient_name || ''),
                'recipient_phone': escapeHtml(order.recipient_phone || ''),
                'recipient_email': escapeHtml(order.recipient_email || ''),
                'shipping_cost': order.shipping_cost + ' ₽',
                'total_items_cost': order.total_items_cost + ' ₽',
                'total_cost': order.total_cost + ' ₽',
                'user_name': escapeHtml(order.user_name || '')
            };
            return map[field] || '';
        }
        
        async function deleteSingleOrder(orderId) {
            if (!confirm('Вы уверены, что хотите удалить этот заказ?')) return;
            try {
                const response = await fetch('/api/orders/bulk-delete', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ order_ids: [orderId] })
                });
                const result = await response.json();
                if (result.success) {
                    alert(result.message);
                    loadOrders();
                    loadFolders();
                } else {
                    alert(result.error || 'Ошибка удаления');
                }
            } catch(e) {
                alert('Ошибка удаления');
            }
        }
        
        function renderOrders(orders) {
            if (!orders || orders.length === 0) {
                ordersContainer.innerHTML = '<div class="loading">Нет заказов</div>';
                updateBulkActionsVisibility();
                return;
            }
            
            let html = '';
            
            for (const order of orders) {
                let cardClass = '';
                if (order.status_payment_name === 'Не оплачен') {
                    cardClass = 'status-payment-not-paid';
                } else if (order.status_order_name === 'Упакован') {
                    cardClass = 'status-order-packed';
                } else if (order.status_order_name === 'Отправлен') {
                    cardClass = 'status-order-sent';
                }
                
                const isCollapsed = userSettings.collapsedOrders.includes(order.id);
                const urgentClass = order.is_urgent ? 'urgent-order' : '';
                const visibleColumns = userSettings.columnsOrder.filter(col => userSettings.columnsVisibility[col]);
                
                // Открываем карточку заказа
                html += '<div class="order-card ' + cardClass + ' ' + urgentClass + '" data-order-id="' + order.id + '">';
                html += '<div class="order-card-header">';
                html += '<div class="order-card-actions">';
                html += '<button class="expand-btn" data-order-id="' + order.id + '" title="' + (isCollapsed ? 'Развернуть' : 'Свернуть') + '">' + (isCollapsed ? '▶' : '▼') + '</button>';
                html += '<input type="checkbox" class="order-checkbox" data-id="' + order.id + '">';
                html += '<button class="delete-order-btn" data-order-id="' + order.id + '" title="Удалить заказ">🗑️</button>';
                html += '</div>';
                
                html += '<div class="order-card-fields" data-order-id="' + order.id + '">';
                for (const col of visibleColumns) {
                    html += '<div class="order-field" data-column="' + col + '">';
                        html += '<span class="order-field-label drag-handle">⋮⋮ ' + (allColumns.find(c => c.key === col)?.label || col) + ':</span>';
                        html += '<span class="order-field-value">' + getFieldValue(order, col) + '</span>';
                    html += '</div>';
                }
                html += '<button class="edit-order-btn" data-order-id="' + order.id + '" title="Редактировать">✏️ Редактировать</button>';
                html += '</div>'; // закрываем order-card-fields
                html += '</div>'; // закрываем order-card-header
                
                // Товары (отдельный блок, но внутри карточки)
                if (!isCollapsed && order.items && order.items.length > 0) {
                    html += '<div class="order-items">';
                    html += '<table class="order-items-table"><thead><tr><th>Наименование</th><th>Формат</th><th>Статус</th></tr></thead><tbody>';
                    for (const item of order.items) {
                        let itemRowClass = '';
                        if (item.status_name === 'Сделать') itemRowClass = 'item-status-make';
                        else if (item.status_name === 'Готов') itemRowClass = 'item-status-ready';
                        else if (item.status_name === 'В работе') itemRowClass = 'item-status-work';
                        html += '<tr class="' + itemRowClass + '">';
                        html += '<td>' + escapeHtml(item.product_name) + '</td>';
                        html += '<td>' + escapeHtml(item.format_name) + '</td>';
                        html += '<td><select class="status-select item-status-select" data-item-id="' + item.id + '">';
                        html += '<option value="6" ' + (item.status_id == 6 ? 'selected' : '') + '>В работе</option>';
                        html += '<option value="7" ' + (item.status_id == 7 ? 'selected' : '') + '>Сделать</option>';
                        html += '<option value="8" ' + (item.status_id == 8 ? 'selected' : '') + '>Готов</option>';
                        html += '</select></td>';
                        html += '</tr>';
                    }
                    html += '</tbody></table>';
                    html += '</div>';
                } else if (!isCollapsed) {
                    html += '<div class="order-items empty">Нет товаров</div>';
                }
                
                html += '</div>'; // закрываем order-card
            }
            
            ordersContainer.innerHTML = html;
            
            // Drag-and-drop только за иконку ⋮⋮ (drag-handle)
            document.querySelectorAll('.order-card-fields').forEach(container => {
                new Sortable(container, {
                    handle: '.drag-handle',
                    animation: 150,
                    onEnd: function() {
                        const orderId = container.closest('.order-card').dataset.orderId;
                        const newOrder = [];
                        container.querySelectorAll('.order-field').forEach(field => {
                            const column = field.dataset.column;
                            if (column) newOrder.push(column);
                        });
                        if (newOrder.length > 0) {
                            userSettings.columnsOrder = newOrder;
                            saveUserSettings();
                        }
                    }
                });
            });
            
            document.querySelectorAll('.expand-btn').forEach(btn => {
                btn.addEventListener('click', async (e) => {
                    e.stopPropagation();
                    const orderId = parseInt(btn.dataset.orderId);
                    await toggleOrderCollapsed(orderId);
                });
            });
            document.querySelectorAll('.delete-order-btn').forEach(btn => {
                btn.addEventListener('click', async (e) => {
                    e.stopPropagation();
                    const orderId = parseInt(btn.dataset.orderId);
                    await deleteSingleOrder(orderId);
                });
            });
            document.querySelectorAll('.order-status-select').forEach(s => s.addEventListener('change', () => updateOrderStatus(s.dataset.orderId, 'order', s.value)));
            document.querySelectorAll('.payment-status-select').forEach(s => s.addEventListener('change', () => updateOrderStatus(s.dataset.orderId, 'payment', s.value)));
            document.querySelectorAll('.item-status-select').forEach(s => s.addEventListener('change', () => updateItemStatus(s.dataset.itemId, s.value)));
            document.querySelectorAll('.edit-order-btn').forEach(b => b.addEventListener('click', () => window.openOrderModal(b.dataset.orderId)));
            document.querySelectorAll('.order-checkbox').forEach(cb => cb.addEventListener('change', updateBulkActionsVisibility));
            updateBulkActionsVisibility();
        }
        
        function updateBulkActionsVisibility() {
            const checked = document.querySelectorAll('.order-checkbox:checked');
            if (bulkActionsDiv) bulkActionsDiv.style.display = checked.length > 0 ? 'flex' : 'none';
            updateSelectAllCheckbox();
        }
        
        function updateSelectAllCheckbox() {
            const all = document.querySelectorAll('.order-checkbox');
            const checked = document.querySelectorAll('.order-checkbox:checked');
            if (selectAllCheckbox) {
                if (all.length === 0) {
                    selectAllCheckbox.checked = false;
                    selectAllCheckbox.indeterminate = false;
                } else if (checked.length === 0) {
                    selectAllCheckbox.checked = false;
                    selectAllCheckbox.indeterminate = false;
                } else if (checked.length === all.length) {
                    selectAllCheckbox.checked = true;
                    selectAllCheckbox.indeterminate = false;
                } else {
                    selectAllCheckbox.checked = false;
                    selectAllCheckbox.indeterminate = true;
                }
            }
        }
        
        function selectAllOrders(checked) {
            document.querySelectorAll('.order-checkbox').forEach(cb => cb.checked = checked);
            updateBulkActionsVisibility();
        }
        
        function getSelectedOrderIds() {
            return Array.from(document.querySelectorAll('.order-checkbox:checked')).map(cb => parseInt(cb.dataset.id));
        }
        
        async function bulkChangeStatus() {
            const ids = getSelectedOrderIds();
            if (!ids.length) { alert('Выберите заказы'); return; }
            const status = prompt('Статус (1=В работе, 2=Упакован, 3=Отправлен):');
            if (!status) return;
            try {
                const res = await fetch('/api/orders/bulk-update-status', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ order_ids: ids, status_id: parseInt(status) }) });
                const result = await res.json();
                if (result.success) { alert(result.message); loadOrders(); } else alert(result.error);
            } catch(e) { alert('Ошибка'); }
        }
        
        async function bulkDelete() {
            const ids = getSelectedOrderIds();
            if (!ids.length) { alert('Выберите заказы'); return; }
            if (!confirm('Удалить ' + ids.length + ' заказ(ов)?')) return;
            try {
                const res = await fetch('/api/orders/bulk-delete', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ order_ids: ids }) });
                const result = await res.json();
                if (result.success) { alert(result.message); loadOrders(); loadFolders(); } else alert(result.error);
            } catch(e) { alert('Ошибка'); }
        }
        
        async function updateOrderStatus(id, type, status) {
            try {
                const res = await fetch('/api/orders/' + id + '/status-' + type, { method: 'PUT', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ status_id: status }) });
                const result = await res.json();
                if (!result.success) alert(result.error);
                loadOrders();
            } catch(e) { alert('Ошибка'); }
        }
        
        async function updateItemStatus(id, status) {
            try {
                const res = await fetch('/api/orders/items/' + id + '/status', { method: 'PUT', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ status_id: status }) });
                const result = await res.json();
                if (!result.success) alert(result.error);
                loadOrders();
            } catch(e) { alert('Ошибка'); }
        }
        
        async function logout() { await fetch('/api/auth/logout', { method: 'POST' }); window.location.href = '/login'; }
        
        function applyFilters() {
            currentFilters.dateFrom = dateFromInput.value;
            currentFilters.dateTo = dateToInput.value;
            currentFilters.statusId = statusFilter.value;
            currentFilters.search = globalSearch.value.trim() || null;
            loadOrders();
        }
        
        function resetFilters() {
            dateFromInput.value = '';
            dateToInput.value = '';
            statusFilter.value = '';
            globalSearch.value = '';
            currentFilters = { year: null, month: null, dateFrom: null, dateTo: null, statusId: null, search: null };
            loadOrders();
            loadFolders();
        }
        
        function escapeHtml(s) { if (!s) return ''; return s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;'); }
        
        let searchTimeout;
        globalSearch.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                applyFilters();
            }, 500);
        });
        
        async function init() {
        await loadUserSettings();
        await loadFolders();
        await loadOrders();
        await loadTildaUnreadCount();
            logoutBtn.addEventListener('click', logout);
            createOrderBtn.addEventListener('click', () => window.openOrderModal());
            productionListBtn.addEventListener('click', () => window.open('/production', '_blank'));
            document.getElementById('tildaBtn')?.addEventListener('click', () => {
                window.location.href = '/tilda';
            });
            applyFiltersBtn.addEventListener('click', applyFilters);
            resetFiltersBtn.addEventListener('click', resetFilters);
            if (selectAllCheckbox) selectAllCheckbox.addEventListener('change', (e) => selectAllOrders(e.target.checked));
            if (applyBulkActionBtn) applyBulkActionBtn.addEventListener('click', () => {
                const action = bulkActionSelect?.value;
                if (action === 'change-status') bulkChangeStatus();
                else if (action === 'delete') bulkDelete();
                else alert('Выберите действие');
            });
            setInterval(() => { loadOrders(); loadFolders(); loadTildaUnreadCount(); }, 30000);
        }
        init();
    </script>
</body>
</html>