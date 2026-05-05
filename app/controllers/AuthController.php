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
        
        // Валидация входных данных
        $rules = [
            'name' => 'required|string|max:100',
            'password' => 'required|string|max:255'
        ];
        
        $messages = [
            'name.required' => 'Имя пользователя обязательно',
            'password.required' => 'Пароль обязателен'
        ];
        
        if (!$this->validate($data, $rules, $messages)) {
            return;
        }
        
        $validatedData = $this->getValidatedData();
        
        $name = trim($validatedData['name']);
        $password = $validatedData['password'];
        
        // Дополнительная защита от слишком длинных строк (на всякий случай)
        if (strlen($name) > 100) {
            $this->error('Имя пользователя не должно превышать 100 символов', 400);
            return;
        }
        
        if (strlen($password) > 255) {
            $this->error('Пароль слишком длинный', 400);
            return;
        }
        
        // Ищем пользователя
        $userModel = new User();
        $user = $userModel->authenticate($name, $password);
        
        if (!$user) {
            // Логируем неудачную попытку входа (без пароля)
            $this->logFailedLogin($name);
            $this->error('Неверное имя пользователя или пароль', 401);
            return;
        }
        
        // Проверяем, не заблокирован ли пользователь (если есть поле deleted_at)
        if (isset($user['deleted_at']) && $user['deleted_at'] !== null) {
            $this->error('Учётная запись заблокирована', 403);
            return;
        }
        
        // Создаём сессию
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        
        // Обновляем время последнего входа (если есть поле last_login)
        // $userModel->updateLastLogin($user['id']);
        
        // Возвращаем данные пользователя (без пароля)
        unset($user['password_hash']);
        unset($user['deleted_at']);
        
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
    
    /**
     * Логирование неудачных попыток входа
     * 
     * @param string $username
     */
    private function logFailedLogin($username)
    {
        $logFile = ROOT_PATH . '/logs/auth.log';
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $time = date('Y-m-d H:i:s');
        $logEntry = "[{$time}] Failed login attempt - Username: {$username}, IP: {$ip}\n";
        
        // Создаём папку для логов, если её нет
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
}