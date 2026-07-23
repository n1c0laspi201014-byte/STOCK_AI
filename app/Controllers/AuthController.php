<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\UserRepository;
use App\Services\AuthService;
use App\Support\Container;
use App\Support\Request;
use App\Support\Response;
use App\Support\View;
use InvalidArgumentException;

final class AuthController
{
    public function showLogin(Request $request): string
    {
        $error = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_error']);
        return View::render('auth/login', ['pageTitle' => 'Sign in', 'error' => $error], 'layouts/auth');
    }

    public function showRegister(Request $request): string
    {
        $error = $_SESSION['flash_error'] ?? null;
        $old = $_SESSION['registration_old'] ?? [];
        unset($_SESSION['flash_error'], $_SESSION['registration_old']);
        return View::render('auth/register', [
            'pageTitle' => 'Create account',
            'error' => $error,
            'old' => is_array($old) ? $old : [],
            'timezones' => timezone_identifiers_list(),
        ], 'layouts/auth');
    }

    public function login(Request $request): never
    {
        $service = new AuthService(Container::get(UserRepository::class));
        if ($service->attempt((string) $request->input('email'), (string) $request->input('password'))) {
            $target = $_SESSION['intended_url'] ?? url('/dashboard');
            unset($_SESSION['intended_url'], $_SESSION['flash_error']);
            Response::redirect((string) $target);
        }
        $_SESSION['flash_error'] = 'Invalid email or password, or too many recent attempts.';
        Response::redirect(url('/login'));
    }

    public function register(Request $request): never
    {
        try {
            (new AuthService(Container::get(UserRepository::class)))->register($request->all());
            $_SESSION['flash_success'] = 'Your paper-trading account is ready. Personalize Telegram and alert settings below.';
            Response::redirect(url('/setup?welcome=1'));
        } catch (InvalidArgumentException $exception) {
            $_SESSION['flash_error'] = $exception->getMessage();
            $old = $request->all();
            unset($old['password'], $old['password_confirmation'], $old['_csrf']);
            $_SESSION['registration_old'] = $old;
            Response::redirect(url('/register'));
        }
    }

    public function logout(Request $request): never
    {
        (new AuthService(Container::get(UserRepository::class)))->logout();
        Response::redirect(url('/login'));
    }
}
