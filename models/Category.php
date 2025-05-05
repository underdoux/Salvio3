<?php
/**
 * Category Model
 * Handles category-related database operations
 */
class Category extends Model {
    protected $table = 'categories';
    protected $fillable = [
        'name',
        'description',
        'parent_id',
        'status'
    ];

    /**
     * Get all categories with pagination
     * @param int $page Current page
     * @param int $limit Items per page
     * @param string $search Search term
     * @return array Categories data with pagination
     */
    public function getAll($page = 1, $limit = 10, $search = '') {
        $offset = ($page - 1) * $limit;
        $searchTerm = null;

        // Get total count
        if (!empty($search)) {
            $searchTerm = "%{$search}%";
            $countSql = "SELECT COUNT(*) as total FROM {$this->table} c WHERE c.status = 'active' AND c.name LIKE ?";
            $countQuery = $this->getDb()->query($countSql);
            $countQuery->bind(1, $searchTerm);
        } else {
            $countSql = "SELECT COUNT(*) as total FROM {$this->table} c WHERE c.status = 'active'";
            $countQuery = $this->getDb()->query($countSql);
        }
        $total = $countQuery->single()['total'];

        // Get paginated data
        $sql = "SELECT c.*, p.name as parent_name, 
            (SELECT COUNT(*) FROM products pr WHERE pr.category_id = c.id AND pr.status = 'active') as product_count 
            FROM {$this->table} c 
            LEFT JOIN {$this->table} p ON c.parent_id = p.id
            WHERE c.status = 'active'";

        if (!empty($search)) {
            $sql .= " AND c.name LIKE ?";
        }

        $sql .= " ORDER BY c.name ASC LIMIT ? OFFSET ?";

        $query = $this->getDb()->query($sql);

        if (!empty($search)) {
            $query->bind(1, $searchTerm);
            $query->bind(2, $limit);
            $query->bind(3, $offset);
        } else {
            $query->bind(1, $limit);
            $query->bind(2, $offset);
        }

        return [
            'data' => $query->resultSet(),
            'total' => $total,
            'page' => $page,
            'last_page' => ceil($total / $limit)
        ];
    }

