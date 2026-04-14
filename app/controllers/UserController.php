<?php
/**
 * Контроллер пользователей
 * 
 * Отвечает за все операции с пользователями: получение списка, создание,
 * редактирование, удаление (только для администраторов)
 */

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/UserSetting.php';

class UserController extends Controller
{
    private $userModel;
    private $userSettingModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->userModel = new User();
        $this->userSettingModel = new UserSetting();
    }
    
    /**
     * Получить список пользователей
     * GET /api/users
     */
    public function index()
    {
        if (!$this->requireAdmin()) {
            return;
        }
        
        $search = $_GET['search'] ?? '';
        
        if ($search) {
            $users = $this->userModel->search($search);
        } else {
            $users = $this->userModel->getAllUsers();
        }
        
        $this->success($users);
    }
    
    /**
     * Получить пагинированный список пользователей
     * GET /api/users/paginated
     */
    public function paginated()
    {
        if (!$this->requireAdmin()) {
            return;
        }
        
        $page = (int)($_GET['page'] ?? 1);
        $perPage = (int)($_GET['per_page'] ?? 20);
        
        $result = $this->userModel->getPaginated($page, $perPage);
        $this->success($result);
    }
    
    /**
     * Получить пользователя по ID
     * GET /api/users/{id}
     */
    public function show($params)
    {
        if (!$this->requireAdmin()) {
            return;
        }
        
        $id = (int)$params['id'];
        $user = $this->userModel->getUserById($id);
        
        if (!$user) {
            $this->error('Пользователь не найден', 404);
            return;
        }
        
        $this->success($user);
    }
    
    /**
     * Создать пользователя
     * POST /api/users
     */
    public function store()
    {
        if (!$this->requireAdmin()) {
            return;
        }
        
        $data = $this->getRequestData();
        
        if (empty($data['name'])) {
            $this->error('Имя пользователя обязательно');
            return;
        }
        
        if (empty($data['password'])) {
            $this->error('Пароль обязателен');
            return;
        }
        
        if (strlen($data['password']) < 4) {
            $this->error('Пароль должен содержать минимум 4 символа');
            return;
        }
        
        $role = $data['role'] ?? 'manager';
        if (!in_array($role, ['admin', 'manager'])) {
            $this->error('Некорректная роль');
            return;
        }
        
        // Проверяем уникальность имени
        if ($this->userModel->nameExists($data['name'])) {
            $this->error('Пользователь с таким именем уже существует');
            return;
        }
        
        $id = $this->userModel->createUser($data['name'], $data['password'], $role);
        
        if (!$id) {
            $this->error('Ошибка при создании пользователя', 500);
            return;
        }
        
        $user = $this->userModel->getUserById($id);
        $this->success($user, 'Пользователь успешно создан');
    }
    
    /**
     * Обновить пользователя
     * PUT /api/users/{id}
     */
    public function update($params)
    {
        if (!$this->requireAdmin()) {
            return;
        }
        
        $id = (int)$params['id'];
        $data = $this->getRequestData();
        
        // Запрещаем редактирование самого себя через этот эндпоинт
        if ($id == $this->currentUser['id']) {
            $this->error('Используйте другой раздел для изменения своего профиля');
            return;
        }
        
        // Проверяем существование пользователя
        $user = $this->userModel->getByIdWithDeleted($id);
        if (!$user) {
            $this->error('Пользователь не найден', 404);
            return;
        }
        
        if (empty($data['name'])) {
            $this->error('Имя пользователя обязательно');
            return;
        }
        
        $role = $data['role'] ?? $user['role'];
        if (!in_array($role, ['admin', 'manager'])) {
            $this->error('Некорректная роль');
            return;
        }
        
        // Проверяем, не пытаемся ли удалить последнего администратора
        if ($user['role'] === 'admin' && $role !== 'admin') {
            if ($this->userModel->isLastAdmin($id)) {
                $this->error('Нельзя изменить роль последнего администратора');
                return;
            }
        }
        
        // Проверяем уникальность имени
        if ($data['name'] !== $user['name'] && $this->userModel->nameExists($data['name'], $id)) {
            $this->error('Пользователь с таким именем уже существует');
            return;
        }
        
        $password = $data['password'] ?? null;
        if ($password !== null && $password !== '') {
            if (strlen($password) < 4) {
                $this->error('Пароль должен содержать минимум 4 символа');
                return;
            }
        }
        
        $result = $this->userModel->updateUser($id, $data['name'], $password, $role);
        
        if (!$result) {
            $this->error('Ошибка при обновлении пользователя', 500);
            return;
        }
        
        $updated = $this->userModel->getUserById($id);
        $this->success($updated, 'Пользователь успешно обновлён');
    }
    
    /**
     * Удалить пользователя (soft delete)
     * DELETE /api/users/{id}
     */
    public function delete($params)
    {
        if (!$this->requireAdmin()) {
            return;
        }
        
        $id = (int)$params['id'];
        
        // Запрещаем удаление самого себя
        if ($id == $this->currentUser['id']) {
            $this->error('Нельзя удалить самого себя');
            return;
        }
        
        // Проверяем существование пользователя
        $user = $this->userModel->getByIdWithDeleted($id);
        if (!$user) {
            $this->error('Пользователь не найден', 404);
            return;
        }
        
        // Проверяем, не удаляем ли последнего администратора
        if ($user['role'] === 'admin' && $this->userModel->isLastAdmin($id)) {
            $this->error('Нельзя удалить последнего администратора');
            return;
        }
        
        $result = $this->userModel->delete($id);
        
        if (!$result) {
            $this->error('Ошибка при удалении пользователя', 500);
            return;
        }
        
        $this->success(null, 'Пользователь успешно удалён');
    }
    
    /**
     * Восстановить удалённого пользователя
     * POST /api/users/{id}/restore
     */
    public function restore($params)
    {
        if (!$this->requireAdmin()) {
            return;
        }
        
        $id = (int)$params['id'];
        
        $result = $this->userModel->restore($id);
        
        if (!$result) {
            $this->error('Ошибка при восстановлении пользователя', 500);
            return;
        }
        
        $user = $this->userModel->getUserById($id);
        $this->success($user, 'Пользователь успешно восстановлен');
    }
    
    /**
     * Получить удалённых пользователей
     * GET /api/users/deleted
     */
    public function getDeleted()
    {
        if (!$this->requireAdmin()) {
            return;
        }
        
        $users = $this->userModel->getDeleted();
        $this->success($users);
    }
    
    /**
     * Получить список менеджеров
     * GET /api/users/managers
     */
    public function getManagers()
    {
        if (!$this->requireAuth()) {
            return;
        }
        
        $managers = $this->userModel->getManagers();
        $this->success($managers);
    }
    
    /**
     * Изменить свой пароль
     * POST /api/users/change-password
     */
    public function changePassword()
    {
        if (!$this->requireAuth()) {
            return;
        }
        
        $data = $this->getRequestData();
        
        if (empty($data['current_password'])) {
            $this->error('Текущий пароль обязателен');
            return;
        }
        
        if (empty($data['new_password'])) {
            $this->error('Новый пароль обязателен');
            return;
        }
        
        if (strlen($data['new_password']) < 4) {
            $this->error('Новый пароль должен содержать минимум 4 символа');
            return;
        }
        
        // Проверяем текущий пароль
        $user = $this->userModel->find($this->currentUser['id']);
        
        if (!password_verify($data['current_password'], $user['password_hash'])) {
            $this->error('Неверный текущий пароль');
            return;
        }
        
        $result = $this->userModel->changePassword($this->currentUser['id'], $data['new_password']);
        
        if (!$result) {
            $this->error('Ошибка при смене пароля', 500);
            return;
        }
        
        $this->success(null, 'Пароль успешно изменён');
    }
    
    /**
     * Обновить свои настройки (имя)
     * POST /api/users/update-profile
     */
    public function updateProfile()
    {
        if (!$this->requireAuth()) {
            return;
        }
        
        $data = $this->getRequestData();
        
        if (empty($data['name'])) {
            $this->error('Имя пользователя обязательно');
            return;
        }
        
        // Проверяем уникальность имени
        if ($data['name'] !== $this->currentUser['name'] && 
            $this->userModel->nameExists($data['name'], $this->currentUser['id'])) {
            $this->error('Пользователь с таким именем уже существует');
            return;
        }
        
        $result = $this->userModel->update($this->currentUser['id'], ['name' => $data['name']]);
        
        if (!$result) {
            $this->error('Ошибка при обновлении профиля', 500);
            return;
        }
        
        // Обновляем сессию
        $_SESSION['user_name'] = $data['name'];
        $this->currentUser['name'] = $data['name'];
        
        $this->success(['name' => $data['name']], 'Профиль успешно обновлён');
    }
}