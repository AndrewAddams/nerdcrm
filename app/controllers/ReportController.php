<?php
/**
 * Контроллер отчётов
 * 
 * Отвечает за генерацию различных отчётов:
 * - Выручка по постановщикам
 * - Заказы по покупателям
 * - Популярные товары
 * - Популярные форматы
 * - Продажи по меткам (первичные/вторичные)
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
     * GET /reports
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
        
        // Валидация и нормализация дат
        $dateFrom = $this->validateAndNormalizeDate($data['date_from'] ?? null);
        $dateTo = $this->validateAndNormalizeDate($data['date_to'] ?? null);
        
        // Если даты не указаны или невалидны — используем значения по умолчанию
        if (!$dateFrom) {
            $dateFrom = $this->reportModel->getCurrentMonthStart();
        }
        if (!$dateTo) {
            $dateTo = $this->reportModel->getToday();
        }
        
        // Проверяем, что date_from не больше date_to
        if ($dateFrom && $dateTo && $dateFrom > $dateTo) {
            $this->error('Дата начала не может быть позже даты окончания');
            return;
        }
        
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
        
        // Валидация и нормализация дат
        $dateFrom = $this->validateAndNormalizeDate($data['date_from'] ?? null);
        $dateTo = $this->validateAndNormalizeDate($data['date_to'] ?? null);
        
        if (!$dateFrom) {
            $dateFrom = $this->reportModel->getCurrentMonthStart();
        }
        if (!$dateTo) {
            $dateTo = $this->reportModel->getToday();
        }
        
        if ($dateFrom && $dateTo && $dateFrom > $dateTo) {
            $this->error('Дата начала не может быть позже даты окончания');
            return;
        }
        
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
        
        // Валидация и нормализация дат
        $dateFrom = $this->validateAndNormalizeDate($data['date_from'] ?? null);
        $dateTo = $this->validateAndNormalizeDate($data['date_to'] ?? null);
        
        if (!$dateFrom) {
            $dateFrom = $this->reportModel->getCurrentMonthStart();
        }
        if (!$dateTo) {
            $dateTo = $this->reportModel->getToday();
        }
        
        if ($dateFrom && $dateTo && $dateFrom > $dateTo) {
            $this->error('Дата начала не может быть позже даты окончания');
            return;
        }
        
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
        
        // Валидация и нормализация дат
        $dateFrom = $this->validateAndNormalizeDate($data['date_from'] ?? null);
        $dateTo = $this->validateAndNormalizeDate($data['date_to'] ?? null);
        
        if (!$dateFrom) {
            $dateFrom = $this->reportModel->getCurrentMonthStart();
        }
        if (!$dateTo) {
            $dateTo = $this->reportModel->getToday();
        }
        
        if ($dateFrom && $dateTo && $dateFrom > $dateTo) {
            $this->error('Дата начала не может быть позже даты окончания');
            return;
        }
        
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
        
        // Валидация и нормализация дат
        $dateFrom = $this->validateAndNormalizeDate($data['date_from'] ?? null);
        $dateTo = $this->validateAndNormalizeDate($data['date_to'] ?? null);
        
        if (!$dateFrom) {
            $dateFrom = $this->reportModel->getCurrentMonthStart();
        }
        if (!$dateTo) {
            $dateTo = $this->reportModel->getToday();
        }
        
        if ($dateFrom && $dateTo && $dateFrom > $dateTo) {
            $this->error('Дата начала не может быть позже даты окончания');
            return;
        }
        
        $result = $this->reportModel->getSalesByLabel($dateFrom, $dateTo);
        
        $this->success($result);
    }
    
    /**
     * Валидация и нормализация даты
     * 
     * @param string|null $date Дата в формате YYYY-MM-DD
     * @return string|null Нормализованная дата или null, если дата невалидна
     */
    private function validateAndNormalizeDate($date)
    {
        if ($date === null || $date === '') {
            return null;
        }
        
        // Проверяем формат YYYY-MM-DD
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return null;
        }
        
        // Проверяем, что дата существует (например, не 2025-13-40)
        $timestamp = strtotime($date);
        if ($timestamp === false || $timestamp <= 0) {
            return null;
        }
        
        // Проверяем, что дата не в будущем (опционально)
        $today = strtotime(date('Y-m-d'));
        if ($timestamp > $today) {
            return null;
        }
        
        return $date;
    }
}