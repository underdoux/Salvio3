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
        $where = "WHERE status = 'active'";
        
        if (!empty($search)) {
            $where .= " AND name LIKE ?";
            $searchTerm = "%{$search}%";
        }

        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM {$this->table} {$where}";
        $countQuery = $this->db->query($countSql);
        if (!empty($search)) {
            $countQuery->bind(1, $searchTerm);
        }
        $total = $countQuery->single()['total'];

        // Get paginated data
        $sql = sprintf(
            "SELECT c.*, p.name as parent_name, 
            (SELECT COUNT(*) FROM products WHERE category_id = c.id AND status = 'active') as product_count 
            FROM %s c 
            LEFT JOIN %s p ON c.parent_id = p.id 
            %s 
            ORDER BY c.name ASC 
            LIMIT ? OFFSET ?",
            $this->table,
            $this->table,
            $where
        );

        $query = $this->db->query($sql);
        $paramIndex = 1;
        
        if (!empty($search)) {
            $query->bind($paramIndex++, $searchTerm);
        }
        
        $query->bind($paramIndex++, $limit);
        $query->bind($paramIndex, $offset);

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
        return $this->db->query("
            SELECT * FROM {$this->table}
            WHERE id = ? AND status = 'active'
        ")
        ->bind(1, $id)
        ->single();
    }

    /**
     * Get active categories
     * @return array
     */
    public function getActive() {
        return $this->db->query("
            SELECT * FROM {$this->table}
            WHERE status = 'active'
            ORDER BY name ASC
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
                    id,
                    name,
                    parent_id,
                    0 as level,
                    CAST(name AS CHAR(1000)) as path
                FROM {$this->table}
                WHERE parent_id " . ($parentId === null ? "IS NULL" : "= ?") . "
                AND status = 'active'
                
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
            SELECT * FROM category_tree
            ORDER BY path
        ";

        $query = $this->db->query($sql);
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
        return $this->db->query("
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
        $category = $this->db->query("
            SELECT * FROM {$this->table}
            WHERE id = ? AND status = 'active'
        ")
        ->bind(1, $id)
        ->single();

        if (!$category) {
            return null;
        }

        // Get category products
        $products = $this->db->query("
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
        $total = $this->db->query("
            SELECT COUNT(*) as total
            FROM products
            WHERE category_id = ?
            AND status = 'active'
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
        $result = $this->db->query("
            SELECT COUNT(*) as count
            FROM {$this->table}
            WHERE parent_id = ?
            AND status = 'active'
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
        $result = $this->db->query("
            SELECT COUNT(*) as count
            FROM products
            WHERE category_id = ?
            AND status = 'active'
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
        return $this->db->query("
            WITH RECURSIVE category_path AS (
                -- Base case: start with the target category
                SELECT 
                    id,
                    name,
                    parent_id,
                    1 as level
                FROM {$this->table}
                WHERE id = ?
                AND status = 'active'
                
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
            SELECT * FROM category_path
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
        
        $this->db->query($sql)
            ->bind(1, $data['name'])
            ->bind(2, $data['description'] ?? null)
            ->bind(3, $data['parent_id'] ?? null)
            ->bind(4, $data['status'] ?? 'active')
            ->execute();
            
        return $this->db->lastInsertId();
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
        $sql = "UPDATE {$this->table} SET " . implode(', ', $updates) . " WHERE id = ?";
        
        $query = $this->db->query($sql);
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
        return $this->db->query("UPDATE {$this->table} SET status = 'inactive' WHERE id = ?")
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
        $countSql = "SELECT COUNT(*) as total FROM products WHERE category_id = ? AND status = 'active'";
        $countQuery = $this->db->query($countSql)->bind(1, $id);
        $total = $countQuery->single()['total'];

        // Get paginated data
        $sql = "SELECT * FROM products WHERE category_id = ? AND status = 'active' ORDER BY name ASC LIMIT ? OFFSET ?";
        $query = $this->db->query($sql)
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
                    id,
                    name,
                    parent_id,
                    0 as level,
                    CAST(name AS CHAR(1000)) as path
                FROM {$this->table}
                WHERE parent_id IS NULL
                AND status = 'active'
                " . ($excludeId ? "AND id != ?" : "") . "
                
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
                id,
                path as name,
                level
            FROM category_tree
            ORDER BY path
        ";

        $query = $this->db->query($sql);
        if ($excludeId) {
            $query->bind(1, $excludeId);
            $query->bind(2, $excludeId);
        }
        return $query->resultSet();
    }
}
