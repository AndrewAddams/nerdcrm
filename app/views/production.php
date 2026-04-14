<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Список для производства - Nerd</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <div class="header">
        <div class="logo">
            <h2>Список для производства</h2>
        </div>
        <div class="user-info">
            <span class="user-name"><?php echo htmlspecialchars($user['name']); ?></span>
            <span class="role-badge"><?php echo $user['role'] === 'admin' ? 'Администратор' : 'Менеджер'; ?></span>
            <a href="/dashboard" class="btn btn-secondary" style="text-decoration: none;">← К заказам</a>
            <button class="logout-btn" id="logoutBtn">Выйти</button>
        </div>
    </div>
    
    <div class="production-container">
        <div class="production-header">
            <h1 class="production-title">📋 Список для производства</h1>
            <button class="btn btn-primary" id="refreshBtn">🔄 Обновить</button>
        </div>
        
        <div class="production-toolbar">
            <div class="filter-group">
                <label>Фильтр по формату</label>
                <select id="formatFilter">
                    <option value="">Все форматы</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Фильтр по названию товара</label>
                <input type="text" id="productFilter" placeholder="Введите название...">
            </div>
            <div class="filter-actions">
                <button class="btn btn-secondary" id="applyFiltersBtn">Применить</button>
                <button class="btn" id="resetFiltersBtn">Сбросить</button>
            </div>
            <div class="filter-actions">
                <input type="checkbox" id="selectAllCheckbox">
                <label for="selectAllCheckbox">Выбрать все</label>
                <select id="bulkStatusSelect">
                    <option value="">Массовое действие</option>
                    <option value="ready">Отметить как готовые</option>
                    <option value="work">Вернуть в работу</option>
                </select>
                <button class="btn btn-primary btn-sm" id="applyBulkBtn">Применить</button>
            </div>
        </div>
        
        <div class="production-table-container">
            <table class="production-table" id="productionTable">
                <thead>
                    <tr>
                        <th style="width: 40px;"><input type="checkbox" id="headerCheckbox"></th>
                        <th>Номер заказа</th>
                        <th>Наименование товара</th>
                        <th>Формат</th>
                        <th>Статус</th>
                        <th style="width: 100px;">Действия</th>
                    </tr>
                </thead>
                <tbody id="productionTableBody">
                    <tr><td colspan="6" class="loading">Загрузка...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
    
    <script>
        let productionItems = [];
        let formatsList = [];
        let currentFilters = {
            format: '',
            productName: ''
        };
        
        const productionTableBody = document.getElementById('productionTableBody');
        const refreshBtn = document.getElementById('refreshBtn');
        const formatFilter = document.getElementById('formatFilter');
        const productFilter = document.getElementById('productFilter');
        const applyFiltersBtn = document.getElementById('applyFiltersBtn');
        const resetFiltersBtn = document.getElementById('resetFiltersBtn');
        const selectAllCheckbox = document.getElementById('selectAllCheckbox');
        const headerCheckbox = document.getElementById('headerCheckbox');
        const bulkStatusSelect = document.getElementById('bulkStatusSelect');
        const applyBulkBtn = document.getElementById('applyBulkBtn');
        const logoutBtn = document.getElementById('logoutBtn');
        
        async function loadFormats() {
            try {
                const response = await fetch('/api/formats/select');
                const result = await response.json();
                if (result.success) {
                    formatsList = result.data;
                    let html = '<option value="">Все форматы</option>';
                    formatsList.forEach(f => {
                        html += '<option value="' + escapeHtml(f.text) + '">' + escapeHtml(f.text) + '</option>';
                    });
                    formatFilter.innerHTML = html;
                }
            } catch (error) {
                console.error('Ошибка загрузки форматов:', error);
            }
        }
        
        async function loadProductionItems() {
            productionTableBody.innerHTML = '<tr><td colspan="6" class="loading">Загрузка...</td></tr>';
            try {
                const response = await fetch('/api/production/items');
                const result = await response.json();
                if (result.success) {
                    productionItems = result.data;
                    renderTable();
                } else {
                    productionTableBody.innerHTML = '<tr><td colspan="6" class="loading">Ошибка загрузки</td></tr>';
                }
            } catch (error) {
                console.error('Ошибка:', error);
                productionTableBody.innerHTML = '<tr><td colspan="6" class="loading">Ошибка загрузки</td></tr>';
            }
        }
        
        function filterItems() {
            return productionItems.filter(item => {
                if (currentFilters.format && item.format_name !== currentFilters.format) {
                    return false;
                }
                if (currentFilters.productName && !item.product_name.toLowerCase().includes(currentFilters.productName.toLowerCase())) {
                    return false;
                }
                return true;
            });
        }
        
        function renderTable() {
            const filteredItems = filterItems();
            if (filteredItems.length === 0) {
                productionTableBody.innerHTML = '<tr><td colspan="6" class="empty">Нет товаров для производства</td></tr>';
                updateHeaderCheckboxState();
                return;
            }
            let html = '';
            for (const item of filteredItems) {
                html += '<tr data-item-id="' + item.id + '">';
                html += '<td><input type="checkbox" class="item-checkbox" data-id="' + item.id + '"></td>';
                html += '<td><a href="/dashboard?order_id=' + item.order_id + '" class="order-link" target="_blank">' + escapeHtml(item.order_number) + '</a></td>';
                html += '<td>' + escapeHtml(item.product_name) + '</td>';
                html += '<td>' + escapeHtml(item.format_name) + '</td>';
                html += '<td><select class="status-select" data-item-id="' + item.id + '" data-current-status="' + item.status_id + '">';
                html += '<option value="6" ' + (item.status_id == 6 ? 'selected' : '') + '>В работе</option>';
                html += '<option value="7" ' + (item.status_id == 7 ? 'selected' : '') + '>Сделать</option>';
                html += '<option value="8" ' + (item.status_id == 8 ? 'selected' : '') + '>Готов</option>';
                html += '</select></td>';
                html += '<td><button class="btn btn-primary btn-sm mark-ready-btn" data-item-id="' + item.id + '">Готово</button></td>';
                html += '</tr>';
            }
            productionTableBody.innerHTML = html;
            
            document.querySelectorAll('.status-select').forEach(select => {
                select.addEventListener('change', (e) => {
                    const itemId = select.dataset.itemId;
                    const newStatus = select.value;
                    updateItemStatus(itemId, newStatus);
                });
            });
            document.querySelectorAll('.mark-ready-btn').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    const itemId = btn.dataset.itemId;
                    markAsReady(itemId);
                });
            });
            document.querySelectorAll('.item-checkbox').forEach(cb => {
                cb.addEventListener('change', updateHeaderCheckboxState);
            });
            updateHeaderCheckboxState();
        }
        
        async function updateItemStatus(itemId, statusId) {
            try {
                const response = await fetch('/api/production/items/' + itemId + '/status', {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ status_id: statusId })
                });
                const result = await response.json();
                if (result.success) {
                    const item = productionItems.find(i => i.id == itemId);
                    if (item) {
                        item.status_id = parseInt(statusId);
                        const statusMap = {6: 'В работе', 7: 'Сделать', 8: 'Готов'};
                        item.status_name = statusMap[statusId];
                    }
                    if (statusId == 8) {
                        productionItems = productionItems.filter(i => i.id != itemId);
                    }
                    renderTable();
                } else {
                    alert(result.error || 'Ошибка обновления статуса');
                    loadProductionItems();
                }
            } catch (error) {
                console.error('Ошибка:', error);
                alert('Ошибка обновления статуса');
            }
        }
        
        async function markAsReady(itemId) {
            try {
                const response = await fetch('/api/production/items/' + itemId + '/complete', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' }
                });
                const result = await response.json();
                if (result.success) {
                    productionItems = productionItems.filter(i => i.id != itemId);
                    renderTable();
                } else {
                    alert(result.error || 'Ошибка');
                }
            } catch (error) {
                console.error('Ошибка:', error);
                alert('Ошибка');
            }
        }
        
        function getSelectedItemIds() {
            const checkboxes = document.querySelectorAll('.item-checkbox:checked');
            return Array.from(checkboxes).map(cb => parseInt(cb.dataset.id));
        }
        
        function updateHeaderCheckboxState() {
            const checkboxes = document.querySelectorAll('.item-checkbox');
            const checked = document.querySelectorAll('.item-checkbox:checked');
            if (checkboxes.length === 0) {
                headerCheckbox.checked = false;
                headerCheckbox.indeterminate = false;
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = false;
            } else if (checked.length === 0) {
                headerCheckbox.checked = false;
                headerCheckbox.indeterminate = false;
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = false;
            } else if (checked.length === checkboxes.length) {
                headerCheckbox.checked = true;
                headerCheckbox.indeterminate = false;
                selectAllCheckbox.checked = true;
                selectAllCheckbox.indeterminate = false;
            } else {
                headerCheckbox.checked = false;
                headerCheckbox.indeterminate = true;
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = true;
            }
        }
        
        function selectAll(checked) {
            document.querySelectorAll('.item-checkbox').forEach(cb => {
                cb.checked = checked;
            });
            updateHeaderCheckboxState();
        }
        
        async function bulkUpdateStatus(action) {
            const selectedIds = getSelectedItemIds();
            if (selectedIds.length === 0) {
                alert('Выберите хотя бы один товар');
                return;
            }
            let statusId = null;
            if (action === 'ready') statusId = 8;
            else if (action === 'work') statusId = 6;
            if (!statusId) return;
            try {
                const response = await fetch('/api/production/bulk-update-status', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        item_ids: selectedIds,
                        status_id: statusId
                    })
                });
                const result = await response.json();
                if (result.success) {
                    loadProductionItems();
                } else {
                    alert(result.error || 'Ошибка массового обновления');
                }
            } catch (error) {
                console.error('Ошибка:', error);
                alert('Ошибка');
            }
        }
        
        function applyFilters() {
            currentFilters.format = formatFilter.value;
            currentFilters.productName = productFilter.value.trim();
            renderTable();
        }
        
        function resetFilters() {
            formatFilter.value = '';
            productFilter.value = '';
            currentFilters.format = '';
            currentFilters.productName = '';
            renderTable();
        }
        
        function escapeHtml(str) {
            if (!str) return '';
            return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
        }
        
        async function init() {
            await loadFormats();
            await loadProductionItems();
            
            refreshBtn.addEventListener('click', loadProductionItems);
            applyFiltersBtn.addEventListener('click', applyFilters);
            resetFiltersBtn.addEventListener('click', resetFilters);
            headerCheckbox.addEventListener('change', (e) => selectAll(e.target.checked));
            selectAllCheckbox.addEventListener('change', (e) => selectAll(e.target.checked));
            applyBulkBtn.addEventListener('click', () => {
                const action = bulkStatusSelect.value;
                if (action) bulkUpdateStatus(action);
                bulkStatusSelect.value = '';
            });
            logoutBtn.addEventListener('click', async () => {
                await fetch('/api/auth/logout', { method: 'POST' });
                window.location.href = '/login';
            });
            setInterval(loadProductionItems, 10000);
        }
        
        init();
    </script>
</body>
</html>