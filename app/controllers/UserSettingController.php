<?php
/**
 * Контроллер настроек пользователя
 * 
 * Отвечает за получение и сохранение персональных настроек:
 * порядок столбцов, видимость, свёрнутые заказы
 */

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/UserSetting.php';

class UserSettingController extends Controller
{
    private $userSettingModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->userSettingModel = new UserSetting();
    }
    
    /**
     * Получить настройки текущего пользователя
     * GET /api/user-settings
     */
    public function get()
    {
        if (!$this->requireAuth()) {
            return;
        }
        
        $settings = $this->userSettingModel->getSettings($this->currentUser['id']);
        
        $this->success($settings);
    }
    
    /**
     * Сохранить настройки текущего пользователя
     * POST /api/user-settings
     */
    public function save()
    {
        if (!$this->requireAuth()) {
            return;
        }
        
        $data = $this->getRequestData();
        
        $saveData = [];
        
        // Проверяем и сохраняем порядок столбцов
        if (isset($data['orders_columns_order']) && is_array($data['orders_columns_order'])) {
            $saveData['orders_columns_order'] = $data['orders_columns_order'];
        }
        
        // Проверяем и сохраняем видимость столбцов
        if (isset($data['orders_columns_visibility']) && is_array($data['orders_columns_visibility'])) {
            $saveData['orders_columns_visibility'] = $data['orders_columns_visibility'];
        }
        
        // Проверяем и сохраняем свёрнутые заказы
        if (isset($data['collapsed_orders']) && is_array($data['collapsed_orders'])) {
            $saveData['collapsed_orders'] = $data['collapsed_orders'];
        }
        
        if (empty($saveData)) {
            $this->error('Нет данных для сохранения');
            return;
        }
        
        $result = $this->userSettingModel->saveSettings($this->currentUser['id'], $saveData);
        
        if (!$result) {
            $this->error('Ошибка сохранения настроек', 500);
            return;
        }
        
        $this->success(null, 'Настройки сохранены');
    }
    
    /**
     * Обновить порядок столбцов
     * POST /api/user-settings/columns-order
     */
    public function updateColumnsOrder()
    {
        if (!$this->requireAuth()) {
            return;
        }
        
        $data = $this->getRequestData();
        
        if (!isset($data['columns_order']) || !is_array($data['columns_order'])) {
            $this->error('Неверный формат данных');
            return;
        }
        
        $result = $this->userSettingModel->updateColumnsOrder(
            $this->currentUser['id'],
            $data['columns_order']
        );
        
        if (!$result) {
            $this->error('Ошибка сохранения порядка столбцов', 500);
            return;
        }
        
        $this->success(null, 'Порядок столбцов сохранён');
    }
    
    /**
     * Обновить видимость столбцов
     * POST /api/user-settings/columns-visibility
     */
    public function updateColumnsVisibility()
    {
        if (!$this->requireAuth()) {
            return;
        }
        
        $data = $this->getRequestData();
        
        if (!isset($data['columns_visibility']) || !is_array($data['columns_visibility'])) {
            $this->error('Неверный формат данных');
            return;
        }
        
        $result = $this->userSettingModel->updateColumnsVisibility(
            $this->currentUser['id'],
            $data['columns_visibility']
        );
        
        if (!$result) {
            $this->error('Ошибка сохранения видимости столбцов', 500);
            return;
        }
        
        $this->success(null, 'Видимость столбцов сохранена');
    }
    
    /**
     * Переключить видимость столбца
     * POST /api/user-settings/toggle-column
     */
    public function toggleColumn()
    {
        if (!$this->requireAuth()) {
            return;
        }
        
        $data = $this->getRequestData();
        
        if (empty($data['column'])) {
            $this->error('Не указан столбец');
            return;
        }
        
        $visible = isset($data['visible']) ? (bool)$data['visible'] : true;
        
        $result = $this->userSettingModel->toggleColumnVisibility(
            $this->currentUser['id'],
            $data['column'],
            $visible
        );
        
        if (!$result) {
            $this->error('Ошибка изменения видимости столбца', 500);
            return;
        }
        
        $this->success(null, 'Видимость столбца изменена');
    }
    
    /**
     * Переключить состояние заказа (свёрнут/развёрнут)
     * POST /api/user-settings/toggle-order-collapsed
     */
    public function toggleOrderCollapsed()
    {
        if (!$this->requireAuth()) {
            return;
        }
        
        $data = $this->getRequestData();
        
        if (empty($data['order_id'])) {
            $this->error('Не указан ID заказа');
            return;
        }
        
        $orderId = (int)$data['order_id'];
        
        $result = $this->userSettingModel->toggleOrderCollapsed(
            $this->currentUser['id'],
            $orderId
        );
        
        if (!$result) {
            $this->error('Ошибка изменения состояния заказа', 500);
            return;
        }
        
        // Возвращаем обновлённый список свёрнутых заказов
        $collapsedOrders = $this->userSettingModel->getCollapsedOrders($this->currentUser['id']);
        
        $this->success([
            'collapsed_orders' => $collapsedOrders,
            'is_collapsed' => in_array($orderId, $collapsedOrders)
        ], 'Состояние заказа изменено');
    }
    
    /**
     * Сбросить настройки пользователя к значениям по умолчанию
     * POST /api/user-settings/reset
     */
    public function reset()
    {
        if (!$this->requireAuth()) {
            return;
        }
        
        $result = $this->userSettingModel->resetSettings($this->currentUser['id']);
        
        if (!$result) {
            $this->error('Ошибка сброса настроек', 500);
            return;
        }
        
        $settings = $this->userSettingModel->getSettings($this->currentUser['id']);
        
        $this->success($settings, 'Настройки сброшены к значениям по умолчанию');
    }
    
    /**
     * Получить список видимых столбцов в правильном порядке
     * GET /api/user-settings/visible-columns
     */
    public function getVisibleColumns()
    {
        if (!$this->requireAuth()) {
            return;
        }
        
        // Список всех возможных столбцов таблицы заказов
        $allColumns = [
            'order_number' => 'Номер заказа',
            'date_created' => 'Дата создания',
            'status_order' => 'Статус заказа',
            'status_payment' => 'Статус оплаты',
            'sale_label' => 'Метка продажи',
            'source' => 'Источник',
            'link' => 'Ссылка',
            'comments' => 'Комментарии',
            'shipping_method' => 'Способ доставки',
            'tracking_number' => 'Трек номер',
            'recipient_name' => 'Получатель',
            'recipient_phone' => 'Телефон',
            'recipient_email' => 'Email',
            'shipping_cost' => 'Стоимость доставки',
            'total_cost' => 'Итого'
        ];
        
        $visibleColumns = $this->userSettingModel->getVisibleColumnsInOrder(
            $this->currentUser['id'],
            array_keys($allColumns)
        );
        
        // Формируем результат с названиями
        $result = [];
        foreach ($visibleColumns as $column) {
            $result[] = [
                'key' => $column,
                'label' => $allColumns[$column] ?? $column
            ];
        }
        
        $this->success([
            'columns' => $result,
            'all_columns' => $allColumns
        ]);
    }
}