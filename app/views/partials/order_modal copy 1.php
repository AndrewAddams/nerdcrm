<div id="orderModal" class="modal">
    <div class="modal-content" style="width: 800px; max-width: 95%;">
        <div class="modal-header">
            <h3 class="modal-title" id="orderModalTitle">Создание заказа</h3>
            <button class="close-modal" id="closeOrderModalBtn">&times;</button>
        </div>
        
        <form id="orderForm">
            <input type="hidden" id="orderId" value="">
            
            <div class="tabs">
                <button type="button" class="tab-btn active" data-tab="order-info">📋 Информация о заказе</button>
                <button type="button" class="tab-btn" data-tab="delivery-info">🚚 Информация о доставке</button>
                <button type="button" class="tab-btn" data-tab="order-items">🛍️ Товары</button>
            </div>
            
            <div class="tab-pane active" id="tab-order-info">
                <div class="form-row">
                    <div class="form-group">
                        <label>Метка продажи <span class="required">*</span></label>
                        <select id="saleLabel" required>
                            <option value="">Выберите...</option>
                            <option value="Первичная">Первичная</option>
                            <option value="Вторичная">Вторичная</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Источник заказа <span class="required">*</span></label>
                        <select id="sourceId" required><option value="">Выберите...</option></select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Ссылка <span class="required">*</span></label>
                    <input type="url" id="link" placeholder="https://..." required>
                </div>
                <div class="form-group">
                    <label>Комментарии</label>
                    <div id="commentsEditor" style="height: 150px; border: 1px solid #ddd; border-radius: 6px; padding: 8px;"></div>
                    <input type="hidden" id="comments">
                </div>
                <div class="form-group" style="grid-column: span 2;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" id="isUrgent" value="1"> 
                        <span>🚨 Срочный заказ</span>
                    </label>
                </div>
            </div>
            
            <div class="tab-pane" id="tab-delivery-info">
                <div class="form-row">
                    <div class="form-group">
                        <label>Способ доставки <span class="required">*</span></label>
                        <select id="shippingMethodId" required><option value="">Выберите...</option></select>
                    </div>
                    <div class="form-group">
                        <label>Трек-номер <span class="required">*</span></label>
                        <input type="text" id="trackingNumber" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Имя получателя <span class="required">*</span></label>
                        <input type="text" id="recipientName" required>
                    </div>
                    <div class="form-group">
                        <label>Телефон получателя <span class="required">*</span></label>
                        <input type="tel" id="recipientPhone" placeholder="+7 (___) ___-__-__" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>E-mail получателя <span class="required">*</span></label>
                        <input type="email" id="recipientEmail" required>
                    </div>
                    <div class="form-group">
                        <label>Стоимость доставки <span class="required">*</span></label>
                        <input type="number" id="shippingCost" step="0.01" min="0" value="0" required>
                    </div>
                </div>
            </div>
            
            <div class="tab-pane" id="tab-order-items">
                <div id="itemsList">
                    <div class="items-header">
                        <span>Наименование</span>
                        <span>Формат</span>
                        <span>Скидка %</span>
                        <span>Цена</span>
                        <span>Цена со скидкой</span>
                        <span></span>
                    </div>
                    <div id="itemsContainer"></div>
                    <button type="button" class="btn btn-secondary btn-sm" id="addItemBtn" style="margin-top: 12px;">+ Добавить товар</button>
                </div>
                <div class="totals">
                    <div class="total-row"><span>Общая стоимость товаров:</span><span id="totalItemsCost">0.00 ₽</span></div>
                    <div class="total-row"><span>Стоимость доставки:</span><span id="displayShippingCost">0.00 ₽</span></div>
                    <div class="total-row grand-total"><span>ИТОГО:</span><span id="totalCost">0.00 ₽</span></div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="cancelOrderBtn">Отмена</button>
                <button type="submit" class="btn btn-primary">Сохранить заказ</button>
            </div>
        </form>
    </div>
</div>

