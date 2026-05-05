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
        
        // Валидация ID
        $rules = ['id' => 'required|int|exists:formats,id'];
        if (!$this->validate(['id' => $id], $rules)) {
            return;
        }
        
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
        
        // Валидация
        $rules = [
            'name' => 'required|string|max:100',
            'price' => 'required|numeric|min:0|max:1000000'
        ];
        
        $messages = [
            'name.required' => 'Название формата обязательно',
            'name.max' => 'Название не должно превышать 100 символов',
            'price.required' => 'Цена обязательна',
            'price.numeric' => 'Цена должна быть числом',
            'price.min' => 'Цена не может быть отрицательной',
            'price.max' => 'Цена не может превышать 1 000 000 ₽'
        ];
        
        if (!$this->validate($data, $rules, $messages)) {
            return;
        }
        
        $validatedData = $this->getValidatedData();
        
        // Проверяем уникальность названия
        if ($this->formatModel->nameExists($validatedData['name'])) {
            $this->error('Формат с таким названием уже существует');
            return;
        }
        
        $id = $this->formatModel->save($validatedData['name'], $validatedData['price']);
        
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
        
        // Валидация ID
        $idRules = ['id' => 'required|int|exists:formats,id'];
        if (!$this->validate(['id' => $id], $idRules)) {
            return;
        }
        
        // Валидация данных
        $rules = [
            'name' => 'required|string|max:100',
            'price' => 'required|numeric|min:0|max:1000000'
        ];
        
        $messages = [
            'name.required' => 'Название формата обязательно',
            'name.max' => 'Название не должно превышать 100 символов',
            'price.required' => 'Цена обязательна',
            'price.numeric' => 'Цена должна быть числом',
            'price.min' => 'Цена не может быть отрицательной',
            'price.max' => 'Цена не может превышать 1 000 000 ₽'
        ];
        
        if (!$this->validate($data, $rules, $messages)) {
            return;
        }
        
        $validatedData = $this->getValidatedData();
        
        // Проверяем существование формата
        $format = $this->formatModel->getById($id);
        if (!$format) {
            $this->error('Формат не найден', 404);
            return;
        }
        
        // Проверяем уникальность названия (исключая текущий)
        if ($this->formatModel->nameExists($validatedData['name'], $id)) {
            $this->error('Формат с таким названием уже существует');
            return;
        }
        
        $result = $this->formatModel->save($validatedData['name'], $validatedData['price'], $id);
        
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
        
        // Валидация ID
        $rules = ['id' => 'required|int|exists:formats,id'];
        if (!$this->validate(['id' => $id], $rules)) {
            return;
        }
        
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
        
        // Валидация ID
        $rules = ['id' => 'required|int|exists:formats,id'];
        if (!$this->validate(['id' => $id], $rules)) {
            return;
        }
        
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
        
        // Валидация параметров пагинации
        if ($page < 1) $page = 1;
        if ($perPage < 1) $perPage = 20;
        if ($perPage > 100) $perPage = 100;
        
        $result = $this->formatModel->getPaginated($page, $perPage);
        $this->success($result);
    }
}