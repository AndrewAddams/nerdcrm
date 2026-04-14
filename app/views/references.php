<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Справочники - Nerd</title>
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
    
    <div class="references-container">
        <div class="references-sidebar">
            <div class="references-sidebar-title">Справочники</div>
            <ul class="references-menu">
                <li><a data-section="products">📦 Товары</a></li>
                <li><a data-section="formats">🏷️ Форматы</a></li>
                <li><a data-section="packaging">📦 Упаковка</a></li>
            </ul>
        </div>
        
        <div class="references-main">
            <!-- Товары -->
            <div id="productsSection" class="section">
                <div class="section-header">
                    <h1 class="section-title">📦 Товары</h1>
                    <div class="search-box">
                        <input type="text" id="productsSearch" placeholder="Поиск по названию..." autocomplete="off">
                        <button class="btn btn-secondary" id="productsSearchBtn">🔍 Найти</button>
                        <button class="btn" id="productsResetBtn">Сбросить</button>
                    </div>
                </div>
                <div id="productsContainer" class="cards-grid">
                    <div class="loading">Загрузка...</div>
                </div>
                <div id="productsPagination" class="pagination"></div>
            </div>
            
            <!-- Форматы -->
            <div id="formatsSection" class="section">
                <div class="section-header">
                    <h1 class="section-title">🏷️ Форматы</h1>
                </div>
                <div id="formatsContainer" class="cards-grid">
                    <div class="loading">Загрузка...</div>
                </div>
            </div>
            
            <!-- Упаковка -->
            <div id="packagingSection" class="section">
                <div class="section-header">
                    <h1 class="section-title">📦 Упаковка</h1>
                    <div>
                        <button class="btn btn-primary" id="addPackagingBtn">+ Добавить упаковку</button>
                    </div>
                </div>
                <div id="packagingContainer" class="cards-grid">
                    <div class="loading">Загрузка...</div>
                </div>
                <div id="packagingPagination" class="pagination"></div>
            </div>
        </div>
    </div>
    
    <!-- Модальное окно для упаковки -->
    <div id="packagingModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="packagingModalTitle">Добавить упаковку</h3>
                <button class="close-modal" id="closePackagingModal">&times;</button>
            </div>
            <div id="packagingModalBody">
                <div class="form-group">
                    <label>Наименование упаковки</label>
                    <input type="text" id="packagingName" required>
                </div>
                <div class="form-group">
                    <label>Размеры</label>
                    <input type="text" id="packagingDimensions" placeholder="например: 10x15x5 см" required>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" id="cancelPackagingBtn">Отмена</button>
                <button class="btn btn-primary" id="savePackagingBtn">Сохранить</button>
            </div>
        </div>
    </div>
    
    <script>
        let currentSection = 'products';
        let currentEditPackagingId = null;
        
        let productsPage = 1;
        let productsSearch = '';
        let productsTotalPages = 1;
        
        let packagingPage = 1;
        let packagingSearch = '';
        let packagingTotalPages = 1;
        
        const sections = ['products', 'formats', 'packaging'];
        
        async function loadProducts() {
            const container = document.getElementById('productsContainer');
            container.innerHTML = '<div class="loading">Загрузка...</div>';
            
            let url = '/api/products?page=' + productsPage + '&per_page=12';
            if (productsSearch) url += '&search=' + encodeURIComponent(productsSearch);
            
            try {
                const response = await fetch(url);
                const result = await response.json();
                if (result.success) {
                    renderProducts(result.data);
                    productsTotalPages = result.data.last_page || 1;
                    renderPagination('products', productsPage, productsTotalPages);
                } else {
                    container.innerHTML = '<div class="empty">Ошибка загрузки</div>';
                }
            } catch(e) {
                container.innerHTML = '<div class="empty">Ошибка загрузки</div>';
            }
        }
        
        function renderProducts(products) {
            const container = document.getElementById('productsContainer');
            if (!products || products.length === 0) {
                container.innerHTML = '<div class="empty">Нет товаров</div>';
                return;
            }
            
            let html = '';
            for (const product of products) {
                html += '<div class="card">';
                html += '<div class="card-header">';
                html += '<div class="card-title">' + escapeHtml(product.name) + '</div>';
                html += '</div>';
                html += '<div class="card-body">';
                html += '<div class="card-field">';
                html += '<div class="card-field-label">📝 Краткое описание</div>';
                html += '<div class="card-field-value">' + escapeHtml(product.short_description || '—') + '</div>';
                html += '</div>';
                html += '<div class="card-field">';
                html += '<div class="card-field-label">📄 Полное описание</div>';
                html += '<div class="card-field-value">' + nl2br(escapeHtml(product.full_description || '—')) + '</div>';
                html += '</div>';
                html += '</div>';
                html += '</div>';
            }
            container.innerHTML = html;
        }
        
        async function loadFormats() {
            const container = document.getElementById('formatsContainer');
            container.innerHTML = '<div class="loading">Загрузка...</div>';
            
            try {
                const response = await fetch('/api/formats');
                const result = await response.json();
                if (result.success) {
                    renderFormats(result.data);
                } else {
                    container.innerHTML = '<div class="empty">Ошибка загрузки</div>';
                }
            } catch(e) {
                container.innerHTML = '<div class="empty">Ошибка загрузки</div>';
            }
        }
        
        function renderFormats(formats) {
            const container = document.getElementById('formatsContainer');
            if (!formats || formats.length === 0) {
                container.innerHTML = '<div class="empty">Нет форматов</div>';
                return;
            }
            
            let html = '';
            for (const format of formats) {
                html += '<div class="card">';
                html += '<div class="card-header">';
                html += '<div class="card-title">' + escapeHtml(format.name) + '</div>';
                html += '</div>';
                html += '<div class="card-body">';
                html += '<div class="card-field">';
                html += '<div class="card-field-label">💰 Цена</div>';
                html += '<div class="card-field-value price">' + parseFloat(format.price).toFixed(2) + ' ₽</div>';
                html += '</div>';
                html += '</div>';
                html += '</div>';
            }
            container.innerHTML = html;
        }
        
        async function loadPackaging() {
            const container = document.getElementById('packagingContainer');
            container.innerHTML = '<div class="loading">Загрузка...</div>';
            
            let url = '/api/packaging?page=' + packagingPage + '&per_page=12';
            if (packagingSearch) url += '&search=' + encodeURIComponent(packagingSearch);
            
            try {
                const response = await fetch(url);
                const result = await response.json();
                if (result.success) {
                    renderPackaging(result.data.data);
                    packagingTotalPages = result.data.last_page || 1;
                    renderPagination('packaging', packagingPage, packagingTotalPages);
                } else {
                    container.innerHTML = '<div class="empty">Ошибка загрузки</div>';
                }
            } catch(e) {
                container.innerHTML = '<div class="empty">Ошибка загрузки</div>';
            }
        }
        
        function renderPackaging(items) {
            const container = document.getElementById('packagingContainer');
            if (!items || items.length === 0) {
                container.innerHTML = '<div class="empty">Нет упаковок</div>';
                return;
            }
            
            let html = '';
            for (const item of items) {
                html += '<div class="card">';
                html += '<div class="card-header">';
                html += '<div class="card-title">' + escapeHtml(item.name) + '</div>';
                html += '<div class="card-actions">';
                html += '<button class="btn btn-secondary btn-sm edit-packaging" data-id="' + item.id + '" data-name="' + escapeHtml(item.name) + '" data-dimensions="' + escapeHtml(item.dimensions) + '">✏️</button>';
                html += '<button class="btn btn-danger btn-sm delete-packaging" data-id="' + item.id + '">🗑️</button>';
                html += '</div>';
                html += '</div>';
                html += '<div class="card-body">';
                html += '<div class="card-field">';
                html += '<div class="card-field-label">📏 Размеры</div>';
                html += '<div class="card-field-value">' + escapeHtml(item.dimensions) + '</div>';
                html += '</div>';
                html += '</div>';
                html += '</div>';
            }
            container.innerHTML = html;
            
            document.querySelectorAll('.edit-packaging').forEach(btn => {
                btn.addEventListener('click', () => {
                    openPackagingModal(btn.dataset.id, btn.dataset.name, btn.dataset.dimensions);
                });
            });
            
            document.querySelectorAll('.delete-packaging').forEach(btn => {
                btn.addEventListener('click', () => deletePackaging(btn.dataset.id));
            });
        }
        
        function renderPagination(type, currentPage, totalPages) {
            const container = document.getElementById(type + 'Pagination');
            if (!container || totalPages <= 1) {
                if (container) container.innerHTML = '';
                return;
            }
            
            let html = '';
            html += '<button class="pagination-btn ' + (currentPage === 1 ? 'disabled' : '') + '" data-page="' + (currentPage - 1) + '" ' + (currentPage === 1 ? 'disabled' : '') + '>←</button>';
            
            let startPage = Math.max(1, currentPage - 2);
            let endPage = Math.min(totalPages, currentPage + 2);
            
            for (let i = startPage; i <= endPage; i++) {
                html += '<button class="pagination-btn ' + (i === currentPage ? 'active' : '') + '" data-page="' + i + '">' + i + '</button>';
            }
            
            html += '<button class="pagination-btn ' + (currentPage === totalPages ? 'disabled' : '') + '" data-page="' + (currentPage + 1) + '" ' + (currentPage === totalPages ? 'disabled' : '') + '>→</button>';
            
            container.innerHTML = html;
            
            container.querySelectorAll('.pagination-btn:not(.disabled)').forEach(btn => {
                btn.addEventListener('click', () => {
                    const page = parseInt(btn.dataset.page);
                    if (type === 'products') {
                        productsPage = page;
                        loadProducts();
                    } else if (type === 'packaging') {
                        packagingPage = page;
                        loadPackaging();
                    }
                });
            });
        }
        
        function openPackagingModal(id = null, name = '', dimensions = '') {
            currentEditPackagingId = id;
            const modal = document.getElementById('packagingModal');
            const title = document.getElementById('packagingModalTitle');
            const nameInput = document.getElementById('packagingName');
            const dimensionsInput = document.getElementById('packagingDimensions');
            
            if (id) {
                title.textContent = 'Редактировать упаковку';
                nameInput.value = name;
                dimensionsInput.value = dimensions;
            } else {
                title.textContent = 'Добавить упаковку';
                nameInput.value = '';
                dimensionsInput.value = '';
            }
            modal.classList.add('show');
        }
        
        async function savePackaging() {
            const name = document.getElementById('packagingName').value.trim();
            const dimensions = document.getElementById('packagingDimensions').value.trim();
            
            if (!name) {
                alert('Введите наименование упаковки');
                return;
            }
            if (!dimensions) {
                alert('Введите размеры упаковки');
                return;
            }
            
            const url = currentEditPackagingId ? '/api/packaging/' + currentEditPackagingId : '/api/packaging';
            const method = currentEditPackagingId ? 'PUT' : 'POST';
            
            try {
                const response = await fetch(url, {
                    method: method,
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ name: name, dimensions: dimensions })
                });
                const result = await response.json();
                if (result.success) {
                    closePackagingModal();
                    loadPackaging();
                } else {
                    alert(result.error || 'Ошибка сохранения');
                }
            } catch(e) {
                alert('Ошибка сохранения');
            }
        }
        
        async function deletePackaging(id) {
            if (!confirm('Удалить эту упаковку?')) return;
            try {
                const response = await fetch('/api/packaging/' + id, { method: 'DELETE' });
                const result = await response.json();
                if (result.success) {
                    loadPackaging();
                } else {
                    alert(result.error || 'Ошибка удаления');
                }
            } catch(e) {
                alert('Ошибка удаления');
            }
        }
        
        function closePackagingModal() {
            document.getElementById('packagingModal').classList.remove('show');
            currentEditPackagingId = null;
        }
        
        function switchSection(section) {
            sections.forEach(s => {
                const el = document.getElementById(s + 'Section');
                if (el) el.classList.remove('active');
            });
            document.getElementById(section + 'Section').classList.add('active');
            document.querySelectorAll('.references-menu a').forEach(a => a.classList.remove('active'));
            document.querySelector('.references-menu a[data-section="' + section + '"]').classList.add('active');
            currentSection = section;
            
            if (section === 'products') {
                productsPage = 1;
                loadProducts();
            } else if (section === 'formats') {
                loadFormats();
            } else if (section === 'packaging') {
                packagingPage = 1;
                loadPackaging();
            }
        }
        
        function escapeHtml(str) {
            if (!str) return '';
            return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
        }
        
        function nl2br(str) {
            if (!str) return '';
            return str.replace(/\n/g, '<br>');
        }
        
        document.querySelectorAll('.references-menu a').forEach(link => {
            link.addEventListener('click', () => switchSection(link.dataset.section));
        });
        
        document.getElementById('productsSearchBtn')?.addEventListener('click', () => {
            productsSearch = document.getElementById('productsSearch').value.trim();
            productsPage = 1;
            loadProducts();
        });
        
        document.getElementById('productsResetBtn')?.addEventListener('click', () => {
            document.getElementById('productsSearch').value = '';
            productsSearch = '';
            productsPage = 1;
            loadProducts();
        });
        
        document.getElementById('productsSearch')?.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                productsSearch = e.target.value.trim();
                productsPage = 1;
                loadProducts();
            }
        });
        
        document.getElementById('addPackagingBtn')?.addEventListener('click', () => openPackagingModal());
        document.getElementById('closePackagingModal')?.addEventListener('click', closePackagingModal);
        document.getElementById('cancelPackagingBtn')?.addEventListener('click', closePackagingModal);
        document.getElementById('savePackagingBtn')?.addEventListener('click', savePackaging);
        
        document.getElementById('logoutBtn')?.addEventListener('click', async () => {
            await fetch('/api/auth/logout', { method: 'POST' });
            window.location.href = '/login';
        });
        
        switchSection('products');
    </script>
</body>
</html>