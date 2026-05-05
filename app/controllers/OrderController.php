<?php
/**
 * Контроллер заказов
 * 
 * Отвечает за все операции с заказами: получение списка, создание,
 * редактирование, обновление статусов, массовые операции
 */

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/OrderItem.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Format.php';
require_once __DIR__ . '/../models/Source.php';
require_once __DIR__ . '/../models/ShippingMethod.php';
require_once __DIR__ . '/../models/Status.php';

// Константы для формата "Свободная цена" (если файл constants.php не загружен)
if (!defined('FLEXIBLE_PRICE_FORMAT_ID')) {
    define('FLEXIBLE_PRICE_FORMAT_ID', 99);
}
if (!defined('ORDER_ITEM_STATUS_READY')) {
    define('ORDER_ITEM_STATUS_READY', 8);
}
if (!defined('ORDER_ITEM_STATUS_IN_WORK')) {
    define('ORDER_ITEM_STATUS_IN_WORK', 6);
}

class OrderController extends Controller
{
    private $orderModel;
    private $orderItemModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->orderModel = new Order();
        $this->orderItemModel = new OrderItem();
    }
    
    /**
     * Получить список заказов с фильтрацией
     * GET /api/orders
     */
    public function index()
    {
        if (!$this->requireAuth()) {
            return;
        }
        
        $filters = [];
        
        if (isset($_GET['year']) && $_GET['year']) {
            $filters['year'] = (int)$_GET['year'];
        }
        
        if (isset($_GET['month']) && $_GET['month']) {
            $filters['month'] = (int)$_GET['month'];
        }
        
        if (isset($_GET['date_from']) && $_GET['date_from']) {
            $filters['date_from'] = $_GET['date_from'];
        }
        
        if (isset($_GET['date_to']) && $_GET['date_to']) {
            $filters['date_to'] = $_GET['date_to'];
        }
        
        if (isset($_GET['status_id']) && $_GET['status_id']) {
            $filters['status_id'] = (int)$_GET['status_id'];
        }
        
        if (isset($_GET['search']) && $_GET['search']) {
            $filters['search'] = $_GET['search'];
        }
        
        $orders = $this->orderModel->getAllWithFilters($filters);
        
        $this->success($orders);
    }
    
    /**
     * Получить структуру папок (годы/месяцы)
     * GET /api/orders/folders
     */
    public function getFolders()
    {
        if (!$this->requireAuth()) {
            return;
        }
        
        $folders = $this->orderModel->getFolders();
        $this->success($folders);
    }
    
    /**
     * Получить один заказ с товарами
     * GET /api/orders/{id}
     */
    public function show($params)
    {
        if (!$this->requireAuth()) {
            return;
        }
        
        $id = (int)$params['id'];
        $order = $this->orderModel->getOrderWithItems($id);
        
        if (!$order) {
            $this->error('Заказ не найден', 404);
            return;
        }
        
        $this->success($order);
    }
    
    /**
     * Создать новый заказ
     * POST /api/orders
     */
    public function store()
    {
        if (!$this->requireAuth()) {
            return;
        }
        
        $data = $this->getRequestData();
        
        // =====================================================
        // НОВАЯ СТРОГАЯ ВАЛИДАЦИЯ
        // =====================================================
$rules = [
    'sale_label' => 'required|in:Первичная,Вторичная',
    'source_id' => 'required|int|exists:sources,id',
    'link' => 'required|url|max:500',
    'comments' => 'string|max:5000',
    'shipping_method_id' => 'required|int|exists:shipping_methods,id',
    'tracking_number' => 'string',
    'recipient_name' => 'required|string|max:255',
    'recipient_phone' => 'required|phone',
    'recipient_email' => 'required|email|max:255',
    'shipping_cost' => 'required|numeric|min:0|max:10000',
    'is_urgent' => 'in:0,1',  // не required, может отсутствовать
    'items' => 'required|array|min:1|max:50',
    'items.*.product_id' => 'required|int|exists:products,id',
    'items.*.format_id' => 'required|int|exists:formats,id',
    'items.*.discount_percent' => 'int|discount',
    'items.*.custom_price' => 'required_if:items.*.format_id,99|numeric|positive'
];
        
        $messages = [
            'items.required' => 'Добавьте хотя бы один товар',
            'items.min' => 'Добавьте хотя бы один товар',
            'items.max' => 'Нельзя добавить более 50 товаров в один заказ',
            'items.*.product_id.required' => 'Выберите товар для каждой позиции',
            'items.*.format_id.required' => 'Выберите формат для каждой позиции',
            'items.*.custom_price.required_if' => 'Для товара со свободной ценой укажите сумму',
            'items.*.custom_price.positive' => 'Цена должна быть больше 0',
            'shipping_cost.max' => 'Стоимость доставки не может превышать 10 000 ₽',
        ];
        
        if (!$this->validate($data, $rules, $messages)) {
            return;
        }
        
        // Получаем очищенные данные после валидации
        $validatedData = $this->getValidatedData();
        
        // Подготавливаем данные заказа
        $orderData = [
            'sale_label' => $validatedData['sale_label'],
            'source_id' => (int)$validatedData['source_id'],
            'link' => $validatedData['link'],
            'comments' => $validatedData['comments'] ?? '',
            'shipping_method_id' => (int)$validatedData['shipping_method_id'],
            'tracking_number' => $validatedData['tracking_number'],
            'recipient_name' => $validatedData['recipient_name'],
            'recipient_phone' => $validatedData['recipient_phone'],
            'recipient_email' => $validatedData['recipient_email'],
            'shipping_cost' => (float)$validatedData['shipping_cost'],
            'total_items_cost' => 0,
            'total_cost' => 0,
            'is_urgent' => isset($validatedData['is_urgent']) ? (int)$validatedData['is_urgent'] : 0
        ];
        
        // Подготавливаем товары
        $items = [];
        $totalItemsCost = 0;
        
        foreach ($validatedData['items'] as $item) {
            $formatId = (int)$item['format_id'];
            $isFlexiblePrice = ($formatId == FLEXIBLE_PRICE_FORMAT_ID);
            
            if ($isFlexiblePrice) {
                // Для товаров со свободной ценой
                $price = (float)($item['custom_price'] ?? 0);
                $discount = (int)($item['discount_percent'] ?? 0);
                $priceWithDiscount = $this->orderItemModel->calculatePriceWithDiscount($price, $discount);
                
                $items[] = [
                    'product_id' => (int)$item['product_id'],
                    'format_id' => $formatId,
                    'price' => $price,
                    'discount_percent' => $discount,
                    'price_with_discount' => $priceWithDiscount,
                    'status_order_item_id' => ORDER_ITEM_STATUS_READY
                ];
            } else {
                // Существующая логика для обычных товаров
                $price = (float)$item['price'];
                $discount = (int)($item['discount_percent'] ?? 0);
                $priceWithDiscount = $this->orderItemModel->calculatePriceWithDiscount($price, $discount);
                
                $items[] = [
                    'product_id' => (int)$item['product_id'],
                    'format_id' => $formatId,
                    'price' => $price,
                    'discount_percent' => $discount,
                    'price_with_discount' => $priceWithDiscount
                ];
            }
            
            $totalItemsCost += $priceWithDiscount;
        }
        
        $orderData['total_items_cost'] = round($totalItemsCost, 2);
        $orderData['total_cost'] = $this->orderItemModel->calculateTotalCost(
            $orderData['total_items_cost'],
            $orderData['shipping_cost']
        );
        
        // Создаём заказ
        $orderId = $this->orderModel->createOrder($orderData, $items, $this->currentUser['id']);
        
        if (!$orderId) {
            $this->error('Ошибка при создании заказа', 500);
            return;
        }
        
        $this->success(['id' => $orderId], 'Заказ успешно создан');
    }
    
