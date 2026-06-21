<?php

declare(strict_types=1);

namespace A\Http;

use A\Http\Exception\HttpException;

class Application
{
    public Router $router;

    protected bool $mapped = false;

    public function __construct(
        protected string $root,
        protected mixed $fallback = null,
    ) {
        $this->router = new Router();
    }

    public function run() : int
    {
        try
        {
            $this->map();
            $response = $this->router->dispatch(Request::from_globals(), $this->fallback);
        }
        catch (HttpException $exception)
        {
            $response = new Response(status: $exception->getStatusCode(), reason: $exception->getReasonPhrase(), body: $exception->getReasonPhrase());
        }
        catch (\Throwable $exception)
        {
            $response = new Response(status: 500, reason: 'Internal Server Error', body: $exception->getMessage());
        }

        Sender::send($response);

        return 0;
    }

    protected function map() : void
    {
        if ($this->mapped)
        {
            return;
        }

        $this->router->map($this->root . '/app');

        if (is_dir($this->root . '/api'))
        {
            $this->router->map($this->root . '/api');
        }

        $this->mapped = true;
    }
}
