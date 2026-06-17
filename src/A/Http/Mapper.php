<?php

declare(strict_types=1);

namespace A\Http;

class Mapper
{
    public function __construct(protected string $path)
    {
    }

    public function map(Router $router) : Router
    {
        foreach ($this->routes() as $route)
        {
            $router->route($route['path'], $route['action']);
        }

        return $router;
    }

    public function routes() : array
    {
        $routes = [];

        foreach ($this->classes() as $class)
        {
            foreach (static::routes_for($class) as $route)
            {
                $routes[] = [
                    'path' => $route->path,
                    'action' => $class,
                ];
            }
        }

        return $routes;
    }

    public static function routes_for(string $class) : array
    {
        $reflection = new \ReflectionClass($class);

        if (!$reflection->isInstantiable())
        {
            return [];
        }

        $chain = [];

        do
        {
            array_unshift($chain, $reflection);
        } while ($reflection = $reflection->getParentClass());

        $routes = [new Route('')];
        $found = false;

        foreach ($chain as $item)
        {
            $attributes = array_map(
                static fn (\ReflectionAttribute $attribute) : Route => $attribute->newInstance(),
                $item->getAttributes(Route::class),
            );

            if ($attributes === [])
            {
                continue;
            }

            $found = true;
            $next = [];

            foreach ($routes as $parent)
            {
                foreach ($attributes as $attribute)
                {
                    $route = clone $attribute;

                    if ($parent->path !== '')
                    {
                        $route->inherit($parent);
                    }

                    $next[] = $route;
                }
            }

            $routes = $next;
        }

        return $found ? $routes : [];
    }

    protected function classes() : array
    {
        $declared = get_declared_classes();

        foreach ($this->files() as $file)
        {
            include_once $file;
        }

        return array_values(array_diff(get_declared_classes(), $declared));
    }

    protected function files() : \Traversable
    {
        if (is_file($this->path))
        {
            yield $this->path;
            return;
        }

        if (!is_dir($this->path))
        {
            return;
        }

        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->path, \FilesystemIterator::SKIP_DOTS)) as $file)
        {
            if ($file instanceof \SplFileInfo && $file->getExtension() === 'php' && $file->getRealPath() !== false)
            {
                yield $file->getRealPath();
            }
        }
    }
}
