<?php
/**
 * Модель формата
 * 
 * Управляет форматами товаров: CRUD, получение цены, связь с товарами
 */

require_once __DIR__ . '/../core/Model.php';

class Format extends Model
{
    protected $table = 'formats';
    protected $softDelete = true;
    
    /**
     * Получить все форматы с сортировкой
     */
    public function getAll($orderBy = 'name', $direction = 'ASC')
    {
        return $this->all([], $orderBy, $direction);
    }
    
    /**
     * Получить формат по ID
     */
    public function getById($id)
    {
        return $this->find($id);
    }
    
    /**
     * Создать или обновить формат
     */
    public function save($name, $price, $id = null)
    {
        $data = [
            'name' => trim($name),
            'price' => (float)$price
        ];
        
        if ($id) {
            $result = $this->update($id, $data);
            return $result ? $id : false;
        } else {
            return $this->create($data);
        }
    }
    
    /**
     * Проверить, существует ли формат с таким названием
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
     * Получить цену формата
     */
    public function getPrice($id)
    {
        $format = $this->find($id);
        return $format ? (float)$format['price'] : null;
    }
    
    /**
     * Получить форматы для выпадающего списка (с автодополнением)
     */
    public function getForSelect($search = '')
    {
        $sql = "SELECT id, name, price FROM {$this->table} WHERE 1=1";
        $params = [];
        
        if ($this->softDelete) {
            $sql .= " AND {$this->deletedAt} IS NULL";
        }
        
        if ($search) {
            $sql .= " AND name LIKE :search";
            $params['search'] = '%' . $search . '%';
        }
        
        $sql .= " ORDER BY name ASC LIMIT 50";
        
        $stmt = $this->db->query($sql, $params);
        $results = $stmt->fetchAll();
        
        $list = [];
        foreach ($results as $item) {
            $list[] = [
                'id' => $item['id'],
                'text' => $item['name'],
                'price' => (float)$item['price']
            ];
        }
        
        return $list;
    }
    
    /**
     * Получить количество форматов
     */
    public function getCount()
    {
        return $this->count();
    }
    
    /**
     * Проверить, используется ли формат в заказах
     */
    public function isUsedInOrders($id)
    {
        $sql = "SELECT COUNT(*) as count FROM order_items WHERE format_id = :format_id";
        $stmt = $this->db->query($sql, ['format_id' => $id]);
        $result = $stmt->fetch();
        
        return (int)$result['count'];
    }
    
    /**
     * Удалить формат (с проверкой использования)
     */
    public function deleteFormat($id)
    {
        $usedCount = $this->isUsedInOrders($id);
        
        if ($usedCount > 0) {
            return [
                'success' => false,
                'error' => "Невозможно удалить формат: он используется в {$usedCount} заказах"
            ];
        }
        
        $result = $this->delete($id);
        
        return [
            'success' => $result,
            'error' => $result ? null : 'Ошибка при удалении формата'
        ];
    }
    
    /**
     * Получить форматы с пагинацией для админки
     */
    public function getPaginated($page = 1, $perPage = 20)
    {
        $offset = ($page - 1) * $perPage;
        $formats = $this->paginate($perPage, $offset, [], 'name', 'ASC');
        $total = $this->count();
        
        return [
            'data' => $formats,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => ceil($total / $perPage)
        ];
    }
    
    /**
     * Обновить цену формата
     */
    public function updatePrice($id, $price)
    {
        return $this->update($id, ['price' => (float)$price]);
    }
    
    /**
     * Получить все форматы в виде ассоциативного массива [id => name]
     */
    public function getIdNameMap()
    {
        $formats = $this->getAll();
        $map = [];
        foreach ($formats as $format) {
            $map[$format['id']] = $format['name'];
        }
        return $map;
    }
}