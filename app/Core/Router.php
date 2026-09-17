<?php

declare(strict_types=1);

namespace App\Core;

use App\Exceptions\NotFoundException;

class Router
{
    private array $routes = [];
    private array $groupStack = [];

    public function get(string $path, array|callable $handler, array $middlewares = []): self
    {
        return $this->addRoute('GET', $path, $handler, $middlewares);
    }

    public function post(string $path, array|callable $handler, array $middlewares = []): self
    {
        return $this->addRoute('POST', $path, $handler, $middlewares);
    }

    public function put(string $path, array|callable $handler, array $middlewares = []): self
    {
        return $this->addRoute('PUT', $path, $handler, $middlewares);
    }

    public function delete(string $path, array|callable $handler, array $middlewares = []): self
    {
        return $this->addRoute('DELETE', $path, $handler, $middlewares);
    }

    public function group(array $attributes, callable $callback): void
    {
        $this->groupStack[] = $attributes;
        $callback($this);
        array_pop($this->groupStack);
    }

    private function addRoute(string $method, string $path, array|callable $handler, array $middlewares = []): self
    {
        $prefix = '';
        $groupMiddlewares = [];

        foreach ($this->groupStack as $group) {
            if (isset($group['prefix'])) {
                $prefix .= '/' . trim($group['prefix'], '/');
            }
            if (isset($group['middleware'])) {
                $m = is_array($group['middleware']) ? $group['middleware'] : [$group['middleware']];
                $groupMiddlewares = array_merge($groupMiddlewares, $m);
            }
        }

        $fullPath = '/' . trim($prefix . '/' . trim($path, '/'), '/');
        if ($fullPath === '') {
            $fullPath = '/';
        }

        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $fullPath);
        $regex = '#^' . $pattern . '$#';

        $this->routes[] = [
            'method' => $method,
            'path' => $fullPath,
            'regex' => $regex,
            'handler' => $handler,
            'middlewares' => array_merge($groupMiddlewares, $middlewares),
        ];

        return $this;
    }

    public function dispatch(Request $request): Response
    {
        $reqMethod = $request->method();
        $reqPath = $request->path();

        foreach ($this->routes as $route) {
            $methodMatches = ($route['method'] === $reqMethod)
                || ($route['method'] === 'ANY')
                || ($reqMethod === 'HEAD' && $route['method'] === 'GET');

            if (!$methodMatches) {
                continue;
            }

            if (preg_match($route['regex'], $reqPath, $matches)) {
                $params = [];
                foreach ($matches as $key => $val) {
                    if (is_string($key)) {
                        $params[$key] = $val;
                    }
                }

                return $this->runRoute($route, $request, $params);
            }
        }

        throw new NotFoundException("Page not found: [{$reqMethod}] {$reqPath}");
    }

    private function runRoute(array $route, Request $request, array $params): Response
    {
        $middlewares = $route['middlewares'];

        $runner = function (Request $req) use ($route, $params) {
            $handler = $route['handler'];

            if (is_callable($handler)) {
                $result = call_user_func($handler, $req, ...array_values($params));
            } elseif (is_array($handler)) {
                [$class, $method] = $handler;
                if (!class_exists($class)) {
                    throw new NotFoundException("Controller {$class} not found");
                }
                $controller = new $class();
                if (!method_exists($controller, $method)) {
                    throw new NotFoundException("Method {$method} not found on {$class}");
                }
                $result = call_user_func_array([$controller, $method], array_merge([$req], $params));
            } else {
                throw new \InvalidArgumentException("Invalid route handler");
            }

            if ($result instanceof Response) {
                return $result;
            }

            return new Response((string)$result);
        };

        // Run through middleware stack in reverse
        $pipeline = array_reduce(
            array_reverse($middlewares),
            function ($next, $middlewareClass) {
                return function (Request $req) use ($next, $middlewareClass) {
                    $instance = new $middlewareClass();
                    return $instance->handle($req, $next);
                };
            },
            $runner
        );

        return $pipeline($request);
    }
}
