<?php
/**
 * Базовый контроллер
 * 
 * Все контроллеры приложения наследуются от этого класса
 * Содержит общие методы для работы с ответами, аутентификацией и валидацией
 */

require_once __DIR__ . '/Validator.php';

class Controller
{
    /**
     * Текущий авторизованный пользователь
     * 
     * @var array|null
     */
    protected $currentUser = null;
    
    /**
     * Экземпляр валидатора
     * 
     * @var Validator|null
     */
    protected $validator = null;
    
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
        
        // Инициализируем валидатор
        $this->validator = new Validator();
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
            $data = json_decode($input, true) ?? [];
        } else {
            // Иначе возвращаем $_POST
            $data = $_POST;
        }
        
        // Автоматически очищаем все входящие данные от XSS
        return $this->validator->sanitize($data);
    }
    
    /**
     * НОВЫЙ МЕТОД: Строгая валидация данных
     * 
     * @param array $data Данные для валидации
     * @param array $rules Правила валидации
     * @param array $messages Кастомные сообщения об ошибках (опционально)
     * @return bool true если валидация пройдена
     */
    protected function validate($data, $rules, $messages = [])
    {
        if ($this->validator->validate($data, $rules, $messages)) {
            return true;
        }
        
        // Если валидация не пройдена — возвращаем ошибку
        $errors = $this->validator->getErrors();
        $errorMessage = $this->formatValidationErrors($errors);
        
        $this->error($errorMessage, 422);
        return false;
    }
    
    /**
     * Получить очищенные данные после валидации
     * 
     * @return array
     */
    protected function getValidatedData()
    {
        return $this->validator->getSanitizedData();
    }
    
    /**
     * Форматирование ошибок валидации в читаемую строку
     * 
     * @param array $errors Ошибки валидации
     * @return string
     */
    private function formatValidationErrors($errors)
    {
        $messages = [];
        foreach ($errors as $field => $fieldErrors) {
            // Преобразуем имя поля в человекочитаемый вид
            $fieldName = $this->getFieldName($field);
            $messages[] = $fieldName . ': ' . implode(', ', $fieldErrors);
        }
        return implode('; ', $messages);
    }
    
    /**
     * Преобразование имени поля в человекочитаемый вид
     * 
     * @param string $field Имя поля
     * @return string
     */
    private function getFieldName($field)
    {
        $names = [
            'sale_label' => 'Метка продажи',
            'source_id' => 'Источник заказа',
            'link' => 'Ссылка',
            'comments' => 'Комментарии',
            'shipping_method_id' => 'Способ доставки',
            'tracking_number' => 'Трек-номер',
            'recipient_name' => 'Имя получателя',
            'recipient_phone' => 'Телефон получателя',
            'recipient_email' => 'E-mail получателя',
            'shipping_cost' => 'Стоимость доставки',
            'is_urgent' => 'Срочность заказа',
            'items' => 'Товары',
            'items.*.product_id' => 'ID товара',
            'items.*.format_id' => 'Формат товара',
            'items.*.discount_percent' => 'Скидка на товар',
            'items.*.custom_price' => 'Цена (вручную)',
            'order_id' => 'ID заказа',
            'status_id' => 'Статус',
            'order_ids' => 'ID заказов',
        ];
        
        return $names[$field] ?? $field;
    }
    
    // =====================================================
    // УСТАРЕВШИЕ МЕТОДЫ (УДАЛЕНЫ ДЛЯ БЕЗОПАСНОСТИ)
    // Больше не используются. Вся валидация теперь через validate()
    // =====================================================
    
    // Методы validateRequired(), validateEmail(), validatePhone(), 
    // validateUrl(), validatePositiveNumber(), validateDiscountPercent()
    // были УДАЛЕНЫ, так как они создавали дыры в безопасности.
    // Используйте новый метод $this->validate() вместо них.
}