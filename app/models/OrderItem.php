<?php
/**
 * Модель товара в заказе
 */

require_once __DIR__ . '/../core/Model.php';

// Константа для формата "Свободная цена" (если файл constants.php не загружен)
if (!defined('FLEXIBLE_PRICE_FORMAT_ID')) {
    define('FLEXIBLE_PRICE_FORMAT_ID', 99);
}

class OrderItem extends Model
{
    protected $table = 'order_items';
    protected $softDelete = false;
    
    /**
     * Получить товары для производства
     * Исключаем товары с форматом "Свободная цена" (сертификаты и т.п.)
     */
    public function getProductionItems()
    {
        $sql = "SELECT 
                    oi.id,
                    oi.order_id,
                    o.order_number,
                    o.date_created,
                    p.name as product_name,
                    f.name as format_name,
                    s.name as status_name,
                    s.id as status_id
                FROM order_items oi
                INNER JOIN orders o ON oi.order_id = o.id
                INNER JOIN products p ON oi.product_id = p.id
                INNER JOIN formats f ON oi.format_id = f.id
                INNER JOIN statuses s ON oi.status_order_item_id = s.id
                WHERE s.name = 'Сделать'
                AND o.deleted_at IS NULL
                AND oi.format_id != " . FLEXIBLE_PRICE_FORMAT_ID . "
                ORDER BY p.name ASC";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
    
    /**
     * Обновить статус товара
     */
    public function updateStatus($itemId, $statusId)
    {
        return $this->update($itemId, ['status_order_item_id' => $statusId]);
    }
    
    /**
     * Рассчитать цену со скидкой
     */
    public function calculatePriceWithDiscount($price, $discountPercent)
    {
        return round($price * (1 - $discountPercent / 100), 2);
    }
    
    /**
     * Рассчитать общую стоимость товаров
     */
    public function calculateTotalItemsCost($items)
    {
        $total = 0;
        foreach ($items as $item) {
            $total += $item['price_with_discount'];
        }
        return round($total, 2);
    }
    
    /**
     * Рассчитать итоговую стоимость заказа
     */
    public function calculateTotalCost($totalItemsCost, $shippingCost)
    {
        return round($totalItemsCost + $shippingCost, 2);
    }
    
    /**
     * Получить количество товаров со статусом "Сделать"
     * Исключаем товары с форматом "Свободная цена"
     */
    public function getMakeItemsCount()
    {
        $sql = "SELECT COUNT(*) as count 
                FROM order_items oi
                INNER JOIN statuses s ON oi.status_order_item_id = s.id
                INNER JOIN orders o ON oi.order_id = o.id
                WHERE s.name = 'Сделать'
                AND o.deleted_at IS NULL
                AND oi.format_id != " . FLEXIBLE_PRICE_FORMAT_ID;
        
        $stmt = $this->db->query($sql);
        $result = $stmt->fetch();
        
        return (int)$result['count'];
    }
    
    /**
     * Получить товары со статусом "Сделать" по заказу
     * Исключаем товары с форматом "Свободная цена"
     */
    public function getMakeItemsByOrderId($orderId)
    {
        $sql = "SELECT 
                    oi.*,
                    p.name as product_name,
                    f.name as format_name
                FROM order_items oi
                INNER JOIN products p ON oi.product_id = p.id
                INNER JOIN formats f ON oi.format_id = f.id
                INNER JOIN statuses s ON oi.status_order_item_id = s.id
                WHERE oi.order_id = :order_id
                AND s.name = 'Сделать'
                AND oi.format_id != " . FLEXIBLE_PRICE_FORMAT_ID;
        
        $stmt = $this->db->query($sql, ['order_id' => $orderId]);
        return $stmt->fetchAll();
    }
}