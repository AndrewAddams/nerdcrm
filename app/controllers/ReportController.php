<?php
/**
 * Контроллер отчётов
 */

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/Report.php';

class ReportController extends Controller
{
    private $reportModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->reportModel = new Report();
    }
    
    /**
     * Страница отчётов
     */
    public function index()
    {
        if (!$this->requireAuth()) {
            return;
        }
        
        $this->view('reports', [
            'user' => $this->currentUser
        ]);
    }
    
    /**
     * Отчёт 1: Выручка по постановщикам
     * POST /api/reports/revenue-by-assigner
     */
    public function getRevenueByAssigner()
    {
        if (!$this->requireAuth()) {
            return;
        }
        
        $data = $this->getRequestData();
        
        $dateFrom = $data['date_from'] ?? $this->reportModel->getCurrentMonthStart();
        $dateTo = $data['date_to'] ?? $this->reportModel->getToday();
        
        $result = $this->reportModel->getRevenueByAssigner($dateFrom, $dateTo);
        
        $this->success($result);
    }
    
    /**
     * Отчёт 2: Количество заказов по покупателю
     * POST /api/reports/orders-by-customer
     */
    public function getOrdersByCustomer()
    {
        if (!$this->requireAuth()) {
            return;
        }
        
        $data = $this->getRequestData();
        
        $dateFrom = $data['date_from'] ?? $this->reportModel->getCurrentMonthStart();
        $dateTo = $data['date_to'] ?? $this->reportModel->getToday();
        
        $result = $this->reportModel->getOrdersByCustomer($dateFrom, $dateTo);
        
        $this->success($result);
    }
    
    /**
     * Отчёт 3: Самый популярный товар
     * POST /api/reports/popular-products
     */
    public function getPopularProducts()
    {
        if (!$this->requireAuth()) {
            return;
        }
        
        $data = $this->getRequestData();
        
        $dateFrom = $data['date_from'] ?? $this->reportModel->getCurrentMonthStart();
        $dateTo = $data['date_to'] ?? $this->reportModel->getToday();
        
        $result = $this->reportModel->getPopularProducts($dateFrom, $dateTo);
        
        $this->success($result);
    }
    
    /**
     * Отчёт 4: Самый популярный формат
     * POST /api/reports/popular-formats
     */
    public function getPopularFormats()
    {
        if (!$this->requireAuth()) {
            return;
        }
        
        $data = $this->getRequestData();
        
        $dateFrom = $data['date_from'] ?? $this->reportModel->getCurrentMonthStart();
        $dateTo = $data['date_to'] ?? $this->reportModel->getToday();
        
        $result = $this->reportModel->getPopularFormats($dateFrom, $dateTo);
        
        $this->success($result);
    }
    
    /**
     * Отчёт 5: Количество первичных и вторичных продаж
     * POST /api/reports/sales-by-label
     */
    public function getSalesByLabel()
    {
        if (!$this->requireAuth()) {
            return;
        }
        
        $data = $this->getRequestData();
        
        $dateFrom = $data['date_from'] ?? $this->reportModel->getCurrentMonthStart();
        $dateTo = $data['date_to'] ?? $this->reportModel->getToday();
        
        $result = $this->reportModel->getSalesByLabel($dateFrom, $dateTo);
        
        $this->success($result);
    }
}