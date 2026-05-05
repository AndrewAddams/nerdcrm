<?php
/**
 * Класс для строгой валидации данных
 * 
 * Поддерживаемые правила:
 * - required: поле обязательно
 * - required_if:field,value: поле обязательно, если другое поле равно значению
 * - required_with:field1,field2: поле обязательно, если присутствует хотя бы одно из указанных полей
 * - email: валидный email
 * - phone: российский номер телефона (автоматически нормализуется)
 * - int: целое число
 * - float: число с плавающей точкой
 * - numeric: число (целое или float)
 * - string: строка
 * - array: массив
 * - min:X: минимальное значение (для чисел) или длина (для строк)
 * - max:X: максимальное значение (для чисел) или длина (для строк)
 * - between:X,Y: значение между X и Y
 * - in:value1,value2,...: значение должно быть в списке
 * - not_in:value1,value2,...: значение не должно быть в списке
 * - exists:table,column: значение должно существовать в таблице БД
 * - unique:table,column: значение должно быть уникальным в таблице
 * - positive: значение должно быть больше 0
 * - discount: значение должно быть от 0 до 100
 * - date: формат YYYY-MM-DD
 * - url: валидный URL
 * - regex:pattern: проверка по регулярному выражению
 * - safe: очистка от HTML/скриптов (санитизация)
 */

class Validator
{
    /**
     * @var array Ошибки валидации
     */
    private $errors = [];
    
    /**
     * @var object PDO подключение для exists/unique правил
     */
    private $db;
    
    /**
     * @var array Очищенные данные
     */
    private $sanitized = [];
    
    public function __construct()
    {
        $dbInstance = Database::getInstance();
        if ($dbInstance) {
            $this->db = $dbInstance->getConnection();
        }
    }
    
    /**
     * Нормализация номера телефона к формату +7(XXX)XXX-XX-XX
     * 
     * @param string $phone Исходный номер телефона
     * @return string Нормализованный номер или исходная строка, если не удалось нормализовать
     */
    public function normalizePhone($phone)
    {
        if (empty($phone)) {
            return $phone;
        }
        
        // Удаляем все нецифровые символы
        $digits = preg_replace('/[^0-9]/', '', $phone);
        
        // Если номер начинается с 8, заменяем на 7
        if (strlen($digits) == 11 && preg_match('/^8/', $digits)) {
            $digits = '7' . substr($digits, 1);
        }
        
        // Если номер из 10 цифр (без кода страны), добавляем 7
        if (strlen($digits) == 10) {
            $digits = '7' . $digits;
        }
        
        // Если номер начинается с 7 и имеет 11 цифр
        if (strlen($digits) == 11 && preg_match('/^7/', $digits)) {
            // Форматируем: +7(XXX)XXX-XX-XX
            return '+' . $digits[0] . '(' . substr($digits, 1, 3) . ')' . substr($digits, 4, 3) . '-' . substr($digits, 7, 2) . '-' . substr($digits, 9, 2);
        }
        
        // Если не удалось нормализовать, возвращаем как есть (валидация покажет ошибку)
        return $phone;
    }
    
