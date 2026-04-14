<?php
/**
 * Модель для работы с отчётами
 */

require_once __DIR__ . '/../core/Model.php';

class Report extends Model
{
    protected $table = 'orders';
    protected $softDelete = true;
    
    /**
     * Получить ID статуса "Оплачен"
     */
    public function getPaidStatusId()
    {
        $sql = "SELECT id FROM statuses WHERE type = 'payment' AND name = 'Оплачен' LIMIT 1";
        $stmt = $this->db->query($sql);
        $result = $stmt->fetch();
        return $result ? $result['id'] : null;
    }
    
    /**
     * Отчёт 1: Выручка по постановщикам (с общим итогом)
     * 
     * @param string $dateFrom
     * @param string $dateTo
     * @return array
     */
    public function getRevenueByAssigner($dateFrom, $dateTo)
    {
        $paidStatusId = $this->getPaidStatusId();
        if (!$paidStatusId) {
            return ['data' => [], 'total' => null];
        }
        
        $sql = "SELECT 
                    u.name as assigner_name,
                    COUNT(o.id) as orders_count,
                    AVG(o.total_items_cost) as avg_check,
                    SUM(o.total_items_cost) as total_revenue
                FROM {$this->table} o
                LEFT JOIN users u ON o.user_id = u.id
                WHERE o.status_payment_id = :paid_status_id
                AND o.date_created BETWEEN :date_from AND :date_to
                AND o.deleted_at IS NULL
                GROUP BY u.id, u.name
                ORDER BY total_revenue DESC";
        
        $stmt = $this->db->query($sql, [
            'paid_status_id' => $paidStatusId,
            'date_from' => $dateFrom,
            'date_to' => $dateTo
        ]);
        
        $data = $stmt->fetchAll();
        
        // Подсчёт общих итогов
        $total = [
            'assigner_name' => 'ИТОГО',
            'orders_count' => 0,
            'avg_check' => 0,
            'total_revenue' => 0
        ];
        
        foreach ($data as $row) {
            $total['orders_count'] += $row['orders_count'];
            $total['total_revenue'] += $row['total_revenue'];
        }
        
        if ($total['orders_count'] > 0) {
            $total['avg_check'] = round($total['total_revenue'] / $total['orders_count'], 2);
        }
        
        return [
            'data' => $data,
            'total' => $total
        ];
    }
    
    /**
     * Отчёт 2: Количество заказов по покупателю
     * 
     * @param string $dateFrom
     * @param string $dateTo
     * @return array
     */
    public function getOrdersByCustomer($dateFrom, $dateTo)
    {
        $paidStatusId = $this->getPaidStatusId();
        if (!$paidStatusId) {
            return [];
        }
        
        $sql = "SELECT 
                    TRIM(LOWER(o.recipient_name)) as customer_name_normalized,
                    o.recipient_name as customer_name_original,
                    COUNT(o.id) as orders_count
                FROM {$this->table} o
                WHERE o.status_payment_id = :paid_status_id
                AND o.date_created BETWEEN :date_from AND :date_to
                AND o.deleted_at IS NULL
                AND o.recipient_name IS NOT NULL
                AND o.recipient_name != ''
                GROUP BY TRIM(LOWER(o.recipient_name)), o.recipient_name
                ORDER BY orders_count DESC";
        
        $stmt = $this->db->query($sql, [
            'paid_status_id' => $paidStatusId,
            'date_from' => $dateFrom,
            'date_to' => $dateTo
        ]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Отчёт 3: Самый популярный товар
     * 
     * @param string $dateFrom
     * @param string $dateTo
     * @return array
     */
    public function getPopularProducts($dateFrom, $dateTo)
    {
        $paidStatusId = $this->getPaidStatusId();
        if (!$paidStatusId) {
            return [];
        }
        
        $sql = "SELECT 
                    p.name as product_name,
                    COUNT(DISTINCT oi.order_id) as orders_count
                FROM order_items oi
                INNER JOIN {$this->table} o ON oi.order_id = o.id
                INNER JOIN products p ON oi.product_id = p.id
                WHERE o.status_payment_id = :paid_status_id
                AND o.date_created BETWEEN :date_from AND :date_to
                AND o.deleted_at IS NULL
                AND p.deleted_at IS NULL
                GROUP BY p.id, p.name
                ORDER BY orders_count DESC";
        
        $stmt = $this->db->query($sql, [
            'paid_status_id' => $paidStatusId,
            'date_from' => $dateFrom,
            'date_to' => $dateTo
        ]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Отчёт 4: Самый популярный формат
     * 
     * @param string $dateFrom
     * @param string $dateTo
     * @return array
     */
    public function getPopularFormats($dateFrom, $dateTo)
    {
        $paidStatusId = $this->getPaidStatusId();
        if (!$paidStatusId) {
            return [];
        }
        
        $sql = "SELECT 
                    f.name as format_name,
                    COUNT(DISTINCT oi.order_id) as orders_count
                FROM order_items oi
                INNER JOIN {$this->table} o ON oi.order_id = o.id
                INNER JOIN formats f ON oi.format_id = f.id
                WHERE o.status_payment_id = :paid_status_id
                AND o.date_created BETWEEN :date_from AND :date_to
                AND o.deleted_at IS NULL
                AND f.deleted_at IS NULL
                GROUP BY f.id, f.name
                ORDER BY orders_count DESC";
        
        $stmt = $this->db->query($sql, [
            'paid_status_id' => $paidStatusId,
            'date_from' => $dateFrom,
            'date_to' => $dateTo
        ]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Отчёт 5: Количество первичных и вторичных продаж
     * 
     * @param string $dateFrom
     * @param string $dateTo
     * @return array
     */
    public function getSalesByLabel($dateFrom, $dateTo)
    {
        $paidStatusId = $this->getPaidStatusId();
        if (!$paidStatusId) {
            return [];
        }
        
        $sql = "SELECT 
                    o.sale_label,
                    COUNT(o.id) as orders_count
                FROM {$this->table} o
                WHERE o.status_payment_id = :paid_status_id
                AND o.date_created BETWEEN :date_from AND :date_to
                AND o.deleted_at IS NULL
                AND o.sale_label IS NOT NULL
                AND o.sale_label != ''
                GROUP BY o.sale_label
                ORDER BY orders_count DESC";
        
        $stmt = $this->db->query($sql, [
            'paid_status_id' => $paidStatusId,
            'date_from' => $dateFrom,
            'date_to' => $dateTo
        ]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Получить дату начала текущего месяца
     */
    public function getCurrentMonthStart()
    {
        return date('Y-m-01');
    }
    
    /**
     * Получить сегодняшнюю дату
     */
    public function getToday()
    {
        return date('Y-m-d');
    }
}