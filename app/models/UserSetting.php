<?php
/**
 * Модель настроек пользователя
 * 
 * Управляет персональными настройками: порядок столбцов, видимость,
 * свёрнутые заказы и другие пользовательские предпочтения
 */

require_once __DIR__ . '/../core/Model.php';

class UserSetting extends Model
{
    /**
     * Название таблицы
     * 
     * @var string
     */
    protected $table = 'user_settings';
    
    /**
     * Использовать soft delete (настройки не удаляются)
     * 
     * @var bool
     */
    protected $softDelete = false;
    
    /**
     * Получить настройки пользователя
     * 
     * @param int $userId
     * @return array Настройки с значениями по умолчанию
     */
    public function getSettings($userId)
    {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = :user_id";
        $stmt = $this->db->query($sql, ['user_id' => $userId]);
        $settings = $stmt->fetch();
        
        if (!$settings) {
            // Возвращаем настройки по умолчанию
            return [
                'user_id' => $userId,
                'orders_columns_order' => ['order_number', 'date_created', 'status_order', 'status_payment'],
                'orders_columns_visibility' => [
                    'order_number' => true,
                    'date_created' => true,
                    'status_order' => true,
                    'status_payment' => true
                ],
                'collapsed_orders' => []
            ];
        }
        
        // Декодируем JSON поля
        $settings['orders_columns_order'] = json_decode($settings['orders_columns_order'], true) ?: $this->getDefaultColumnsOrder();
        $settings['orders_columns_visibility'] = json_decode($settings['orders_columns_visibility'], true) ?: $this->getDefaultColumnsVisibility();
        $settings['collapsed_orders'] = json_decode($settings['collapsed_orders'], true) ?: [];
        
        return $settings;
    }
    
    /**
     * Сохранить настройки пользователя
     * 
     * @param int $userId
     * @param array $data Настройки
     * @return bool
     */
    public function saveSettings($userId, $data)
    {
        // Подготавливаем данные для сохранения
        $saveData = [
            'user_id' => $userId,
            'orders_columns_order' => isset($data['orders_columns_order']) 
                ? json_encode($data['orders_columns_order']) 
                : null,
            'orders_columns_visibility' => isset($data['orders_columns_visibility']) 
                ? json_encode($data['orders_columns_visibility']) 
                : null,
            'collapsed_orders' => isset($data['collapsed_orders']) 
                ? json_encode($data['collapsed_orders']) 
                : null
        ];
        
        // Проверяем, существуют ли уже настройки
        $existing = $this->findBy('user_id', $userId);
        
        if ($existing) {
            // Обновляем существующие
            return $this->update($existing['id'], $saveData);
        } else {
            // Создаём новые
            return $this->create($saveData);
        }
    }
    
    /**
     * Обновить порядок столбцов
     * 
     * @param int $userId
     * @param array $columnsOrder
     * @return bool
     */
    public function updateColumnsOrder($userId, $columnsOrder)
    {
        $settings = $this->getSettings($userId);
        $settings['orders_columns_order'] = $columnsOrder;
        
        return $this->saveSettings($userId, [
            'orders_columns_order' => $columnsOrder,
            'orders_columns_visibility' => $settings['orders_columns_visibility'],
            'collapsed_orders' => $settings['collapsed_orders']
        ]);
    }
    
    /**
     * Обновить видимость столбцов
     * 
     * @param int $userId
     * @param array $columnsVisibility
     * @return bool
     */
    public function updateColumnsVisibility($userId, $columnsVisibility)
    {
        $settings = $this->getSettings($userId);
        
        return $this->saveSettings($userId, [
            'orders_columns_order' => $settings['orders_columns_order'],
            'orders_columns_visibility' => $columnsVisibility,
            'collapsed_orders' => $settings['collapsed_orders']
        ]);
    }
    
    /**
     * Переключить видимость столбца
     * 
     * @param int $userId
     * @param string $column
     * @param bool $visible
     * @return bool
     */
    public function toggleColumnVisibility($userId, $column, $visible)
    {
        $settings = $this->getSettings($userId);
        $settings['orders_columns_visibility'][$column] = $visible;
        
        return $this->updateColumnsVisibility($userId, $settings['orders_columns_visibility']);
    }
    