    /**
     * Нормализация телефонных номеров во всех полях, которые содержат 'phone'
     * 
     * @param array $data Данные для нормализации
     * @return array Нормализованные данные
     */
    public function normalizePhoneFields($data)
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->normalizePhoneFields($value);
            } elseif (is_string($value) && (strpos($key, 'phone') !== false || $key === 'recipient_phone')) {
                $data[$key] = $this->normalizePhone($value);
            }
        }
        return $data;
    }
    
    /**
     * Очистка данных от XSS и лишних пробелов
     * 
     * @param mixed $data Данные для очистки
     * @return mixed Очищенные данные
     */
    public function sanitize($data)
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->sanitize($value);
            }
            return $data;
        }
        
        if (is_string($data)) {
            // Удаляем лишние пробелы в начале и конце
            $data = trim($data);
            // Экранируем HTML-теги для защиты от XSS
            $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
            return $data;
        }
        
        return $data;
    }
    
    /**
     * Валидация данных по правилам
     * 
     * @param array $data Данные для валидации
     * @param array $rules Правила валидации
     * @param array $messages Кастомные сообщения об ошибках (опционально)
     * @return bool true если валидация пройдена
     */
    public function validate($data, $rules, $messages = [])
    {
        $this->errors = [];
        
        // Нормализуем телефоны перед очисткой
        $data = $this->normalizePhoneFields($data);
        
        // Очищаем от XSS
        $this->sanitized = $this->sanitize($data);
        
        foreach ($rules as $field => $ruleString) {
            $rulesList = explode('|', $ruleString);
            $value = $this->getNestedValue($this->sanitized, $field);
            
            foreach ($rulesList as $rule) {
                $this->applyRule($field, $value, $rule, $this->sanitized, $messages);
            }
        }
        
        return empty($this->errors);
    }
    
    /**
     * Получение значения по вложенному ключу (например, items.*.product_id)
     * 
     * @param array $data Массив данных
     * @param string $field Ключ поля (поддерживает * для массивов)
     * @return mixed Значение поля
     */
    private function getNestedValue($data, $field)
    {
        // Для простых полей
        if (strpos($field, '.') === false && strpos($field, '*') === false) {
            return $data[$field] ?? null;
        }
        
        // Для полей типа items.*.product_id
        if (strpos($field, '*') !== false) {
            $parts = explode('.', $field);
            $arrayKey = $parts[0]; // items
            $nestedKey = $parts[2] ?? null; // product_id
            
            if (isset($data[$arrayKey]) && is_array($data[$arrayKey])) {
                $result = [];
                foreach ($data[$arrayKey] as $item) {
                    if ($nestedKey && isset($item[$nestedKey])) {
                        $result[] = $item[$nestedKey];
                    } elseif (!$nestedKey) {
                        $result[] = $item;
                    }
                }
                return $result;
            }
            return null;
        }
        
        // Для вложенных полей без звёздочки
        $parts = explode('.', $field);
        $current = $data;
        foreach ($parts as $part) {
            if (!isset($current[$part])) {
                return null;
            }
            $current = $current[$part];
        }
        return $current;
    }
    
    /**
     * Применить одно правило к полю
     */
    private function applyRule($field, $value, $rule, $allData, $messages = [])
    {
        // Правило с параметром (например, min:10)
        if (strpos($rule, ':') !== false) {
            list($ruleName, $parameter) = explode(':', $rule, 2);
        } else {
            $ruleName = $rule;
            $parameter = null;
        }
        
        $customMessage = $messages[$field . '.' . $ruleName] ?? null;
        
        // Пропускаем валидацию для null/пустых значений, если поле не required
        if ($ruleName !== 'required' && ($value === null || $value === '')) {
            return;
        }
        
        // Для массивов применяем специальную обработку
        if (is_array($value) && $ruleName !== 'array' && $ruleName !== 'required' && $ruleName !== 'min' && $ruleName !== 'max') {
            // Для элементов массива валидация будет применена отдельно через items.*.правило
            return;
        }
        
        switch ($ruleName) {
            case 'required':
                if ($value === null || $value === '' || (is_array($value) && empty($value))) {
                    $this->addError($field, $customMessage ?? 'Обязательное поле');
                }
                break;
                
            case 'required_if':
                list($depField, $depValue) = explode(',', $parameter, 2);
                $depFieldValue = $this->getNestedValue($allData, $depField);
                
                // Обрабатываем случай, когда зависимое поле может быть массивом (items.*.format_id)
                if (is_array($depFieldValue)) {
                    $depFieldValue = $depFieldValue[0] ?? null;
                }
                
                if ($depFieldValue == $depValue) {
                    if ($value === null || $value === '') {
                        $this->addError($field, $customMessage ?? 'Обязательное поле');
                    }
                }
                break;
                
            case 'required_with':
                $fields = explode(',', $parameter);
                $anyPresent = false;
                foreach ($fields as $f) {
                    $fValue = $this->getNestedValue($allData, $f);
                    if ($fValue !== null && $fValue !== '') {
                        $anyPresent = true;
                        break;
                    }
                }
                if ($anyPresent && ($value === null || $value === '')) {
                    $this->addError($field, $customMessage ?? 'Обязательное поле');
                }
                break;
                
            case 'email':
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, $customMessage ?? 'Некорректный email адрес');
                }
                break;
                
            case 'phone':
                if (!preg_match('/^\+7\(\d{3}\)\d{3}-\d{2}-\d{2}$/', $value)) {
                    $this->addError($field, $customMessage ?? 'Некорректный номер телефона. Формат: +7(XXX)XXX-XX-XX');
                }
                break;
                
            case 'int':
                if (!filter_var($value, FILTER_VALIDATE_INT) && $value !== 0 && $value !== '0') {
                    $this->addError($field, $customMessage ?? 'Должно быть целым числом');
                }
                break;
                
            case 'float':
            case 'numeric':
                if (!is_numeric($value)) {
                    $this->addError($field, $customMessage ?? 'Должно быть числом');
                }
                break;
                
            case 'string':
                if (!is_string($value) && $value !== null) {
                    $this->addError($field, $customMessage ?? 'Должно быть строкой');
                }
                break;
                
            case 'array':
                if (!is_array($value)) {
                    $this->addError($field, $customMessage ?? 'Должно быть массивом');
                }
                break;
                
            case 'min':
                $this->validateMin($field, $value, $parameter, $customMessage);
                break;
                
            case 'max':
                $this->validateMax($field, $value, $parameter, $customMessage);
                break;
                
            case 'between':
                $parts = explode(',', $parameter);
                $min = (float)$parts[0];
                $max = (float)$parts[1];
                $this->validateBetween($field, $value, $min, $max, $customMessage);
                break;
                
            case 'in':
                $allowed = explode(',', $parameter);
                if (!in_array($value, $allowed)) {
                    $this->addError($field, $customMessage ?? 'Недопустимое значение');
                }
                break;
                
            case 'not_in':
                $disallowed = explode(',', $parameter);
                if (in_array($value, $disallowed)) {
                    $this->addError($field, $customMessage ?? 'Запрещённое значение');
                }
                break;
                
            case 'exists':
                $this->validateExists($field, $value, $parameter, $customMessage);
                break;
                
            case 'unique':
                $this->validateUnique($field, $value, $parameter, $allData, $customMessage);
                break;
                
            case 'positive':
                if (is_numeric($value) && $value <= 0) {
                    $this->addError($field, $customMessage ?? 'Значение должно быть больше 0');
                }
                break;
                
            case 'discount':
                if (is_numeric($value) && ($value < 0 || $value > 100)) {
                    $this->addError($field, $customMessage ?? 'Скидка должна быть от 0 до 100%');
                }
                break;
                
            case 'date':
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                    $this->addError($field, $customMessage ?? 'Неверный формат даты (ГГГГ-ММ-ДД)');
                }
                break;
                
            case 'url':
                if (!filter_var($value, FILTER_VALIDATE_URL)) {
                    $this->addError($field, $customMessage ?? 'Некорректный URL');
                }
                break;
                
            case 'regex':
                if (!preg_match($parameter, $value)) {
                    $this->addError($field, $customMessage ?? 'Неверный формат');
                }
                break;
                
            case 'safe':
                if (is_string($value) && $value !== strip_tags($value)) {
                    $this->addError($field, $customMessage ?? 'Содержит недопустимые HTML-теги');
                }
                break;
        }
    }
    
    /**
     * Валидация минимального значения/длины
     */
    private function validateMin($field, $value, $parameter, $customMessage = null)
    {
        if ($value === null || $value === '') {
            return;
        }
        
        $min = (float)$parameter;
        
        if (is_numeric($value)) {
            if ((float)$value < $min) {
                $this->addError($field, $customMessage ?? "Значение должно быть не меньше {$min}");
            }
        } elseif (is_string($value)) {
            if (mb_strlen($value) < $min) {
                $this->addError($field, $customMessage ?? "Длина должна быть не меньше {$min} символов");
            }
        } elseif (is_array($value)) {
            if (count($value) < $min) {
                $this->addError($field, $customMessage ?? "Количество элементов должно быть не меньше {$min}");
            }
        }
    }
    
    /**
     * Валидация максимального значения/длины
     */
    private function validateMax($field, $value, $parameter, $customMessage = null)
    {
        if ($value === null || $value === '') {
            return;
        }
        
        $max = (float)$parameter;
        
        if (is_numeric($value)) {
            if ((float)$value > $max) {
                $this->addError($field, $customMessage ?? "Значение должно быть не больше {$max}");
            }
        } elseif (is_string($value)) {
            if (mb_strlen($value) > $max) {
                $this->addError($field, $customMessage ?? "Длина должна быть не больше {$max} символов");
            }
        } elseif (is_array($value)) {
            if (count($value) > $max) {
                $this->addError($field, $customMessage ?? "Количество элементов должно быть не больше {$max}");
            }
        }
    }
    
    /**
     * Валидация значения между минимумом и максимумом
     */
    private function validateBetween($field, $value, $min, $max, $customMessage = null)
    {
        if ($value === null || $value === '') {
            return;
        }
        
        if (is_numeric($value)) {
            if ((float)$value < $min || (float)$value > $max) {
                $this->addError($field, $customMessage ?? "Значение должно быть от {$min} до {$max}");
            }
        } elseif (is_string($value)) {
            $length = mb_strlen($value);
            if ($length < $min || $length > $max) {
                $this->addError($field, $customMessage ?? "Длина должна быть от {$min} до {$max} символов");
            }
        }
    }
    
    /**
     * Проверка существования значения в БД
     */
    private function validateExists($field, $value, $parameter, $customMessage = null)
    {
        if ($value === null || $value === '') {
            return;
        }
        
        list($table, $column) = explode(',', $parameter);
        
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$table} WHERE {$column} = ? AND deleted_at IS NULL");
        $stmt->execute([$value]);
        $count = $stmt->fetchColumn();
        
        if ($count == 0) {
            $this->addError($field, $customMessage ?? "Значение не найдено");
        }
    }
    
    /**
     * Проверка уникальности значения в БД
     */
    private function validateUnique($field, $value, $parameter, $allData, $customMessage = null)
    {
        if ($value === null || $value === '') {
            return;
        }
        
        $parts = explode(',', $parameter);
        $table = $parts[0];
        $column = $parts[1];
        $ignoreId = $parts[2] ?? null;
        $ignoreColumn = $parts[3] ?? 'id';
        
        $sql = "SELECT COUNT(*) FROM {$table} WHERE {$column} = ? AND deleted_at IS NULL";
        $params = [$value];
        
        if ($ignoreId && isset($allData[$ignoreColumn])) {
            $sql .= " AND {$ignoreColumn} != ?";
            $params[] = $allData[$ignoreColumn];
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $count = $stmt->fetchColumn();
        
        if ($count > 0) {
            $this->addError($field, $customMessage ?? "Значение должно быть уникальным");
        }
    }
    
    /**
     * Добавить ошибку
     */
    private function addError($field, $message)
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
    }
    
    /**
     * Получить все ошибки
     */
    public function getErrors()
    {
        return $this->errors;
    }
    
    /**
     * Получить первую ошибку для поля
     */
    public function getFirstError($field)
    {
        return isset($this->errors[$field][0]) ? $this->errors[$field][0] : null;
    }
    
    /**
     * Получить все ошибки в виде строки
     */
    public function getErrorsString()
    {
        $strings = [];
        foreach ($this->errors as $field => $messages) {
            $fieldName = $this->getFieldName($field);
            $strings[] = $fieldName . ': ' . implode(', ', $messages);
        }
        return implode('; ', $strings);
    }
    
    /**
     * Получить очищенные данные
     */
    public function getSanitizedData()
    {
        return $this->sanitized;
    }
    
    /**
     * Проверить, есть ли ошибки для конкретного поля
     */
    public function hasError($field)
    {
        return isset($this->errors[$field]);
    }
    
    /**
     * Преобразование имени поля в человекочитаемый вид
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
            'items.*.status_id' => 'Статус товара',
            'order_id' => 'ID заказа',
            'status_id' => 'Статус',
            'order_ids' => 'ID заказов',
            'id' => 'ID',
            'name' => 'Название',
            'price' => 'Цена',
            'confirm' => 'Подтверждение',
            'hours' => 'Часы',
            'lines' => 'Количество строк'
        ];
        
        return $names[$field] ?? $field;
    }
}