<style>
    .tabs {
        display: flex;
        gap: 4px;
        border-bottom: 1px solid #e0e0e0;
        margin-bottom: 20px;
    }
    .tab-btn {
        padding: 10px 20px;
        background: none;
        border: none;
        cursor: pointer;
        font-size: 14px;
        color: #666;
        transition: all 0.2s;
    }
    .tab-btn:hover {
        background: #f5f5f5;
    }
    .tab-btn.active {
        color: #667eea;
        border-bottom: 2px solid #667eea;
    }
    .tab-pane {
        display: none;
        padding: 20px 0;
    }
    .tab-pane.active {
        display: block;
    }
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
        margin-bottom: 16px;
    }
    .form-group {
        margin-bottom: 16px;
    }
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 500;
        color: #333;
    }
    .form-group input, .form-group select, .form-group textarea {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 14px;
    }
    .required {
        color: #c33;
    }
    .items-header {
        display: grid;
        grid-template-columns: 2fr 1.5fr 0.8fr 1fr 1fr 0.5fr;
        gap: 12px;
        padding: 10px 0;
        font-weight: 600;
        font-size: 13px;
        color: #666;
        border-bottom: 1px solid #e0e0e0;
        margin-bottom: 8px;
    }
    .item-row {
        display: grid;
        grid-template-columns: 2fr 1.5fr 0.8fr 1fr 1fr 0.5fr;
        gap: 12px;
        align-items: center;
        margin-bottom: 8px;
        padding: 8px 0;
        background: #fafafa;
        border-radius: 6px;
    }
    .item-row input, .item-row select {
        width: 100%;
        padding: 6px 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 13px;
    }
    .remove-item-btn {
        background: none;
        border: none;
        color: #c33;
        font-size: 18px;
        cursor: pointer;
        padding: 4px 8px;
    }
    .remove-item-btn:hover {
        background: #fee;
        border-radius: 4px;
    }
    .totals {
        margin-top: 20px;
        padding-top: 16px;
        border-top: 1px solid #e0e0e0;
        text-align: right;
    }
    .total-row {
        margin-bottom: 8px;
        font-size: 14px;
    }
    .total-row span:first-child {
        color: #666;
        margin-right: 16px;
    }
    .grand-total {
        font-size: 18px;
        font-weight: 700;
        color: #667eea;
        margin-top: 8px;
        padding-top: 8px;
        border-top: 1px dashed #e0e0e0;
    }
    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.5);
        z-index: 1000;
        align-items: center;
        justify-content: center;
    }
    .modal.show {
        display: flex;
    }
    .modal-content {
        background: white;
        border-radius: 12px;
        max-width: 90%;
        max-height: 90%;
        overflow-y: auto;
        padding: 24px;
    }
    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    .modal-title {
        font-size: 20px;
        font-weight: 600;
    }
    .close-modal {
        background: none;
        border: none;
        font-size: 24px;
        cursor: pointer;
        color: #999;
    }
    .modal-footer {
        display: flex;
        justify-content: flex-end;
        gap: 12px;
        margin-top: 24px;
    }
    .btn {
        padding: 8px 16px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 14px;
        transition: all 0.2s;
    }
    .btn-primary {
        background: #667eea;
        color: white;
    }
    .btn-primary:hover {
        background: #5a67d8;
    }
    .btn-secondary {
        background: #e0e7ff;
        color: #667eea;
    }
    .btn-secondary:hover {
        background: #c7d2fe;
    }
    .btn-sm {
        padding: 4px 12px;
        font-size: 12px;
    }
    .ql-container {
        font-size: 14px;
    }
    datalist {
        max-height: 200px;
        overflow-y: auto;
    }
    input[list]::-webkit-calendar-picker-indicator {
        cursor: pointer;
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>
<link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet">

<script>
// Глобальные переменные
let quillEditor = null;
let itemCounter = 0;
let productsList = [];
let formatsList = [];
let sourcesList = [];
let shippingMethodsList = [];

// Инициализация
function initOrderModal() {
    // Quill
    if (!quillEditor && document.getElementById('commentsEditor')) {
        quillEditor = new Quill('#commentsEditor', {
            theme: 'snow',
            placeholder: 'Введите комментарий...',
            modules: {
                toolbar: [
                    ['bold', 'italic', 'underline', 'strike'],
                    [{ 'color': [] }, { 'background': [] }],
                    [{ 'size': ['small', false, 'large', 'huge'] }],
                    ['clean']
                ]
            }
        });
        quillEditor.on('text-change', function() {
            document.getElementById('comments').value = quillEditor.root.innerHTML;
        });
    }
    
    loadAllData();
    
    document.getElementById('addItemBtn')?.addEventListener('click', () => addNewItemRow());
    document.getElementById('cancelOrderBtn')?.addEventListener('click', () => closeOrderModal());
    document.getElementById('closeOrderModalBtn')?.addEventListener('click', () => closeOrderModal());
    document.getElementById('orderForm')?.addEventListener('submit', (e) => saveOrder(e));
    document.getElementById('shippingCost')?.addEventListener('input', () => updateTotals());
    
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            document.querySelectorAll('.tab-pane').forEach(pane => pane.classList.remove('active'));
            document.getElementById(`tab-${btn.dataset.tab}`).classList.add('active');
        });
    });
}

