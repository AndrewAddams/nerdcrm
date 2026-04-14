<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tilda заказы - Nerd</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <div class="header">
        <div class="logo">
            <h2>Nerd Заказы</h2>
        </div>
        <div class="user-info">
            <span class="user-name"><?php echo htmlspecialchars($user['name']); ?></span>
            <span class="role-badge"><?php echo $user['role'] === 'admin' ? 'Администратор' : 'Менеджер'; ?></span>
            <a href="/dashboard" class="btn" style="text-decoration: none;">📋 Заказы</a>
            <a href="/profile" class="btn" style="text-decoration: none;">👤 Профиль</a>
            <?php if ($user['role'] === 'admin'): ?>
                <a href="/admin" class="admin-link">⚙️ Админка</a>
            <?php endif; ?>
            <a href="/reports" class="admin-link" style="background: #48bb78;">📊 Отчёты</a>
            <button class="logout-btn" id="logoutBtn">Выйти</button>
        </div>
    </div>
    
    <div class="tilda-container">
        <div class="tilda-sidebar">
            <div class="tilda-sidebar-title">Фильтр по дате</div>
            <div id="tildaFolderTree" class="tilda-folder-tree">
                <div class="loading">Загрузка...</div>
            </div>
        </div>
        
        <div class="tilda-main">
            <div class="tilda-toolbar">
                <button class="btn btn-primary" id="refreshBtn">🔄 Обновить</button>
                <span style="flex:1;"></span>
                <span style="font-size:12px; color:#64748b;">📌 Кликните по полю, чтобы скопировать значение</span>
            </div>
            
            <div class="tilda-orders-container" id="tildaOrdersContainer">
                <div class="loading">Загрузка заказов...</div>
            </div>
        </div>
    </div>
    
    <script>
        let currentFilters = { year: null, month: null };
        let isLoading = false;
        
        const tildaFolderTree = document.getElementById('tildaFolderTree');
        const tildaOrdersContainer = document.getElementById('tildaOrdersContainer');
        const refreshBtn = document.getElementById('refreshBtn');
        const logoutBtn = document.getElementById('logoutBtn');
        
        let collapsedCards = JSON.parse(localStorage.getItem('tilda_collapsed_cards') || '{}');
        
        function saveCollapsedState() {
            localStorage.setItem('tilda_collapsed_cards', JSON.stringify(collapsedCards));
        }
        
        function toggleCard(cardId) {
            const card = document.querySelector('.tilda-card[data-id="' + cardId + '"]');
            if (card) {
                card.classList.toggle('expanded');
                collapsedCards[cardId] = !card.classList.contains('expanded');
                saveCollapsedState();
                const icon = card.querySelector('.expand-icon');
                if (icon) {
                    icon.textContent = card.classList.contains('expanded') ? '▼' : '▶';
                }
            }
        }
        
        async function loadFolders() {
            try {
                const response = await fetch('/api/tilda/folders');
                const result = await response.json();
                if (result.success) renderFolders(result.data);
            } catch(e) {
                tildaFolderTree.innerHTML = '<div class="loading">Ошибка</div>';
            }
        }
        
        function renderFolders(folders) {
            if (!folders || folders.length === 0) {
                tildaFolderTree.innerHTML = '<div class="loading">Нет заказов</div>';
                return;
            }
            
            let html = '<ul class="tilda-folder-tree">';
            for (const year of folders) {
                const yearActive = currentFilters.year == year.year ? 'active' : '';
                html += '<li class="tilda-folder-year">';
                html += '<div class="tilda-folder-link ' + yearActive + '" data-year="' + year.year + '">📁 ' + year.year + '</div>';
                if (year.months && year.months.length > 0) {
                    html += '<ul class="tilda-folder-months">';
                    for (const month of year.months) {
                        const monthActive = (currentFilters.year == year.year && currentFilters.month == month.month) ? 'active' : '';
                        html += '<li class="tilda-folder-month">';
                        html += '<div class="tilda-folder-link ' + monthActive + '" data-year="' + year.year + '" data-month="' + month.month + '">📄 ' + month.month_name + ' (' + month.count + ')</div>';
                        html += '</li>';
                    }
                    html += '</ul>';
                }
                html += '</li>';
            }
            html += '</ul>';
            tildaFolderTree.innerHTML = html;
            
            document.querySelectorAll('.tilda-folder-link[data-year]').forEach(el => {
                el.addEventListener('click', () => {
                    const year = el.dataset.year;
                    const month = el.dataset.month;
                    if (month) {
                        currentFilters.year = year;
                        currentFilters.month = month;
                    } else {
                        currentFilters.year = year;
                        currentFilters.month = null;
                    }
                    loadOrders();
                    loadFolders();
                });
            });
        }
        
        async function loadOrders() {
            if (isLoading) return;
            isLoading = true;
            try {
                tildaOrdersContainer.innerHTML = '<div class="loading">Загрузка...</div>';
                let url = '/api/tilda/orders?t=' + Date.now();
                if (currentFilters.year) url += '&year=' + currentFilters.year;
                if (currentFilters.month) url += '&month=' + currentFilters.month;
                const response = await fetch(url);
                const result = await response.json();
                if (result.success) {
                    renderOrders(result.data);
                } else {
                    tildaOrdersContainer.innerHTML = '<div class="loading">Ошибка загрузки</div>';
                }
            } catch(e) {
                tildaOrdersContainer.innerHTML = '<div class="loading">Ошибка загрузки</div>';
            } finally {
                isLoading = false;
            }
        }
        
        function formatDateTime(dateStr) {
            if (!dateStr) return 'Дата неизвестна';
            const date = new Date(dateStr);
            return date.toLocaleString('ru-RU', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
        
        function formatDateForTitle(dateStr) {
            if (!dateStr) return 'Неизвестная дата';
            const date = new Date(dateStr);
            return date.toLocaleString('ru-RU', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
        
        function copyToClipboard(text, element) {
            navigator.clipboard.writeText(text).then(() => {
                element.classList.add('copied');
                setTimeout(() => {
                    element.classList.remove('copied');
                }, 1000);
            });
        }
        
        async function markAsProcessed(orderId) {
            try {
                const response = await fetch('/api/tilda/orders/' + orderId + '/mark-read', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' }
                });
                const result = await response.json();
                if (result.success) {
                    loadOrders();
                    loadFolders();
                } else {
                    alert(result.error || 'Ошибка');
                }
            } catch(e) {
                alert('Ошибка');
            }
        }
        
        async function deleteOrder(orderId) {
            if (!confirm('Удалить этот заказ?')) return;
            try {
                const response = await fetch('/api/tilda/orders/' + orderId, { method: 'DELETE' });
                const result = await response.json();
                if (result.success) {
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
                tildaOrdersContainer.innerHTML = '<div class="empty">Нет заказов из Tilda</div>';
                return;
            }
            
            let html = '';
            for (const order of orders) {
                const parsed = order.parsed;
                const statusBadge = order.is_processed ? 
                    '<span class="badge badge-processed">✓ Обработан</span>' : 
                    '<span class="badge badge-new">🆕 Новый</span>';
                
                const isCollapsed = collapsedCards[order.id] === true;
                const expandedClass = isCollapsed ? '' : 'expanded';
                const expandIcon = isCollapsed ? '▶' : '▼';
                
                html += '<div class="tilda-card ' + expandedClass + '" data-id="' + order.id + '">';
                html += '<div class="tilda-card-header" data-id="' + order.id + '">';
                html += '<div class="tilda-card-title">';
                html += '<span class="expand-icon">' + expandIcon + '</span>';
                html += '<span class="order-label">Заказ от ' + formatDateForTitle(order.created_at) + '</span>';
                html += statusBadge;
                html += '</div>';
                html += '<div class="tilda-card-actions">';
                if (!order.is_processed) {
                    html += '<button class="btn btn-success btn-sm mark-processed" data-id="' + order.id + '">✓ Обработано</button>';
                }
                html += '<button class="btn btn-danger btn-sm delete-order" data-id="' + order.id + '">🗑️ Удалить</button>';
                html += '</div>';
                html += '</div>';
                
                html += '<div class="tilda-card-body">';
                
                html += '<div class="tilda-card-fields">';
                
                html += '<div class="tilda-field">';
                html += '<div class="tilda-field-label">👤 Имя получателя</div>';
                html += '<div class="tilda-field-value" data-copy="' + escapeHtml(parsed.customer_name) + '">' + escapeHtml(parsed.customer_name) + '</div>';
                html += '</div>';
                
                html += '<div class="tilda-field">';
                html += '<div class="tilda-field-label">📞 Телефон</div>';
                html += '<div class="tilda-field-value" data-copy="' + escapeHtml(parsed.phone) + '">' + escapeHtml(parsed.phone) + '</div>';
                html += '</div>';
                
                html += '<div class="tilda-field">';
                html += '<div class="tilda-field-label">✉️ E-mail</div>';
                html += '<div class="tilda-field-value" data-copy="' + escapeHtml(parsed.email) + '">' + escapeHtml(parsed.email) + '</div>';
                html += '</div>';
                
                html += '<div class="tilda-field">';
                html += '<div class="tilda-field-label">🚚 Служба доставки</div>';
                html += '<div class="tilda-field-value" data-copy="' + escapeHtml(parsed.delivery_service) + '">' + escapeHtml(parsed.delivery_service) + '</div>';
                html += '</div>';
                
                html += '<div class="tilda-field">';
                html += '<div class="tilda-field-label">🏙️ Город</div>';
                html += '<div class="tilda-field-value" data-copy="' + escapeHtml(parsed.delivery_city) + '">' + escapeHtml(parsed.delivery_city) + '</div>';
                html += '</div>';
                
                html += '<div class="tilda-field">';
                html += '<div class="tilda-field-label">📍 Адрес</div>';
                html += '<div class="tilda-field-value" data-copy="' + escapeHtml(parsed.delivery_address) + '">' + escapeHtml(parsed.delivery_address) + '</div>';
                html += '</div>';
                
                html += '<div class="tilda-field tilda-field-fullwidth">';
                html += '<div class="tilda-field-label">💬 Комментарий</div>';
                html += '<div class="tilda-field-value" data-copy="' + escapeHtml(parsed.comment) + '">' + escapeHtml(parsed.comment) + '</div>';
                html += '</div>';
                
                html += '</div>';
                
                if (parsed.items && parsed.items.length > 0) {
                    html += '<div class="tilda-items-table-container">';
                    html += '<table class="tilda-items-table">';
                    html += '<thead><tr><th>Наименование</th><th>Формат</th><th>Упаковка</th></tr></thead>';
                    html += '<tbody>';
                    for (const item of parsed.items) {
                        html += '<tr>';
                        html += '<td>' + escapeHtml(item.name) + '</td>';
                        html += '<td>' + escapeHtml(item.format || '—') + '</td>';
                        html += '<td>' + escapeHtml(item.packing || '—') + '</td>';
                        html += '</tr>';
                    }
                    html += '</tbody></table>';
                    html += '</div>';
                }
                
                html += '</div>';
                html += '</div>';
            }
            
            tildaOrdersContainer.innerHTML = html;
            
            document.querySelectorAll('.tilda-card-header').forEach(header => {
                const cardId = header.dataset.id;
                header.addEventListener('click', (e) => {
                    if (e.target.tagName === 'BUTTON') return;
                    toggleCard(cardId);
                });
            });
            
            document.querySelectorAll('.tilda-field-value').forEach(el => {
                el.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const text = el.dataset.copy;
                    if (text && text !== '—') {
                        copyToClipboard(text, el);
                    }
                });
            });
            
            document.querySelectorAll('.mark-processed').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    markAsProcessed(btn.dataset.id);
                });
            });
            
            document.querySelectorAll('.delete-order').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    deleteOrder(btn.dataset.id);
                });
            });
        }
        
        function escapeHtml(str) {
            if (!str) return '';
            return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
        }
        
        refreshBtn.addEventListener('click', () => { loadOrders(); loadFolders(); });
        
        logoutBtn.addEventListener('click', async () => {
            await fetch('/api/auth/logout', { method: 'POST' });
            window.location.href = '/login';
        });
        
        loadFolders();
        loadOrders();
        setInterval(() => { loadOrders(); loadFolders(); }, 30000);
    </script>
</body>
</html>