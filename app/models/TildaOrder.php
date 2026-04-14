<?php
/**
 * Модель заказа из Tilda
 * 
 * Управляет заказами, полученными через вебхук
 */

require_once __DIR__ . '/../core/Model.php';

class TildaOrder extends Model
{
    protected $table = 'tilda_orders';
    protected $softDelete = true;
    
    /**
     * Получить все заказы с группировкой по годам и месяцам
     */
    public function getAllWithDates()
    {
        $sql = "SELECT 
                    id,
                    raw_json,
                    order_date,
                    created_at,
                    is_processed,
                    DATE(created_at) as date_created,
                    YEAR(created_at) as year,
                    MONTH(created_at) as month,
                    DATE_FORMAT(created_at, '%M') as month_name
                FROM {$this->table}
                WHERE {$this->deletedAt} IS NULL
                ORDER BY created_at DESC";
        
        $stmt = $this->db->query($sql);
        $orders = $stmt->fetchAll();
        
        // Парсим каждый заказ для удобства отображения
        foreach ($orders as &$order) {
            $parsed = $this->parseOrderData($order['raw_json']);
            $order['parsed'] = $parsed;
        }
        
        return $orders;
    }
    
    /**
     * Получить структуру папок (годы и месяцы)
     */
    public function getFolders()
    {
        $sql = "SELECT 
                    DISTINCT 
                    YEAR(created_at) as year,
                    MONTH(created_at) as month,
                    DATE_FORMAT(created_at, '%M') as month_name,
                    COUNT(*) as count
                FROM {$this->table}
                WHERE {$this->deletedAt} IS NULL
                GROUP BY YEAR(created_at), MONTH(created_at), month_name
                ORDER BY year DESC, month DESC";
        
        $stmt = $this->db->query($sql);
        $results = $stmt->fetchAll();
        
        $folders = [];
        foreach ($results as $row) {
            $year = $row['year'];
            if (!isset($folders[$year])) {
                $folders[$year] = [
                    'year' => $year,
                    'months' => []
                ];
            }
            $folders[$year]['months'][] = [
                'month' => $row['month'],
                'month_name' => $row['month_name'],
                'count' => $row['count']
            ];
        }
        
        return array_values($folders);
    }
    
    /**
     * Парсинг данных заказа из JSON
     */
    public function parseOrderData($rawJson)
    {
        $data = json_decode($rawJson, true);
        if (!$data) {
            return [
                'customer_name' => '—',
                'phone' => '—',
                'email' => '—',
                'comment' => '—',
                'delivery_service' => '—',
                'delivery_city' => '—',
                'delivery_address' => '—',
                'items' => []
            ];
        }
        
        // Декодируем payment, если это строка JSON
        $payment = $data['payment'] ?? '';
        if (is_string($payment)) {
            $payment = json_decode($payment, true);
        }
        if (!is_array($payment)) {
            $payment = [];
        }
        
        // Очистка адреса
        $rawAddress = $payment['delivery_address'] ?? '';
        $deliveryAddress = $this->cleanAddress($rawAddress);
        
        // Парсинг товаров
        $items = [];
        $products = $payment['products'] ?? [];
        if (is_string($products)) {
            $products = [$products];
        }
        
        foreach ($products as $productString) {
            $parsedItem = $this->parseProductString($productString);
            if ($parsedItem['name']) {
                $items[] = $parsedItem;
            }
        }
        
        return [
            'customer_name' => $payment['delivery_fio'] ?? $data['name'] ?? '—',
            'phone' => $data['phone'] ?? '—',
            'email' => $data['email'] ?? '—',
            'comment' => $data['comment'] ?? '—',
            'delivery_service' => $payment['delivery'] ?? '—',
            'delivery_city' => $payment['delivery_city'] ?? '—',
            'delivery_address' => $deliveryAddress,
            'items' => $items
        ];
    }
    
    /**
     * Очистка адреса доставки
     */
    private function cleanAddress($address)
    {
        if (empty($address)) {
            return '—';
        }
        
        // Удаляем префикс "RU: Пункт выдачи заказа: "
        $address = preg_replace('/^RU: Пункт выдачи заказа:\s*/', '', $address);
        
        // Обрезаем до первой открывающей скобки
        $pos = strpos($address, '(');
        if ($pos !== false) {
            $address = trim(substr($address, 0, $pos));
        }
        
        return $address ?: '—';
    }
    
    /**
     * Парсинг строки товара
     */
    private function parseProductString($productString)
    {
        $result = [
            'name' => '',
            'format' => '',
            'packing' => ''
        ];
        
        // Ищем позицию открывающей скобки
        $openParen = strpos($productString, '(');
        if ($openParen === false) {
            $result['name'] = trim($productString);
            return $result;
        }
        
        // Наименование — всё, что до скобки
        $result['name'] = trim(substr($productString, 0, $openParen));
        
        // Внутренности скобок
        $inner = substr($productString, $openParen + 1);
        $closeParen = strpos($inner, ')');
        if ($closeParen !== false) {
            $inner = substr($inner, 0, $closeParen);
        }
        
        // Ищем Формат
        if (preg_match('/Формат:\s*([^,]+)/u', $inner, $matches)) {
            $result['format'] = trim($matches[1]);
        }
        
        // Ищем Упаковка
        if (preg_match('/Упаковка:\s*([^)]+)/u', $inner, $matches)) {
            $result['packing'] = trim($matches[1]);
        }
        
        return $result;
    }
    
    /**
     * Сохранить заказ из вебхука
     */
    public function saveFromWebhook($jsonData)
    {
        return $this->create([
            'raw_json' => $jsonData,
            'order_date' => null,
            'is_processed' => 0
        ]);
    }
    
    /**
     * Отметить заказ как обработанный
     */
    public function markAsProcessed($id)
    {
        return $this->update($id, ['is_processed' => 1]);
    }
    
    /**
     * Получить заказ по ID
     */
    public function getById($id)
    {
        $order = $this->find($id);
        if ($order) {
            $order['parsed'] = $this->parseOrderData($order['raw_json']);
        }
        return $order;
    }
}