// Загрузка всех данных
async function loadAllData() {
    try {
        let response = await fetch('/api/products/select');
        let result = await response.json();
        if (result.success) productsList = result.data;
        
        response = await fetch('/api/formats/select');
        result = await response.json();
        if (result.success) formatsList = result.data;
        
        response = await fetch('/api/sources/select');
        result = await response.json();
        if (result.success) {
            sourcesList = result.data;
            const sourceSelect = document.getElementById('sourceId');
            if (sourceSelect) {
                sourceSelect.innerHTML = '<option value="">Выберите...</option>' + 
                    sourcesList.map(s => `<option value="${s.id}">${escapeHtml(s.name)}</option>`).join('');
            }
        }
        
        response = await fetch('/api/shipping-methods/select');
        result = await response.json();
        if (result.success) {
            shippingMethodsList = result.data;
            const shippingSelect = document.getElementById('shippingMethodId');
            if (shippingSelect) {
                shippingSelect.innerHTML = '<option value="">Выберите...</option>' + 
                    shippingMethodsList.map(s => `<option value="${s.id}">${escapeHtml(s.name)}</option>`).join('');
            }
        }
    } catch (error) {
        console.error('Ошибка загрузки данных:', error);
    }
}

// Добавление строки товара с datalist
function addNewItemRow(productId = null, formatId = null, discountPercent = 0) {
    const container = document.getElementById('itemsContainer');
    if (!container) return;
    
    const itemId = itemCounter++;
    const row = document.createElement('div');
    row.className = 'item-row';
    row.dataset.itemId = itemId;
    
    const productDatalistId = `products_${itemId}`;
    let productOptions = '';
    productsList.forEach(p => {
        productOptions += `<option value="${escapeHtml(p.text)}" data-id="${p.id}">`;
    });
    
    const formatDatalistId = `formats_${itemId}`;
    let formatOptions = '';
    formatsList.forEach(f => {
        formatOptions += `<option value="${escapeHtml(f.text)}" data-id="${f.id}" data-price="${f.price}">`;
    });
    
    row.innerHTML = `
        <div style="position: relative; width: 100%;">
            <input type="text" class="product-input" data-item-id="${itemId}" list="${productDatalistId}" placeholder="Введите название товара..." autocomplete="off" style="width: 100%;">
            <datalist id="${productDatalistId}">${productOptions}</datalist>
        </div>
        <div style="position: relative; width: 100%;">
            <input type="text" class="format-input" data-item-id="${itemId}" list="${formatDatalistId}" placeholder="Введите название формата..." autocomplete="off" style="width: 100%;">
            <datalist id="${formatDatalistId}">${formatOptions}</datalist>
        </div>
        <input type="number" class="discount-input" data-item-id="${itemId}" value="${discountPercent}" min="0" max="100" step="1">
        <span class="item-price">0.00 ₽</span>
        <span class="item-price-with-discount">0.00 ₽</span>
        <button type="button" class="remove-item-btn" data-item-id="${itemId}">✕</button>
    `;
    container.appendChild(row);
    
    const productInput = row.querySelector('.product-input');
    const formatInput = row.querySelector('.format-input');
    
    const productMap = {};
    productsList.forEach(p => {
        productMap[p.text] = p.id;
    });
    
    const formatMap = {};
    const formatPriceMap = {};
    formatsList.forEach(f => {
        formatMap[f.text] = f.id;
        formatPriceMap[f.text] = f.price;
    });
    
    productInput.addEventListener('change', function() {
        const selectedText = this.value;
        const productId = productMap[selectedText];
        if (productId) {
            this.dataset.productId = productId;
        }
    });
    
    formatInput.addEventListener('change', function() {
        const selectedText = this.value;
        const formatId = formatMap[selectedText];
        const price = formatPriceMap[selectedText] || 0;
        if (formatId) {
            this.dataset.formatId = formatId;
            this.dataset.price = price;
        }
        calculateItemPrice(row);
    });
    
    row.querySelector('.discount-input').addEventListener('input', () => calculateItemPrice(row));
    row.querySelector('.remove-item-btn').addEventListener('click', () => row.remove());
    
    if (productId) {
        const product = productsList.find(p => p.id == productId);
        if (product) {
            productInput.value = product.text;
            productInput.dataset.productId = productId;
        }
    }
    
    if (formatId) {
        const format = formatsList.find(f => f.id == formatId);
        if (format) {
            formatInput.value = format.text;
            formatInput.dataset.formatId = formatId;
            formatInput.dataset.price = format.price;
        }
    }
    
    calculateItemPrice(row);
}