    /**
     * Get category by ID
     * @param int $id Category ID
     * @return array|null
     */
    public function getById($id) {
        return $this->getDb()->query("
            SELECT c.* FROM {$this->table} c
            WHERE c.id = ? AND c.status = 'active'
        ")
        ->bind(1, $id)
        ->single();
    }

    /**
     * Get active categories
     * @return array
     */
    public function getActive() {
        return $this->getDb()->query("
            SELECT c.* FROM {$this->table} c
            WHERE c.status = 'active'
            ORDER BY c.name ASC
        ")->resultSet();
    }

    /**
     * Get category tree
     * @param int|null $parentId
     * @return array
     */
    public function getTree($parentId = null) {
        $sql = "
            WITH RECURSIVE category_tree AS (
                -- Base case: parent categories
                SELECT 
                    c.id,
                    c.name,
                    c.parent_id,
                    0 as level,
                    CAST(c.name AS CHAR(1000)) as path
                FROM {$this->table} c
                WHERE c.parent_id " . ($parentId === null ? "IS NULL" : "= ?") . "
                AND c.status = 'active'
                
                UNION ALL
                
                -- Recursive case: child categories
                SELECT 
                    c.id,
                    c.name,
                    c.parent_id,
                    ct.level + 1,
                    CONCAT(ct.path, ' > ', c.name)
                FROM {$this->table} c
                INNER JOIN category_tree ct ON c.parent_id = ct.id
                WHERE c.status = 'active'
            )
            SELECT ct.* FROM category_tree ct
            ORDER BY path
        ";

        $query = $this->getDb()->query($sql);
        if ($parentId !== null) {
            $query->bind(1, $parentId);
        }
        return $query->resultSet();
    }

    /**
     * Get category with product count
     * @return array
     */
    public function getWithProductCount() {
        return $this->getDb()->query("
            SELECT 
                c.*,
                COUNT(p.id) as product_count
            FROM {$this->table} c
            LEFT JOIN products p ON c.id = p.category_id AND p.status = 'active'
            WHERE c.status = 'active'
            GROUP BY c.id
            ORDER BY c.name ASC
        ")->resultSet();
    }

    /**
     * Get category by ID with products
     * @param int $id Category ID
     * @param int $limit Product limit
     * @param int $offset Product offset
     * @return array|null
     */
    public function getWithProducts($id, $limit = 10, $offset = 0) {
        // Get category details
        $category = $this->getDb()->query("
            SELECT c.* FROM {$this->table} c
            WHERE c.id = ? AND c.status = 'active'
        ")
        ->bind(1, $id)
        ->single();

        if (!$category) {
            return null;
        }

        // Get category products
        $products = $this->getDb()->query("
            SELECT p.*
            FROM products p
            WHERE p.category_id = ?
            AND p.status = 'active'
            ORDER BY p.name ASC
            LIMIT ? OFFSET ?
        ")
        ->bind(1, $id)
        ->bind(2, $limit)
        ->bind(3, $offset)
        ->resultSet();

        // Get total product count
        $total = $this->getDb()->query("
            SELECT COUNT(*) as total
            FROM products p
            WHERE p.category_id = ?
            AND p.status = 'active'
        ")
        ->bind(1, $id)
        ->single();

        return [
            'category' => $category,
            'products' => $products,
            'total' => (int)$total['total']
        ];
    }

    /**
     * Check if category has children
     * @param int $id Category ID
     * @return bool
     */
    public function hasChildren($id) {
        $result = $this->getDb()->query("
            SELECT COUNT(*) as count
            FROM {$this->table} c
            WHERE c.parent_id = ?
            AND c.status = 'active'
        ")
        ->bind(1, $id)
        ->single();

        return (int)$result['count'] > 0;
    }

    /**
     * Check if category has products
     * @param int $id Category ID
     * @return bool
     */
    public function hasProducts($id) {
        $result = $this->getDb()->query("
            SELECT COUNT(*) as count
            FROM products p
            WHERE p.category_id = ?
            AND p.status = 'active'
        ")
        ->bind(1, $id)
        ->single();

        return (int)$result['count'] > 0;
    }

    /**
     * Get category breadcrumb
     * @param int $id Category ID
     * @return array
     */
    public function getBreadcrumb($id) {
        return $this->getDb()->query("
            WITH RECURSIVE category_path AS (
                -- Base case: start with the target category
                SELECT 
                    c.id,
                    c.name,
                    c.parent_id,
                    1 as level
                FROM {$this->table} c
                WHERE c.id = ?
                AND c.status = 'active'
                
                UNION ALL
                
                -- Recursive case: add parent categories
                SELECT 
                    c.id,
                    c.name,
                    c.parent_id,
                    cp.level + 1
                FROM {$this->table} c
                INNER JOIN category_path cp ON c.id = cp.parent_id
                WHERE c.status = 'active'
            )
            SELECT cp.* FROM category_path cp
            ORDER BY level DESC
        ")
        ->bind(1, $id)
        ->resultSet();
    }

    /**
     * Create category
     * @param array $data Category data
     * @return int|false The new category ID or false on failure
     */
    public function create($data) {
        $sql = "INSERT INTO {$this->table} (name, description, parent_id, status) VALUES (?, ?, ?, ?)";
        
        $this->getDb()->query($sql)
            ->bind(1, $data['name'])
            ->bind(2, $data['description'] ?? null)
            ->bind(3, $data['parent_id'] ?? null)
            ->bind(4, $data['status'] ?? 'active')
            ->execute();
            
        return $this->getDb()->lastInsertId();
    }

    /**
     * Update category
     * @param int $id Category ID
     * @param array $data Category data
     * @return bool Success status
     */
    public function update($id, $data) {
        $updates = [];
        $params = [];
        
        foreach ($this->fillable as $field) {
            if (isset($data[$field])) {
                $updates[] = "{$field} = ?";
                $params[] = $data[$field];
            }
        }
        
        if (empty($updates)) {
            return false;
        }

        $params[] = $id;
        $sql = "UPDATE {$this->table} c SET " . implode(', ', $updates) . " WHERE c.id = ?";
        
        $query = $this->getDb()->query($sql);
        for ($i = 0; $i < count($params); $i++) {
            $query->bind($i + 1, $params[$i]);
        }
        
        return $query->execute();
    }

    /**
     * Delete category (soft delete)
     * @param int $id Category ID
     * @return bool Success status
     */
    public function delete($id) {
        return $this->getDb()->query("UPDATE {$this->table} c SET c.status = 'inactive' WHERE c.id = ? AND c.status = 'active'")
            ->bind(1, $id)
            ->execute();
    }

    /**
     * Get products for a category with pagination
     * @param int $id Category ID
     * @param int $page Current page
     * @param int $limit Items per page
     * @return array Products data with pagination
     */
    public function getProducts($id, $page = 1, $limit = 10) {
        $offset = ($page - 1) * $limit;

        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM products p WHERE p.category_id = ? AND p.status = 'active'";
        $countQuery = $this->getDb()->query($countSql)->bind(1, $id);
        $total = $countQuery->single()['total'];

        // Get paginated data
        $sql = "SELECT p.* FROM products p WHERE p.category_id = ? AND p.status = 'active' ORDER BY p.name ASC LIMIT ? OFFSET ?";
        $query = $this->getDb()->query($sql)
            ->bind(1, $id)
            ->bind(2, $limit)
            ->bind(3, $offset);

        return [
            'data' => $query->resultSet(),
            'total' => $total,
            'page' => $page,
            'last_page' => ceil($total / $limit)
        ];
    }

    /**
     * Get parent categories for dropdown
     * @param int|null $excludeId Category ID to exclude (for edit form)
     * @return array List of potential parent categories
     */
    public function getParentCategories($excludeId = null) {
        $sql = "
            WITH RECURSIVE category_tree AS (
                -- Base case: root categories
                SELECT 
                    c.id,
                    c.name,
                    c.parent_id,
                    0 as level,
                    CAST(c.name AS CHAR(1000)) as path
                FROM {$this->table} c
                WHERE c.parent_id IS NULL
                AND c.status = 'active'
                " . ($excludeId ? "AND c.id != ?" : "") . "
                
                UNION ALL
                
                -- Recursive case: child categories
                SELECT 
                    c.id,
                    c.name,
                    c.parent_id,
                    ct.level + 1,
                    CONCAT(ct.path, ' > ', c.name)
                FROM {$this->table} c
                INNER JOIN category_tree ct ON c.parent_id = ct.id
                WHERE c.status = 'active'
                " . ($excludeId ? "AND c.id != ?" : "") . "
            )
            SELECT 
                ct.id,
                ct.path as name,
                ct.level
            FROM category_tree ct
            ORDER BY path
        ";

        $query = $this->getDb()->query($sql);
        if ($excludeId) {
            $query->bind(1, $excludeId);
            $query->bind(2, $excludeId);
        }
        return $query->resultSet();
    }
}
