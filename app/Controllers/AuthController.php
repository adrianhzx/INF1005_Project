<?php
namespace App\Controllers;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AuthController extends BaseController {

    public function loginPage(Request $request, Response $response): Response {
        global $auth;
        if ($auth->isLoggedIn()) return $this->redirect($response, '/');
        $page_title='Login'; $current_page='login'; $errors=[]; $old_email='';
        $csrf_token = generate_csrf_token();
        return $this->render($response, 'auth/login', compact('page_title','current_page','errors','old_email','csrf_token'));
    }

    public function login(Request $request, Response $response): Response {
        global $auth;
        if ($auth->isLoggedIn()) return $this->redirect($response, '/');
        $data = $request->getParsedBody();
        $errors = []; $old_email = trim($data['email'] ?? '');
        if (!validate_csrf_token($data['csrf_token'] ?? '')) { ekea_log('Login CSRF failed','WARNING'); $errors[]='Invalid form submission.'; }
        if (empty($old_email)) $errors[]='Email is required.';
        elseif (!filter_var($old_email, FILTER_VALIDATE_EMAIL)) $errors[]='Please enter a valid email address.';
        if (empty($data['password'] ?? '')) $errors[]='Password is required.';
        if (empty($errors)) {
            $rememberDuration = null;
            if (isset($data['remember']) && $data['remember'] == 1) $rememberDuration = (int)(60*60*24*365.25);
            try {
                $auth->login($old_email, $data['password'], $rememberDuration);
                ekea_log('Login successful','INFO');
                return $this->redirect($response, '/');
            } catch (\Delight\Auth\InvalidEmailException $e) { $errors[]='Wrong email address.'; }
            catch (\Delight\Auth\InvalidPasswordException $e) { $errors[]='Wrong password.'; }
            catch (\Delight\Auth\EmailNotVerifiedException $e) { $errors[]='Please verify your email first.'; }
            catch (\Delight\Auth\TooManyRequestsException $e) { $errors[]='Too many attempts. Please try again later.'; }
            catch (\Exception $e) { ekea_log_exception($e,'Login error'); $errors[]='An unexpected error occurred.'; }
        }
        $page_title='Login'; $current_page='login'; $csrf_token=generate_csrf_token();
        return $this->render($response, 'auth/login', compact('page_title','current_page','errors','old_email','csrf_token'));
    }

    public function registerPage(Request $request, Response $response): Response {
        global $auth;
        if ($auth->isLoggedIn()) return $this->redirect($response, '/');
        $page_title='Register'; $current_page='register'; $errors=[];
        $old=['first_name'=>'','last_name'=>'','email'=>'','phone'=>''];
        $csrf_token=generate_csrf_token();
        return $this->render($response, 'auth/register', compact('page_title','current_page','errors','old','csrf_token'));
    }

    public function register(Request $request, Response $response): Response {
        global $auth, $pdo;
        if ($auth->isLoggedIn()) return $this->redirect($response, '/');
        $data=$request->getParsedBody(); $errors=[];
        $first_name=trim($data['first_name']??''); $last_name=trim($data['last_name']??'');
        $email=trim($data['email']??''); $phone=trim($data['phone']??'');
        $password=$data['password']??''; $confirm=$data['confirm_password']??'';
        $old=compact('first_name','last_name','email','phone');
        if (!validate_csrf_token($data['csrf_token']??'')) $errors[]='Invalid form submission.';
        if (empty($email)) $errors[]='Email is required.';
        elseif (!filter_var($email,FILTER_VALIDATE_EMAIL)) $errors[]='Please enter a valid email address.';
        if (empty($password)) $errors[]='Password is required.';
        elseif (strlen($password)<8) $errors[]='Password must be at least 8 characters.';
        if ($password!==$confirm) $errors[]='Passwords do not match.';
        if (empty($first_name)) $errors[]='First name is required.';
        if (empty($last_name)) $errors[]='Last name is required.';
        if (!empty($phone)&&!preg_match('/^\d{8,15}$/',$phone)) $errors[]='Phone number must be 8-15 digits.';
        if (empty($errors)) {
            try {
                $userId=$auth->register($email,$password,null,function($selector,$token) use ($email) {
                    $verify_url=BASE_URL.'/email-verification?selector='.urlencode($selector).'&token='.urlencode($token);
                    ekea_log("Verification URL: {$verify_url}",'DEBUG');
                    sendVerificationEmail($email,$verify_url);
                });
                $pdo->prepare('INSERT INTO user_profiles (user_id,first_name,last_name,phone) VALUES (:uid,:fn,:ln,:phone)')
                    ->execute([':uid'=>$userId,':fn'=>$first_name,':ln'=>$last_name,':phone'=>$phone]);
                ekea_log('User registered','INFO',['email'=>$email]);
                $this->flash('Account created! Please check your email to verify, then log in.','success');
                return $this->redirect($response, '/login');
            } catch (\Delight\Auth\UserAlreadyExistsException $e) { $errors[]='An account with that email already exists.'; }
            catch (\Delight\Auth\TooManyRequestsException $e) { $errors[]='Too many requests. Please try again later.'; }
            catch (\Exception $e) { ekea_log_exception($e,'Registration error'); $errors[]='An error occurred. Please try again.'; }
        }
        $page_title='Register'; $current_page='register'; $csrf_token=generate_csrf_token();
        return $this->render($response, 'auth/register', compact('page_title','current_page','errors','old','csrf_token'));
    }

    public function logout(Request $request, Response $response): Response {
        global $auth;
        try { $auth->logOut(); unset($_SESSION['cached_first_name']); $this->flash('You have been safely logged out. See you next time!','success'); }
        catch (\Delight\Auth\NotLoggedInException $e) {}
        return $this->redirect($response, '/login');
    }

    public function verifyEmail(Request $request, Response $response): Response {
        global $auth;
        $params=$request->getQueryParams(); $error=null;
        if (isset($params['selector'],$params['token'])) {
            try {
                $auth->confirmEmail($params['selector'],$params['token']);
                ekea_log('Email verified','INFO');
                $this->flash('Your email has been verified! You can now log in.','success');
                return $this->redirect($response, '/login');
            } catch (\Delight\Auth\InvalidSelectorTokenPairException $e) { $error='Invalid verification link.'; }
            catch (\Delight\Auth\TokenExpiredException $e) { $error='This link has expired. Please request a new one.'; }
            catch (\Exception $e) { ekea_log_exception($e,'Email verify error'); $error='An unexpected error occurred.'; }
        }
        $page_title='Email Verification'; $current_page='';
        return $this->render($response, 'auth/verify', compact('page_title','current_page','error'));
    }
}
