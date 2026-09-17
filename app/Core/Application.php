<?php

declare(strict_types=1);

namespace App\Core;

use App\Exceptions\AppException;
use App\Exceptions\AuthenticationException;
use App\Exceptions\AuthorizationException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Support\Logger;
use Throwable;

class Application
{
    private static ?self $instance = null;
    private string $basePath;
    private Config $config;
    private Router $router;
    private Request $request;
    private View $view;
    private Database $database;

    public function __construct(string $basePath)
    {
        self::$instance = $this;
        $this->basePath = $basePath;

        // Initialize core components
        $this->config = new Config($basePath . '/config');
        $this->database = Database::getInstance();
        $this->router = new Router();
        $this->request = new Request();
        $this->view = new View($this->config->get('app.theme', 'classic'));

        // Start session
        Session::start();
    }

    public static function getInstance(): self
    {
        return self::$instance;
    }

    public function getBasePath(): string
    {
        return $this->basePath;
    }

    public function getConfig(): Config
    {
        return $this->config;
    }

    public function getRouter(): Router
    {
        return $this->router;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getView(): View
    {
        return $this->view;
    }

    public function getDatabase(): Database
    {
        return $this->database;
    }

    public function run(): void
    {
        try {
            // Load routes
            $router = $this->router;
            require_once $this->basePath . '/routes/web.php';
            require_once $this->basePath . '/routes/api.php';
            require_once $this->basePath . '/routes/admin.php';

            $response = $this->router->dispatch($this->request);
            $response->send();
        } catch (ValidationException $e) {
            $this->handleValidationException($e);
        } catch (AuthenticationException $e) {
            $this->handleAuthenticationException($e);
        } catch (AuthorizationException $e) {
            $this->handleAuthorizationException($e);
        } catch (NotFoundException $e) {
            $this->handleNotFoundException($e);
        } catch (Throwable $e) {
            $this->handleGeneralException($e);
        }
    }

    private function handleValidationException(ValidationException $e): void
    {
        if ($this->request->isAjax()) {
            (new Response())->json(['error' => 'Validation failed', 'errors' => $e->getErrors()], 422)->send();
            return;
        }

        Session::setFlash('errors', json_encode($e->getErrors()));
        Session::setFlash('old', json_encode($this->request->all()));
        $firstError = reset($e->getErrors()) ?: 'Please check your inputs.';
        Session::setFlash('error', is_array($firstError) ? reset($firstError) : $firstError);

        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        (new Response())->redirect($referer)->send();
    }

    private function handleAuthenticationException(AuthenticationException $e): void
    {
        if ($this->request->isAjax()) {
            (new Response())->json(['error' => $e->getMessage()], 401)->send();
            return;
        }

        Session::setFlash('error', $e->getMessage());
        $redirectUrl = str_starts_with($this->request->path(), '/admin') ? '/admin/login' : '/login';
        (new Response())->redirect($redirectUrl)->send();
    }

    private function handleAuthorizationException(AuthorizationException $e): void
    {
        if ($this->request->isAjax()) {
            (new Response())->json(['error' => $e->getMessage()], 403)->send();
            return;
        }

        (new Response())
            ->setStatusCode(403)
            ->setContent("<h1>403 - Access Forbidden</h1><p>{$e->getMessage()}</p>")
            ->send();
    }

    private function handleNotFoundException(NotFoundException $e): void
    {
        if ($this->request->isAjax()) {
            (new Response())->json(['error' => $e->getMessage()], 404)->send();
            return;
        }

        // Try to render custom 404 view
        try {
            $content = $this->view->render('errors/404', ['message' => $e->getMessage()], 'main');
            (new Response($content, 404))->send();
        } catch (Throwable $ex) {
            (new Response("<h1>404 - Page Not Found</h1><p>{$e->getMessage()}</p>", 404))->send();
        }
    }

    private function handleGeneralException(Throwable $e): void
    {
        Logger::error("Unhandled exception: " . $e->getMessage(), [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ]);

        if ($this->request->isAjax()) {
            $msg = $this->config->get('app.debug') ? $e->getMessage() : 'An internal server error occurred.';
            (new Response())->json(['error' => $msg], 500)->send();
            return;
        }

        if ($this->config->get('app.debug')) {
            $msg = "<h1>500 - Server Error</h1><p><strong>" . e($e->getMessage()) . "</strong></p><pre>" . e($e->getTraceAsString()) . "</pre>";
        } else {
            $msg = "<h1>500 - Server Error</h1><p>Something went wrong on our end. Please try again shortly.</p>";
        }

        (new Response($msg, 500))->send();
    }
}
