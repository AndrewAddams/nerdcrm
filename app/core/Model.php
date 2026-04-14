<?php
/**
 * Базовая модель
 * 
 * Все модели приложения наследуются от этого класса
 * Содержит общие методы для работы с БД: CRUD, soft delete, выборки
 */

abstract class Model
{
    /**
     * Название таблицы в БД (должно быть переопределено в дочернем классе)
     * 
     * @var string
     */
    protected $table = '';
    
    /**
     * Название первичного ключа (обычно 'id')
     * 
     * @var string
     */
    protected $primaryKey = 'id';
    
    /**
     * Использовать ли soft delete (поле deleted_at)
     * 
     * @var bool
     */
    protected $softDelete = true;
    
    /**
     * Название поля для soft delete
     * 
     * @var string
     */
    protected $deletedAt = 'deleted_at';
    
    /**
     * Экземпляр подключения к БД
     * 
     * @var Database
     */
    protected $db;
    
    /**
     * Конструктор — инициализирует подключение к БД
     */
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Получить все записи (с учётом soft delete)
     * 
     * @param array $where Условия в формате ['поле' => 'значение']
     * @param string $orderBy Сортировка
     * @param string $direction Направление (ASC/DESC)
     * @return array
     */
    public function all($where = [], $orderBy = '', $direction = 'ASC')
    {
        $sql = "SELECT * FROM {$this->table} WHERE 1=1";
        $params = [];
        
        // Условия soft delete
        if ($this->softDelete) {
            $sql .= " AND {$this->deletedAt} IS NULL";
        }
        
        // Добавляем условия where
        foreach ($where as $field => $value) {
            $sql .= " AND {$field} = :{$field}";
            $params[$field] = $value;
        }
        
        // Добавляем сортировку
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy} {$direction}";
        }
        
        $stmt = $this->db->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Найти запись по ID
     * 
     * @param int $id
     * @return array|null
     */
    public function find($id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id";
        $params = ['id' => $id];
        
        if ($this->softDelete) {
            $sql .= " AND {$this->deletedAt} IS NULL";
        }
        
        $stmt = $this->db->query($sql, $params);
        $result = $stmt->fetch();
        
        return $result ?: null;
    }
    
    /**
     * Найти запись по условию
     * 
     * @param string $field Поле
     * @param mixed $value Значение
     * @return array|null
     */
    public function findBy($field, $value)
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$field} = :value";
        $params = ['value' => $value];
        
        if ($this->softDelete) {
            $sql .= " AND {$this->deletedAt} IS NULL";
        }
        
        $sql .= " LIMIT 1";
        
        $stmt = $this->db->query($sql, $params);
        $result = $stmt->fetch();
        
        return $result ?: null;
    }
    
    /**
     * Создать новую запись
     * 
     * @param array $data Данные для вставки
     * @return int|false ID новой записи или false
     */
    public function create($data)
    {
        $fields = array_keys($data);
        $placeholders = array_map(function($field) {
            return ":{$field}";
        }, $fields);
        
        $sql = sprintf(
            "INSERT INTO {$this->table} (%s) VALUES (%s)",
            implode(', ', $fields),
            implode(', ', $placeholders)
        );
        
        try {
            $this->db->query($sql, $data);
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            // В продакшене логировать ошибку
            error_log("Ошибка при создании записи в {$this->table}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Обновить запись
     * 
     * @param int $id ID записи
     * @param array $data Данные для обновления
     * @return bool
     */
    public function update($id, $data)
    {
        $setClause = [];
        $params = [];
        
        foreach ($data as $field => $value) {
            $setClause[] = "{$field} = :{$field}";
            $params[$field] = $value;
        }
        
        $params['id'] = $id;
        
        $sql = sprintf(
            "UPDATE {$this->table} SET %s WHERE {$this->primaryKey} = :id",
            implode(', ', $setClause)
        );
        
        try {
            $this->db->query($sql, $params);
            return true;
        } catch (PDOException $e) {
            error_log("Ошибка при обновлении записи в {$this->table}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Soft delete — помечает запись как удалённую
     * 
     * @param int $id
     * @return bool
     */
    public function delete($id)
    {
        if (!$this->softDelete) {
            return $this->hardDelete($id);
        }
        
        return $this->update($id, [
            $this->deletedAt => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Полное удаление записи из БД
     * 
     * @param int $id
     * @return bool
     */
    public function hardDelete($id)
    {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id";
        
        try {
            $this->db->query($sql, ['id' => $id]);
            return true;
        } catch (PDOException $e) {
            error_log("Ошибка при удалении записи из {$this->table}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Выполнить произвольный запрос
     * 
     * @param string $sql SQL-запрос
     * @param array $params Параметры
     * @return PDOStatement
     */
    public function query($sql, $params = [])
    {
        return $this->db->query($sql, $params);
    }
    
    /**
     * Начать транзакцию
     */
    public function beginTransaction()
    {
        return $this->db->beginTransaction();
    }
    
    /**
     * Зафиксировать транзакцию
     */
    public function commit()
    {
        return $this->db->commit();
    }
    
    /**
     * Откатить транзакцию
     */
    public function rollback()
    {
        return $this->db->rollback();
    }
    
    /**
     * Подсчитать количество записей
     * 
     * @param array $where Условия
     * @return int
     */
    public function count($where = [])
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE 1=1";
        $params = [];
        
        if ($this->softDelete) {
            $sql .= " AND {$this->deletedAt} IS NULL";
        }
        
        foreach ($where as $field => $value) {
            $sql .= " AND {$field} = :{$field}";
            $params[$field] = $value;
        }
        
        $stmt = $this->db->query($sql, $params);
        $result = $stmt->fetch();
        
        return (int) $result['count'];
    }
    
    /**
     * Постраничная выборка
     * 
     * @param int $limit Количество записей
     * @param int $offset Смещение
     * @param array $where Условия
     * @param string $orderBy Сортировка
     * @param string $direction Направление
     * @return array
     */
    public function paginate($limit = 20, $offset = 0, $where = [], $orderBy = '', $direction = 'ASC')
    {
        $sql = "SELECT * FROM {$this->table} WHERE 1=1";
        $params = [];
        
        if ($this->softDelete) {
            $sql .= " AND {$this->deletedAt} IS NULL";
        }
        
        foreach ($where as $field => $value) {
            $sql .= " AND {$field} = :{$field}";
            $params[$field] = $value;
        }
        
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy} {$direction}";
        }
        
        $sql .= " LIMIT :limit OFFSET :offset";
        $params['limit'] = $limit;
        $params['offset'] = $offset;
        
        $stmt = $this->db->query($sql, $params);
        return $stmt->fetchAll();
    }
}