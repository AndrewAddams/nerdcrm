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
        
        // Валидация обязательных полей
        $required = [
            'sale_label', 'source_id', 'link', 'shipping_method_id',
            'tracking_number', 'recipient_name', 'recipient_phone',
            'recipient_email', 'shipping_cost', 'items'
        ];
        
        if (!$this->validateRequired($data, $required)) {
            return;
        }
        
        if (!$this->validateEmail($data['recipient_email'])) {
            return;
        }
        
        if (!$this->validatePhone($data['recipient_phone'])) {
            return;
        }
        
        if (!$this->validatePositiveNumber($data['shipping_cost'], 'Стоимость доставки')) {
            return;
        }
        
        if (!is_array($data['items']) || empty($data['items'])) {
            $this->error('Добавьте хотя бы один товар');
            return;
        }
        
        // Подготавливаем данные заказа
        $orderData = [
            'sale_label' => $data['sale_label'],
            'source_id' => (int)$data['source_id'],
            'link' => $data['link'],
            'comments' => $data['comments'] ?? '',
            'shipping_method_id' => (int)$data['shipping_method_id'],
            'tracking_number' => $data['tracking_number'],
            'recipient_name' => $data['recipient_name'],
            'recipient_phone' => $data['recipient_phone'],
            'recipient_email' => $data['recipient_email'],
            'shipping_cost' => (float)$data['shipping_cost'],
            'total_items_cost' => 0,
            'total_cost' => 0,
            'is_urgent' => isset($data['is_urgent']) ? (int)$data['is_urgent'] : 0
        ];
        
        // Подготавливаем товары
        $items = [];
        $totalItemsCost = 0;
        
        foreach ($data['items'] as $item) {
            if (empty($item['product_id']) || empty($item['format_id'])) {
                $this->error('Заполните наименование и формат для всех товаров');
                return;
            }
            
            $price = (float)$item['price'];
            $discount = (int)($item['discount_percent'] ?? 0);
            
            if (!$this->validateDiscountPercent($discount)) {
                return;
            }
            
            $priceWithDiscount = $this->orderItemModel->calculatePriceWithDiscount($price, $discount);
            
            $items[] = [
                'product_id' => (int)$item['product_id'],
                'format_id' => (int)$item['format_id'],
                'price' => $price,
                'discount_percent' => $discount,
                'price_with_discount' => $priceWithDiscount
            ];
            
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
        
        // Валидация
        $required = [
            'sale_label', 'source_id', 'link', 'shipping_method_id',
            'tracking_number', 'recipient_name', 'recipient_phone',
            'recipient_email', 'shipping_cost', 'items'
        ];
        
        if (!$this->validateRequired($data, $required)) {
            return;
        }
        
        if (!$this->validateEmail($data['recipient_email'])) {
            return;
        }
        
        if (!$this->validatePhone($data['recipient_phone'])) {
            return;
        }
        
        if (!$this->validatePositiveNumber($data['shipping_cost'], 'Стоимость доставки')) {
            return;
        }
        
        // Подготавливаем данные
        $orderData = [
            'sale_label' => $data['sale_label'],
            'source_id' => (int)$data['source_id'],
            'link' => $data['link'],
            'comments' => $data['comments'] ?? '',
            'shipping_method_id' => (int)$data['shipping_method_id'],
            'tracking_number' => $data['tracking_number'],
            'recipient_name' => $data['recipient_name'],
            'recipient_phone' => $data['recipient_phone'],
            'recipient_email' => $data['recipient_email'],
            'shipping_cost' => (float)$data['shipping_cost'],
            'is_urgent' => isset($data['is_urgent']) ? (int)$data['is_urgent'] : 0
        ];
        
        // Подготавливаем товары
        $items = [];
        $totalItemsCost = 0;
        
        foreach ($data['items'] as $item) {
            if (empty($item['product_id']) || empty($item['format_id'])) {
                $this->error('Заполните наименование и формат для всех товаров');
                return;
            }
            
            $price = (float)$item['price'];
            $discount = (int)($item['discount_percent'] ?? 0);
            
            if (!$this->validateDiscountPercent($discount)) {
                return;
            }
            
            $priceWithDiscount = $this->orderItemModel->calculatePriceWithDiscount($price, $discount);
            
            $items[] = [
                'product_id' => (int)$item['product_id'],
                'format_id' => (int)$item['format_id'],
                'status_order_item_id' => (int)($item['status_id'] ?? $this->orderModel->getDefaultStatusId('order_item')),
                'price' => $price,
                'discount_percent' => $discount,
                'price_with_discount' => $priceWithDiscount
            ];
            
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
        
        if (empty($data['status_id'])) {
            $this->error('Не указан статус');
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
        
        if (empty($data['status_id'])) {
            $this->error('Не указан статус');
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
        
        if (empty($data['status_id'])) {
            $this->error('Не указан статус');
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
        
        if (empty($data['order_ids']) || !is_array($data['order_ids'])) {
            $this->error('Не выбраны заказы');
            return;
        }
        
        if (empty($data['status_id'])) {
            $this->error('Не указан статус');
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
        
        if (empty($data['order_ids']) || !is_array($data['order_ids'])) {
            $this->error('Не выбраны заказы');
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