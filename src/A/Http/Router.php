<?php

declare(strict_types=1);

namespace A\Http;

class Router
{
    const PATTERN = '{ (.*) ({ (?<key>[a-zA-Z]+) (\:(?<reg>(\\\{|\{.*[^}]\}|.)+))? })? }xU';

    protected array $routes = [];

    public function __construct(?string $filename = null)
    {
        if ($filename && is_file($filename))
        {
            $this->routes = json_decode((string)file_get_contents($filename), true, flags: \JSON_THROW_ON_ERROR);
        }
    }

    public function map(string $filepath) : static
    {
        return (new Mapper($filepath))->map($this);
    }

    public function route(string $pattern, string $action) : static
    {
        $this->routes[] = [
            'regex' => static::patternRegex($pattern),
            'action' => $action,
        ];

        return $this;
    }

    public function dispatch(Request $request, ?callable $fallback = null) : Response
    {
        foreach ($this->routes as $route)
        {
            if (preg_match('{^' . $route['regex'] . '$}', $request->path, $matches))
            {
                foreach (array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY) as $key => $value)
                {
                    $request = $request->with($key, urldecode($value));
                }

                return (new $route['action'])->handle($request);
            }
        }

        return $fallback ? $fallback($request) : new Response(status: 404, body: 'Not found');
    }

    public static function patternRegex(string $pattern) : string
    {
        return preg_replace_callback(
            static::PATTERN,
            function ($matches)
            {
                $str = preg_quote($matches[1], '{');

                if (isset($matches[2]))
                {
                    $key = $matches['key'];
                    $reg = $matches['reg'] ?? '[^/]+';
                    $str .= "(?<$key>$reg)";
                }

                return $str;
            },
            '/' . ltrim($pattern, '/')
        );
    }

}
