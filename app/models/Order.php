<?php
/**
 * Модель заказа
 * 
 * Управляет заказами: создание, обновление, получение списка, папки и т.д.
 */

require_once __DIR__ . '/../core/Model.php';

class Order extends Model
{
    protected $table = 'orders';
    protected $softDelete = true;
    
    /**
     * Получить все заказы с фильтрацией
     */
    public function getAllWithFilters($filters = [])
    {
        $sql = "SELECT 
                    o.*,
                    so.name as status_order_name,
                    so.color as status_order_color,
                    sp.name as status_payment_name,
                    sp.color as status_payment_color,
                    s.name as source_name,
                    sm.name as shipping_method_name,
                    u.name as user_name
                FROM {$this->table} o
                LEFT JOIN statuses so ON o.status_order_id = so.id
                LEFT JOIN statuses sp ON o.status_payment_id = sp.id
                LEFT JOIN sources s ON o.source_id = s.id
                LEFT JOIN shipping_methods sm ON o.shipping_method_id = sm.id
                LEFT JOIN users u ON o.user_id = u.id
                WHERE 1=1";
        
        $params = [];
        
        if ($this->softDelete) {
            $sql .= " AND o.{$this->deletedAt} IS NULL";
        }
        
        if (!empty($filters['year'])) {
            $sql .= " AND YEAR(o.date_created) = :year";
            $params['year'] = $filters['year'];
        }
        
        if (!empty($filters['month'])) {
            $sql .= " AND MONTH(o.date_created) = :month";
            $params['month'] = $filters['month'];
        }
        
        if (!empty($filters['date_from'])) {
            $sql .= " AND o.date_created >= :date_from";
            $params['date_from'] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND o.date_created <= :date_to";
            $params['date_to'] = $filters['date_to'];
        }
        
        if (!empty($filters['status_id'])) {
            $sql .= " AND o.status_order_id = :status_id";
            $params['status_id'] = $filters['status_id'];
        }
        
        // Глобальный поиск по всем полям
        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $sql .= " AND (
                o.order_number LIKE :search1 OR
                o.recipient_name LIKE :search2 OR
                o.recipient_phone LIKE :search3 OR
                o.recipient_email LIKE :search4 OR
                o.tracking_number LIKE :search5 OR
                o.link LIKE :search6 OR
                o.comments LIKE :search7 OR
                s.name LIKE :search8 OR
                sm.name LIKE :search9 OR
                u.name LIKE :search10 OR
                EXISTS (
                    SELECT 1 FROM order_items oi 
                    INNER JOIN products p ON oi.product_id = p.id
                    WHERE oi.order_id = o.id AND p.name LIKE :search11
                ) OR
                EXISTS (
                    SELECT 1 FROM order_items oi2 
                    INNER JOIN formats f ON oi2.format_id = f.id
                    WHERE oi2.order_id = o.id AND f.name LIKE :search12
                )
            )";
            $params['search1'] = $search;
            $params['search2'] = $search;
            $params['search3'] = $search;
            $params['search4'] = $search;
            $params['search5'] = $search;
            $params['search6'] = $search;
            $params['search7'] = $search;
            $params['search8'] = $search;
            $params['search9'] = $search;
            $params['search10'] = $search;
            $params['search11'] = $search;
            $params['search12'] = $search;
        }
        
        $sql .= " ORDER BY o.date_created DESC, o.id DESC";
        
        $stmt = $this->db->query($sql, $params);
        $orders = $stmt->fetchAll();
        
        foreach ($orders as &$order) {
            $order['items'] = $this->getOrderItems($order['id']);
        }
        
        return $orders;
    }
    
    /**
     * Получить заказ по ID с товарами
     */
    public function getOrderWithItems($id)
    {
        $sql = "SELECT 
                    o.*,
                    so.name as status_order_name,
                    sp.name as status_payment_name,
                    s.name as source_name,
                    sm.name as shipping_method_name,
                    u.name as user_name
                FROM {$this->table} o
                LEFT JOIN statuses so ON o.status_order_id = so.id
                LEFT JOIN statuses sp ON o.status_payment_id = sp.id
                LEFT JOIN sources s ON o.source_id = s.id
                LEFT JOIN shipping_methods sm ON o.shipping_method_id = sm.id
                LEFT JOIN users u ON o.user_id = u.id
                WHERE o.{$this->primaryKey} = :id";
        
        if ($this->softDelete) {
            $sql .= " AND o.{$this->deletedAt} IS NULL";
        }
        
        $stmt = $this->db->query($sql, ['id' => $id]);
        $order = $stmt->fetch();
        
        if ($order) {
            $order['items'] = $this->getOrderItems($id);
        }
        
        return $order;
    }
    
    /**
     * Получить товары заказа
     */
    public function getOrderItems($orderId)
    {
        $sql = "SELECT 
                    oi.*,
                    p.name as product_name,
                    f.name as format_name,
                    s.name as status_name,
                    s.id as status_id
                FROM order_items oi
                LEFT JOIN products p ON oi.product_id = p.id
                LEFT JOIN formats f ON oi.format_id = f.id
                LEFT JOIN statuses s ON oi.status_order_item_id = s.id
                WHERE oi.order_id = :order_id
                ORDER BY oi.id ASC";
        
        $stmt = $this->db->query($sql, ['order_id' => $orderId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Создать новый заказ с товарами
     */
    public function createOrder($orderData, $items, $userId)
    {
        $this->db->beginTransaction();
        
        try {
            $counter = $this->getOrderCounter();
            $orderNumber = 'Заказ №' . ($counter + 1);
            $this->updateOrderCounter($counter + 1);
            
            $orderId = $this->create([
                'order_number' => $orderNumber,
                'user_id' => $userId,
                'date_created' => date('Y-m-d'),
                'status_order_id' => $this->getDefaultStatusId('order'),
                'status_payment_id' => $this->getDefaultStatusId('payment'),
                'sale_label' => $orderData['sale_label'],
                'source_id' => $orderData['source_id'],
                'link' => $orderData['link'],
                'comments' => $orderData['comments'],
                'shipping_method_id' => $orderData['shipping_method_id'],
                'tracking_number' => $orderData['tracking_number'],
                'recipient_name' => $orderData['recipient_name'],
                'recipient_phone' => $orderData['recipient_phone'],
                'recipient_email' => $orderData['recipient_email'],
                'shipping_cost' => $orderData['shipping_cost'],
                'total_items_cost' => $orderData['total_items_cost'],
                'total_cost' => $orderData['total_cost'],
                'is_urgent' => $orderData['is_urgent'] ?? 0
            ]);
            
            if (!$orderId) {
                throw new Exception('Ошибка создания заказа');
            }
            
            $itemModel = new OrderItem();
            foreach ($items as $item) {
                $itemData = [
                    'order_id' => $orderId,
                    'product_id' => $item['product_id'],
                    'format_id' => $item['format_id'],
                    'status_order_item_id' => $this->getDefaultStatusId('order_item'),
                    'price' => $item['price'],
                    'discount_percent' => $item['discount_percent'],
                    'price_with_discount' => $item['price_with_discount']
                ];
                
                if (!$itemModel->create($itemData)) {
                    throw new Exception('Ошибка создания товара заказа');
                }
            }
            
            $this->db->commit();
            return $orderId;
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Ошибка создания заказа: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Обновить заказ
     */
    public function updateOrder($orderId, $orderData, $items)
    {
        $this->db->beginTransaction();
        
        try {
            $updateData = [
                'sale_label' => $orderData['sale_label'],
                'source_id' => $orderData['source_id'],
                'link' => $orderData['link'],
                'comments' => $orderData['comments'],
                'shipping_method_id' => $orderData['shipping_method_id'],
                'tracking_number' => $orderData['tracking_number'],
                'recipient_name' => $orderData['recipient_name'],
                'recipient_phone' => $orderData['recipient_phone'],
                'recipient_email' => $orderData['recipient_email'],
                'shipping_cost' => $orderData['shipping_cost'],
                'total_items_cost' => $orderData['total_items_cost'],
                'total_cost' => $orderData['total_cost'],
                'is_urgent' => $orderData['is_urgent'] ?? 0
            ];
            
            if (!$this->update($orderId, $updateData)) {
                throw new Exception('Ошибка обновления заказа');
            }
            
            $itemModel = new OrderItem();
            $sql = "DELETE FROM order_items WHERE order_id = :order_id";
            $this->db->query($sql, ['order_id' => $orderId]);
            
            foreach ($items as $item) {
                $itemData = [
                    'order_id' => $orderId,
                    'product_id' => $item['product_id'],
                    'format_id' => $item['format_id'],
                    'status_order_item_id' => $item['status_order_item_id'] ?? $this->getDefaultStatusId('order_item'),
                    'price' => $item['price'],
                    'discount_percent' => $item['discount_percent'],
                    'price_with_discount' => $item['price_with_discount']
                ];
                
                if (!$itemModel->create($itemData)) {
                    throw new Exception('Ошибка создания товара заказа');
                }
            }
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Ошибка обновления заказа: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Обновить статус заказа или оплаты
     */
    public function updateStatus($orderId, $type, $statusId)
    {
        $field = ($type === 'order') ? 'status_order_id' : 'status_payment_id';
        return $this->update($orderId, [$field => $statusId]);
    }
    
    /**
     * Получить структуру папок
     */
    public function getFolders()
    {
        $sql = "SELECT 
                    DISTINCT 
                    YEAR(date_created) as year,
                    MONTH(date_created) as month,
                    DATE_FORMAT(date_created, '%M') as month_name
                FROM {$this->table}
                WHERE 1=1";
        
        if ($this->softDelete) {
            $sql .= " AND {$this->deletedAt} IS NULL";
        }
        
        $sql .= " ORDER BY year DESC, month DESC";
        
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
                'month_name' => $row['month_name']
            ];
        }
        
        return array_values($folders);
    }
    
    /**
     * Получить ID статуса по умолчанию
     */
    public function getDefaultStatusId($type)
    {
        $sql = "SELECT id FROM statuses WHERE type = :type AND is_default = 1 LIMIT 1";
        $stmt = $this->db->query($sql, ['type' => $type]);
        $result = $stmt->fetch();
        return $result ? $result['id'] : null;
    }
    
    /**
     * Получить счётчик заказов
     */
    public function getOrderCounter()
    {
        $sql = "SELECT setting_value FROM settings WHERE setting_key = 'order_counter' LIMIT 1";
        $stmt = $this->db->query($sql);
        $result = $stmt->fetch();
        return $result ? (int)$result['setting_value'] : 0;
    }
    
    /**
     * Обновить счётчик заказов
     */
    public function updateOrderCounter($value)
    {
        $sql = "UPDATE settings SET setting_value = :value WHERE setting_key = 'order_counter'";
        $this->db->query($sql, ['value' => $value]);
        return true;
    }
    
    /**
     * Сбросить счётчик заказов
     */
    public function resetOrderCounter()
    {
        return $this->updateOrderCounter(0);
    }
    
    /**
     * Массовое обновление статуса
     */
    public function bulkUpdateStatus($orderIds, $statusId)
    {
        if (empty($orderIds)) {
            return false;
        }
        
        $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
        $sql = "UPDATE {$this->table} SET status_order_id = ? WHERE {$this->primaryKey} IN ({$placeholders})";
        
        $params = array_merge([$statusId], $orderIds);
        $this->db->query($sql, $params);
        
        return true;
    }
    
    /**
     * Массовое удаление
     */
    public function bulkDelete($orderIds)
    {
        if (empty($orderIds)) {
            return false;
        }
        
        $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
        $sql = "UPDATE {$this->table} SET {$this->deletedAt} = NOW() WHERE {$this->primaryKey} IN ({$placeholders})";
        
        $this->db->query($sql, $orderIds);
        
        return true;
    }
}