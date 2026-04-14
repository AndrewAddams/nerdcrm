<?php
/**
 * Контроллер форматов
 * 
 * Отвечает за все операции с форматами: получение списка, создание,
 * редактирование, удаление
 */

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/Format.php';

class FormatController extends Controller
{
    private $formatModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->formatModel = new Format();
    }
    
    /**
     * Получить список форматов
     * GET /api/formats
     */
    public function index()
    {
        if (!$this->requireAuth()) {
            return;
        }
        
        $search = $_GET['search'] ?? '';
        
        if ($search) {
            $formats = $this->formatModel->getForSelect($search);
        } else {
            $formats = $this->formatModel->getAll();
        }
        
        $this->success($formats);
    }
    
    /**
     * Получить форматы для выпадающего списка (автодополнение)
     * GET /api/formats/select
     */
    public function select()
    {
        if (!$this->requireAuth()) {
            return;
        }
        
        $search = $_GET['q'] ?? $_GET['search'] ?? '';
        $formats = $this->formatModel->getForSelect($search);
        
        $this->success($formats);
    }
    
/**
 * Получить формат по ID
 * GET /api/formats/{id}
 */
public function show($params)
{
    if (!$this->requireAdmin()) {
        return;
    }
    
    $id = (int)$params['id'];
    $format = $this->formatModel->getById($id);
    
    if (!$format) {
        $this->error('Формат не найден', 404);
        return;
    }
    
    $this->success($format);
}

    /**
     * Создать формат
     * POST /api/formats
     */
    public function store()
    {
        if (!$this->requireAdmin()) {
            return;
        }
        
        $data = $this->getRequestData();
        
        if (empty($data['name'])) {
            $this->error('Название формата обязательно');
            return;
        }
        
        if (!isset($data['price']) || !is_numeric($data['price']) || $data['price'] < 0) {
            $this->error('Цена должна быть неотрицательным числом');
            return;
        }
        
        // Проверяем уникальность названия
        if ($this->formatModel->nameExists($data['name'])) {
            $this->error('Формат с таким названием уже существует');
            return;
        }
        
        $id = $this->formatModel->save($data['name'], $data['price']);
        
        if (!$id) {
            $this->error('Ошибка при создании формата', 500);
            return;
        }
        
        $format = $this->formatModel->getById($id);
        $this->success($format, 'Формат успешно создан');
    }
    
    /**
     * Обновить формат
     * PUT /api/formats/{id}
     */
    public function update($params)
    {
        if (!$this->requireAdmin()) {
            return;
        }
        
        $id = (int)$params['id'];
        $data = $this->getRequestData();
        
        if (empty($data['name'])) {
            $this->error('Название формата обязательно');
            return;
        }
        
        if (!isset($data['price']) || !is_numeric($data['price']) || $data['price'] < 0) {
            $this->error('Цена должна быть неотрицательным числом');
            return;
        }
        
        // Проверяем существование формата
        $format = $this->formatModel->getById($id);
        if (!$format) {
            $this->error('Формат не найден', 404);
            return;
        }
        
        // Проверяем уникальность названия (исключая текущий)
        if ($this->formatModel->nameExists($data['name'], $id)) {
            $this->error('Формат с таким названием уже существует');
            return;
        }
        
        $result = $this->formatModel->save($data['name'], $data['price'], $id);
        
        if (!$result) {
            $this->error('Ошибка при обновлении формата', 500);
            return;
        }
        
        $updated = $this->formatModel->getById($id);
        $this->success($updated, 'Формат успешно обновлён');
    }
    
    /**
     * Удалить формат (soft delete)
     * DELETE /api/formats/{id}
     */
    public function delete($params)
    {
        if (!$this->requireAdmin()) {
            return;
        }
        
        $id = (int)$params['id'];
        
        // Проверяем существование формата
        $format = $this->formatModel->getById($id);
        if (!$format) {
            $this->error('Формат не найден', 404);
            return;
        }
        
        $result = $this->formatModel->deleteFormat($id);
        
        if (!$result['success']) {
            $this->error($result['error'], 400);
            return;
        }
        
        $this->success(null, 'Формат успешно удалён');
    }
    
    /**
     * Получить цену формата
     * GET /api/formats/{id}/price
     */
    public function getPrice($params)
    {
        if (!$this->requireAuth()) {
            return;
        }
        
        $id = (int)$params['id'];
        $price = $this->formatModel->getPrice($id);
        
        if ($price === null) {
            $this->error('Формат не найден', 404);
            return;
        }
        
        $this->success(['price' => $price]);
    }
    
    /**
     * Получить пагинированный список форматов для админки
     * GET /api/formats/paginated
     */
    public function paginated()
    {
        if (!$this->requireAdmin()) {
            return;
        }
        
        $page = (int)($_GET['page'] ?? 1);
        $perPage = (int)($_GET['per_page'] ?? 20);
        
        $result = $this->formatModel->getPaginated($page, $perPage);
        $this->success($result);
    }
}