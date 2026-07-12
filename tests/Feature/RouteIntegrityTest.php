<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RouteIntegrityTest extends TestCase
{
    public function test_every_controller_route_references_an_existing_method(): void
    {
        foreach (Route::getRoutes() as $route) {
            $action = $route->getActionName();
            if ($action === 'Closure' || !str_contains($action, '@')) {
                continue;
            }

            [$controller, $method] = explode('@', $action, 2);
            $this->assertTrue(class_exists($controller), "Route controller does not exist: {$controller}");
            $this->assertTrue(method_exists($controller, $method), "Route action does not exist: {$action}");
        }
    }

    public function test_every_named_route_used_by_blade_exists(): void
    {
        $routeNames = [];

        foreach (File::allFiles(resource_path('views')) as $view) {
            $contents = File::get($view->getPathname());
            preg_match_all('/\broute\(\s*[\'\"]([^\'\"]+)[\'\"]/', $contents, $matches);

            foreach ($matches[1] ?? [] as $routeName) {
                $routeNames[$routeName][] = $view->getRelativePathname();
            }
        }

        foreach ($routeNames as $routeName => $views) {
            $this->assertTrue(
                Route::has($routeName),
                "Missing named route {$routeName}, referenced by " . implode(', ', array_unique($views))
            );
        }
    }

    public function test_route_names_are_unique(): void
    {
        $seen = [];

        foreach (Route::getRoutes() as $route) {
            $name = $route->getName();
            if (!$name) {
                continue;
            }

            $this->assertArrayNotHasKey($name, $seen, "Duplicate route name: {$name}");
            $seen[$name] = $route->uri();
        }
    }
}
