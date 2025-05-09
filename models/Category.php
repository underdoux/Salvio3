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
        'status'
    ];

    /**
     * Search categories
     * @param array $fields
     * @param string $keyword
     * @param string|null $orderBy
     * @param string $order
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function search($fields, $keyword, $orderBy = null, $order = 'ASC', $limit = 10, $offset = 0) {
        $conditions = [];
        $params = [];
        
        foreach ($fields as $field) {
            $conditions[] = "{$field} LIKE ?";
            $params[] = "%{$keyword}%";
        }
        
        $sql = "
            SELECT * FROM {$this->table}
            WHERE (" . implode(' OR ', $conditions) . ")
            AND status = 'active'
            LIMIT ? OFFSET ?
        ";
        
        $query = $this->db->query($sql);
        
        foreach ($params as $i => $param) {
            $query->bind($i + 1, $param);
        }
        $query->bind(count($params) + 1, $limit);
        $query->bind(count($params) + 2, $offset);
        
        return $query->resultSet();
    }

    /**
     * Soft delete category
     * @param int $id
     * @return bool
     */
    public function softDelete($id) {
        return $this->db->query("UPDATE {$this->table} SET status = 'inactive' WHERE id = ?")
                       ->bind(1, $id)
                       ->execute();
    }
}