// Расчёт цены товара
function calculateItemPrice(row) {
    const formatInput = row.querySelector('.format-input');
    const discountInput = row.querySelector('.discount-input');
    const priceSpan = row.querySelector('.item-price');
    const priceWithDiscountSpan = row.querySelector('.item-price-with-discount');
    
    const price = parseFloat(formatInput?.dataset?.price || 0);
    const discount = parseFloat(discountInput?.value || 0);
    const priceWithDiscount = price * (1 - discount / 100);
    
    priceSpan.textContent = price.toFixed(2) + ' ₽';
    priceWithDiscountSpan.textContent = priceWithDiscount.toFixed(2) + ' ₽';
    updateTotals();
}

// Обновление итоговых сумм
function updateTotals() {
    const rows = document.querySelectorAll('#itemsContainer .item-row');
    let totalItemsCost = 0;
    rows.forEach(row => {
        const priceSpan = row.querySelector('.item-price-with-discount');
        totalItemsCost += parseFloat(priceSpan?.textContent || 0);
    });
    const shippingCost = parseFloat(document.getElementById('shippingCost')?.value || 0);
    const totalCost = totalItemsCost + shippingCost;
    
    document.getElementById('totalItemsCost').textContent = totalItemsCost.toFixed(2) + ' ₽';
    document.getElementById('displayShippingCost').textContent = shippingCost.toFixed(2) + ' ₽';
    document.getElementById('totalCost').textContent = totalCost.toFixed(2) + ' ₽';
}

