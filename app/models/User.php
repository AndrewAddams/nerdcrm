<?php
/**
 * Модель пользователя
 * 
 * Управляет пользователями системы: аутентификация, CRUD, роли
 */

require_once __DIR__ . '/../core/Model.php';

class User extends Model
{
    /**
     * Название таблицы
     * 
     * @var string
     */
    protected $table = 'users';
    
    /**
     * Использовать soft delete
     * 
     * @var bool
     */
    protected $softDelete = true;
    
    /**
     * Аутентификация пользователя
     * 
     * @param string $name Имя пользователя
     * @param string $password Пароль
     * @return array|false Данные пользователя или false
     */
    public function authenticate($name, $password)
    {
        $user = $this->findBy('name', $name);
        
        if (!$user) {
            return false;
        }
        
        // Проверяем пароль
        if (password_verify($password, $user['password_hash'])) {
            // Не возвращаем хеш пароля
            unset($user['password_hash']);
            return $user;
        }
        
        return false;
    }
    
    /**
     * Создать нового пользователя
     * 
     * @param string $name Имя
     * @param string $password Пароль (в открытом виде)
     * @param string $role Роль (admin/manager)
     * @return int|false ID пользователя или false
     */
    public function createUser($name, $password, $role = 'manager')
    {
        // Проверяем, не существует ли пользователь с таким именем
        $existing = $this->findBy('name', $name);
        if ($existing) {
            return false;
        }
        
        $hash = password_hash($password, PASSWORD_DEFAULT);
        
        return $this->create([
            'name' => $name,
            'password_hash' => $hash,
            'role' => $role
        ]);
    }
    
    /**
     * Обновить пользователя
     * 
     * @param int $id ID пользователя
     * @param string $name Имя
     * @param string|null $password Новый пароль (если передан)
     * @param string|null $role Роль (если передана)
     * @return bool
     */
    public function updateUser($id, $name, $password = null, $role = null)
    {
        $data = ['name' => $name];
        
        if ($password !== null && $password !== '') {
            $data['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        }
        
        if ($role !== null) {
            $data['role'] = $role;
        }
        
        return $this->update($id, $data);
    }
    
    /**
     * Получить всех пользователей (без учёта удалённых)
     * 
     * @return array
     */
    public function getAllUsers()
    {
        $users = $this->all([], 'name', 'ASC');
        
        // Убираем хеши паролей
        foreach ($users as &$user) {
            unset($user['password_hash']);
        }
        
        return $users;
    }
    
    /**
     * Получить пользователя по ID (без хеша пароля)
     * 
     * @param int $id
     * @return array|null
     */
    public function getUserById($id)
    {
        $user = $this->find($id);
        
        if ($user) {
            unset($user['password_hash']);
        }
        
        return $user;
    }
    
    /**
     * Проверить, существует ли пользователь с таким именем
     * 
     * @param string $name
     * @param int|null $excludeId Исключить ID (для редактирования)
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
     * Получить список имён пользователей для выпадающих списков
     * 
     * @return array
     */
    public function getNamesList()
    {
        $users = $this->all([], 'name', 'ASC');
        
        $list = [];
        foreach ($users as $user) {
            $list[] = [
                'id' => $user['id'],
                'name' => $user['name']
            ];
        }
        
        return $list;
    }
    
    /**
     * Сменить пароль пользователя
     * 
     * @param int $id
     * @param string $newPassword
     * @return bool
     */
    public function changePassword($id, $newPassword)
    {
        return $this->update($id, [
            'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT)
        ]);
    }
    
    /**
     * Получить количество пользователей
     * 
     * @return int
     */
    public function getCount()
    {
        return $this->count();
    }
    
    /**
     * Проверить, является ли пользователь администратором
     * 
     * @param int $id
     * @return bool
     */
    public function isAdmin($id)
    {
        $user = $this->find($id);
        return $user && $user['role'] === 'admin';
    }
    /**
     * Получить пагинированный список пользователей для админки
     * 
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public function getPaginated($page = 1, $perPage = 20)
    {
        $offset = ($page - 1) * $perPage;
        $users = $this->paginate($perPage, $offset, [], 'name', 'ASC');
        $total = $this->count();
        
        // Убираем хеши паролей
        foreach ($users as &$user) {
            unset($user['password_hash']);
        }
        
        return [
            'data' => $users,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => ceil($total / $perPage)
        ];
    }
    
    /**
     * Поиск пользователей
     * 
     * @param string $query
     * @param int $limit
     * @return array
     */
    public function search($query, $limit = 20)
    {
        $sql = "SELECT id, name, role, created_at FROM {$this->table} 
                WHERE name LIKE :query 
                AND {$this->deletedAt} IS NULL
                ORDER BY name ASC 
                LIMIT :limit";
        
        $stmt = $this->db->query($sql, [
            'query' => '%' . $query . '%',
            'limit' => $limit
        ]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Получить всех менеджеров (для выпадающего списка)
     * 
     * @return array
     */
    public function getManagers()
    {
        $sql = "SELECT id, name FROM {$this->table} 
                WHERE role = 'manager' 
                AND {$this->deletedAt} IS NULL
                ORDER BY name ASC";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
    
    /**
     * Получить всех администраторов
     * 
     * @return array
     */
    public function getAdmins()
    {
        $sql = "SELECT id, name FROM {$this->table} 
                WHERE role = 'admin' 
                AND {$this->deletedAt} IS NULL
                ORDER BY name ASC";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
    
    /**
     * Обновить роль пользователя
     * 
     * @param int $id
     * @param string $role
     * @return bool
     */
    public function updateRole($id, $role)
    {
        if (!in_array($role, ['admin', 'manager'])) {
            return false;
        }
        
        return $this->update($id, ['role' => $role]);
    }
    
    /**
     * Получить пользователя по ID (включая удалённых)
     * 
     * @param int $id
     * @return array|null
     */
    public function getByIdWithDeleted($id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id";
        $stmt = $this->db->query($sql, ['id' => $id]);
        $user = $stmt->fetch();
        
        if ($user) {
            unset($user['password_hash']);
        }
        
        return $user ?: null;
    }
    
    /**
     * Восстановить удалённого пользователя
     * 
     * @param int $id
     * @return bool
     */
    public function restore($id)
    {
        return $this->update($id, [$this->deletedAt => null]);
    }
    
    /**
     * Получить удалённых пользователей
     * 
     * @return array
     */
    public function getDeleted()
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->deletedAt} IS NOT NULL ORDER BY name ASC";
        $stmt = $this->db->query($sql);
        $users = $stmt->fetchAll();
        
        foreach ($users as &$user) {
            unset($user['password_hash']);
        }
        
        return $users;
    }
    
    /**
     * Проверить, является ли пользователь единственным администратором
     * 
     * @param int $id
     * @return bool
     */
    public function isLastAdmin($id)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} 
                WHERE role = 'admin' 
                AND {$this->deletedAt} IS NULL";
        $stmt = $this->db->query($sql);
        $result = $stmt->fetch();
        
        if ($result['count'] <= 1) {
            // Проверяем, является ли этот пользователь тем самым админом
            $user = $this->find($id);
            if ($user && $user['role'] === 'admin') {
                return true;
            }
        }
        
        return false;
    }
}