/**
 * Обновить заказ
 * PUT /api/orders/{id}
 */
public function update($params)
{
    if (!$this->requireAuth()) {
        return;
    }
    
    $id = (int)$params['id'];
    $data = $this->getRequestData();
    
    // Проверяем существование заказа
    $order = $this->orderModel->find($id);
    if (!$order) {
        $this->error('Заказ не найден', 404);
        return;
    }
    
    // =====================================================
    // НОВАЯ СТРОГАЯ ВАЛИДАЦИЯ
    // =====================================================
    $rules = [
        'sale_label' => 'required|in:Первичная,Вторичная',
        'source_id' => 'required|int|exists:sources,id',
        'link' => 'required|url|max:500',
        'comments' => 'string|max:5000',
        'shipping_method_id' => 'required|int|exists:shipping_methods,id',
        'tracking_number' => 'string',
        'recipient_name' => 'required|string|max:255',
        'recipient_phone' => 'required|phone',
        'recipient_email' => 'required|email|max:255',
        'shipping_cost' => 'required|numeric|min:0|max:10000',
        'is_urgent' => 'int|in:0,1',
        'items' => 'required|array|min:1|max:50',
        'items.*.product_id' => 'required|int|exists:products,id',
        'items.*.format_id' => 'required|int|exists:formats,id',
        'items.*.discount_percent' => 'int|discount',
        'items.*.status_id' => 'int|exists:statuses,id',
        'items.*.custom_price' => 'required_if:items.*.format_id,99|numeric|positive|max:1000000',
    ];
    
    $messages = [
        'items.required' => 'Добавьте хотя бы один товар',
        'items.min' => 'Добавьте хотя бы один товар',
        'items.max' => 'Нельзя добавить более 50 товаров в один заказ',
        'items.*.product_id.required' => 'Выберите товар для каждой позиции',
        'items.*.format_id.required' => 'Выберите формат для каждой позиции',
        'items.*.custom_price.required_if' => 'Для товара со свободной ценой укажите сумму',
        'items.*.custom_price.positive' => 'Цена должна быть больше 0',
        'shipping_cost.max' => 'Стоимость доставки не может превышать 10 000 ₽',
    ];
    
    if (!$this->validate($data, $rules, $messages)) {
        return;
    }
    
    // Получаем очищенные данные после валидации
    $validatedData = $this->getValidatedData();
    
    // Подготавливаем данные
    $orderData = [
        'sale_label' => $validatedData['sale_label'],
        'source_id' => (int)$validatedData['source_id'],
        'link' => $validatedData['link'],
        'comments' => $validatedData['comments'] ?? '',
        'shipping_method_id' => (int)$validatedData['shipping_method_id'],
        'tracking_number' => $validatedData['tracking_number'],
        'recipient_name' => $validatedData['recipient_name'],
        'recipient_phone' => $validatedData['recipient_phone'],
        'recipient_email' => $validatedData['recipient_email'],
        'shipping_cost' => (float)$validatedData['shipping_cost'],
        'is_urgent' => isset($validatedData['is_urgent']) ? (int)$validatedData['is_urgent'] : 0
    ];
    
    // Подготавливаем товары
    $items = [];
    $totalItemsCost = 0;

    foreach ($validatedData['items'] as $item) {
        $formatId = (int)$item['format_id'];
        $isFlexiblePrice = ($formatId == FLEXIBLE_PRICE_FORMAT_ID);
        
        // Получаем ID существующего товара (если есть)
        $itemId = isset($item['id']) ? (int)$item['id'] : null;
        
        // Получаем текущий статус товара из БД (сохраняем, если не передан новый)
        $currentStatusId = null;
        if ($itemId) {
            $currentItem = $this->orderItemModel->find($itemId);
            if ($currentItem) {
                $currentStatusId = $currentItem['status_order_item_id'];
            }
        }
        
        if ($isFlexiblePrice) {
            // Для товаров со свободной ценой
            $price = (float)($item['custom_price'] ?? 0);
            $discount = (int)($item['discount_percent'] ?? 0);
            $priceWithDiscount = $this->orderItemModel->calculatePriceWithDiscount($price, $discount);
            
            $items[] = [
                'id' => $itemId,
                'product_id' => (int)$item['product_id'],
                'format_id' => $formatId,
                'status_order_item_id' => ORDER_ITEM_STATUS_READY, // Сертификаты всегда готовы
                'price' => $price,
                'discount_percent' => $discount,
                'price_with_discount' => $priceWithDiscount
            ];
        } else {
            // Существующая логика для обычных товаров
            $price = (float)$item['price'];
            $discount = (int)($item['discount_percent'] ?? 0);
            $priceWithDiscount = $this->orderItemModel->calculatePriceWithDiscount($price, $discount);
            
            // Сохраняем статус: если передан новый — используем его, иначе оставляем текущий
            $newStatusId = null;
            if (isset($item['status_id']) && !empty($item['status_id'])) {
                $newStatusId = (int)$item['status_id'];
            } elseif ($currentStatusId) {
                $newStatusId = $currentStatusId;
            } else {
                $newStatusId = $this->orderModel->getDefaultStatusId('order_item');
            }
            
            $items[] = [
                'id' => $itemId,
                'product_id' => (int)$item['product_id'],
                'format_id' => $formatId,
                'status_order_item_id' => $newStatusId,
                'price' => $price,
                'discount_percent' => $discount,
                'price_with_discount' => $priceWithDiscount
            ];
        }
        
        $totalItemsCost += $priceWithDiscount;
    }
    
    $orderData['total_items_cost'] = round($totalItemsCost, 2);
    $orderData['total_cost'] = $this->orderItemModel->calculateTotalCost(
        $orderData['total_items_cost'],
        $orderData['shipping_cost']
    );
    
    // Обновляем заказ
    $result = $this->orderModel->updateOrder($id, $orderData, $items);
    
    if (!$result) {
        $this->error('Ошибка при обновлении заказа', 500);
        return;
    }
    
    $this->success(null, 'Заказ успешно обновлён');
}    
    /**
     * Обновить статус заказа
     * PUT /api/orders/{id}/status-order
     */
    public function updateStatusOrder($params)
    {
        if (!$this->requireAuth()) {
            return;
        }
        
        $id = (int)$params['id'];
        $data = $this->getRequestData();
        
        // Валидация
        $rules = [
            'status_id' => 'required|int|in:1,2,3'
        ];
        
        $messages = [
            'status_id.required' => 'Не указан статус',
            'status_id.in' => 'Недопустимый статус заказа'
        ];
        
        if (!$this->validate($data, $rules, $messages)) {
            return;
        }
        
        $result = $this->orderModel->updateStatus($id, 'order', (int)$data['status_id']);
        
        if (!$result) {
            $this->error('Ошибка обновления статуса', 500);
            return;
        }
        
        $this->success(null, 'Статус заказа обновлён');
    }
    
    /**
     * Обновить статус оплаты
     * PUT /api/orders/{id}/status-payment
     */
    public function updateStatusPayment($params)
    {
        if (!$this->requireAuth()) {
            return;
        }
        
        $id = (int)$params['id'];
        $data = $this->getRequestData();
        
        // Валидация
        $rules = [
            'status_id' => 'required|int|in:4,5'
        ];
        
        $messages = [
            'status_id.required' => 'Не указан статус',
            'status_id.in' => 'Недопустимый статус оплаты'
        ];
        
        if (!$this->validate($data, $rules, $messages)) {
            return;
        }
        
        $result = $this->orderModel->updateStatus($id, 'payment', (int)$data['status_id']);
        
        if (!$result) {
            $this->error('Ошибка обновления статуса', 500);
            return;
        }
        
        $this->success(null, 'Статус оплаты обновлён');
    }
    
    /**
     * Обновить статус товара в заказе
     * PUT /api/orders/items/{itemId}/status
     */
    public function updateItemStatus($params)
    {
        if (!$this->requireAuth()) {
            return;
        }
        
        $itemId = (int)$params['itemId'];
        $data = $this->getRequestData();
        
        // Валидация
        $rules = [
            'status_id' => 'required|int|in:6,7,8'
        ];
        
        $messages = [
            'status_id.required' => 'Не указан статус',
            'status_id.in' => 'Недопустимый статус товара'
        ];
        
        if (!$this->validate($data, $rules, $messages)) {
            return;
        }
        
        $result = $this->orderItemModel->updateStatus($itemId, (int)$data['status_id']);
        
        if (!$result) {
            $this->error('Ошибка обновления статуса товара', 500);
            return;
        }
        
        $this->success(null, 'Статус товара обновлён');
    }
    
    /**
     * Массовое обновление статуса заказов
     * POST /api/orders/bulk-update-status
     */
    public function bulkUpdateStatus()
    {
        if (!$this->requireAuth()) {
            return;
        }
        
        $data = $this->getRequestData();
        
        // Валидация
        $rules = [
            'order_ids' => 'required|array|min:1',
            'order_ids.*' => 'int|exists:orders,id',
            'status_id' => 'required|int|in:1,2,3'
        ];
        
        $messages = [
            'order_ids.required' => 'Не выбраны заказы',
            'order_ids.min' => 'Выберите хотя бы один заказ',
            'status_id.required' => 'Не указан статус',
            'status_id.in' => 'Недопустимый статус заказа'
        ];
        
        if (!$this->validate($data, $rules, $messages)) {
            return;
        }
        
        $result = $this->orderModel->bulkUpdateStatus($data['order_ids'], (int)$data['status_id']);
        
        if (!$result) {
            $this->error('Ошибка массового обновления', 500);
            return;
        }
        
        $this->success(null, 'Статусы успешно обновлены');
    }
    
    /**
     * Массовое удаление заказов
     * POST /api/orders/bulk-delete
     */
    public function bulkDelete()
    {
        if (!$this->requireAuth()) {
            return;
        }
        
        $data = $this->getRequestData();
        
        // Валидация
        $rules = [
            'order_ids' => 'required|array|min:1',
            'order_ids.*' => 'int|exists:orders,id'
        ];
        
        $messages = [
            'order_ids.required' => 'Не выбраны заказы',
            'order_ids.min' => 'Выберите хотя бы один заказ'
        ];
        
        if (!$this->validate($data, $rules, $messages)) {
            return;
        }
        
        $result = $this->orderModel->bulkDelete($data['order_ids']);
        
        if (!$result) {
            $this->error('Ошибка удаления заказов', 500);
            return;
        }
        
        $this->success(null, 'Заказы успешно удалены');
    }
}