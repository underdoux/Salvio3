<?php
/**
 * Notifications Controller
 * Handles notification management and delivery
 */
class NotificationsController extends Controller {
    private $notificationHandler;

    public function __construct() {
        parent::__construct();
        $this->requireAuth();
        $this->notificationHandler = NotificationHandler::getInstance();
    }

    /**
     * Get unread notifications (AJAX)
     */
    public function unread() {
        if (!$this->isAjax()) {
            $this->error(400, 'Invalid request');
        }

        $notifications = $this->notificationHandler->getUnread(
            $this->auth->user()['id'],
            10
        );

        $this->json([
            'count' => count($notifications),
            'notifications' => array_map(function($notification) {
                return [
                    'id' => $notification['id'],
                    'title' => $notification['title'],
                    'message' => $notification['message'],
                    'icon' => $notification['icon'],
                    'color' => $notification['color'],
                    'url' => $notification['url'] ?? null,
                    'time_ago' => $this->timeAgo($notification['created_at'])
                ];
            }, $notifications)
        ]);
    }

    /**
     * Mark notification as read (AJAX)
     */
    public function markAsRead($id = null) {
        if (!$this->isAjax() || !$id) {
            $this->error(400, 'Invalid request');
        }

        $success = $this->notificationHandler->markAsRead(
            $id,
            $this->auth->user()['id']
        );

        $this->json(['success' => $success]);
    }

    /**
     * Mark all notifications as read (AJAX)
     */
    public function markAllAsRead() {
        if (!$this->isAjax()) {
            $this->error(400, 'Invalid request');
        }

        $success = $this->notificationHandler->markAllAsRead(
            $this->auth->user()['id']
        );

        $this->json(['success' => $success]);
    }

    /**
     * Show notification preferences
     */
    public function preferences() {
        $preferences = $this->notificationHandler->getPreferences(
            $this->auth->user()['id']
        );

        $this->view->render('notifications/preferences', [
            'title' => 'Notification Preferences - ' . APP_NAME,
            'preferences' => $preferences,
            'templates' => require CONFIG_PATH . '/notifications.php'
        ]);
    }

    /**
     * Update notification preferences
     */
    public function updatePreferences() {
        if (!$this->isPost()) {
            $this->redirect('notifications/preferences');
        }

        $preferences = [
            'email' => $this->getPost('email') === 'on',
            'browser' => $this->getPost('browser') === 'on',
            'types' => $this->getPost('types', [])
        ];

        $this->notificationHandler->updatePreferences(
            $this->auth->user()['id'],
            $preferences
        );

        $this->setFlash('success', 'Notification preferences updated successfully');
        $this->redirect('notifications/preferences');
    }

    /**
     * Show all notifications
     */
    public function index() {
        $page = $this->getQuery('page', 1);
        $type = $this->getQuery('type');
        $status = $this->getQuery('status');

        $notifications = $this->db->query("
            SELECT n.*, bn.read_at
            FROM notifications n
            INNER JOIN browser_notifications bn ON bn.notification_id = n.id
            WHERE bn.user_id = ?
            " . ($type ? "AND n.type = ?" : "") . "
            " . ($status === 'unread' ? "AND bn.read_at IS NULL" : "") . "
            ORDER BY n.created_at DESC
            LIMIT ? OFFSET ?
        ");

        $notifications->bind(1, $this->auth->user()['id']);
        $paramIndex = 2;

        if ($type) {
            $notifications->bind($paramIndex++, $type);
        }

        $limit = 20;
        $offset = ($page - 1) * $limit;

        $notifications->bind($paramIndex++, $limit);
        $notifications->bind($paramIndex, $offset);

        $this->view->render('notifications/index', [
            'title' => 'Notifications - ' . APP_NAME,
            'notifications' => $notifications->resultSet(),
            'type' => $type,
            'status' => $status,
            'page' => $page
        ]);
    }

    /**
     * Test notification (Admin only)
     */
    public function test() {
        $this->requireAdmin();

        if (!$this->isPost()) {
            $this->view->render('notifications/test', [
                'title' => 'Test Notification - ' . APP_NAME,
                'templates' => require CONFIG_PATH . '/notifications.php'
            ]);
            return;
        }

        $type = $this->getPost('type');
        $recipients = explode(',', $this->getPost('recipients'));
        $data = json_decode($this->getPost('data'), true) ?: [];

        $this->notificationHandler->send($type, $data, $recipients);

        $this->setFlash('success', 'Test notification sent successfully');
        $this->redirect('notifications/test');
    }

    /**
     * Format time ago
     */
    private function timeAgo($datetime) {
        $time = strtotime($datetime);
        $now = time();
        $diff = $now - $time;

        if ($diff < 60) {
            return 'just now';
        } elseif ($diff < 3600) {
            $mins = floor($diff / 60);
            return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 604800) {
            $days = floor($diff / 86400);
            return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 2592000) {
            $weeks = floor($diff / 604800);
            return $weeks . ' week' . ($weeks > 1 ? 's' : '') . ' ago';
        } else {
            return date('M j, Y', $time);
        }
    }
}
