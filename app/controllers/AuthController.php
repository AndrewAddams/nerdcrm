<?php
/**
 * Контроллер аутентификации
 * 
 * Отвечает за вход, выход и проверку авторизации пользователей
 */

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/User.php';

class AuthController extends Controller
{
    /**
     * Вход в систему
     * POST /api/auth/login
     */
    public function login()
    {
        $data = $this->getRequestData();
        
        // Валидируем обязательные поля
        $required = ['name', 'password'];
        if (!$this->validateRequired($data, $required)) {
            return;
        }
        
        $name = trim($data['name']);
        $password = $data['password'];
        
        // Ищем пользователя
        $userModel = new User();
        $user = $userModel->authenticate($name, $password);
        
        if (!$user) {
            $this->error('Неверное имя пользователя или пароль', 401);
            return;
        }
        
        // Создаём сессию
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        
        // Возвращаем данные пользователя (без пароля)
        unset($user['password_hash']);
        
        $this->success([
            'user' => $user,
            'redirect' => '/dashboard'
        ], 'Вход выполнен успешно');
    }
    
    /**
     * Выход из системы
     * POST /api/auth/logout
     */
    public function logout()
    {
        // Очищаем сессию
        $_SESSION = [];
        
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        
        session_destroy();
        
        $this->success(['redirect' => '/login'], 'Выход выполнен');
    }
    
    /**
     * Проверка статуса авторизации
     * GET /api/auth/check
     */
    public function check()
    {
        if ($this->currentUser) {
            $this->success([
                'authenticated' => true,
                'user' => $this->currentUser
            ]);
        } else {
            $this->success([
                'authenticated' => false
            ]);
        }
    }
}