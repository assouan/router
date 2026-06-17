<?php

namespace A\Http;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class Template
{
    public function __construct(protected string $path = '')
    {
    }

    public function resolve(mixed $data, \ReflectionMethod $method, object $controller) : Response
    {
        $class = $method->getDeclaringClass();
        $file = (string)$class->getFileName();
        $path = $this->path !== ''
            ? dirname($file) . '/' . $this->path
            : dirname($file) . '/' . basename($file, '.php') . '.phtml';

        if (!is_file($path))
        {
            throw new \RuntimeException("Template file not found: {$path}");
        }

        $render = function () use ($data, $path): array {
            $layout = null;

            if (is_array($data))
            {
                extract($data, EXTR_SKIP);
            }
            else
            {
                $value = $data;
            }

            ob_start();
            include $path;

            return [
                'body' => (string)ob_get_clean(),
                'layout' => $layout,
            ];
        };

        $result = $render->call($controller);
        $body = $this->layout((string)$result['body'], $result['layout'] ?? null, $controller);

        return new Response(
            headers: ['Content-Type' => 'text/html; charset=utf-8'],
            body: $body,
        );
    }

    private function layout(string $body, mixed $layout, object $controller) : string
    {
        if (!is_array($layout))
        {
            return $body;
        }

        $render = function () use ($layout, $body): string {
            return match ((string)($layout['type'] ?? '')) {
                'public' => $this->page(
                    (string)($layout['active'] ?? ''),
                    (string)($layout['title'] ?? ''),
                    (string)($layout['lead'] ?? ''),
                    is_array($layout['cards'] ?? null) ? $layout['cards'] : [],
                    $body,
                ),
                'dashboard' => $this->dashboard_page(
                    (string)($layout['active'] ?? ''),
                    (string)($layout['title'] ?? ''),
                    (string)($layout['subtitle'] ?? ''),
                    $body,
                    is_array($layout['user'] ?? null) ? $layout['user'] : [],
                ),
                'admin' => $this->admin_page(
                    (string)($layout['active'] ?? ''),
                    (string)($layout['title'] ?? ''),
                    $body,
                ),
                'bare' => $this->bare_page(
                    (string)($layout['title'] ?? ''),
                    (string)($layout['subtitle'] ?? ''),
                    $body,
                ),
                default => $body,
            };
        };

        return $render->call($controller);
    }
}
