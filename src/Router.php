<?php

declare(strict_types=1);

namespace App;

/**
 * Routeur minimal : associe méthode HTTP + chemin à un callable.
 * Les segments {param} capturent un entier et sont passés en argument nommé.
 */
final class Router
{
    /** @var list<array{string, string, callable}> */
    private array $routes = [];

    public function get(string $path, callable $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, callable $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = rtrim(parse_url($uri, PHP_URL_PATH) ?: '/', '/') ?: '/';
        $pathMatched = false;

        foreach ($this->routes as [$routeMethod, $regex, $handler]) {
            if (!preg_match($regex, $path, $matches)) {
                continue;
            }
            $pathMatched = true;
            if ($routeMethod !== $method) {
                continue;
            }

            $params = array_map('intval', array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY));
            $handler(...$params);
            return;
        }

        throw new HttpException($pathMatched ? 405 : 404);
    }

    private function add(string $method, string $path, callable $handler): void
    {
        $regex = '#^' . preg_replace('#\{(\w+)\}#', '(?P<$1>\d+)', $path) . '$#';
        $this->routes[] = [$method, $regex, $handler];
    }
}
