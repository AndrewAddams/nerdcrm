<?php
/**
 * Модель источника заказа
 * 
 * Управляет источниками заказов: CRUD
 */

require_once __DIR__ . '/../core/Model.php';

class Source extends Model
{
    /**
     * Название таблицы
     * 
     * @var string
     */
    protected $table = 'sources';
    
    /**
     * Использовать soft delete
     * 
     * @var bool
     */
    protected $softDelete = true;
    
    /**
     * Получить все источники с сортировкой
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
     * Получить источник по ID
     * 
     * @param int $id
     * @return array|null
     */
    public function getById($id)
    {
        return $this->find($id);
    }
    
    /**
     * Создать или обновить источник
     * 
     * @param string $name Название источника
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
     * Проверить, существует ли источник с таким названием
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
     * Получить источники для выпадающего списка
     * 
     * @return array
     */
    public function getForSelect()
    {
        $sources = $this->getAll();
        
        $list = [];
        foreach ($sources as $source) {
            $list[] = [
                'id' => $source['id'],
                'name' => $source['name']
            ];
        }
        
        return $list;
    }
    
    /**
     * Получить количество источников
     * 
     * @return int
     */
    public function getCount()
    {
        return $this->count();
    }
    
    /**
     * Проверить, используется ли источник в заказах
     * 
     * @param int $id
     * @return bool|int Количество использований или false
     */
    public function isUsedInOrders($id)
    {
        $sql = "SELECT COUNT(*) as count FROM orders WHERE source_id = :source_id AND deleted_at IS NULL";
        $stmt = $this->db->query($sql, ['source_id' => $id]);
        $result = $stmt->fetch();
        
        return (int)$result['count'];
    }
    
    /**
     * Удалить источник (с проверкой использования)
     * 
     * @param int $id
     * @return array Результат удаления
     */
    public function deleteSource($id)
    {
        $usedCount = $this->isUsedInOrders($id);
        
        if ($usedCount > 0) {
            return [
                'success' => false,
                'error' => "Невозможно удалить источник: он используется в {$usedCount} заказах"
            ];
        }
        
        $result = $this->delete($id);
        
        return [
            'success' => $result,
            'error' => $result ? null : 'Ошибка при удалении источника'
        ];
    }
    
    /**
     * Получить предопределённые источники (которые нельзя удалить)
     * 
     * @return array
     */
    public function getPredefinedSources()
    {
        $sql = "SELECT id, name FROM {$this->table} WHERE name IN ('ВК', 'Сайт')";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
}