// Сбор данных из формы
function getFormData() {
    const rows = document.querySelectorAll('#itemsContainer .item-row');
    const items = [];
    rows.forEach(row => {
        const productInput = row.querySelector('.product-input');
        const formatInput = row.querySelector('.format-input');
        const discountInput = row.querySelector('.discount-input');
        
        const productId = productInput?.dataset?.productId;
        const formatId = formatInput?.dataset?.formatId;
        const price = parseFloat(formatInput?.dataset?.price || 0);
        
        if (productId && formatId) {
            items.push({
                product_id: parseInt(productId),
                format_id: parseInt(formatId),
                discount_percent: parseInt(discountInput?.value || 0),
                price: price
            });
        }
    });
    return {
        id: document.getElementById('orderId')?.value || '',
        sale_label: document.getElementById('saleLabel')?.value || '',
        source_id: parseInt(document.getElementById('sourceId')?.value || 0),
        link: document.getElementById('link')?.value || '',
        comments: document.getElementById('comments')?.value || '',
        shipping_method_id: parseInt(document.getElementById('shippingMethodId')?.value || 0),
        tracking_number: document.getElementById('trackingNumber')?.value || '',
        recipient_name: document.getElementById('recipientName')?.value || '',
        recipient_phone: document.getElementById('recipientPhone')?.value || '',
        recipient_email: document.getElementById('recipientEmail')?.value || '',
        shipping_cost: parseFloat(document.getElementById('shippingCost')?.value || 0),
        is_urgent: document.getElementById('isUrgent')?.checked ? 1 : 0,
        items: items
    };
}

// Заполнение формы данными заказа
function fillFormData(order) {
    document.getElementById('orderId').value = order.id || '';
    document.getElementById('saleLabel').value = order.sale_label || '';
    document.getElementById('sourceId').value = order.source_id || '';
    document.getElementById('link').value = order.link || '';
    document.getElementById('shippingMethodId').value = order.shipping_method_id || '';
    document.getElementById('trackingNumber').value = order.tracking_number || '';
    document.getElementById('recipientName').value = order.recipient_name || '';
    document.getElementById('recipientPhone').value = order.recipient_phone || '';
    document.getElementById('recipientEmail').value = order.recipient_email || '';
    document.getElementById('shippingCost').value = order.shipping_cost || 0;
    document.getElementById('isUrgent').checked = order.is_urgent == 1;
    
    if (quillEditor) quillEditor.root.innerHTML = order.comments || '';
    document.getElementById('comments').value = order.comments || '';
    
    const container = document.getElementById('itemsContainer');
    if (container) container.innerHTML = '';
    itemCounter = 0;
    
    if (order.items && order.items.length > 0) {
        order.items.forEach(item => {
            addNewItemRow(item.product_id, item.format_id, item.discount_percent);
        });
    }
    updateTotals();
}

