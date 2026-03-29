<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ProfileController extends BaseController
{
    public function index(Request $request, Response $response): Response
    {
        global $pdo, $auth;

        $stmt = $pdo->prepare('SELECT * FROM user_profiles WHERE user_id = :uid');
        $stmt->execute([':uid' => $auth->getUserId()]);
        $user_profile = $stmt->fetch();

        $current_email = $auth->getEmail();
        $page_title    = 'My Profile';
        $current_page  = 'profile';
        $errors        = [];
        $csrf_token    = generate_csrf_token();

        return $this->render($response, 'auth/profile', compact(
            'user_profile',
            'current_email',
            'page_title',
            'current_page',
            'errors',
            'csrf_token'
        ));
    }

    public function update(Request $request, Response $response): Response
    {
        global $pdo, $auth;

        $data       = $request->getParsedBody();
        $errors     = [];

        $first_name = trim($data['first_name'] ?? '');
        $last_name  = trim($data['last_name'] ?? '');
        $phone      = trim($data['phone'] ?? '');
        $address    = trim($data['address'] ?? '');

        $old_password     = $data['old_password'] ?? '';
        $new_password     = $data['new_password'] ?? '';
        $confirm_password = $data['confirm_password'] ?? '';

        // 1. Basic Validation
        if (!validate_csrf_token($data['csrf_token'] ?? '')) {
            $errors[] = 'Invalid form submission.';
        }
        if (empty($first_name)) {
            $errors[] = 'First name is required.';
        }
        if (empty($last_name)) {
            $errors[] = 'Last name is required.';
        }
        if (!empty($phone) && !preg_match('/^\d{8,15}$/', $phone)) {
            $errors[] = 'Phone number must be 8-15 digits.';
        }

        // 2. Password Validation (Only run if no basic errors exist yet)
        if (empty($errors) && (!empty($new_password) || !empty($old_password))) {
            if (empty($old_password)) {
                $errors[] = 'Current password is required to set a new password.';
            } elseif (strlen($new_password) < 8) {
                $errors[] = 'New password must be at least 8 characters.';
            } elseif ($new_password !== $confirm_password) {
                $errors[] = 'New passwords do not match.';
            } else {
                // If password validation passes, attempt the actual change
                try {
                    $auth->changePassword($old_password, $new_password);
                    $this->flash('Password updated successfully. ', 'success');
                } catch (\Delight\Auth\InvalidPasswordException $e) {
                    $errors[] = 'Current password is incorrect.';
                } catch (\Exception $e) {
                    ekea_log_exception($e, 'Password change error');
                    $errors[] = 'An error occurred changing your password.';
                }
            }
        }

        // 3. Final Database Update (If everything is perfectly valid)
        if (empty($errors)) {
            $pdo->prepare('UPDATE user_profiles SET first_name=:fn, last_name=:ln, phone=:phone, address=:addr WHERE user_id=:uid')
                ->execute([
                    ':fn'    => $first_name,
                    ':ln'    => $last_name,
                    ':phone' => $phone,
                    ':addr'  => $address,
                    ':uid'   => $auth->getUserId()
                ]);

            ekea_log('Profile updated', 'INFO', ['user_id' => $auth->getUserId()]);

            // Append profile success message to flash (in case password also updated)
            $existing_flash = $_SESSION['flash_message'] ?? '';
            $this->flash($existing_flash . 'Profile information updated!', 'success');

            return $this->redirect($response, '/profile');
        }

        // 4. Re-render page if there were errors
        $stmt = $pdo->prepare('SELECT * FROM user_profiles WHERE user_id = :uid');
        $stmt->execute([':uid' => $auth->getUserId()]);
        $user_profile  = $stmt->fetch();

        $current_email = $auth->getEmail();
        $page_title    = 'My Profile';
        $current_page  = 'profile';
        $csrf_token    = generate_csrf_token();

        return $this->render($response, 'auth/profile', compact(
            'user_profile',
            'current_email',
            'page_title',
            'current_page',
            'errors',
            'csrf_token'
        ));
    }
}
