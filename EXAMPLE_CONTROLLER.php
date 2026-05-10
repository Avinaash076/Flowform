<?php
/**
 * EXAMPLE CONTROLLER
 * 
 * This is a complete example showing all patterns:
 * - Session checks
 * - Data retrieval
 * - View rendering
 * - Flash messages
 * - Redirects
 * - JSON responses
 * 
 * Copy this as a template for new controllers!
 */

class ExampleController extends BaseController {
    
    /**
     * List all items - Protected page, admin only
     * Usage: http://localhost/flowform/items
     */
    public function list() {
        // ✓ ONE LINE SESSION CHECK - Admin only
        $this->checkAdminAccess();
        
        // Now we're safe - user is admin and logged in
        
        // Get data from model
        $items = $this->getAllItems();
        
        // Pass data to view
        $this->set('items', $items);
        $this->set('title', 'Items List');
        
        // Render view (automatically finds app/views/admin/list.php)
        $this->render('list');
    }
    
    /**
     * View single item - Protected page, anyone logged in
     * Usage: http://localhost/flowform/item-view?id=1
     */
    public function view() {
        // ✓ ONE LINE SESSION CHECK - Any logged-in user
        $this->checkAccess();
        
        $id = $_GET['id'] ?? null;
        
        if (!$id) {
            $this->jsonError('Item ID required', 400);
        }
        
        $item = $this->getItem($id);
        
        if (!$item) {
            http_response_code(404);
            $this->set('code', 404);
            $this->set('message', 'Item not found');
            $this->render('error');
            return;
        }
        
        $this->set('item', $item);
        $this->render('view');
    }
    
    /**
     * Create item form - Shows empty form
     * Usage: http://localhost/flowform/item-create
     */
    public function create() {
        $this->checkAccess();
        
        $this->set('title', 'Create New Item');
        $this->render('form');  // Renders: app/views/admin/form.php
    }
    
    /**
     * Store item (handle form submission)
     * Usage: POST to http://localhost/flowform/item-store
     */
    public function store() {
        $this->checkAccess();
        
        // Only allow POST requests
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/item-create');
        }
        
        // ✓ CSRF PROTECTION - Verify token
        if (!isset($_POST['csrf_token']) || 
            !verifyCsrfToken($_POST['csrf_token'])) {
            
            setFlashMessage('Invalid security token. Please try again.', 'error');
            $this->redirect('/item-create');
        }
        
        // Get form data
        $name = $_POST['name'] ?? '';
        $description = $_POST['description'] ?? '';
        
        // Validate
        if (empty($name)) {
            setFlashMessage('Name is required', 'error');
            $this->redirect('/item-create');
        }
        
        // Save to database
        $id = $this->saveItem($name, $description);
        
