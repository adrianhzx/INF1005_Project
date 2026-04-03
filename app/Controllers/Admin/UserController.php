<?php
namespace App\Controllers\Admin;
use App\Controllers\BaseController;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class UserController extends BaseController {

    public function index(Request $request, Response $response): Response {
        global $pdo;

        $users = [];
        $active_sessions = [];
        $all_reviews = [];
        $errors = [];

        // --- Query 1: Users with stats (use subqueries to avoid GROUP BY issues) ---
        try {
            $stmt = $pdo->query(
                'SELECT u.id, u.email, u.status, u.verified, u.roles_mask, u.registered, u.last_login,
                        up.first_name, up.last_name, up.phone,
                        (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id) AS order_count,
                        (SELECT COALESCE(SUM(o2.total), 0) FROM orders o2 WHERE o2.user_id = u.id) AS total_spent,
                        (SELECT COUNT(*) FROM reviews rv WHERE rv.user_id = u.id) AS review_count
                 FROM users u
                 LEFT JOIN user_profiles up ON u.id = up.user_id
                 ORDER BY u.registered'
            );
            $users = $stmt->fetchAll();
        } catch (\Throwable $e) {
            ekea_log_exception($e, 'Admin users query failed');
            $errors[] = 'Failed to load users: ' . $e->getMessage();
        }

        // --- Query 2: Active sessions ---
        try {
            $sess_stmt = $pdo->query('SELECT user_id, ip_address, last_active, user_agent FROM user_sessions');
            $sessions_raw = $sess_stmt->fetchAll();
            foreach ($sessions_raw as $s) {
                $active_sessions[$s['user_id']] = $s;
            }
        } catch (\Throwable $e) {
            ekea_log_exception($e, 'Admin sessions query failed');
        }

        // --- Query 3: Reviews for moderation (LEFT JOINs for safety) ---
        try {
            $stmt = $pdo->query(
                'SELECT r.id, r.user_id, r.product_id, r.rating, r.comment, r.created_at,
                        COALESCE(up.first_name, "Unknown") AS first_name,
                        COALESCE(up.last_name, "") AS last_name,
                        COALESCE(u.email, "unknown") AS email,
                        COALESCE(p.name, "Deleted Product") AS product_name
                 FROM reviews r
                 LEFT JOIN user_profiles up ON r.user_id = up.user_id
                 LEFT JOIN users u ON r.user_id = u.id
                 LEFT JOIN products p ON r.product_id = p.id
                 ORDER BY r.created_at DESC'
            );
            $all_reviews = $stmt->fetchAll();
        } catch (\Throwable $e) {
            ekea_log_exception($e, 'Admin reviews query failed');
            $errors[] = 'Failed to load reviews: ' . $e->getMessage();
        }

        $csrf_token = generate_csrf_token();
        $page_title = 'Manage Users';
        $current_page = 'admin';
        return $this->render($response, 'admin/users', compact('users', 'active_sessions', 'all_reviews', 'errors', 'page_title', 'current_page', 'csrf_token'));
    }

    public function update(Request $request, Response $response): Response {
        global $pdo, $auth;
        $data=$request->getParsedBody(); $errors=[];
        if (!validate_csrf_token($data['csrf_token']??'')) { $this->flash('Invalid request.','danger'); return $this->redirect($response, '/admin/users'); }

        // Update role
        if (isset($data['update_role'])) {
            $uid=(int)($data['user_id']??0); $new_role=($data['new_role']??'')==='admin'?'admin':'user';
            if ($uid===(int)$auth->getUserId()&&$new_role!=='admin') { $this->flash('You cannot remove your own admin privileges.','danger'); return $this->redirect($response, '/admin/users'); }
            try {
                if ($new_role==='admin') $auth->admin()->addRoleForUserById($uid,\Delight\Auth\Role::ADMIN);
                else $auth->admin()->removeRoleForUserById($uid,\Delight\Auth\Role::ADMIN);
                ekea_log('User role updated','INFO',['user_id'=>$uid,'new_role'=>$new_role]);
                $this->flash('User role updated successfully.','success');
            } catch (\Delight\Auth\UnknownIdException $e) { $this->flash('Unknown user ID.','danger'); }
        }

        // Delete user
        if (isset($data['delete_user'])) {
            $uid=(int)($data['user_id']??0);
            if ($uid===(int)$auth->getUserId()) { $this->flash('You cannot delete your own account.','danger'); return $this->redirect($response, '/admin/users'); }
            try { $auth->admin()->deleteUserById($uid); ekea_log('User deleted','WARNING',['user_id'=>$uid]); $this->flash('User account deleted.','success'); }
            catch (\Delight\Auth\UnknownIdException $e) { $this->flash('Unknown user ID.','danger'); }
        }

        // Force logout
        if (isset($data['force_logout'])) {
            $uid=(int)($data['user_id']??0);
            $pdo->prepare('DELETE FROM user_sessions WHERE user_id=:uid')->execute([':uid'=>$uid]);
            ekea_log('Admin force-logged out user','WARNING',['target_user_id'=>$uid,'admin_id'=>$auth->getUserId()]);
            $this->flash('User session terminated.','success');
        }

        // Delete review
        if (isset($data['delete_review'])) {
            $review_id=(int)($data['review_id']??0);
            $pdo->prepare('DELETE FROM reviews WHERE id=:id')->execute([':id'=>$review_id]);
            ekea_log('Review deleted by admin','INFO',['review_id'=>$review_id]);
            $this->flash('Review deleted successfully.','success');
        }

        return $this->redirect($response, '/admin/users');
    }
}