    /**
     * Обновить список свёрнутых заказов
     * 
     * @param int $userId
     * @param array $collapsedOrders
     * @return bool
     */
    public function updateCollapsedOrders($userId, $collapsedOrders)
    {
        $settings = $this->getSettings($userId);
        
        return $this->saveSettings($userId, [
            'orders_columns_order' => $settings['orders_columns_order'],
            'orders_columns_visibility' => $settings['orders_columns_visibility'],
            'collapsed_orders' => $collapsedOrders
        ]);
    }
    
    /**
     * Переключить состояние заказа (свёрнут/развёрнут)
     * 
     * @param int $userId
     * @param int $orderId
     * @return bool
     */
    public function toggleOrderCollapsed($userId, $orderId)
    {
        $settings = $this->getSettings($userId);
        $collapsed = $settings['collapsed_orders'];
        
        $key = array_search($orderId, $collapsed);
        
        if ($key !== false) {
            // Удаляем из списка (разворачиваем)
            unset($collapsed[$key]);
            $collapsed = array_values($collapsed);
        } else {
            // Добавляем в список (сворачиваем)
            $collapsed[] = $orderId;
        }
        
        return $this->updateCollapsedOrders($userId, $collapsed);
    }
    
    /**
     * Получить список свёрнутых заказов
     * 
     * @param int $userId
     * @return array
     */
    public function getCollapsedOrders($userId)
    {
        $settings = $this->getSettings($userId);
        return $settings['collapsed_orders'];
    }
    
    /**
     * Получить порядок столбцов
     * 
     * @param int $userId
     * @return array
     */
    public function getColumnsOrder($userId)
    {
        $settings = $this->getSettings($userId);
        return $settings['orders_columns_order'];
    }
    
    /**
     * Получить видимость столбцов
     * 
     * @param int $userId
     * @return array
     */
    public function getColumnsVisibility($userId)
    {
        $settings = $this->getSettings($userId);
        return $settings['orders_columns_visibility'];
    }
    
    /**
     * Получить список видимых столбцов (в правильном порядке)
     * 
     * @param int $userId
     * @param array $allColumns Список всех возможных столбцов
     * @return array
     */
    public function getVisibleColumnsInOrder($userId, $allColumns)
    {
        $order = $this->getColumnsOrder($userId);
        $visibility = $this->getColumnsVisibility($userId);
        
        $visibleColumns = [];
        
        // Сначала добавляем столбцы в заданном порядке
        foreach ($order as $column) {
            if (isset($visibility[$column]) && $visibility[$column]) {
                $visibleColumns[] = $column;
            }
        }
        
        // Добавляем остальные видимые столбцы, которые не попали в порядок
        foreach ($allColumns as $column) {
            if (isset($visibility[$column]) && $visibility[$column] && !in_array($column, $visibleColumns)) {
                $visibleColumns[] = $column;
            }
        }
        
        return $visibleColumns;
    }
    
    /**
     * Сбросить настройки пользователя к значениям по умолчанию
     * 
     * @param int $userId
     * @return bool
     */
    public function resetSettings($userId)
    {
        return $this->saveSettings($userId, [
            'orders_columns_order' => $this->getDefaultColumnsOrder(),
            'orders_columns_visibility' => $this->getDefaultColumnsVisibility(),
            'collapsed_orders' => []
        ]);
    }
    
    /**
     * Получить порядок столбцов по умолчанию
     * 
     * @return array
     */
    private function getDefaultColumnsOrder()
    {
        return ['order_number', 'date_created', 'status_order', 'status_payment'];
    }
    
    /**
     * Получить видимость столбцов по умолчанию
     * 
     * @return array
     */
    private function getDefaultColumnsVisibility()
    {
        return [
            'order_number' => true,
            'date_created' => true,
            'status_order' => true,
            'status_payment' => true
        ];
    }
}