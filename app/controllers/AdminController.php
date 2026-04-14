<?php
/**
 * Контроллер администратора
 * 
 * Отвечает за административные действия: сброс счётчика заказов,
 * системные настройки и другие операции только для админов
 */

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/OrderItem.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Format.php';
require_once __DIR__ . '/../models/Source.php';
require_once __DIR__ . '/../models/ShippingMethod.php';
require_once __DIR__ . '/../models/Status.php';

class AdminController extends Controller
{
    private $orderModel;
    private $userModel;
    private $productModel;
    private $formatModel;
    private $sourceModel;
    private $shippingModel;
    private $statusModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->orderModel = new Order();
        $this->userModel = new User();
        $this->productModel = new Product();
        $this->formatModel = new Format();
        $this->sourceModel = new Source();
        $this->shippingModel = new ShippingMethod();
        $this->statusModel = new Status();
    }
    
    /**
     * Сброс счётчика заказов
     * POST /api/admin/reset-order-counter
     */
    public function resetOrderCounter()
    {
        if (!$this->requireAdmin()) {
            return;
        }
        
        $data = $this->getRequestData();
        
        // Подтверждение действия (можно передать confirm: true)
        if (empty($data['confirm'])) {
            $this->error('Для сброса счётчика требуется подтверждение');
            return;
        }
        
        $result = $this->orderModel->resetOrderCounter();
        
        if (!$result) {
            $this->error('Ошибка сброса счётчика', 500);
            return;
        }
        
        $this->success(null, 'Счётчик заказов успешно сброшен. Следующий заказ будет №1');
    }
    
    /**
     * Получить системную статистику
     * GET /api/admin/statistics
     */
    public function getStatistics()
    {
        if (!$this->requireAdmin()) {
            return;
        }
        
        $stats = [
            'orders' => [
                'total' => $this->orderModel->count(),
                'in_progress' => 0,
                'packed' => 0,
                'sent' => 0
            ],
            'users' => [
                'total' => $this->userModel->getCount(),
                'admins' => 0,
                'managers' => 0
            ],
            'products' => $this->productModel->getCount(),
            'formats' => $this->formatModel->getCount(),
            'sources' => $this->sourceModel->getCount(),
            'shipping_methods' => $this->shippingModel->getCount()
        ];
        
        // Получаем статусы
        $orderStatuses = $this->statusModel->getOrderStatuses();
        foreach ($orderStatuses as $status) {
            $sql = "SELECT COUNT(*) as count FROM orders WHERE status_order_id = :status_id AND deleted_at IS NULL";
            $stmt = $this->orderModel->query($sql, ['status_id' => $status['id']]);
            $result = $stmt->fetch();
            
            if ($status['name'] === 'В работе') {
                $stats['orders']['in_progress'] = (int)$result['count'];
            } elseif ($status['name'] === 'Упакован') {
                $stats['orders']['packed'] = (int)$result['count'];
            } elseif ($status['name'] === 'Отправлен') {
                $stats['orders']['sent'] = (int)$result['count'];
            }
        }
        
        // Получаем количество пользователей по ролям
        $sql = "SELECT role, COUNT(*) as count FROM users WHERE deleted_at IS NULL GROUP BY role";
        $stmt = $this->userModel->query($sql);
        $roleCounts = $stmt->fetchAll();
        
        foreach ($roleCounts as $roleCount) {
            if ($roleCount['role'] === 'admin') {
                $stats['users']['admins'] = (int)$roleCount['count'];
            } elseif ($roleCount['role'] === 'manager') {
                $stats['users']['managers'] = (int)$roleCount['count'];
            }
        }
        
        $this->success($stats);
    }
    
    /**
     * Получить информацию о системе
     * GET /api/admin/system-info
     */
    public function getSystemInfo()
    {
        if (!$this->requireAdmin()) {
            return;
        }
        
        $info = [
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'database' => 'MySQL',
            'order_counter' => $this->orderModel->getOrderCounter(),
            'last_order_number' => $this->getLastOrderNumber()
        ];
        
        $this->success($info);
    }
    
    /**
     * Получить последний номер заказа
     * 
     * @return string|null
     */
    private function getLastOrderNumber()
    {
        $sql = "SELECT order_number FROM orders WHERE deleted_at IS NULL ORDER BY id DESC LIMIT 1";
        $stmt = $this->orderModel->query($sql);
        $result = $stmt->fetch();
        
        return $result ? $result['order_number'] : null;
    }
    
    /**
     * Очистить временные файлы
     * POST /api/admin/cleanup-temp
     */
    public function cleanupTemp()
    {
        if (!$this->requireAdmin()) {
            return;
        }
        
        $tempDir = ROOT_PATH . '/uploads/temp/';
        $deletedCount = 0;
        
        if (is_dir($tempDir)) {
            $files = scandir($tempDir);
            $now = time();
            
            foreach ($files as $file) {
                if ($file === '.' || $file === '..') {
                    continue;
                }
                
                $filePath = $tempDir . $file;
                // Удаляем файлы старше 24 часов
                if (is_file($filePath) && ($now - filemtime($filePath)) > 86400) {
                    if (unlink($filePath)) {
                        $deletedCount++;
                    }
                }
            }
        }
        
        $this->success(['deleted' => $deletedCount], "Удалено {$deletedCount} временных файлов");
    }
    
    /**
     * Получить логи (если есть)
     * GET /api/admin/logs
     */
    public function getLogs()
    {
        if (!$this->requireAdmin()) {
            return;
        }
        
        $logFile = ROOT_PATH . '/logs/error.log';
        $logs = [];
        
        if (file_exists($logFile)) {
            $content = file_get_contents($logFile);
            $lines = explode("\n", $content);
            $logs = array_slice(array_reverse($lines), 0, 100);
        }
        
        $this->success(['logs' => $logs]);
    }
    
    /**
     * Проверить подключение к базе данных
     * GET /api/admin/check-db
     */
    public function checkDatabase()
    {
        if (!$this->requireAdmin()) {
            return;
        }
        
        try {
            // Простой запрос для проверки
            $sql = "SELECT 1 as test";
            $stmt = $this->orderModel->query($sql);
            $result = $stmt->fetch();
            
            $this->success([
                'status' => 'ok',
                'message' => 'Подключение к базе данных работает нормально'
            ]);
        } catch (Exception $e) {
            $this->error('Ошибка подключения к базе данных: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Создать резервную копию базы данных (структура + данные)
     * GET /api/admin/backup
     */
    public function backupDatabase()
    {
        if (!$this->requireAdmin()) {
            return;
        }
        
        // Здесь будет логика создания бекапа
        // Пока возвращаем заглушку
        $this->success(null, 'Функция резервного копирования будет добавлена позже');
    }
    
    /**
     * Получить настройки системы
     * GET /api/admin/settings
     */
    public function getSettings()
    {
        if (!$this->requireAdmin()) {
            return;
        }
        
        $settings = [
            'order_counter' => $this->orderModel->getOrderCounter(),
            'default_statuses' => [
                'order' => $this->statusModel->getDefault('order'),
                'payment' => $this->statusModel->getDefault('payment'),
                'order_item' => $this->statusModel->getDefault('order_item')
            ]
        ];
        
        $this->success($settings);
    }
}