<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админка - Nerd</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <div class="header">
        <div class="logo">
            <h2>Nerd Админка</h2>
        </div>
        <div class="user-info">
            <span class="user-name"><?php echo htmlspecialchars($user['name']); ?></span>
            <span class="role-badge">Администратор</span>
            <a href="/profile" class="btn" style="text-decoration: none;">👤 Профиль</a>
            <button class="btn" id="backBtn">← К заказам</button>
            <button class="btn" id="logoutBtn">Выйти</button>
        </div>
    </div>
    
    <div class="admin-wrapper">
        <div class="admin-sidebar">
            <ul class="admin-menu">
                <li><a data-section="users">👥 Пользователи</a></li>
                <li><a data-section="products">📦 Товары</a></li>
                <li><a data-section="formats">🏷️ Форматы</a></li>
                <li><a data-section="sources">📡 Источники</a></li>
                <li><a data-section="shipping">🚚 Способы доставки</a></li>
                <li><a data-section="settings">⚙️ Настройки</a></li>
            </ul>
        </div>
        
        <div class="admin-content">
            <!-- Пользователи -->
            <div id="usersSection" class="section">
                <div class="section-header">
                    <h1 class="section-title">👥 Управление пользователями</h1>
                    <button class="btn btn-primary" id="addUserBtn">+ Добавить пользователя</button>
                </div>
                <div class="data-table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Имя</th>
                                <th>Роль</th>
                                <th>Дата создания</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody id="usersTableBody">
                            <tr><td colspan="5" class="loading">Загрузка...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Товары -->
            <div id="productsSection" class="section">
                <div class="section-header">
                    <h1 class="section-title">📦 Управление товарами</h1>
                    <div>
                        <button class="btn btn-secondary" id="importProductsBtn">📥 Импорт CSV</button>
                        <button class="btn btn-secondary" id="exportProductsBtn">📤 Экспорт CSV</button>
                        <button class="btn btn-primary" id="addProductBtn">+ Добавить товар</button>
                    </div>
                </div>
                <div class="import-area" id="importArea" style="display: none;">
                    <p>Выберите файл CSV для импорта</p>
                    <input type="file" id="importFile" accept=".csv">
                    <button class="btn btn-primary btn-sm" id="confirmImportBtn">Импортировать</button>
                    <button class="btn btn-secondary btn-sm" id="cancelImportBtn">Отмена</button>
                </div>
                <div class="data-table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Наименование</th>
                                <th>Краткое описание</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody id="productsTableBody">
                            <tr><td colspan="4" class="loading">Загрузка...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Форматы -->
            <div id="formatsSection" class="section">
                <div class="section-header">
                    <h1 class="section-title">🏷️ Управление форматами</h1>
                    <button class="btn btn-primary" id="addFormatBtn">+ Добавить формат</button>
                </div>
                <div class="data-table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Название</th>
                                <th>Цена</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody id="formatsTableBody">
                            <tr><td colspan="4" class="loading">Загрузка...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Источники -->
            <div id="sourcesSection" class="section">
                <div class="section-header">
                    <h1 class="section-title">📡 Управление источниками заказов</h1>
                    <button class="btn btn-primary" id="addSourceBtn">+ Добавить источник</button>
                </div>
                <div class="data-table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Название</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody id="sourcesTableBody">
                            <tr><td colspan="3" class="loading">Загрузка...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Способы доставки -->
            <div id="shippingSection" class="section">
                <div class="section-header">
                    <h1 class="section-title">🚚 Управление способами доставки</h1>
                    <button class="btn btn-primary" id="addShippingBtn">+ Добавить способ</button>
                </div>
                <div class="data-table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Название</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody id="shippingTableBody">
                            <tr><td colspan="3" class="loading">Загрузка...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Настройки -->
            <div id="settingsSection" class="section">
                <div class="section-header">
                    <h1 class="section-title">⚙️ Настройки системы</h1>
                </div>
                <div class="stat-cards">
                    <div class="stat-card">
                        <h3>Счётчик заказов</h3>
                        <div class="value" id="orderCounter">--</div>
                    </div>
                </div>
                <div class="import-area">
                    <p>Сбросить счётчик заказов (следующий заказ будет №1)</p>
                    <button class="btn btn-danger" id="resetCounterBtn">Сбросить счётчик</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Модальное окно -->
    <div id="modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="modalTitle">Заголовок</h3>
                <button class="close-modal">&times;</button>
            </div>
            <div id="modalBody"></div>
            <div class="modal-footer">
                <button class="btn btn-secondary" id="cancelModalBtn">Отмена</button>
                <button class="btn btn-primary" id="saveModalBtn">Сохранить</button>
            </div>
        </div>
    </div>
    
    <script>
        let currentSection = 'users';
        let currentEditId = null;
        let currentEntity = null;
        
        const sections = ['users', 'products', 'formats', 'sources', 'shipping', 'settings'];
        
        async function loadOrderCounter() {
            try {
                const response = await fetch('/api/admin/settings');
                const result = await response.json();
                if (result.success) {
                    document.getElementById('orderCounter').textContent = result.data.order_counter;
                }
            } catch (error) {
                console.error('Ошибка загрузки счётчика:', error);
            }
        }
        
        async function resetOrderCounter() {
            if (!confirm('Вы уверены? Следующий заказ будет №1. Старые заказы сохранят свои номера.')) return;
            try {
                const response = await fetch('/api/admin/reset-order-counter', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ confirm: true })
                });
                const result = await response.json();
                if (result.success) {
                    alert(result.message);
                    loadOrderCounter();
                } else {
                    alert(result.error);
                }
            } catch (error) {
                alert('Ошибка сброса счётчика');
            }
        }
        
        async function loadUsers() { await loadEntity('users', '/api/users', renderUsersTable); }
        async function loadProducts() { await loadEntity('products', '/api/products', renderProductsTable); }
        async function loadFormats() { await loadEntity('formats', '/api/formats', renderFormatsTable); }
        async function loadSources() { await loadEntity('sources', '/api/sources', renderSourcesTable); }
        async function loadShipping() { await loadEntity('shipping', '/api/shipping-methods', renderShippingTable); }
        
        async function loadEntity(entity, url, renderFn) {
            try {
                const response = await fetch(url);
                const result = await response.json();
                if (result.success) {
                    renderFn(result.data);
                }
            } catch (error) {
                console.error('Ошибка загрузки ' + entity + ':', error);
            }
        }
        
        function renderUsersTable(users) {
            const tbody = document.getElementById('usersTableBody');
            if (!users || users.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="loading">Нет пользователей</td></tr>';
                return;
            }
            let html = '';
            for (const user of users) {
                html += '<tr>';
                html += '<td>' + user.id + '</td>';
                html += '<td>' + escapeHtml(user.name) + '</td>';
                html += '<td>' + (user.role === 'admin' ? 'Админ' : 'Менеджер') + '</td>';
                html += '<td>' + (user.created_at || '') + '</td>';
                html += '<td class="action-buttons"><button class="btn btn-secondary btn-sm" onclick="editUser(' + user.id + ')">✏️</button><button class="btn btn-danger btn-sm" onclick="deleteUser(' + user.id + ')">🗑️</button></td>';
                html += '</tr>';
            }
            tbody.innerHTML = html;
        }
        
        function renderProductsTable(products) {
            const tbody = document.getElementById('productsTableBody');
            if (!products || products.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" class="loading">Нет товаров</td></tr>';
                return;
            }
            let html = '';
            for (const product of products) {
                html += '<tr>';
                html += '<td>' + product.id + '</td>';
                html += '<td>' + escapeHtml(product.name) + '</td>';
                html += '<td>' + escapeHtml(product.short_description || '') + '</td>';
                html += '<td class="action-buttons"><button class="btn btn-secondary btn-sm" onclick="editProduct(' + product.id + ')">✏️</button><button class="btn btn-danger btn-sm" onclick="deleteProduct(' + product.id + ')">🗑️</button></td>';
                html += '</tr>';
            }
            tbody.innerHTML = html;
        }
        
        function renderFormatsTable(formats) {
            const tbody = document.getElementById('formatsTableBody');
            if (!formats || formats.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" class="loading">Нет форматов</td></tr>';
                return;
            }
            let html = '';
            for (const format of formats) {
                html += '<tr>';
                html += '<td>' + format.id + '</td>';
                html += '<td>' + escapeHtml(format.name) + '</td>';
                html += '<td>' + format.price + ' ₽</td>';
                html += '<td class="action-buttons"><button class="btn btn-secondary btn-sm" onclick="editFormat(' + format.id + ')">✏️</button><button class="btn btn-danger btn-sm" onclick="deleteFormat(' + format.id + ')">🗑️</button></td>';
                html += '</tr>';
            }
            tbody.innerHTML = html;
        }
        
        function renderSourcesTable(sources) {
            const tbody = document.getElementById('sourcesTableBody');
            if (!sources || sources.length === 0) {
                tbody.innerHTML = '<tr><td colspan="3" class="loading">Нет источников</td></tr>';
                return;
            }
            let html = '';
            for (const source of sources) {
                html += '<tr>';
                html += '<td>' + source.id + '</td>';
                html += '<td>' + escapeHtml(source.name) + '</td>';
                html += '<td class="action-buttons"><button class="btn btn-secondary btn-sm" onclick="editSource(' + source.id + ')">✏️</button><button class="btn btn-danger btn-sm" onclick="deleteSource(' + source.id + ')">🗑️</button></td>';
                html += '</tr>';
            }
            tbody.innerHTML = html;
        }
        
        function renderShippingTable(methods) {
            const tbody = document.getElementById('shippingTableBody');
            if (!methods || methods.length === 0) {
                tbody.innerHTML = '<tr><td colspan="3" class="loading">Нет способов доставки</td></tr>';
                return;
            }
            let html = '';
            for (const method of methods) {
                html += '<tr>';
                html += '<td>' + method.id + '</td>';
                html += '<td>' + escapeHtml(method.name) + '</td>';
                html += '<td class="action-buttons"><button class="btn btn-secondary btn-sm" onclick="editShipping(' + method.id + ')">✏️</button><button class="btn btn-danger btn-sm" onclick="deleteShipping(' + method.id + ')">🗑️</button></td>';
                html += '</tr>';
            }
            tbody.innerHTML = html;
        }
        
        window.editUser = (id) => openModal('user', id);
        window.editProduct = (id) => openModal('product', id);
        window.editFormat = (id) => openModal('format', id);
        window.editSource = (id) => openModal('source', id);
        window.editShipping = (id) => openModal('shipping', id);
        
        async function deleteUser(id) { await deleteEntity(id, '/api/users', 'пользователя', loadUsers); }
        async function deleteProduct(id) { await deleteEntity(id, '/api/products', 'товар', loadProducts); }
        async function deleteFormat(id) { await deleteEntity(id, '/api/formats', 'формат', loadFormats); }
        async function deleteSource(id) { await deleteEntity(id, '/api/sources', 'источник', loadSources); }
        async function deleteShipping(id) { await deleteEntity(id, '/api/shipping-methods', 'способ доставки', loadShipping); }
        
        async function deleteEntity(id, url, name, reloadFn) {
            if (!confirm('Удалить ' + name + '?')) return;
            try {
                const response = await fetch(url + '/' + id, { method: 'DELETE' });
                const result = await response.json();
                if (result.success) {
                    alert(result.message);
                    reloadFn();
                } else {
                    alert(result.error);
                }
            } catch (error) {
                alert('Ошибка удаления');
            }
        }
        
        function openModal(entity, id = null) {
            currentEntity = entity;
            currentEditId = id;
            const modal = document.getElementById('modal');
            const modalTitle = document.getElementById('modalTitle');
            const modalBody = document.getElementById('modalBody');
            
            if (id) {
                modalTitle.textContent = 'Редактировать ' + getEntityName(entity);
                loadEntityData(entity, id);
            } else {
                modalTitle.textContent = 'Добавить ' + getEntityName(entity);
                showEntityForm(entity, null);
            }
            modal.classList.add('show');
        }
        
        function getEntityName(entity) {
            const names = { user: 'пользователя', product: 'товар', format: 'формат', source: 'источник', shipping: 'способ доставки' };
            return names[entity] || entity;
        }
        
        async function loadEntityData(entity, id) {
            const urls = {
                user: '/api/users/' + id,
                product: '/api/products/' + id,
                format: '/api/formats/' + id,
                source: '/api/sources/' + id,
                shipping: '/api/shipping-methods/' + id
            };
            try {
                const response = await fetch(urls[entity]);
                const result = await response.json();
                if (result.success) {
                    showEntityForm(entity, result.data);
                } else {
                    alert('Ошибка загрузки данных');
                    closeModal();
                }
            } catch (error) {
                console.error('Ошибка загрузки данных:', error);
                alert('Ошибка загрузки данных');
                closeModal();
            }
        }
        
        function showEntityForm(entity, data) {
            const modalBody = document.getElementById('modalBody');
            let html = '';
            
            if (entity === 'user') {
                html = '<div class="form-group"><label>Имя</label><input type="text" id="userName" value="' + escapeHtml(data?.name || '') + '"></div>';
                html += '<div class="form-group"><label>Пароль ' + (data ? '(оставьте пустым, чтобы не менять)' : '') + '</label><input type="password" id="userPassword"></div>';
                html += '<div class="form-group"><label>Роль</label><select id="userRole">';
                html += '<option value="manager"' + (data?.role === 'manager' ? ' selected' : '') + '>Менеджер</option>';
                html += '<option value="admin"' + (data?.role === 'admin' ? ' selected' : '') + '>Админ</option>';
                html += '</select></div>';
            } 
            else if (entity === 'product') {
                html = '<div class="form-group"><label>Наименование</label><input type="text" id="productName" value="' + escapeHtml(data?.name || '') + '"></div>';
                html += '<div class="form-group"><label>Краткое описание</label><textarea id="productShortDesc" rows="3">' + escapeHtml(data?.short_description || '') + '</textarea></div>';
                html += '<div class="form-group"><label>Полное описание</label><textarea id="productFullDesc" rows="5">' + escapeHtml(data?.full_description || '') + '</textarea></div>';
            } 
            else if (entity === 'format') {
                html = '<div class="form-group"><label>Название формата</label><input type="text" id="formatName" value="' + escapeHtml(data?.name || '') + '"></div>';
                html += '<div class="form-group"><label>Цена</label><input type="number" step="0.01" id="formatPrice" value="' + (data?.price || 0) + '"></div>';
            } 
            else if (entity === 'source') {
                html = '<div class="form-group"><label>Название источника</label><input type="text" id="sourceName" value="' + escapeHtml(data?.name || '') + '"></div>';
            } 
            else if (entity === 'shipping') {
                html = '<div class="form-group"><label>Название способа доставки</label><input type="text" id="shippingName" value="' + escapeHtml(data?.name || '') + '"></div>';
            }
            
            modalBody.innerHTML = html;
        }
        
        async function saveModal() {
            const modal = document.getElementById('modal');
            let url = '', method = 'POST', body = {};
            
            if (currentEntity === 'user') {
                body = { name: document.getElementById('userName').value, role: document.getElementById('userRole').value };
                const password = document.getElementById('userPassword').value;
                if (password) body.password = password;
                url = currentEditId ? '/api/users/' + currentEditId : '/api/users';
                method = currentEditId ? 'PUT' : 'POST';
            } else if (currentEntity === 'product') {
                body = { 
                    name: document.getElementById('productName').value, 
                    short_description: document.getElementById('productShortDesc').value, 
                    full_description: document.getElementById('productFullDesc').value 
                };
                url = currentEditId ? '/api/products/' + currentEditId : '/api/products';
                method = currentEditId ? 'PUT' : 'POST';
            } else if (currentEntity === 'format') {
                body = { 
                    name: document.getElementById('formatName').value, 
                    price: parseFloat(document.getElementById('formatPrice').value) 
                };
                url = currentEditId ? '/api/formats/' + currentEditId : '/api/formats';
                method = currentEditId ? 'PUT' : 'POST';
            } else if (currentEntity === 'source') {
                body = { name: document.getElementById('sourceName').value };
                url = currentEditId ? '/api/sources/' + currentEditId : '/api/sources';
                method = currentEditId ? 'PUT' : 'POST';
            } else if (currentEntity === 'shipping') {
                body = { name: document.getElementById('shippingName').value };
                url = currentEditId ? '/api/shipping-methods/' + currentEditId : '/api/shipping-methods';
                method = currentEditId ? 'PUT' : 'POST';
            }
            
            try {
                const response = await fetch(url, { 
                    method: method, 
                    headers: { 'Content-Type': 'application/json' }, 
                    body: JSON.stringify(body) 
                });
                const result = await response.json();
                if (result.success) {
                    alert(result.message);
                    modal.classList.remove('show');
                    if (currentSection === 'users') loadUsers();
                    else if (currentSection === 'products') loadProducts();
                    else if (currentSection === 'formats') loadFormats();
                    else if (currentSection === 'sources') loadSources();
                    else if (currentSection === 'shipping') loadShipping();
                } else {
                    alert(result.error || 'Ошибка сохранения');
                }
            } catch (error) {
                console.error('Ошибка:', error);
                alert('Ошибка сохранения');
            }
        }
        
        // Импорт товаров
        document.getElementById('importProductsBtn')?.addEventListener('click', () => {
            document.getElementById('importArea').style.display = 'block';
        });
        document.getElementById('cancelImportBtn')?.addEventListener('click', () => {
            document.getElementById('importArea').style.display = 'none';
            document.getElementById('importFile').value = '';
        });
        document.getElementById('confirmImportBtn')?.addEventListener('click', async () => {
            const file = document.getElementById('importFile').files[0];
            if (!file) { alert('Выберите файл'); return; }
            const formData = new FormData();
            formData.append('file', file);
            try {
                const response = await fetch('/api/products/import', { method: 'POST', body: formData });
                const result = await response.json();
                alert(result.message || result.error);
                document.getElementById('importArea').style.display = 'none';
                document.getElementById('importFile').value = '';
                loadProducts();
            } catch (error) {
                alert('Ошибка импорта');
            }
        });
        
        // Экспорт товаров
        document.getElementById('exportProductsBtn')?.addEventListener('click', () => {
            window.open('/api/products/export', '_blank');
        });
        
        function switchSection(section) {
            sections.forEach(s => {
                const el = document.getElementById(s + 'Section');
                if (el) el.classList.remove('active');
            });
            document.getElementById(section + 'Section').classList.add('active');
            document.querySelectorAll('.admin-menu a').forEach(a => a.classList.remove('active'));
            document.querySelector('.admin-menu a[data-section="' + section + '"]').classList.add('active');
            currentSection = section;
            if (section === 'users') loadUsers();
            else if (section === 'products') loadProducts();
            else if (section === 'formats') loadFormats();
            else if (section === 'sources') loadSources();
            else if (section === 'shipping') loadShipping();
            else if (section === 'settings') { loadOrderCounter(); }
        }
        
        document.querySelectorAll('.admin-menu a').forEach(link => {
            link.addEventListener('click', () => switchSection(link.dataset.section));
        });
        
        document.getElementById('backBtn')?.addEventListener('click', () => window.location.href = '/dashboard');
        document.getElementById('logoutBtn')?.addEventListener('click', async () => {
            await fetch('/api/auth/logout', { method: 'POST' });
            window.location.href = '/login';
        });
        document.getElementById('resetCounterBtn')?.addEventListener('click', resetOrderCounter);
        document.getElementById('addUserBtn')?.addEventListener('click', () => openModal('user'));
        document.getElementById('addProductBtn')?.addEventListener('click', () => openModal('product'));
        document.getElementById('addFormatBtn')?.addEventListener('click', () => openModal('format'));
        document.getElementById('addSourceBtn')?.addEventListener('click', () => openModal('source'));
        document.getElementById('addShippingBtn')?.addEventListener('click', () => openModal('shipping'));
        
        document.querySelector('.close-modal')?.addEventListener('click', () => document.getElementById('modal').classList.remove('show'));
        document.getElementById('cancelModalBtn')?.addEventListener('click', () => document.getElementById('modal').classList.remove('show'));
        document.getElementById('saveModalBtn')?.addEventListener('click', saveModal);
        
        function escapeHtml(str) { 
            if (!str) return ''; 
            return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;'); 
        }
        
        switchSection('users');
    </script>
</body>
</html>