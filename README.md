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

## Tags

This package provides some custom tags in addition to the standard Liquid tags.

### Auth

Check if the user is authenticated. 
Same as laravel [`@auth`](https://laravel.com/docs/blade#authentication-directives) directive.

```liquid
{% auth %}
user is authenticated
{% endauth %}

{% guest %}
user is not authenticated
{% endguest %}
```

or with custom guard

```liquid
{% auth('admin') %}
admin is authenticated
{% endauth %}

{% guest 'admin' %}
admin is not authenticated
{% endguest %}
```

### Env

Check if the application environment is the specified one. 
Same as laravel [`@env`](https://laravel.com/docs/blade#environment-directives) directive.

```liquid
{% env 'production' %}
application is in production environment
{% endenv %}
```

### Session

Check if the session has a specific key.
Same as laravel [`@session`](https://laravel.com/docs/blade#session-directives) directive.
The value of the session key can be accessed with the `value` variable.

```liquid
{% session 'status' %}
<div class="p-4 bg-green-100">
  {{ value }}
</div>
{% endsession %}
```

### Error

Check if a validation error exists for the given field.
Same as laravel [`@error`](https://laravel.com/docs/blade#validation-errors) directive.
The error message can be accessed with the `message` variable.

```liquid
{% error 'email' %}
<div class="text-red-500 text-sm">
  {{ message }}
</div>
{% enderror %}
```

### Csrf field

Generate a hidden CSRF token form field.
Same as laravel [`@csrf`](https://laravel.com/docs/blade#csrf-field) directive.

```liquid
<form method="POST" action="/foo">
  {% csrf %}
  ...
</form>
```

### Vite

Adds your vite assets to the template.
Same as laravel [`@vite`](https://laravel.com/docs/vite#loading-your-scripts-and-styles) directive.

```liquid
{% vite 'resources/css/app.css', 'resources/js/app.js' %}

{% comment %}With custom build directory{% endcomment %}
{% vite "resources/js/app.js", directory: "custom" %}
```

## Filters

This package provides some custom filters in addition to the standard Liquid filters.

### Debug

Debug variable content with `dump` and `dd` filters.


```liquid
{{ variable | dump }}

{{ variable | dd }}
```

### Localization

Translate a string with `trans` (or `t` alias) and `trans_choice` filters using the Laravel localization system.

```liquid
{{ 'messages.welcome' | trans }}

{{ 'messages.items_count' | trans_choice: 3 }}
```

### Url

Generate urls using the laravel url helpers.

```liquid
{{ 'app.js' | asset }}
{{ 'app.js' | secure_asset }}

{{ '/home' | url }}
{{ '/home' | secure_url }}

{{ 'home' | route }}
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
