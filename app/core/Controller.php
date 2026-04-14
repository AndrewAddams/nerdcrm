<?php
/**
 * Базовый контроллер
 * 
 * Все контроллеры приложения наследуются от этого класса
 * Содержит общие методы для работы с ответами, аутентификацией и валидацией
 */

class Controller
{
    /**
     * Текущий авторизованный пользователь
     * 
     * @var array|null
     */
    protected $currentUser = null;
    
    /**
     * Конструктор — проверяем аутентификацию
     */
    public function __construct()
    {
        // Загружаем текущего пользователя, если он авторизован
        if (isset($_SESSION['user_id'])) {
            $this->currentUser = [
                'id' => $_SESSION['user_id'],
                'name' => $_SESSION['user_name'],
                'role' => $_SESSION['user_role']
            ];
        }
    }
    
    /**
     * Проверить, авторизован ли пользователь
     * Если нет — для API вернуть JSON, для страниц — редирект на логин
     * 
     * @return bool
     */
    protected function requireAuth()
    {
        if (!$this->currentUser) {
            // Определяем, API это или страница
            $isApi = strpos($_SERVER['REQUEST_URI'], '/api/') === 0;
            
            if ($isApi) {
                $this->json(['error' => 'Необходима авторизация'], 401);
            } else {
                $this->redirect('/login');
            }
            return false;
        }
        return true;
    }
    
    /**
     * Проверить, является ли пользователь администратором
     * Если нет — вернуть ошибку 403
     * 
     * @return bool
     */
    protected function requireAdmin()
    {
        if (!$this->requireAuth()) {
            return false;
        }
        
        if ($this->currentUser['role'] !== 'admin') {
            $isApi = strpos($_SERVER['REQUEST_URI'], '/api/') === 0;
            
            if ($isApi) {
                $this->json(['error' => 'Доступ запрещён. Требуются права администратора'], 403);
            } else {
                $this->redirect('/403');
            }
            return false;
        }
        
        return true;
    }
    
    /**
     * Отправить JSON-ответ
     * 
     * @param mixed $data Данные для отправки
     * @param int $statusCode HTTP статус код
     */
    protected function json($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Отправить успешный ответ с данными
     * 
     * @param mixed $data Данные
     * @param string $message Сообщение
     */
    protected function success($data = null, $message = 'Успешно')
    {
        $response = ['success' => true, 'message' => $message];
        if ($data !== null) {
            $response['data'] = $data;
        }
        $this->json($response);
    }
    
    /**
     * Отправить ответ с ошибкой
     * 
     * @param string $message Сообщение об ошибке
     * @param int $statusCode HTTP статус код
     */
    protected function error($message, $statusCode = 400)
    {
        $this->json(['success' => false, 'error' => $message], $statusCode);
    }
    
    /**
     * Отобразить HTML-шаблон
     * 
     * @param string $view Имя шаблона (без расширения .php)
     * @param array $data Данные для передачи в шаблон
     */
    protected function view($view, $data = [])
    {
        $viewPath = ROOT_PATH . '/app/views/' . $view . '.php';
        
        if (!file_exists($viewPath)) {
            die("Шаблон {$view}.php не найден по пути: " . $viewPath);
        }
        
        // Извлекаем данные в переменные
        extract($data);
        
        require_once $viewPath;
        exit;
    }
    
    /**
     * Перенаправить на другой URL
     * 
     * @param string $url URL для перенаправления
     */
    protected function redirect($url)
    {
        header('Location: ' . $url);
        exit;
    }
    
    /**
     * Получить данные из POST-запроса (JSON или form-data)
     * 
     * @return array
     */
    protected function getRequestData()
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        // Если это JSON
        if (strpos($contentType, 'application/json') !== false) {
            $input = file_get_contents('php://input');
            return json_decode($input, true) ?? [];
        }
        
        // Иначе возвращаем $_POST
        return $_POST;
    }
    
    /**
     * Валидировать обязательные поля
     * 
     * @param array $data Данные для проверки
     * @param array $required Список обязательных полей
     * @return bool
     */
    protected function validateRequired($data, $required)
    {
        foreach ($required as $field) {
            if (empty($data[$field]) && $data[$field] !== '0' && $data[$field] !== 0) {
                $this->error("Поле '{$field}' обязательно для заполнения");
                return false;
            }
        }
        return true;
    }
    
    /**
     * Валидировать email
     * 
     * @param string $email
     * @return bool
     */
    protected function validateEmail($email)
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Некорректный формат email');
            return false;
        }
        return true;
    }
    
    /**
     * Валидировать телефон (простая проверка)
     * 
     * @param string $phone
     * @return bool
     */
    protected function validatePhone($phone)
    {
        // Убираем все нецифровые символы
        $digits = preg_replace('/[^0-9]/', '', $phone);
        
        if (strlen($digits) < 10) {
            $this->error('Некорректный номер телефона');
            return false;
        }
        return true;
    }
    
    /**
     * Валидировать URL
     * 
     * @param string $url
     * @return bool
     */
    protected function validateUrl($url)
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $this->error('Некорректный формат ссылки');
            return false;
        }
        return true;
    }
    
    /**
     * Валидировать положительное число
     * 
     * @param mixed $value
     * @param string $fieldName
     * @return bool
     */
    protected function validatePositiveNumber($value, $fieldName = 'Значение')
    {
        if (!is_numeric($value) || $value < 0) {
            $this->error("{$fieldName} должно быть неотрицательным числом");
            return false;
        }
        return true;
    }
    
    /**
     * Валидировать процент скидки (0-100)
     * 
     * @param int $percent
     * @return bool
     */
    protected function validateDiscountPercent($percent)
    {
        if (!is_numeric($percent) || $percent < 0 || $percent > 100) {
            $this->error('Скидка должна быть числом от 0 до 100');
            return false;
        }
        return true;
    }
}