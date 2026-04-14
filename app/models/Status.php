<?php
/**
 * Модель статуса
 * 
 * Управляет статусами для заказов, оплаты и товаров в заказе
 */

require_once __DIR__ . '/../core/Model.php';

class Status extends Model
{
    /**
     * Название таблицы
     * 
     * @var string
     */
    protected $table = 'statuses';
    
    /**
     * Использовать soft delete (статусы не удаляются)
     * 
     * @var bool
     */
    protected $softDelete = false;
    
    /**
     * Получить статусы по типу
     * 
     * @param string $type 'order', 'payment', 'order_item'
     * @return array
     */
    public function getByType($type)
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE type = :type 
                ORDER BY sort_order ASC, id ASC";
        
        $stmt = $this->db->query($sql, ['type' => $type]);
        return $stmt->fetchAll();
    }
    
    /**
     * Получить статусы заказа
     * 
     * @return array
     */
    public function getOrderStatuses()
    {
        return $this->getByType('order');
    }
    
    /**
     * Получить статусы оплаты
     * 
     * @return array
     */
    public function getPaymentStatuses()
    {
        return $this->getByType('payment');
    }
    
    /**
     * Получить статусы товаров в заказе
     * 
     * @return array
     */
    public function getOrderItemStatuses()
    {
        return $this->getByType('order_item');
    }
    
    /**
     * Получить статус по умолчанию для типа
     * 
     * @param string $type
     * @return array|null
     */
    public function getDefault($type)
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE type = :type AND is_default = 1 
                LIMIT 1";
        
        $stmt = $this->db->query($sql, ['type' => $type]);
        $result = $stmt->fetch();
        
        return $result ?: null;
    }
    
    /**
     * Получить статус по ID
     * 
     * @param int $id
     * @return array|null
     */
    public function getById($id)
    {
        return $this->find($id);
    }
    
    /**
     * Получить статус по имени и типу
     * 
     * @param string $type
     * @param string $name
     * @return array|null
     */
    public function getByName($type, $name)
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE type = :type AND name = :name 
                LIMIT 1";
        
        $stmt = $this->db->query($sql, [
            'type' => $type,
            'name' => $name
        ]);
        
        $result = $stmt->fetch();
        return $result ?: null;
    }
    
    /**
     * Создать новый статус
     * 
     * @param string $type
     * @param string $name
     * @param string|null $color
     * @param bool $isDefault
     * @param int $sortOrder
     * @return int|false
     */
    public function createStatus($type, $name, $color = null, $isDefault = false, $sortOrder = 0)
    {
        // Если это статус по умолчанию, снимаем флаг с других
        if ($isDefault) {
            $sql = "UPDATE {$this->table} SET is_default = 0 WHERE type = :type";
            $this->db->query($sql, ['type' => $type]);
        }
        
        return $this->create([
            'type' => $type,
            'name' => $name,
            'color' => $color,
            'is_default' => $isDefault ? 1 : 0,
            'sort_order' => $sortOrder
        ]);
    }
    
    /**
     * Обновить статус
     * 
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function updateStatus($id, $data)
    {
        // Если меняем флаг is_default, нужно обновить другие статусы
        if (isset($data['is_default']) && $data['is_default']) {
            // Сначала получаем текущий статус, чтобы узнать тип
            $current = $this->find($id);
            if ($current) {
                $sql = "UPDATE {$this->table} SET is_default = 0 WHERE type = :type";
                $this->db->query($sql, ['type' => $current['type']]);
            }
        }
        
        return $this->update($id, $data);
    }
    
    /**
     * Удалить статус (только если он не используется)
     * 
     * @param int $id
     * @return bool|array
     */
    public function deleteStatus($id)
    {
        // Проверяем, используется ли статус
        $status = $this->find($id);
        if (!$status) {
            return ['success' => false, 'error' => 'Статус не найден'];
        }
        
        $table = '';
        $field = '';
        
        switch ($status['type']) {
            case 'order':
                $table = 'orders';
                $field = 'status_order_id';
                break;
            case 'payment':
                $table = 'orders';
                $field = 'status_payment_id';
                break;
            case 'order_item':
                $table = 'order_items';
                $field = 'status_order_item_id';
                break;
        }
        
        if ($table && $field) {
            $sql = "SELECT COUNT(*) as count FROM {$table} WHERE {$field} = :id";
            $stmt = $this->db->query($sql, ['id' => $id]);
            $result = $stmt->fetch();
            
            if ($result['count'] > 0) {
                return [
                    'success' => false, 
                    'error' => 'Невозможно удалить статус: он используется в ' . $result['count'] . ' записях'
                ];
            }
        }
        
        // Если статус не используется, удаляем
        $result = $this->hardDelete($id);
        
        return ['success' => $result, 'error' => $result ? null : 'Ошибка удаления'];
    }
    
    /**
     * Получить ID статуса по имени и типу
     * 
     * @param string $type
     * @param string $name
     * @return int|null
     */
    public function getStatusId($type, $name)
    {
        $status = $this->getByName($type, $name);
        return $status ? $status['id'] : null;
    }
    
    /**
     * Получить все статусы в виде ассоциативного массива для выпадающих списков
     * 
     * @return array
     */
    public function getAllForSelect()
    {
        $sql = "SELECT * FROM {$this->table} ORDER BY type, sort_order, id";
        $stmt = $this->db->query($sql);
        $statuses = $stmt->fetchAll();
        
        $result = [
            'order' => [],
            'payment' => [],
            'order_item' => []
        ];
        
        foreach ($statuses as $status) {
            $result[$status['type']][] = [
                'id' => $status['id'],
                'name' => $status['name'],
                'color' => $status['color']
            ];
        }
        
        return $result;
    }
}