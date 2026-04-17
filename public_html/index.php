<?php
/**
 * Точка входа приложения
 * 
 * Все запросы перенаправляются сюда через .htaccess
 * Здесь происходит инициализация приложения и запуск маршрутизатора
 */

// Включаем отображение ошибок при разработке
// В продакшене нужно отключить или залогировать
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Запускаем сессию для аутентификации
session_start();

// Определяем корневую директорию проекта
define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');

// Подключаем автозагрузку классов
spl_autoload_register(function ($className) {
    // Список возможных путей для классов
    $paths = [
        APP_PATH . '/core/',
        APP_PATH . '/controllers/',
        APP_PATH . '/models/',
        APP_PATH . '/helpers/',
    ];
    
    foreach ($paths as $path) {
        $file = $path . $className . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Подключаем конфигурацию базы данных (она не использует автозагрузку)
require_once APP_PATH . '/config/database.php';

// Подключаем конфигурационные константы
require_once APP_PATH . '/config/constants.php';

// Создаём экземпляр роутера
$router = new Router($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);

// =====================================================
// СТРАНИЦЫ (возвращают HTML)
// =====================================================

// Основные страницы
$router->get('/', 'PageController@index');
$router->get('/login', 'PageController@login');
$router->get('/dashboard', 'PageController@dashboard');
$router->get('/production', 'PageController@production');
$router->get('/admin', 'PageController@admin');
$router->get('/profile', 'PageController@profile');
$router->get('/settings', 'PageController@settings');
$router->get('/help', 'PageController@help');
$router->get('/tilda', 'PageController@tilda');

// Страницы ошибок
$router->get('/404', 'PageController@notFound');
$router->get('/403', 'PageController@forbidden');
$router->get('/500', 'PageController@serverError');

$router->get('/reports', 'ReportController@index');

// =====================================================
// API МАРШРУТЫ (возвращают JSON)
// =====================================================

// Аутентификация
$router->post('/api/auth/login', 'AuthController@login');
$router->post('/api/auth/logout', 'AuthController@logout');
$router->get('/api/auth/check', 'AuthController@check');

// Заказы
$router->get('/api/orders', 'OrderController@index');
$router->get('/api/orders/folders', 'OrderController@getFolders');
$router->get('/api/orders/{id}', 'OrderController@show');
$router->post('/api/orders', 'OrderController@store');
$router->put('/api/orders/{id}', 'OrderController@update');
$router->put('/api/orders/{id}/status-order', 'OrderController@updateStatusOrder');
$router->put('/api/orders/{id}/status-payment', 'OrderController@updateStatusPayment');
$router->put('/api/orders/items/{itemId}/status', 'OrderController@updateItemStatus');
$router->post('/api/orders/bulk-update-status', 'OrderController@bulkUpdateStatus');
$router->post('/api/orders/bulk-delete', 'OrderController@bulkDelete');

// Товары (ВАЖНО: сначала специфичные маршруты, потом общие с {id})
$router->get('/api/products/select', 'ProductController@select');
$router->post('/api/products/import', 'ProductController@import');
$router->get('/api/products/export', 'ProductController@export');  // export должен быть перед {id}
$router->get('/api/products', 'ProductController@index');
$router->get('/api/products/{id}', 'ProductController@show');
$router->post('/api/products', 'ProductController@store');
$router->put('/api/products/{id}', 'ProductController@update');
$router->delete('/api/products/{id}', 'ProductController@delete');

// Форматы
$router->get('/api/formats', 'FormatController@index');
$router->get('/api/formats/select', 'FormatController@select');
$router->get('/api/formats/{id}', 'FormatController@show');
$router->post('/api/formats', 'FormatController@store');
$router->put('/api/formats/{id}', 'FormatController@update');
$router->delete('/api/formats/{id}', 'FormatController@delete');
$router->get('/api/formats/{id}/price', 'FormatController@getPrice');
$router->get('/api/formats/paginated', 'FormatController@paginated');

// Источники заказов
$router->get('/api/sources', 'SourceController@index');
$router->get('/api/sources/select', 'SourceController@select');
$router->get('/api/sources/{id}', 'SourceController@show');
$router->post('/api/sources', 'SourceController@store');
$router->put('/api/sources/{id}', 'SourceController@update');
$router->delete('/api/sources/{id}', 'SourceController@delete');
$router->get('/api/sources/paginated', 'SourceController@paginated');

// Способы доставки
$router->get('/api/shipping-methods', 'ShippingController@index');
$router->get('/api/shipping-methods/select', 'ShippingController@select');
$router->get('/api/shipping-methods/{id}', 'ShippingController@show');
$router->post('/api/shipping-methods', 'ShippingController@store');
$router->put('/api/shipping-methods/{id}', 'ShippingController@update');
$router->delete('/api/shipping-methods/{id}', 'ShippingController@delete');
$router->get('/api/shipping-methods/paginated', 'ShippingController@paginated');

// Пользователи
$router->get('/api/users', 'UserController@index');
$router->get('/api/users/paginated', 'UserController@paginated');
$router->get('/api/users/{id}', 'UserController@show');
$router->post('/api/users', 'UserController@store');
$router->put('/api/users/{id}', 'UserController@update');
$router->delete('/api/users/{id}', 'UserController@delete');
$router->post('/api/users/{id}/restore', 'UserController@restore');
$router->get('/api/users/deleted', 'UserController@getDeleted');
$router->get('/api/users/managers', 'UserController@getManagers');
$router->post('/api/users/change-password', 'UserController@changePassword');
$router->post('/api/users/update-profile', 'UserController@updateProfile');

// Админские действия
$router->post('/api/admin/reset-order-counter', 'AdminController@resetOrderCounter');
$router->get('/api/admin/statistics', 'AdminController@getStatistics');
$router->get('/api/admin/system-info', 'AdminController@getSystemInfo');
$router->post('/api/admin/cleanup-temp', 'AdminController@cleanupTemp');
$router->get('/api/admin/logs', 'AdminController@getLogs');
$router->get('/api/admin/check-db', 'AdminController@checkDatabase');
$router->get('/api/admin/backup', 'AdminController@backupDatabase');
$router->get('/api/admin/settings', 'AdminController@getSettings');

// Настройки пользователя
$router->get('/api/user-settings', 'UserSettingController@get');
$router->post('/api/user-settings', 'UserSettingController@save');
$router->post('/api/user-settings/columns-order', 'UserSettingController@updateColumnsOrder');
$router->post('/api/user-settings/columns-visibility', 'UserSettingController@updateColumnsVisibility');
$router->post('/api/user-settings/toggle-column', 'UserSettingController@toggleColumn');
$router->post('/api/user-settings/toggle-order-collapsed', 'UserSettingController@toggleOrderCollapsed');
$router->post('/api/user-settings/reset', 'UserSettingController@reset');
$router->get('/api/user-settings/visible-columns', 'UserSettingController@getVisibleColumns');

// Статусы
$router->get('/api/statuses/order', 'StatusController@getOrderStatuses');
$router->get('/api/statuses/payment', 'StatusController@getPaymentStatuses');
$router->get('/api/statuses/order-item', 'StatusController@getOrderItemStatuses');
$router->get('/api/statuses/all', 'StatusController@getAll');

// Список для производства
$router->get('/api/production/items', 'ProductionController@getItems');
$router->put('/api/production/items/{itemId}/status', 'ProductionController@updateStatus');
$router->get('/api/production/count', 'ProductionController@getCount');
$router->get('/api/production/items/by-order/{orderId}', 'ProductionController@getItemsByOrder');
$router->post('/api/production/bulk-update-status', 'ProductionController@bulkUpdateStatus');
$router->post('/api/production/items/{itemId}/complete', 'ProductionController@completeItem');
$router->post('/api/production/complete-order/{orderId}', 'ProductionController@completeOrderItems');

// Отчёты
$router->post('/api/reports/revenue-by-assigner', 'ReportController@getRevenueByAssigner');
$router->post('/api/reports/orders-by-customer', 'ReportController@getOrdersByCustomer');
$router->post('/api/reports/popular-products', 'ReportController@getPopularProducts');
$router->post('/api/reports/popular-formats', 'ReportController@getPopularFormats');
$router->post('/api/reports/sales-by-label', 'ReportController@getSalesByLabel');

// Tilda вебхук и API
$router->post('/api/webhook/tilda', 'WebhookController@tilda');
$router->post('/api/tilda/orders/{id}/mark-read', 'WebhookController@markAsRead');
$router->get('/api/tilda/orders', 'WebhookController@getOrders');
$router->get('/api/tilda/folders', 'WebhookController@getFolders');
$router->get('/api/tilda/unread-count', 'WebhookController@getUnreadCount');
$router->delete('/api/tilda/orders/{id}', 'WebhookController@deleteOrder');

// Справочники
$router->get('/references', 'PageController@references');

// Упаковка API
$router->get('/api/packaging', 'PackagingController@index');
$router->get('/api/packaging/{id}', 'PackagingController@show');
$router->post('/api/packaging', 'PackagingController@store');
$router->put('/api/packaging/{id}', 'PackagingController@update');
$router->delete('/api/packaging/{id}', 'PackagingController@delete');

// =====================================================
// ЗАПУСК МАРШРУТИЗАТОРА
// =====================================================

try {
    $router->run();
} catch (Exception $e) {
    // Обработка ошибок
    http_response_code(500);
    
    // Логируем ошибку
    error_log("Ошибка в маршрутизаторе: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    
    // Определяем, API это или страница
    if (strpos($_SERVER['REQUEST_URI'], '/api/') === 0) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'Внутренняя ошибка сервера',
            'message' => $e->getMessage()
        ]);
    } else {
        // Перенаправляем на страницу 500
        header('Location: /500');
        exit;
    }
}