// Сохранение заказа с подробными подсказками
async function saveOrder(e) {
    e.preventDefault();
    const formData = getFormData();
    
    // Список обязательных полей с понятными названиями
    const requiredFields = [
        { field: 'sale_label', name: 'Метка продажи' },
        { field: 'source_id', name: 'Источник заказа' },
        { field: 'link', name: 'Ссылка' },
        { field: 'shipping_method_id', name: 'Способ доставки' },
        { field: 'tracking_number', name: 'Трек-номер' },
        { field: 'recipient_name', name: 'Имя получателя' },
        { field: 'recipient_phone', name: 'Телефон получателя' },
        { field: 'recipient_email', name: 'E-mail получателя' }
    ];
    
    // Проверяем обязательные поля
    const missingFields = [];
    for (const req of requiredFields) {
        let value = formData[req.field];
        if (req.field === 'source_id' || req.field === 'shipping_method_id') {
            if (!value || value === 0) {
                missingFields.push(req.name);
            }
        } else if (!value || value === '') {
            missingFields.push(req.name);
        }
    }
    
    // Проверяем товары
    if (formData.items.length === 0) {
        missingFields.push('Товары (добавьте хотя бы один товар)');
    }
    
    // Если есть незаполненные поля — показываем сообщение
    if (missingFields.length > 0) {
        let message = '❌ Пожалуйста, заполните следующие обязательные поля:\n\n';
        message += missingFields.map(f => '• ' + f).join('\n');
        alert(message);
        
        // Подсвечиваем красным незаполненные поля (опционально)
        if (missingFields.includes('Метка продажи')) {
            document.getElementById('saleLabel').style.border = '2px solid #c33';
            setTimeout(() => document.getElementById('saleLabel').style.border = '', 3000);
        }
        if (missingFields.includes('Источник заказа')) {
            document.getElementById('sourceId').style.border = '2px solid #c33';
            setTimeout(() => document.getElementById('sourceId').style.border = '', 3000);
        }
        if (missingFields.includes('Ссылка')) {
            document.getElementById('link').style.border = '2px solid #c33';
            setTimeout(() => document.getElementById('link').style.border = '', 3000);
        }
        if (missingFields.includes('Способ доставки')) {
            document.getElementById('shippingMethodId').style.border = '2px solid #c33';
            setTimeout(() => document.getElementById('shippingMethodId').style.border = '', 3000);
        }
        if (missingFields.includes('Трек-номер')) {
            document.getElementById('trackingNumber').style.border = '2px solid #c33';
            setTimeout(() => document.getElementById('trackingNumber').style.border = '', 3000);
        }
        if (missingFields.includes('Имя получателя')) {
            document.getElementById('recipientName').style.border = '2px solid #c33';
            setTimeout(() => document.getElementById('recipientName').style.border = '', 3000);
        }
        if (missingFields.includes('Телефон получателя')) {
            document.getElementById('recipientPhone').style.border = '2px solid #c33';
            setTimeout(() => document.getElementById('recipientPhone').style.border = '', 3000);
        }
        if (missingFields.includes('E-mail получателя')) {
            document.getElementById('recipientEmail').style.border = '2px solid #c33';
            setTimeout(() => document.getElementById('recipientEmail').style.border = '', 3000);
        }
        
        return;
    }
    
    const isEdit = formData.id;
    const url = isEdit ? `/api/orders/${formData.id}` : '/api/orders';
    const method = isEdit ? 'PUT' : 'POST';
    
    try {
        const response = await fetch(url, { method, headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(formData) });
        const result = await response.json();
        if (result.success) {
            closeOrderModal();
            if (typeof loadOrders === 'function') loadOrders();
            if (typeof loadFolders === 'function') loadFolders();
        } else {
            alert(result.error || 'Ошибка сохранения заказа');
        }
    } catch (error) {
        console.error('Ошибка:', error);
        alert('Ошибка сохранения заказа');
    }
}

// Открытие модального окна
window.openOrderModal = function(orderId = null) {
    const modal = document.getElementById('orderModal');
    if (!modal) return;
    document.getElementById('orderModalTitle').textContent = orderId ? 'Редактирование заказа' : 'Создание заказа';
    if (orderId) {
        loadOrderData(orderId);
    } else {
        resetForm();
    }
    modal.classList.add('show');
};

// Загрузка данных заказа
async function loadOrderData(orderId) {
    try {
        const response = await fetch(`/api/orders/${orderId}`);
        const result = await response.json();
        if (result.success) {
            fillFormData(result.data);
        } else {
            alert('Ошибка загрузки заказа');
            closeOrderModal();
        }
    } catch (error) {
        console.error('Ошибка:', error);
        alert('Ошибка загрузки заказа');
        closeOrderModal();
    }
}

// Сброс формы
function resetForm() {
    document.getElementById('orderId').value = '';
    document.getElementById('saleLabel').value = '';
    document.getElementById('sourceId').value = '';
    document.getElementById('link').value = '';
    document.getElementById('shippingMethodId').value = '';
    document.getElementById('trackingNumber').value = '';
    document.getElementById('recipientName').value = '';
    document.getElementById('recipientPhone').value = '';
    document.getElementById('recipientEmail').value = '';
    document.getElementById('shippingCost').value = '0';
    document.getElementById('isUrgent').checked = false;
    if (quillEditor) quillEditor.root.innerHTML = '';
    document.getElementById('comments').value = '';
    document.getElementById('itemsContainer').innerHTML = '';
    itemCounter = 0;
    updateTotals();
}

// Закрытие модального окна
window.closeOrderModal = function() {
    document.getElementById('orderModal')?.classList.remove('show');
};

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}

document.addEventListener('DOMContentLoaded', function() {
    initOrderModal();
});
</script>