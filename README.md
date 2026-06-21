# Assouan Router

Attribute-based HTTP routing utilities for the A PHP libraries.

```bash
composer require assouan/router
```

Requires PHP 8.5 or later.

## Example

```php
use A\Http\Route;

#[Route('/hello/{name}')]
class Hello extends A\Http\Controller
{
    public function get(string $name): array
    {
        return ['message' => "Hello {$name}"];
    }
}
```

Run an application from a front controller:

```php
require __DIR__ . '/vendor/autoload.php';

exit((new A\Http\Application(__DIR__))->run());
```

Routes use `{name}` placeholders. Controller arguments are resolved from route attributes, query string, form bodies, and JSON bodies.
