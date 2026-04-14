<?php
/**
 * Контроллер статусов
 * 
 * Отвечает за выдачу списков статусов для выпадающих списков в интерфейсе
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