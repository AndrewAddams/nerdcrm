<?php
/**
 * Модель способа доставки
 * 
 * Управляет способами доставки: CRUD
 */

require_once __DIR__ . '/../core/Model.php';

class ShippingMethod extends Model
{
    /**
     * Название таблицы
     * 
     * @var string
     */
    protected $table = 'shipping_methods';
    
    /**
     * Использовать soft delete
     * 
     * @var bool
     */
    protected $softDelete = true;
    
    /**
     * Получить все способы доставки с сортировкой
     * 
     * @param string $orderBy
     * @param string $direction
     * @return array
     */
    public function getAll($orderBy = 'name', $direction = 'ASC')
    {
        return $this->all([], $orderBy, $direction);
    }
    
    /**
     * Получить способ доставки по ID
     * 
     * @param int $id
     * @return array|null
     */
    public function getById($id)
    {
        return $this->find($id);
    }
    
    /**
     * Создать или обновить способ доставки
     * 
     * @param string $name Название способа доставки
     * @param int|null $id ID для обновления
     * @return int|false
     */
    public function save($name, $id = null)
    {
        $data = ['name' => trim($name)];
        
        if ($id) {
            $result = $this->update($id, $data);
            return $result ? $id : false;
        } else {
            return $this->create($data);
        }
    }
    
    /**
     * Проверить, существует ли способ доставки с таким названием
     * 
     * @param string $name
     * @param int|null $excludeId
     * @return bool
     */
    public function nameExists($name, $excludeId = null)
    {
        $sql = "SELECT id FROM {$this->table} WHERE name = :name";
        $params = ['name' => $name];
        
        if ($this->softDelete) {
            $sql .= " AND {$this->deletedAt} IS NULL";
        }
        
        if ($excludeId) {
            $sql .= " AND {$this->primaryKey} != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }
        
        $sql .= " LIMIT 1";
        
        $stmt = $this->db->query($sql, $params);
        $result = $stmt->fetch();
        
        return !empty($result);
    }
    
    /**
     * Получить способы доставки для выпадающего списка
     * 
     * @return array
     */
    public function getForSelect()
    {
        $methods = $this->getAll();
        
        $list = [];
        foreach ($methods as $method) {
            $list[] = [
                'id' => $method['id'],
                'name' => $method['name']
            ];
        }
        
        return $list;
    }
    
    /**
     * Получить количество способов доставки
     * 
     * @return int
     */
    public function getCount()
    {
        return $this->count();
    }
    
    /**
     * Проверить, используется ли способ доставки в заказах
     * 
     * @param int $id
     * @return int Количество использований
     */
    public function isUsedInOrders($id)
    {
        $sql = "SELECT COUNT(*) as count FROM orders WHERE shipping_method_id = :shipping_method_id AND deleted_at IS NULL";
        $stmt = $this->db->query($sql, ['shipping_method_id' => $id]);
        $result = $stmt->fetch();
        
        return (int)$result['count'];
    }
    
    /**
     * Удалить способ доставки (с проверкой использования)
     * 
     * @param int $id
     * @return array Результат удаления
     */
    public function deleteShippingMethod($id)
    {
        $usedCount = $this->isUsedInOrders($id);
        
        if ($usedCount > 0) {
            return [
                'success' => false,
                'error' => "Невозможно удалить способ доставки: он используется в {$usedCount} заказах"
            ];
        }
        
        $result = $this->delete($id);
        
        return [
            'success' => $result,
            'error' => $result ? null : 'Ошибка при удалении способа доставки'
        ];
    }
    
    /**
     * Получить предопределённые способы доставки (которые нельзя удалить)
     * 
     * @return array
     */
    public function getPredefinedMethods()
    {
        $sql = "SELECT id, name FROM {$this->table} WHERE name IN ('СДЭК', 'Яндекс', 'Почта России')";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
    
    /**
     * Получить пагинированный список для админки
     * 
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public function getPaginated($page = 1, $perPage = 20)
    {
        $offset = ($page - 1) * $perPage;
        $methods = $this->paginate($perPage, $offset, [], 'name', 'ASC');
        $total = $this->count();
        
        return [
            'data' => $methods,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => ceil($total / $perPage)
        ];
    }
}