        if ($id) {
            // ✓ FLASH MESSAGE - One-time notification
            setFlashMessage('Item created successfully!', 'success');
            // ✓ REDIRECT
            $this->redirect('/item-view?id=' . $id);
        } else {
            setFlashMessage('Error creating item', 'error');
            $this->redirect('/item-create');
        }
    }
    
    /**
     * Edit item form - Shows filled form
     * Usage: http://localhost/flowform/item-edit?id=1
     */
    public function edit() {
        $this->checkAccess();
        
        $id = $_GET['id'] ?? null;
        if (!$id) {
            $this->redirect('/items');
        }
        
        $item = $this->getItem($id);
        if (!$item) {
            setFlashMessage('Item not found', 'error');
            $this->redirect('/items');
        }
        
        $this->set('item', $item);
        $this->set('title', 'Edit Item');
        $this->render('form');
    }
    
    /**
     * Update item (handle form submission)
     * Usage: POST to http://localhost/flowform/item-update
     */
    public function update() {
        $this->checkAccess();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/items');
        }
        
        // CSRF check
        if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->jsonError('Invalid token', 403);
        }
        
        $id = $_POST['id'] ?? null;
        $name = $_POST['name'] ?? '';
        
        if (!$id || empty($name)) {
            $this->jsonError('Missing required fields', 400);
        }
        
        if ($this->updateItem($id, $name, $_POST['description'] ?? '')) {
            setFlashMessage('Item updated successfully!', 'success');
            $this->jsonSuccess(['id' => $id]);
        } else {
            $this->jsonError('Failed to update item', 500);
        }
    }
    
    /**
     * Delete item
     * Usage: AJAX DELETE request
     */
    public function delete() {
        $this->checkAccess();
        
        $id = $_GET['id'] ?? null;
        if (!$id) {
            $this->jsonError('Item ID required', 400);
        }
        
        // Check CSRF via header (from AJAX)
        $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!verifyCsrfToken($csrfToken)) {
            $this->jsonError('Invalid token', 403);
        }
        
        if ($this->deleteItem($id)) {
            $this->jsonSuccess(['message' => 'Item deleted']);
        } else {
            $this->jsonError('Failed to delete', 500);
        }
    }
    
    /**
     * API endpoint - JSON response for AJAX
     * Usage: AJAX GET /flowform/api?action=list
     */
    public function api() {
        $this->checkAccess();
        
        $action = $_GET['action'] ?? null;
        
        switch ($action) {
            case 'list':
                $items = $this->getAllItems();
                $this->jsonSuccess(['items' => $items]);
                break;
            
            case 'search':
                $query = $_GET['q'] ?? '';
                $items = $this->searchItems($query);
                $this->jsonSuccess(['items' => $items]);
                break;
            
            case 'save':
                if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                    $this->jsonError('POST required', 405);
                }
                
                // Get CSRF token from header (AJAX sends it)
                $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
                if (!verifyCsrfToken($token)) {
                    $this->jsonError('Invalid token', 403);
                }
                
                $data = json_decode(file_get_contents('php://input'), true);
                $id = $this->saveItem($data['name'] ?? '', $data['description'] ?? '');
                
                if ($id) {
                    $this->jsonSuccess(['id' => $id, 'message' => 'Saved']);
                } else {
                    $this->jsonError('Save failed', 500);
                }
                break;
            
            default:
                $this->jsonError('Unknown action', 400);
        }
    }
    
    /**
     * Public method (no session check) - For demo only
     * Usage: http://localhost/flowform/public-info
     */
    public function publicInfo() {
        // NO checkAccess() - anyone can access
        
        $info = [
            'app' => APP_NAME,
            'version' => APP_VERSION
        ];
        
        $this->jsonSuccess($info);
    }
    
    // ========== HELPER METHODS (Database calls, business logic) ==========
    
    private function getAllItems() {
        // TODO: Replace with actual database query
        return [
            ['id' => 1, 'name' => 'Item 1', 'description' => 'Desc 1'],
            ['id' => 2, 'name' => 'Item 2', 'description' => 'Desc 2'],
        ];
    }
    
    private function getItem($id) {
        // TODO: Replace with actual database query
        return ['id' => $id, 'name' => 'Sample Item', 'description' => 'Sample'];
    }
    
    private function saveItem($name, $description) {
        // TODO: Replace with actual database insert
        // Use prepared statements!
        return rand(1, 1000);
    }
    
    private function updateItem($id, $name, $description) {
        // TODO: Replace with actual database update
        // Use prepared statements!
        return true;
    }
    
    private function deleteItem($id) {
        // TODO: Replace with actual database delete
        // Use prepared statements!
        return true;
    }
    
    private function searchItems($query) {
        // TODO: Replace with actual database search
        // Use prepared statements with LIKE clause!
        return [];
    }
}

/**
 * =========================================================================
 * USAGE EXAMPLES FOR VIEW FILES
 * =========================================================================
 * 
 * In your views (app/views/admin/list.php):
 * 
 * <!-- Display items -->
 * <?php foreach ($items as $item): ?>
 *     <div class="item">
 *         <h3><?php echo htmlspecialchars($item['name']); ?></h3>
 *         <p><?php echo htmlspecialchars($item['description']); ?></p>
 *         <a href="<?php echo APP_URL; ?>/item-view?id=<?php echo $item['id']; ?>">View</a>
 *         <a href="<?php echo APP_URL; ?>/item-edit?id=<?php echo $item['id']; ?>">Edit</a>
 *     </div>
 * <?php endforeach; ?>
 * 
 * <!-- Form with CSRF -->
 * <form method="POST" action="<?php echo APP_URL; ?>/item-store">
 *     <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
 *     <input type="text" name="name" required>
 *     <textarea name="description"></textarea>
 *     <button>Save</button>
 * </form>
 * 
 * =========================================================================
 * ROUTING IN index.php
 * =========================================================================
 * 
 * Add to $routes array:
 * 
 * 'items' => ['controller' => 'ExampleController', 'action' => 'list'],
 * 'item-view' => ['controller' => 'ExampleController', 'action' => 'view'],
 * 'item-create' => ['controller' => 'ExampleController', 'action' => 'create'],
 * 'item-store' => ['controller' => 'ExampleController', 'action' => 'store'],
 * 'item-edit' => ['controller' => 'ExampleController', 'action' => 'edit'],
 * 'item-update' => ['controller' => 'ExampleController', 'action' => 'update'],
 * 'item-delete' => ['controller' => 'ExampleController', 'action' => 'delete'],
 * 
 * =========================================================================
 */
