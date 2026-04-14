<?php
/**
 * Модель товара
 * 
 * Управляет товарами (номенклатурой): CRUD, импорт/экспорт
 */

require_once __DIR__ . '/../core/Model.php';

class Product extends Model
{
    protected $table = 'products';
    protected $softDelete = true;
    
    /**
     * Получить все товары с сортировкой
     */
    public function getAll($orderBy = 'name', $direction = 'ASC')
    {
        return $this->all([], $orderBy, $direction);
    }
    
    /**
     * Получить товар по ID
     */
    public function getById($id)
    {
        return $this->find($id);
    }
    
    /**
     * Создать или обновить товар
     */
    public function save($data, $id = null)
    {
        $productData = [
            'name' => trim($data['name']),
            'short_description' => $data['short_description'] ?? '',
            'full_description' => $data['full_description'] ?? ''
        ];
        
        if ($id) {
            $result = $this->update($id, $productData);
            return $result ? $id : false;
        } else {
            return $this->create($productData);
        }
    }
    
    /**
     * Проверить, существует ли товар с таким названием
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
     * Получить товары для выпадающего списка (с автодополнением)
     */
    public function getForSelect($search = '')
    {
        $sql = "SELECT id, name FROM {$this->table} WHERE 1=1";
        $params = [];
        
        if ($this->softDelete) {
            $sql .= " AND {$this->deletedAt} IS NULL";
        }
        
        if ($search) {
            $sql .= " AND name LIKE :search";
            $params['search'] = '%' . $search . '%';
        }
        
        
        $stmt = $this->db->query($sql, $params);
        $results = $stmt->fetchAll();
        
        $list = [];
        foreach ($results as $item) {
            $list[] = [
                'id' => $item['id'],
                'text' => $item['name']
            ];
        }
        
        return $list;
    }
    
    /**
     * Импорт товаров из массива данных
     */
    public function import($rows)
    {
        $result = [
            'added' => 0,
            'updated' => 0,
            'errors' => []
        ];
        
        foreach ($rows as $index => $row) {
            if ($index === 0 && isset($row['наименование'])) {
                continue;
            }
            
            $name = trim($row[0] ?? $row['наименование'] ?? '');
            if (empty($name)) {
                $result['errors'][] = "Строка " . ($index + 1) . ": пустое наименование";
                continue;
            }
            
            $shortDescription = $row[1] ?? $row['краткое_описание'] ?? '';
            $fullDescription = $row[2] ?? $row['полное_описание'] ?? '';
            
            $existing = $this->findBy('name', $name);
            
            try {
                if ($existing) {
                    $this->update($existing['id'], [
                        'short_description' => $shortDescription,
                        'full_description' => $fullDescription
                    ]);
                    $result['updated']++;
                } else {
                    $this->create([
                        'name' => $name,
                        'short_description' => $shortDescription,
                        'full_description' => $fullDescription
                    ]);
                    $result['added']++;
                }
            } catch (Exception $e) {
                $result['errors'][] = "Строка " . ($index + 1) . ": " . $e->getMessage();
            }
        }
        
        return $result;
    }
    
    /**
     * Получить все товары для экспорта
     */
    public function getForExport()
    {
        return $this->getAll('name', 'ASC');
    }
    
    /**
     * Получить количество товаров
     */
    public function getCount()
    {
        return $this->count();
    }
    
    /**
     * Поиск товаров по названию
     */
    public function search($query, $limit = 20)
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
}