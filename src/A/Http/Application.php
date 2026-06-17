<?php

namespace A\Http;

use A\Http\Exception\HttpException;

class Application
{
    public Router $router;

    public function __construct(
        protected string $root,
        protected mixed $fallback = null,
    ) {
        $this->router = (new Router())->map($root . '/app');

        if (is_dir($root . '/api'))
        {
            $this->router->map($root . '/api');
        }
    }

    public function run() : int
    {
        try
        {
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
}
