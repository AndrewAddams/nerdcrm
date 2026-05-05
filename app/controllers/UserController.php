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
    
    // Допустимые роли пользователей
    private $allowedRoles = ['admin', 'manager'];
    
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
        
        // Валидация поискового запроса
        if ($search !== '' && strlen($search) > 100) {
            $this->error('Поисковый запрос слишком длинный', 400);
            return;
        }
        
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
        
        // Валидация параметров пагинации
        if ($page < 1) $page = 1;
        if ($perPage < 1) $perPage = 20;
        if ($perPage > 100) $perPage = 100;
        
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
        
        // Валидация ID
        $rules = ['id' => 'required|int|exists:users,id'];
        if (!$this->validate(['id' => $id], $rules)) {
            return;
        }
        
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
        
        // Валидация
        $rules = [
            'name' => 'required|string|max:100',
            'password' => 'required|string|min:4|max:255',
            'role' => 'string|in:admin,manager'
        ];
        
        $messages = [
            'name.required' => 'Имя пользователя обязательно',
            'name.max' => 'Имя не должно превышать 100 символов',
            'password.required' => 'Пароль обязателен',
            'password.min' => 'Пароль должен содержать минимум 4 символа',
            'role.in' => 'Некорректная роль (допустимы: admin, manager)'
        ];
        
        if (!$this->validate($data, $rules, $messages)) {
            return;
        }
        
        $validatedData = $this->getValidatedData();
        
        $role = $validatedData['role'] ?? 'manager';
        
        // Проверяем уникальность имени
        if ($this->userModel->nameExists($validatedData['name'])) {
            $this->error('Пользователь с таким именем уже существует');
            return;
        }
        
        $id = $this->userModel->createUser($validatedData['name'], $validatedData['password'], $role);
        
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
        
        // Валидация ID
        $idRules = ['id' => 'required|int|exists:users,id'];
        if (!$this->validate(['id' => $id], $idRules)) {
            return;
        }
        
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
        
        // Валидация данных
        $rules = [
            'name' => 'required|string|max:100',
            'role' => 'string|in:admin,manager',
            'password' => 'string|min:4|max:255'
        ];
        
        $messages = [
            'name.required' => 'Имя пользователя обязательно',
            'name.max' => 'Имя не должно превышать 100 символов',
            'role.in' => 'Некорректная роль (допустимы: admin, manager)',
            'password.min' => 'Пароль должен содержать минимум 4 символа'
        ];
        
        if (!$this->validate($data, $rules, $messages)) {
            return;
        }
        
        $validatedData = $this->getValidatedData();
        
        $role = $validatedData['role'] ?? $user['role'];
        
        // Проверяем, не пытаемся ли удалить последнего администратора
        if ($user['role'] === 'admin' && $role !== 'admin') {
            if ($this->userModel->isLastAdmin($id)) {
                $this->error('Нельзя изменить роль последнего администратора');
                return;
            }
        }
        
        // Проверяем уникальность имени
        if ($validatedData['name'] !== $user['name'] && 
            $this->userModel->nameExists($validatedData['name'], $id)) {
            $this->error('Пользователь с таким именем уже существует');
            return;
        }
        
        $password = $validatedData['password'] ?? null;
        
        $result = $this->userModel->updateUser($id, $validatedData['name'], $password, $role);
        
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
        
        // Валидация ID
        $rules = ['id' => 'required|int|exists:users,id'];
        if (!$this->validate(['id' => $id], $rules)) {
            return;
        }
        
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
        
        // Валидация ID
        $rules = ['id' => 'required|int|exists:users,id'];
        if (!$this->validate(['id' => $id], $rules)) {
            return;
        }
        
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
        
        // Валидация
        $rules = [
            'current_password' => 'required|string|max:255',
            'new_password' => 'required|string|min:4|max:255'
        ];
        
        $messages = [
            'current_password.required' => 'Текущий пароль обязателен',
            'new_password.required' => 'Новый пароль обязателен',
            'new_password.min' => 'Новый пароль должен содержать минимум 4 символа'
        ];
        
        if (!$this->validate($data, $rules, $messages)) {
            return;
        }
        
        $validatedData = $this->getValidatedData();
        
        // Проверяем текущий пароль
        $user = $this->userModel->find($this->currentUser['id']);
        
        if (!password_verify($validatedData['current_password'], $user['password_hash'])) {
            $this->error('Неверный текущий пароль');
            return;
        }
        
        $result = $this->userModel->changePassword($this->currentUser['id'], $validatedData['new_password']);
        
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
        
        // Валидация
        $rules = [
            'name' => 'required|string|max:100'
        ];
        
        $messages = [
            'name.required' => 'Имя пользователя обязательно',
            'name.max' => 'Имя не должно превышать 100 символов'
        ];
        
        if (!$this->validate($data, $rules, $messages)) {
            return;
        }
        
        $validatedData = $this->getValidatedData();
        
        // Проверяем уникальность имени
        if ($validatedData['name'] !== $this->currentUser['name'] && 
            $this->userModel->nameExists($validatedData['name'], $this->currentUser['id'])) {
            $this->error('Пользователь с таким именем уже существует');
            return;
        }
        
        $result = $this->userModel->update($this->currentUser['id'], ['name' => $validatedData['name']]);
        
        if (!$result) {
            $this->error('Ошибка при обновлении профиля', 500);
            return;
        }
        
        // Обновляем сессию
        $_SESSION['user_name'] = $validatedData['name'];
        $this->currentUser['name'] = $validatedData['name'];
        
        $this->success(['name' => $validatedData['name']], 'Профиль успешно обновлён');
    }
}