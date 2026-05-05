<?php
/**
 * Контроллер статусов
 * 
 * Отвечает за выдачу списков статусов для выпадающих списков в интерфейсе
 * 
 * ВНИМАНИЕ: В этом контроллере только GET-запросы (чтение данных).
 * Операции создания/обновления/удаления статусов отсутствуют.
 * Валидация здесь не требуется, так как данные только читаются из БД.
 */

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/Status.php';

class StatusController extends Controller
{
    private $statusModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->statusModel = new Status();
    }
    
    /**
     * Получить статусы заказов
     * GET /api/statuses/order
     * 
     * Возвращает статусы с типами: order (В работе, Упакован, Отправлен)
     */
    public function getOrderStatuses()
    {
        if (!$this->requireAuth()) {
            return;
        }
        
        $statuses = $this->statusModel->getOrderStatuses();
        $this->success($statuses);
    }
    
    /**
     * Получить статусы оплаты
     * GET /api/statuses/payment
     * 
     * Возвращает статусы с типами: payment (Не оплачен, Оплачен)
     */
    public function getPaymentStatuses()
    {
        if (!$this->requireAuth()) {
            return;
        }
        
        $statuses = $this->statusModel->getPaymentStatuses();
        $this->success($statuses);
    }
    
    /**
     * Получить статусы товаров в заказе
     * GET /api/statuses/order-item
     * 
     * Возвращает статусы с типами: order_item (В работе, Сделать, Готов)
     */
    public function getOrderItemStatuses()
    {
        if (!$this->requireAuth()) {
            return;
        }
        
        $statuses = $this->statusModel->getOrderItemStatuses();
        $this->success($statuses);
    }
    
    /**
     * Получить все статусы (для админки)
     * GET /api/statuses/all
     * 
     * Возвращает все статусы всех типов, сгруппированные по типу
     * Только для администраторов
     */
    public function getAll()
    {
        if (!$this->requireAdmin()) {
            return;
        }
        
        $statuses = $this->statusModel->getAllForSelect();
        $this->success($statuses);
    }
}