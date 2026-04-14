<?php
/**
 * Контроллер упаковок
 * 
 * Отвечает за все операции с упаковками: получение списка, создание,
 * редактирование, удаление
 */

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/Packaging.php';

class PackagingController extends Controller
{
    private $packagingModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->packagingModel = new Packaging();
    }
    
    /**
     * Получить список упаковок (с пагинацией и поиском)
     * GET /api/packaging
     */
    public function index()
    {
        if (!$this->requireAuth()) {
            return;
        }
        
        $page = (int)($_GET['page'] ?? 1);
        $perPage = (int)($_GET['per_page'] ?? 20);
        $search = $_GET['search'] ?? '';
        
        $result = $this->packagingModel->getPaginated($page, $perPage, $search);
        $this->success($result);
    }
    
    /**
     * Получить упаковку по ID
     * GET /api/packaging/{id}
     */
    public function show($params)
    {
        if (!$this->requireAdmin()) {
            return;
        }
        
        $id = (int)$params['id'];
        $packaging = $this->packagingModel->getById($id);
        
        if (!$packaging) {
            $this->error('Упаковка не найдена', 404);
            return;
        }
        
        $this->success($packaging);
    }
    
    /**
     * Создать упаковку
     * POST /api/packaging
     */
    public function store()
    {
        if (!$this->requireAdmin()) {
            return;
        }
        
        $data = $this->getRequestData();
        
        if (empty($data['name'])) {
            $this->error('Название упаковки обязательно');
            return;
        }
        
        if (empty($data['dimensions'])) {
            $this->error('Размеры упаковки обязательны');
            return;
        }
        
        if ($this->packagingModel->nameExists($data['name'])) {
            $this->error('Упаковка с таким названием уже существует');
            return;
        }
        
        $id = $this->packagingModel->save($data['name'], $data['dimensions']);
        
        if (!$id) {
            $this->error('Ошибка при создании упаковки', 500);
            return;
        }
        
        $packaging = $this->packagingModel->getById($id);
        $this->success($packaging, 'Упаковка успешно создана');
    }
    
    /**
     * Обновить упаковку
     * PUT /api/packaging/{id}
     */
    public function update($params)
    {
        if (!$this->requireAdmin()) {
            return;
        }
        
        $id = (int)$params['id'];
        $data = $this->getRequestData();
        
        if (empty($data['name'])) {
            $this->error('Название упаковки обязательно');
            return;
        }
        
        if (empty($data['dimensions'])) {
            $this->error('Размеры упаковки обязательны');
            return;
        }
        
        $packaging = $this->packagingModel->getById($id);
        if (!$packaging) {
            $this->error('Упаковка не найдена', 404);
            return;
        }
        
        if ($this->packagingModel->nameExists($data['name'], $id)) {
            $this->error('Упаковка с таким названием уже существует');
            return;
        }
        
        $result = $this->packagingModel->save($data['name'], $data['dimensions'], $id);
        
        if (!$result) {
            $this->error('Ошибка при обновлении упаковки', 500);
            return;
        }
        
        $updated = $this->packagingModel->getById($id);
        $this->success($updated, 'Упаковка успешно обновлена');
    }
    
    /**
     * Удалить упаковку
     * DELETE /api/packaging/{id}
     */
    public function delete($params)
    {
        if (!$this->requireAdmin()) {
            return;
        }
        
        $id = (int)$params['id'];
        
        $packaging = $this->packagingModel->getById($id);
        if (!$packaging) {
            $this->error('Упаковка не найдена', 404);
            return;
        }
        
        $result = $this->packagingModel->deletePackaging($id);
        
        if (!$result['success']) {
            $this->error($result['error'], 400);
            return;
        }
        
        $this->success(null, 'Упаковка успешно удалена');
    }
}