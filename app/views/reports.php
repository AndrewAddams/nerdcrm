<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Отчёты - Nerd</title>
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
            <a href="/dashboard" class="btn btn-secondary" style="text-decoration: none;">📋 Заказы</a>
            <a href="/profile" class="btn btn-secondary" style="text-decoration: none;">👤 Профиль</a>
            <?php if ($user['role'] === 'admin'): ?>
                <a href="/admin" class="admin-link">⚙️ Админка</a>
            <?php endif; ?>
            <button class="logout-btn" id="logoutBtn">Выйти</button>
        </div>
    </div>
    
    <div class="reports-page">
        <div class="page-header">
            <h1 class="page-title">📊 Отчёты</h1>
            <p class="page-description">Выберите тип отчёта для анализа данных по заказам</p>
        </div>
        
        <div class="reports-grid">
            <!-- Отчёт 1 -->
            <div class="report-card" data-report="revenue">
                <div class="report-icon">💰</div>
                <h3 class="report-title">Выручка по постановщикам</h3>
                <p class="report-description">Количество заказов, средний чек и выручка по каждому менеджеру. Общий итог за период.</p>
            </div>
            
            <!-- Отчёт 2 -->
            <div class="report-card" data-report="customers">
                <div class="report-icon">👥</div>
                <h3 class="report-title">Заказы по покупателям</h3>
                <p class="report-description">Количество заказов от каждого уникального покупателя за период.</p>
            </div>
            
            <!-- Отчёт 3 -->
            <div class="report-card" data-report="products">
                <div class="report-icon">📦</div>
                <h3 class="report-title">Популярные товары</h3>
                <p class="report-description">Топ товаров по количеству заказов, в которых они встречались.</p>
            </div>
            
            <!-- Отчёт 4 -->
            <div class="report-card" data-report="formats">
                <div class="report-icon">🏷️</div>
                <h3 class="report-title">Популярные форматы</h3>
                <p class="report-description">Топ форматов товаров по количеству заказов.</p>
            </div>
            
            <!-- Отчёт 5 -->
            <div class="report-card" data-report="labels">
                <div class="report-icon">🔖</div>
                <h3 class="report-title">Первичные / Вторичные продажи</h3>
                <p class="report-description">Соотношение первичных и вторичных продаж за период.</p>
            </div>
        </div>
        
        <a href="/dashboard" class="back-link">← Вернуться к заказам</a>
    </div>
    
    <!-- Модальное окно для отчётов -->
    <div id="reportModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="modalTitle">Заголовок отчёта</h3>
                <button class="close-modal" id="closeModalBtn">&times;</button>
            </div>
            
            <div class="filter-form">
                <div class="filter-group">
                    <label>Дата с</label>
                    <input type="date" id="dateFrom" class="date-input">
                </div>
                <div class="filter-group">
                    <label>Дата по</label>
                    <input type="date" id="dateTo" class="date-input">
                </div>
                <div class="filter-group">
                    <button class="btn btn-primary" id="generateBtn">Сформировать</button>
                </div>
            </div>
            
            <div class="results-container" id="resultsContainer">
                <div class="loading">Выберите период и нажмите "Сформировать"</div>
            </div>
        </div>
    </div>
    
    <script>
        let currentReportType = null;
        
        const modal = document.getElementById('reportModal');
        const modalTitle = document.getElementById('modalTitle');
        const dateFromInput = document.getElementById('dateFrom');
        const dateToInput = document.getElementById('dateTo');
        const generateBtn = document.getElementById('generateBtn');
        const resultsContainer = document.getElementById('resultsContainer');
        const closeModalBtn = document.getElementById('closeModalBtn');
        const logoutBtn = document.getElementById('logoutBtn');
        
        function setDefaultDates() {
            const today = new Date();
            const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
            dateFromInput.value = formatDate(firstDay);
            dateToInput.value = formatDate(today);
        }
        
        function formatDate(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return year + '-' + month + '-' + day;
        }
        
        function openReportModal(reportType) {
            currentReportType = reportType;
            
            const titles = {
                'revenue': '💰 Выручка по постановщикам',
                'customers': '👥 Заказы по покупателям',
                'products': '📦 Популярные товары',
                'formats': '🏷️ Популярные форматы',
                'labels': '🔖 Первичные / Вторичные продажи'
            };
            
            modalTitle.textContent = titles[reportType] || 'Отчёт';
            setDefaultDates();
            resultsContainer.innerHTML = '<div class="loading">Выберите период и нажмите "Сформировать"</div>';
            modal.classList.add('show');
        }
        
        async function generateReport() {
            const dateFrom = dateFromInput.value;
            const dateTo = dateToInput.value;
            
            if (!dateFrom || !dateTo) {
                alert('Выберите период');
                return;
            }
            
            resultsContainer.innerHTML = '<div class="loading">Загрузка...</div>';
            
            let url = '';
            let body = { date_from: dateFrom, date_to: dateTo };
            
            switch (currentReportType) {
                case 'revenue':
                    url = '/api/reports/revenue-by-assigner';
                    break;
                case 'customers':
                    url = '/api/reports/orders-by-customer';
                    break;
                case 'products':
                    url = '/api/reports/popular-products';
                    break;
                case 'formats':
                    url = '/api/reports/popular-formats';
                    break;
                case 'labels':
                    url = '/api/reports/sales-by-label';
                    break;
                default:
                    return;
            }
            
            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(body)
                });
                const result = await response.json();
                
                if (result.success) {
                    renderReport(result.data);
                } else {
                    resultsContainer.innerHTML = '<div class="empty">Ошибка загрузки данных: ' + (result.error || 'неизвестная ошибка') + '</div>';
                }
            } catch (error) {
                console.error('Ошибка:', error);
                resultsContainer.innerHTML = '<div class="empty">Ошибка загрузки данных</div>';
            }
        }
        
        function renderReport(data) {
            if (!data || (Array.isArray(data) && data.length === 0) || (data.data && data.data.length === 0)) {
                resultsContainer.innerHTML = '<div class="empty">Нет данных за выбранный период</div>';
                return;
            }
            
            let html = '<table class="results-table">';
            
            switch (currentReportType) {
                case 'revenue':
                    html += '<thead><tr><th>Постановщик</th><th>Количество заказов</th><th>Средний чек</th><th>Выручка</th></tr></thead><tbody>';
                    if (data.data) {
                        for (const row of data.data) {
                            html += '<tr>';
                            html += '<td>' + escapeHtml(row.assigner_name || 'Не указан') + '</td>';
                            html += '<td>' + row.orders_count + '</tr>';
                            html += '<td>' + formatMoney(row.avg_check) + '</td>';
                            html += '<td>' + formatMoney(row.total_revenue) + '</td>';
                            html += '</tr>';
                        }
                        if (data.total) {
                            html += '<tr class="total-row">';
                            html += '<td><strong>' + escapeHtml(data.total.assigner_name) + '</strong></td>';
                            html += '<td><strong>' + data.total.orders_count + '</strong></td>';
                            html += '<td><strong>' + formatMoney(data.total.avg_check) + '</strong></td>';
                            html += '<td><strong>' + formatMoney(data.total.total_revenue) + '</strong></td>';
                            html += '</tr>';
                        }
                    }
                    break;
                    
                case 'customers':
                    html += '<thead><tr><th>Покупатель</th><th>Количество заказов</th></tr></thead><tbody>';
                    for (const row of data) {
                        html += '</tr>';
                        html += '<td>' + escapeHtml(row.customer_name_original || row.customer_name_normalized) + '</td>';
                        html += '<td>' + row.orders_count + '</td>';
                        html += '</tr>';
                    }
                    break;
                    
                case 'products':
                    html += '<thead><tr><th>Наименование товара</th><th>Количество заказов</th></tr></thead><tbody>';
                    for (const row of data) {
                        html += '<tr>';
                        html += '<td>' + escapeHtml(row.product_name) + '</td>';
                        html += '<td>' + row.orders_count + '</td>';
                        html += '</tr>';
                    }
                    break;
                    
                case 'formats':
                    html += '<thead><tr><th>Формат</th><th>Количество заказов</th></tr></thead><tbody>';
                    for (const row of data) {
                        html += '<tr>';
                        html += '<td>' + escapeHtml(row.format_name) + '</td>';
                        html += '<td>' + row.orders_count + '</td>';
                        html += '</tr>';
                    }
                    break;
                    
                case 'labels':
                    html += '<thead><tr><th>Метка продажи</th><th>Количество заказов</th></tr></thead><tbody>';
                    for (const row of data) {
                        html += '<tr>';
                        html += '<td>' + escapeHtml(row.sale_label) + '</td>';
                        html += '<td>' + row.orders_count + '</td>';
                        html += '</tr>';
                    }
                    break;
            }
            
            html += '<\/tbody><\/table>';
            resultsContainer.innerHTML = html;
        }
        
        function formatMoney(value) {
            if (value === null || value === undefined) return '0 ₽';
            return parseFloat(value).toFixed(2) + ' ₽';
        }
        
        function escapeHtml(str) {
            if (!str) return '';
            return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
        }
        
        function closeModal() {
            modal.classList.remove('show');
        }
        
        document.querySelectorAll('.report-card').forEach(card => {
            card.addEventListener('click', () => {
                openReportModal(card.dataset.report);
            });
        });
        
        generateBtn.addEventListener('click', generateReport);
        closeModalBtn.addEventListener('click', closeModal);
        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeModal();
        });
        
        logoutBtn.addEventListener('click', async () => {
            await fetch('/api/auth/logout', { method: 'POST' });
            window.location.href = '/login';
        });
        
        setDefaultDates();
    </script>
</body>
</html>