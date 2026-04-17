<?php
/**
 * Контроллер производства
 * 
 * Отвечает за страницу "Список для производства":
 * получение товаров со статусом "Сделать", обновление их статусов
 */

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/OrderItem.php';
require_once __DIR__ . '/../models/Order.php';

// Константа для формата "Свободная цена" (если файл constants.php не загружен)
if (!defined('FLEXIBLE_PRICE_FORMAT_ID')) {
    define('FLEXIBLE_PRICE_FORMAT_ID', 99);
}

class ProductionController extends Controller
{
    private $orderItemModel;
    private $orderModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->orderItemModel = new OrderItem();
        $this->orderModel = new Order();
    }
    
    /**
     * Получить список товаров для производства
     * GET /api/production/items
     */
    public function getItems()
    {
        if (!$this->requireAuth()) {
            return;
        }
        
        $items = $this->orderItemModel->getProductionItems();
        
        $this->success($items);
    }
    
    /**
     * Обновить статус товара в производстве
     * PUT /api/production/items/{itemId}/status
     */
    public function updateStatus($params)
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
     * Получить количество товаров со статусом "Сделать"
     * GET /api/production/count
     */
    public function getCount()
    {
        if (!$this->requireAuth()) {
            return;
        }
        
        $count = $this->orderItemModel->getMakeItemsCount();
        
        $this->success(['count' => $count]);
    }
    
    /**
     * Получить товары для производства по конкретному заказу
     * GET /api/production/items/by-order/{orderId}
     */
    public function getItemsByOrder($params)
    {
        if (!$this->requireAuth()) {
            return;
        }
        
        $orderId = (int)$params['orderId'];
        
        $order = $this->orderModel->find($orderId);
        if (!$order) {
            $this->error('Заказ не найден', 404);
            return;
        }
        
        $items = $this->orderItemModel->getMakeItemsByOrderId($orderId);
        
        $this->success($items);
    }
    
    /**
     * Массовое обновление статусов товаров
     * POST /api/production/bulk-update-status
     */
    public function bulkUpdateStatus()
    {
        if (!$this->requireAuth()) {
            return;
        }
        
        $data = $this->getRequestData();
        
        if (empty($data['item_ids']) || !is_array($data['item_ids'])) {
            $this->error('Не выбраны товары');
            return;
        }
        
        if (empty($data['status_id'])) {
            $this->error('Не указан статус');
            return;
        }
        
        $successCount = 0;
        $errors = [];
        
        foreach ($data['item_ids'] as $itemId) {
            $result = $this->orderItemModel->updateStatus((int)$itemId, (int)$data['status_id']);
            if ($result) {
                $successCount++;
            } else {
                $errors[] = $itemId;
            }
        }
        
        $this->success([
            'success_count' => $successCount,
            'error_count' => count($errors),
            'errors' => $errors
        ], "Обновлено {$successCount} товаров");
    }
    
    /**
     * Отметить товар как готовый
     * POST /api/production/items/{itemId}/complete
     */
    public function completeItem($params)
    {
        if (!$this->requireAuth()) {
            return;
        }
        
        $itemId = (int)$params['itemId'];
        
        // Получаем ID статуса "Готов"
        $statusModel = new Status();
        $readyStatus = $statusModel->getByName('order_item', 'Готов');
        
        if (!$readyStatus) {
            $this->error('Статус "Готов" не найден', 500);
            return;
        }
        
        $result = $this->orderItemModel->updateStatus($itemId, $readyStatus['id']);
        
        if (!$result) {
            $this->error('Ошибка обновления статуса товара', 500);
            return;
        }
        
        $this->success(null, 'Товар отмечен как готовый');
    }
    
    /**
     * Отметить все товары заказа как готовые
     * POST /api/production/complete-order/{orderId}
     */
    public function completeOrderItems($params)
    {
        if (!$this->requireAuth()) {
            return;
        }
        
        $orderId = (int)$params['orderId'];
        
        // Получаем ID статуса "Готов"
        $statusModel = new Status();
        $readyStatus = $statusModel->getByName('order_item', 'Готов');
        
        if (!$readyStatus) {
            $this->error('Статус "Готов" не найден', 500);
            return;
        }
        
        // Получаем все товары заказа со статусом "Сделать"
        $items = $this->orderItemModel->getMakeItemsByOrderId($orderId);
        
        if (empty($items)) {
            $this->error('В заказе нет товаров со статусом "Сделать"');
            return;
        }
        
        $successCount = 0;
        
        foreach ($items as $item) {
            $result = $this->orderItemModel->updateStatus($item['id'], $readyStatus['id']);
            if ($result) {
                $successCount++;
            }
        }
        
        $this->success([
            'updated_count' => $successCount,
            'total_count' => count($items)
        ], "Отмечено {$successCount} товаров как готовые");
    }
}