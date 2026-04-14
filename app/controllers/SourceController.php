<?php
/**
 * Контроллер источников заказов
 * 
 * Отвечает за все операции с источниками: получение списка, создание,
 * редактирование, удаление
 */

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/Source.php';

class SourceController extends Controller
{
    private $sourceModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->sourceModel = new Source();
    }
    
    /**
     * Получить список источников
     * GET /api/sources
     */
    public function index()
    {
        if (!$this->requireAuth()) {
            return;
        }
        
        $sources = $this->sourceModel->getAll();
        $this->success($sources);
    }
    
    /**
     * Получить источники для выпадающего списка
     * GET /api/sources/select
     */
    public function select()
    {
        if (!$this->requireAuth()) {
            return;
        }
        
        $sources = $this->sourceModel->getForSelect();
        $this->success($sources);
    }
    
/**
 * Получить источник по ID
 * GET /api/sources/{id}
 */
public function show($params)
{
    if (!$this->requireAdmin()) {
        return;
    }
    
    $id = (int)$params['id'];
    $source = $this->sourceModel->getById($id);
    
    if (!$source) {
        $this->error('Источник не найден', 404);
        return;
    }
    
    $this->success($source);
}

    /**
     * Создать источник
     * POST /api/sources
     */
    public function store()
    {
        if (!$this->requireAdmin()) {
            return;
        }
        
        $data = $this->getRequestData();
        
        if (empty($data['name'])) {
            $this->error('Название источника обязательно');
            return;
        }
        
        // Проверяем уникальность названия
        if ($this->sourceModel->nameExists($data['name'])) {
            $this->error('Источник с таким названием уже существует');
            return;
        }
        
        $id = $this->sourceModel->save($data['name']);
        
        if (!$id) {
            $this->error('Ошибка при создании источника', 500);
            return;
        }
        
        $source = $this->sourceModel->getById($id);
        $this->success($source, 'Источник успешно создан');
    }
    
    /**
     * Обновить источник
     * PUT /api/sources/{id}
     */
    public function update($params)
    {
        if (!$this->requireAdmin()) {
            return;
        }
        
        $id = (int)$params['id'];
        $data = $this->getRequestData();
        
        if (empty($data['name'])) {
            $this->error('Название источника обязательно');
            return;
        }
        
        // Проверяем существование источника
        $source = $this->sourceModel->getById($id);
        if (!$source) {
            $this->error('Источник не найден', 404);
            return;
        }
        
        // Запрещаем редактирование предопределённых источников
        if (in_array($source['name'], ['ВК', 'Сайт'])) {
            $this->error('Нельзя редактировать предопределённые источники');
            return;
        }
        
        // Проверяем уникальность названия (исключая текущий)
        if ($this->sourceModel->nameExists($data['name'], $id)) {
            $this->error('Источник с таким названием уже существует');
            return;
        }
        
        $result = $this->sourceModel->save($data['name'], $id);
        
        if (!$result) {
            $this->error('Ошибка при обновлении источника', 500);
            return;
        }
        
        $updated = $this->sourceModel->getById($id);
        $this->success($updated, 'Источник успешно обновлён');
    }
    
    /**
     * Удалить источник (soft delete)
     * DELETE /api/sources/{id}
     */
    public function delete($params)
    {
        if (!$this->requireAdmin()) {
            return;
        }
        
        $id = (int)$params['id'];
        
        // Проверяем существование источника
        $source = $this->sourceModel->getById($id);
        if (!$source) {
            $this->error('Источник не найден', 404);
            return;
        }
        
        // Запрещаем удаление предопределённых источников
        if (in_array($source['name'], ['ВК', 'Сайт'])) {
            $this->error('Нельзя удалить предопределённые источники');
            return;
        }
        
        $result = $this->sourceModel->deleteSource($id);
        
        if (!$result['success']) {
            $this->error($result['error'], 400);
            return;
        }
        
        $this->success(null, 'Источник успешно удалён');
    }
    
    /**
     * Получить пагинированный список источников для админки
     * GET /api/sources/paginated
     */
    public function paginated()
    {
        if (!$this->requireAdmin()) {
            return;
        }
        
        $page = (int)($_GET['page'] ?? 1);
        $perPage = (int)($_GET['per_page'] ?? 20);
        
        $result = $this->sourceModel->getPaginated($page, $perPage);
        $this->success($result);
    }
}