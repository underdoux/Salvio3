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
     */
    public function getAll($page = 1, $limit = 10, $search = '') {
        $offset = ($page - 1) * $limit;
        $searchTerm = null;

        // Get total count with qualified column names
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

        // Get paginated data with qualified column names
        $sql = "SELECT c.*, p.name as parent_name, 
            (SELECT COUNT(*) FROM products pr WHERE pr.category_id = c.id AND pr.status = 'active') as product_count 
            FROM {$this->table} c 
            LEFT JOIN {$this->table} p ON c.parent_id = p.id AND p.status = 'active'
            LEFT JOIN products pr ON pr.category_id = c.id AND pr.status = 'active'
            WHERE c.status = 'active'
            GROUP BY c.id, c.name, c.description, c.parent_id, c.status";

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
     */
    public function getTree($parentId = null) {
        $sql = "
            WITH RECURSIVE category_tree AS (
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
                
                SELECT 
                    child.id,
                    child.name,
                    child.parent_id,
                    ct.level + 1,
                    CONCAT(ct.path, ' > ', child.name)
                FROM {$this->table} child
                INNER JOIN category_tree ct ON child.parent_id = ct.id
                WHERE child.status = 'active'
            )
            SELECT ct.* FROM category_tree ct
            ORDER BY ct.path
        ";

        $query = $this->getDb()->query($sql);
        if ($parentId !== null) {
            $query->bind(1, $parentId);
        }
        return $query->resultSet();
    }

    /**
     * Get category with product count
     */
    public function getWithProductCount() {
        return $this->getDb()->query("
            SELECT 
                c.*,
                COUNT(p.id) as product_count
            FROM {$this->table} c
            LEFT JOIN products p ON c.id = p.category_id AND p.status = 'active'
            WHERE c.status = 'active'
            GROUP BY c.id, c.name, c.description, c.parent_id, c.status
            ORDER BY c.name ASC
        ")->resultSet();
    }

    /**
     * Get category by ID with products
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

        // Get category products with qualified column names
        $products = $this->getDb()->query("
            SELECT p.*, c.name as category_name
            FROM products p
            INNER JOIN {$this->table} c ON p.category_id = c.id AND c.status = 'active'
            WHERE p.category_id = ? AND p.status = 'active'
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
            WHERE p.category_id = ? AND p.status = 'active'
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
     */
    public function hasChildren($id) {
        $result = $this->getDb()->query("
            SELECT COUNT(*) as count
            FROM {$this->table} c
            WHERE c.parent_id = ? AND c.status = 'active'
        ")
        ->bind(1, $id)
        ->single();

        return (int)$result['count'] > 0;
    }

    /**
     * Check if category has products
     */
    public function hasProducts($id) {
        $result = $this->getDb()->query("
            SELECT COUNT(*) as count
            FROM products p
            WHERE p.category_id = ? AND p.status = 'active'
        ")
        ->bind(1, $id)
        ->single();

        return (int)$result['count'] > 0;
    }

    /**
     * Get category breadcrumb
     */
    public function getBreadcrumb($id) {
        return $this->getDb()->query("
            WITH RECURSIVE category_path AS (
                SELECT 
                    c.id,
                    c.name,
                    c.parent_id,
                    1 as level
                FROM {$this->table} c
                WHERE c.id = ? AND c.status = 'active'
                
                UNION ALL
                
                SELECT 
                    parent.id,
                    parent.name,
                    parent.parent_id,
                    cp.level + 1
                FROM {$this->table} parent
                INNER JOIN category_path cp ON parent.id = cp.parent_id
                WHERE parent.status = 'active'
            )
            SELECT cp.* FROM category_path cp
            ORDER BY cp.level DESC
        ")
        ->bind(1, $id)
        ->resultSet();
    }

    /**
     * Create category
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
        
        $query = $this->getDb()->query($sql);
        for ($i = 0; $i < count($params); $i++) {
            $query->bind($i + 1, $params[$i]);
        }
        
        return $query->execute();
    }

    /**
     * Delete category (soft delete)
     */
    public function delete($id) {
        return $this->getDb()->query("UPDATE {$this->table} SET status = 'inactive' WHERE id = ? AND status = 'active'")
            ->bind(1, $id)
            ->execute();
    }

    /**
     * Get products for a category with pagination
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
     */
    public function getParentCategories($excludeId = null) {
        $sql = "
            WITH RECURSIVE category_tree AS (
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
                
                SELECT 
                    child.id,
                    child.name,
                    child.parent_id,
                    ct.level + 1,
                    CONCAT(ct.path, ' > ', child.name)
                FROM {$this->table} child
                INNER JOIN category_tree ct ON child.parent_id = ct.id
                WHERE child.status = 'active'
                " . ($excludeId ? "AND child.id != ?" : "") . "
            )
            SELECT 
                ct.id,
                ct.path as name,
                ct.level
            FROM category_tree ct
            ORDER BY ct.path
        ";

        $query = $this->getDb()->query($sql);
        if ($excludeId) {
            $query->bind(1, $excludeId);
            $query->bind(2, $excludeId);
        }
        return $query->resultSet();
    }
}
