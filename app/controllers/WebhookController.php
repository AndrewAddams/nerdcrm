<?php
/**
 * Контроллер вебхуков
 * 
 * Обрабатывает входящие запросы от Tilda
 */

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/TildaOrder.php';

class WebhookController extends Controller
{
    private $tildaOrderModel;
    
    // Секретный токен для защиты вебхука
    private $secretToken = 'WZ5W8KAiNGcAGveHNobm2xKiaiV7P9ufUip4XKoU793HSX3qMA';
    
    public function __construct()
    {
        parent::__construct();
        $this->tildaOrderModel = new TildaOrder();
    }
    
    /**
     * Эндпоинт для приёма вебхуков от Tilda
     * POST /api/webhook/tilda
     */
    public function tilda()
    {
        // Проверяем токен (передаётся в параметре ?token=...)
        $token = $_GET['token'] ?? '';
        if ($token !== $this->secretToken) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
        
        // Получаем сырые данные
        $rawInput = file_get_contents('php://input');
        
        if (empty($rawInput)) {
            http_response_code(400);
            echo json_encode(['error' => 'Empty request body']);
            exit;
        }
        
        // Определяем тип содержимого
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        // Если это form-urlencoded, парсим в массив
        if (strpos($contentType, 'application/x-www-form-urlencoded') !== false) {
            parse_str($rawInput, $data);
            $jsonData = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        } else {
            // Пробуем как JSON
            $decoded = json_decode($rawInput, true);
            if ($decoded !== null) {
                $jsonData = json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            } else {
                $jsonData = $rawInput;
            }
        }
        
        // Логируем входящий запрос
        $this->logWebhook($jsonData);
        
        // Сохраняем в базу данных
        $id = $this->tildaOrderModel->saveFromWebhook($jsonData);
        
        if ($id) {
            http_response_code(200);
            echo json_encode(['success' => true, 'id' => $id]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to save order']);
        }
        exit;
    }
    
    /**
     * Получить список заказов Tilda
     * GET /api/tilda/orders
     */
    public function getOrders()
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
        
        $orders = $this->tildaOrderModel->getAllWithDates();
        
        // Фильтруем по году и месяцу
        if (!empty($filters['year'])) {
            $orders = array_filter($orders, function($order) use ($filters) {
                return $order['year'] == $filters['year'];
            });
        }
        
        if (!empty($filters['month'])) {
            $orders = array_filter($orders, function($order) use ($filters) {
                return $order['month'] == $filters['month'];
            });
        }
        
        $this->success(array_values($orders));
    }
    
    /**
     * Получить структуру папок для Tilda заказов
     * GET /api/tilda/folders
     */
    public function getFolders()
    {
        if (!$this->requireAuth()) {
            return;
        }
        
        $folders = $this->tildaOrderModel->getFolders();
        $this->success($folders);
    }
    
/**
 * Получить количество необработанных заказов Tilda
 * GET /api/tilda/unread-count
 */
public function getUnreadCount()
{
    if (!$this->requireAuth()) {
        return;
    }
    
    try {
        // Используем прямой запрос к БД через модель
        $sql = "SELECT COUNT(*) as count FROM tilda_orders WHERE is_processed = 0 AND deleted_at IS NULL";
        $stmt = $this->tildaOrderModel->query($sql);
        $result = $stmt->fetch();
        
        $this->success(['count' => (int)$result['count']]);
    } catch (Exception $e) {
        // Логируем ошибку
        error_log("Ошибка в getUnreadCount: " . $e->getMessage());
        $this->error('Ошибка получения счётчика', 500);
    }
}
    
    /**
     * Удалить заказ Tilda
     * DELETE /api/tilda/orders/{id}
     */
    public function deleteOrder($params)
    {
        if (!$this->requireAdmin()) {
            return;
        }
        
        $id = (int)$params['id'];
        $result = $this->tildaOrderModel->delete($id);
        
        if (!$result) {
            $this->error('Ошибка удаления', 500);
            return;
        }
        
        $this->success(null, 'Заказ удалён');
    }
    
    /**
     * Отметить заказ как прочитанный
     * POST /api/tilda/orders/{id}/mark-read
     */
    public function markAsRead($params)
    {
        if (!$this->requireAuth()) {
            return;
        }
        
        $id = (int)$params['id'];
        $result = $this->tildaOrderModel->markAsProcessed($id);
        
        if (!$result) {
            $this->error('Ошибка', 500);
            return;
        }
        
        $this->success(null, 'Заказ отмечен как прочитанный');
    }
    
    /**
     * Логирование вебхуков
     */
    private function logWebhook($data)
    {
        $logDir = ROOT_PATH . '/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $logFile = $logDir . '/webhook.log';
        $logEntry = date('Y-m-d H:i:s') . "\n" . $data . "\n---\n";
        file_put_contents($logFile, $logEntry, FILE_APPEND);
    }
}