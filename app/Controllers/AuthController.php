<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use League\OAuth2\Client\Provider\Google;

class AuthController extends BaseController
{
    public function loginPage(Request $request, Response $response): Response
    {
        global $auth;
        if ($auth->isLoggedIn()) {
            return $this->redirect($response, '/');
        }
        $page_title = 'Login';
        $current_page = 'login';
        $errors = [];
        $old_email = '';
        $csrf_token = generate_csrf_token();
        return $this->render($response, 'auth/login', compact('page_title', 'current_page', 'errors', 'old_email', 'csrf_token'));
    }

    public function login(Request $request, Response $response): Response
    {
        global $auth;
        if ($auth->isLoggedIn()) {
            return $this->redirect($response, '/');
        }
        $data = $request->getParsedBody();
        $errors = [];
        $old_email = trim($data['email'] ?? '');
        if (!validate_csrf_token($data['csrf_token'] ?? '')) {
            ekea_log('Login CSRF failed', 'WARNING');
            $errors[] = 'Invalid form submission.';
        }
        if (empty($old_email)) {
            $errors[] = 'Email is required.';
        } elseif (!filter_var($old_email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }
        if (empty($data['password'] ?? '')) {
            $errors[] = 'Password is required.';
        }
        if (empty($errors)) {
            $rememberDuration = null;
            if (isset($data['remember']) && $data['remember'] == 1) {
                $rememberDuration = (int)(60 * 60 * 24 * 365.25);
            }
            try {
                $auth->login($old_email, $data['password'], $rememberDuration);
                start_user_session_record((int) $auth->getUserId());
                ekea_log('Login successful', 'INFO');

                unset($_SESSION['is_google_user']);
                return $this->redirect($response, '/');
            } catch (\Delight\Auth\InvalidEmailException $e) {
                $errors[] = 'Wrong email address.';
            } catch (\Delight\Auth\InvalidPasswordException $e) {
                $errors[] = 'Wrong password.';
            } catch (\Delight\Auth\EmailNotVerifiedException $e) {
                $errors[] = 'Please verify your email first.';
            } catch (\Delight\Auth\TooManyRequestsException $e) {
                $errors[] = 'Too many attempts. Please try again later.';
            } catch (\Exception $e) {
                ekea_log_exception($e, 'Login error');
                $errors[] = 'An unexpected error occurred.';
            }
        }
        $page_title = 'Login';
        $current_page = 'login';
        $csrf_token = generate_csrf_token();
        return $this->render($response, 'auth/login', compact('page_title', 'current_page', 'errors', 'old_email', 'csrf_token'));
    }

    public function registerPage(Request $request, Response $response): Response
    {
        global $auth;
        if ($auth->isLoggedIn()) {
            return $this->redirect($response, '/');
        }
        $page_title = 'Register';
        $current_page = 'register';
        $errors = $_SESSION['register_errors'] ?? [];
        $old    = $_SESSION['register_old']    ?? ['first_name' => '','last_name' => '','email' => '','phone' => ''];
        unset($_SESSION['register_errors'], $_SESSION['register_old']);
        $csrf_token = generate_csrf_token();
        return $this->render($response, 'auth/register', compact('page_title', 'current_page', 'errors', 'old', 'csrf_token'));
    }

    public function register(Request $request, Response $response): Response
    {
        global $auth, $pdo;
        if ($auth->isLoggedIn()) {
            return $this->redirect($response, '/');
        }
        $data = $request->getParsedBody();
        $errors = [];
        $first_name = trim($data['first_name'] ?? '');
        $last_name = trim($data['last_name'] ?? '');
        $email = trim($data['email'] ?? '');
        $phone = trim($data['phone'] ?? '');
        $password = $data['password'] ?? '';
        $confirm = $data['confirm_password'] ?? '';
        $old = compact('first_name', 'last_name', 'email', 'phone');
        if (!validate_csrf_token($data['csrf_token'] ?? '')) {
            $errors[] = 'Invalid form submission.';
        }
        if (empty($email)) {
            $errors[] = 'Email is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }
        if (empty($password)) {
            $errors[] = 'Password is required.';
        } elseif (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }
        if ($password !== $confirm) {
            $errors[] = 'Passwords do not match.';
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
        if (empty($errors)) {
            try {
                $userId = $auth->register($email, $password, null, function ($selector, $token) use ($email) {
                    $verify_url = 'http://ekea.duckdns.org/email-verification?selector='.urlencode($selector).'&token='.urlencode($token);
                    ekea_log("Verification URL: {$verify_url}", 'DEBUG');
                    sendVerificationEmail($email, $verify_url);
                });
                $pdo->prepare('INSERT INTO user_profiles (user_id, first_name, last_name, phone, auth_provider) VALUES (:uid, :fn, :ln, :phone, :provider)')
                    ->execute([
                        ':uid' => $userId,
                        ':fn' => $first_name,
                        ':ln' => $last_name,
                        ':phone' => $phone,
                        ':provider' => 'local' //initially DB have default value as local. but this just in case if something happens.
                    ]);
                ekea_log('User registered', 'INFO', ['email' => $email]);
                $this->flash('Account created! Please check your email to verify, then log in.', 'success');
                return $this->redirect($response, '/login');
            } catch (\Delight\Auth\UserAlreadyExistsException $e) {
                $errors[] = 'An account with that email already exists.';
            } catch (\Delight\Auth\TooManyRequestsException $e) {
                $errors[] = 'Too many requests. Please try again later.';
            } catch (\Exception $e) {
                ekea_log_exception($e, 'Registration error');
                $errors[] = 'An error occurred. Please try again.';
            }
        }
        // PRG: store errors and old input in session, redirect to GET to prevent re-submit on refresh
        $_SESSION['register_errors'] = $errors;
        $_SESSION['register_old'] = $old;
        return $this->redirect($response, '/register');
    }

    public function logout(Request $request, Response $response): Response
    {
        global $auth;
        $currentUserId = $auth->isLoggedIn() ? (int) $auth->getUserId() : null;
        try {
            // Delete by user_id alone so the record is always removed regardless of token state.
            // Also fall back to token-only delete for edge cases where userId isn't available.
            if ($currentUserId !== null) {
                clear_user_session_record($currentUserId, null);
            } else {
                clear_user_session_record(null, $_SESSION['session_token'] ?? null);
            }
            $auth->logOut();
            unset($_SESSION['cached_first_name'], $_SESSION['is_google_user']);
            $this->flash('You have been safely logged out. See you next time!', 'success');
        } catch (\Delight\Auth\NotLoggedInException $e) {
        }
        return $this->redirect($response, '/login');
    }

    public function forgotPasswordPage(Request $request, Response $response): Response
    {
        global $auth;
        if ($auth->isLoggedIn()) {
            return $this->redirect($response, '/');
        }
        $page_title = 'Forgot Password';
        $current_page = '';
        $errors = [];
        $old_email = '';
        $csrf_token = generate_csrf_token();
        return $this->render($response, 'auth/forgetpassword', compact('page_title', 'current_page', 'errors', 'old_email', 'csrf_token'));
    }

    public function forgotPassword(Request $request, Response $response): Response
    {
        global $auth;
        if ($auth->isLoggedIn()) {
            return $this->redirect($response, '/');
        }
        $data = $request->getParsedBody();
        $errors = [];
        $old_email = trim($data['email'] ?? '');
        if (!validate_csrf_token($data['csrf_token'] ?? '')) {
            $errors[] = 'Invalid form submission.';
        }
        if (empty($old_email)) {
            $errors[] = 'Email is required.';
        } elseif (!filter_var($old_email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }
        if (empty($errors)) {
            try {
                $auth->forgotPassword($old_email, function ($selector, $token) use ($old_email) {
                    $reset_url = 'http://ekea.duckdns.org/resetpassword?selector=' . urlencode($selector) . '&token=' . urlencode($token);
                    ekea_log("Password reset URL: {$reset_url}", 'DEBUG');
                    sendPasswordResetEmail($old_email, $reset_url);
                });
            } catch (\Exception $e) {
                // Silently ignore 窶・don't reveal whether the email exists
                ekea_log_exception($e, 'Forgot password error');
            }
            // Always show success to prevent email enumeration
            $this->flash('If that email is registered, you will receive a password reset link shortly. Please check your inbox.', 'success');
            return $this->redirect($response, '/login');
        }
        $page_title = 'Forgot Password';
        $current_page = '';
        $csrf_token = generate_csrf_token();
        return $this->render($response, 'auth/forgetpassword', compact('page_title', 'current_page', 'errors', 'old_email', 'csrf_token'));
    }

    public function resetPasswordPage(Request $request, Response $response): Response
    {
        global $auth;
        if ($auth->isLoggedIn()) {
            return $this->redirect($response, '/');
        }
        $params = $request->getQueryParams();
        $selector = $params['selector'] ?? '';
        $token = $params['token'] ?? '';
        if (empty($selector) || empty($token)) {
            return $this->redirect($response, '/forgetpassword');
        }
        $page_title = 'Reset Password';
        $current_page = '';
        $errors = [];
        $csrf_token = generate_csrf_token();
        return $this->render($response, 'auth/resetpassword', compact('page_title', 'current_page', 'errors', 'selector', 'token', 'csrf_token'));
    }

    public function resetPassword(Request $request, Response $response): Response
    {
        global $auth;
        if ($auth->isLoggedIn()) {
            return $this->redirect($response, '/');
        }
        $data = $request->getParsedBody();
        $errors = [];
        $selector = $data['selector'] ?? '';
        $token = $data['token'] ?? '';
        $password = $data['password'] ?? '';
        $confirm = $data['confirm_password'] ?? '';
        if (!validate_csrf_token($data['csrf_token'] ?? '')) {
            $errors[] = 'Invalid form submission.';
        }
        if (empty($password)) {
            $errors[] = 'Password is required.';
        } elseif (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }
        if ($password !== $confirm) {
            $errors[] = 'Passwords do not match.';
        }
        if (empty($errors)) {
            try {
                $auth->resetPassword($selector, $token, $password);
                $this->flash('Your password has been reset! You can now log in.', 'success');
                return $this->redirect($response, '/login');
            } catch (\Delight\Auth\InvalidSelectorTokenPairException $e) {
                $errors[] = 'Invalid reset link.';
            } catch (\Delight\Auth\TokenExpiredException $e) {
                $errors[] = 'This reset link has expired. Please request a new one.';
            } catch (\Delight\Auth\ResetDisabledException $e) {
                $errors[] = 'Password reset is disabled.';
            } catch (\Exception $e) {
                ekea_log_exception($e, 'Reset password error');
                $errors[] = 'An unexpected error occurred.';
            }
        }
        $page_title = 'Reset Password';
        $current_page = '';
        $csrf_token = generate_csrf_token();
        return $this->render($response, 'auth/resetpassword', compact('page_title', 'current_page', 'errors', 'selector', 'token', 'csrf_token'));
    }

    public function verifyEmail(Request $request, Response $response): Response
    {
        global $auth;
        $params = $request->getQueryParams();
        $error = null;
        if (isset($params['selector'],$params['token'])) {
            try {
                $auth->confirmEmail($params['selector'], $params['token']);
                ekea_log('Email verified', 'INFO');
                $this->flash('Your email has been verified! You can now log in.', 'success');
                return $this->redirect($response, '/login');
            } catch (\Delight\Auth\InvalidSelectorTokenPairException $e) {
                $error = 'Invalid verification link.';
            } catch (\Delight\Auth\TokenExpiredException $e) {
                $error = 'This link has expired. Please request a new one.';
            } catch (\Exception $e) {
                ekea_log_exception($e, 'Email verify error');
                $error = 'An unexpected error occurred.';
            }
        }
        $page_title = 'Email Verification';
        $current_page = '';
        return $this->render($response, 'auth/verify', compact('page_title', 'current_page', 'error'));
    }

    public function googleLogin(Request $request, Response $response): Response
    {
        global $auth;

        if ($auth->isLoggedIn()) {
            return $this->redirect($response, '/');
        }

        $provider = new Google([
            'clientId'     => '430096688412-a0vr6gdjmrnp8ge5ndj7jnr8qf9fs1b2.apps.googleusercontent.com', // Paste your Client ID here
            'clientSecret' => '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', // Paste your Client Secret here
            'redirectUri'  => 'http://ekea.duckdns.org/auth/google/callback',
        ]);

        // Generate the authorization URL and store the state
        $authUrl = $provider->getAuthorizationUrl(['prompt' => 'select_account']);
        $_SESSION['oauth2state'] = $provider->getState();

        return $response->withHeader('Location', $authUrl)->withStatus(302);
    }

    public function googleCallback(Request $request, Response $response): Response
    {
        global $auth, $pdo;

        $params = $request->getQueryParams();

        // 1. Check for errors
        if (!empty($params['error'])) {
            $this->flash('Google login failed: ' . htmlspecialchars($params['error'], ENT_QUOTES, 'UTF-8'), 'danger');
            return $this->redirect($response, '/login');
        }

        // 2. Validate state to prevent CSRF attacks
        if (empty($params['state']) || ($params['state'] !== ($_SESSION['oauth2state'] ?? ''))) {
            unset($_SESSION['oauth2state']);
            $this->flash('Invalid state. Please try again.', 'danger');
            return $this->redirect($response, '/login');
        }

        $provider = new Google([
            'clientId'     => '430096688412-a0vr6gdjmrnp8ge5ndj7jnr8qf9fs1b2.apps.googleusercontent.com', // Paste your Client ID here
            'clientSecret' => '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', // Paste your Client Secret here
            'redirectUri'  => 'http://ekea.duckdns.org/auth/google/callback',
        ]);

        try {
            // 3. Get the access token and the user's Google profile
            $token = $provider->getAccessToken('authorization_code', [
                'code' => $params['code']
            ]);

            $ownerDetails = $provider->getResourceOwner($token);
            $email = $ownerDetails->getEmail();
            $firstName = $ownerDetails->getFirstName();
            $lastName = $ownerDetails->getLastName();

            // 4. Try to log the user in
            try {
                // Using admin() allows us to securely bypass the password check for a known email
                $auth->admin()->logInAsUserByEmail($email);
                start_user_session_record((int) $auth->getUserId());
                ekea_log('Google Login successful', 'INFO', ['email' => $email]);

                $_SESSION['is_google_user'] = true;

                return $this->redirect($response, '/');

            } catch (\Delight\Auth\InvalidEmailException $e) {
                // 5. If the email is not in the DB, register them silently
                $randomPassword = bin2hex(random_bytes(16)); // Secure, unused placeholder password

                // admin()->createUser creates a verified account automatically without needing the email confirmation flow
                $userId = $auth->admin()->createUser($email, $randomPassword);

                // Insert their profile data into your custom user_profiles table
                $stmt = $pdo->prepare('INSERT INTO user_profiles (user_id, first_name, last_name, auth_provider) VALUES (:uid, :fn, :ln, :provider)');
                $stmt->execute([
                    ':uid' => $userId,
                    ':fn' => $firstName ?: 'Unknown',
                    ':ln' => $lastName ?: 'Unknown',
                    ':provider' => 'google' // Flag them as a Google user
                ]);

                ekea_log('User registered via Google OAuth', 'INFO', ['email' => $email]);

                // Immediately log the newly created user in
                $auth->admin()->logInAsUserById($userId);
                start_user_session_record((int) $auth->getUserId());

                $_SESSION['is_google_user'] = true;

                $this->flash('Account created and logged in successfully via Google!', 'success');
                return $this->redirect($response, '/');
            }

        } catch (\Exception $e) {
            ekea_log_exception($e, 'Google OAuth Callback error');
            $this->flash('Something went wrong during Google Login.', 'danger');
            return $this->redirect($response, '/login');
        }

    }
}
