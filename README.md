# Liquid template engine for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/keepsuit/laravel-liquid.svg?style=flat-square)](https://packagist.org/packages/keepsuit/laravel-liquid)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/keepsuit/laravel-liquid/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/keepsuit/laravel-liquid/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/keepsuit/laravel-liquid/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/keepsuit/laravel-liquid/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/keepsuit/laravel-liquid.svg?style=flat-square)](https://packagist.org/packages/keepsuit/laravel-liquid)

This is a Laravel view integration of the Shopify Liquid template engine.
It uses [keepsuit/liquid](https://github.com/keepsuit/php-liquid) PHP porting under the hood to parse liquid templates.

## Installation

You can install the package via composer:

```bash
composer require keepsuit/laravel-liquid
```

## Usage

1. Create a liquid template file in `resources/views` folder (for example `home.liquid`).
2. Render the template as usual with Laravel view engine.

```php
class HomeController extends Controller
{
    public function index()
    {
        return view('home');
    }
}
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Credits

- [Fabio Capucci](https://github.com/keepsuit)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
