<?php
/**
 * Модель упаковки
 * 
 * Управляет упаковками: CRUD
 */

require_once __DIR__ . '/../core/Model.php';

class Packaging extends Model
{
    protected $table = 'packaging';
    protected $softDelete = true;
    
    /**
     * Получить все упаковки с сортировкой
     */
    public function getAll($orderBy = 'name', $direction = 'ASC')
    {
        return $this->all([], $orderBy, $direction);
    }
    
    /**
     * Получить упаковку по ID
     */
    public function getById($id)
    {
        return $this->find($id);
    }
    
    /**
     * Создать или обновить упаковку
     */
    public function save($name, $dimensions, $id = null)
    {
        $data = [
            'name' => trim($name),
            'dimensions' => trim($dimensions)
        ];
        
        if ($id) {
            $result = $this->update($id, $data);
            return $result ? $id : false;
        } else {
            return $this->create($data);
        }
    }
    
    /**
     * Проверить, существует ли упаковка с таким названием
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
     * Получить количество упаковок
     */
    public function getCount()
    {
        return $this->count();
    }
    
    /**
     * Удалить упаковку
     */
    public function deletePackaging($id)
    {
        $result = $this->delete($id);
        
        return [
            'success' => $result,
            'error' => $result ? null : 'Ошибка при удалении упаковки'
        ];
    }
    
    /**
     * Поиск упаковок по названию
     */
    public function search($query, $limit = 50)
    {
        $sql = "SELECT * FROM {$this->table} WHERE name LIKE :query";
        $params = ['query' => '%' . $query . '%'];
        
        if ($this->softDelete) {
            $sql .= " AND {$this->deletedAt} IS NULL";
        }
        
        $sql .= " ORDER BY name ASC LIMIT :limit";
        $params['limit'] = $limit;
        
        $stmt = $this->db->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Получить пагинированный список
     */
    public function getPaginated($page = 1, $perPage = 20, $search = '')
    {
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT * FROM {$this->table} WHERE 1=1";
        $params = [];
        
        if ($this->softDelete) {
            $sql .= " AND {$this->deletedAt} IS NULL";
        }
        
        if ($search) {
            $sql .= " AND name LIKE :search";
            $params['search'] = '%' . $search . '%';
        }
        
        $sql .= " ORDER BY name ASC LIMIT :limit OFFSET :offset";
        $params['limit'] = $perPage;
        $params['offset'] = $offset;
        
        $stmt = $this->db->query($sql, $params);
        $items = $stmt->fetchAll();
        
        // Подсчёт общего количества
        $countSql = "SELECT COUNT(*) as total FROM {$this->table} WHERE 1=1";
        if ($this->softDelete) {
            $countSql .= " AND {$this->deletedAt} IS NULL";
        }
        if ($search) {
            $countSql .= " AND name LIKE :search";
        }
        $countStmt = $this->db->query($countSql, $search ? ['search' => '%' . $search . '%'] : []);
        $total = $countStmt->fetch()['total'];
        
        return [
            'data' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => ceil($total / $perPage)
        ];
    }
}