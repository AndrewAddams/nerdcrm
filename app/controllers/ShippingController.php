<?php
/**
 * Контроллер способов доставки
 * 
 * Отвечает за все операции со способами доставки: получение списка,
 * создание, редактирование, удаление
 */

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/ShippingMethod.php';

class ShippingController extends Controller
{
    private $shippingModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->shippingModel = new ShippingMethod();
    }
    
    /**
     * Получить список способов доставки
     * GET /api/shipping-methods
     */
    public function index()
    {
        if (!$this->requireAuth()) {
            return;
        }
        
        $methods = $this->shippingModel->getAll();
        $this->success($methods);
    }
    
    /**
     * Получить способы доставки для выпадающего списка
     * GET /api/shipping-methods/select
     */
    public function select()
    {
        if (!$this->requireAuth()) {
            return;
        }
        
        $methods = $this->shippingModel->getForSelect();
        $this->success($methods);
    }
    
/**
 * Получить способ доставки по ID
 * GET /api/shipping-methods/{id}
 */
public function show($params)
{
    if (!$this->requireAdmin()) {
        return;
    }
    
    $id = (int)$params['id'];
    $method = $this->shippingModel->getById($id);
    
    if (!$method) {
        $this->error('Способ доставки не найден', 404);
        return;
    }
    
    $this->success($method);
}

    /**
     * Создать способ доставки
     * POST /api/shipping-methods
     */
    public function store()
    {
        if (!$this->requireAdmin()) {
            return;
        }
        
        $data = $this->getRequestData();
        
        if (empty($data['name'])) {
            $this->error('Название способа доставки обязательно');
            return;
        }
        
        // Проверяем уникальность названия
        if ($this->shippingModel->nameExists($data['name'])) {
            $this->error('Способ доставки с таким названием уже существует');
            return;
        }
        
        $id = $this->shippingModel->save($data['name']);
        
        if (!$id) {
            $this->error('Ошибка при создании способа доставки', 500);
            return;
        }
        
        $method = $this->shippingModel->getById($id);
        $this->success($method, 'Способ доставки успешно создан');
    }
    
    /**
     * Обновить способ доставки
     * PUT /api/shipping-methods/{id}
     */
    public function update($params)
    {
        if (!$this->requireAdmin()) {
            return;
        }
        
        $id = (int)$params['id'];
        $data = $this->getRequestData();
        
        if (empty($data['name'])) {
            $this->error('Название способа доставки обязательно');
            return;
        }
        
        // Проверяем существование
        $method = $this->shippingModel->getById($id);
        if (!$method) {
            $this->error('Способ доставки не найден', 404);
            return;
        }
        
        // Запрещаем редактирование предопределённых способов
        if (in_array($method['name'], ['СДЭК', 'Яндекс', 'Почта России'])) {
            $this->error('Нельзя редактировать предопределённые способы доставки');
            return;
        }
        
        // Проверяем уникальность названия (исключая текущий)
        if ($this->shippingModel->nameExists($data['name'], $id)) {
            $this->error('Способ доставки с таким названием уже существует');
            return;
        }
        
        $result = $this->shippingModel->save($data['name'], $id);
        
        if (!$result) {
            $this->error('Ошибка при обновлении способа доставки', 500);
            return;
        }
        
        $updated = $this->shippingModel->getById($id);
        $this->success($updated, 'Способ доставки успешно обновлён');
    }
    
    /**
     * Удалить способ доставки (soft delete)
     * DELETE /api/shipping-methods/{id}
     */
    public function delete($params)
    {
        if (!$this->requireAdmin()) {
            return;
        }
        
        $id = (int)$params['id'];
        
        // Проверяем существование
        $method = $this->shippingModel->getById($id);
        if (!$method) {
            $this->error('Способ доставки не найден', 404);
            return;
        }
        
        // Запрещаем удаление предопределённых способов
        if (in_array($method['name'], ['СДЭК', 'Яндекс', 'Почта России'])) {
            $this->error('Нельзя удалить предопределённые способы доставки');
            return;
        }
        
        $result = $this->shippingModel->deleteShippingMethod($id);
        
        if (!$result['success']) {
            $this->error($result['error'], 400);
            return;
        }
        
        $this->success(null, 'Способ доставки успешно удалён');
    }
    
    /**
     * Получить пагинированный список для админки
     * GET /api/shipping-methods/paginated
     */
    public function paginated()
    {
        if (!$this->requireAdmin()) {
            return;
        }
        
        $page = (int)($_GET['page'] ?? 1);
        $perPage = (int)($_GET['per_page'] ?? 20);
        
        $result = $this->shippingModel->getPaginated($page, $perPage);
        $this->success($result);
    }
}