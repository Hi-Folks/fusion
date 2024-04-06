<h1 align="center">
    Fusion
</h1>

<p align=center>
    <a href="https://packagist.org/packages/hi-folks/fusion">
        <img src="https://img.shields.io/packagist/v/hi-folks/fusion.svg?style=for-the-badge" alt="Latest Version on Packagist">
    </a>
    <a href="https://packagist.org/packages/hi-folks/fusion">
        <img src="https://img.shields.io/packagist/dt/hi-folks/fusion.svg?style=for-the-badge" alt="Total Downloads">
    </a>
    <br />
    <img src="https://img.shields.io/packagist/l/hi-folks/fusion?style=for-the-badge" alt="Packagist License">
    <img src="https://img.shields.io/packagist/php-v/hi-folks/fusion?style=for-the-badge" alt="Packagist PHP Version Support">
    <img src="https://img.shields.io/github/last-commit/hi-folks/fusion?style=for-the-badge" alt="GitHub last commit">
    <br />
        <img src="https://img.shields.io/github/actions/workflow/status/hi-folks/fusion/main.yml?style=for-the-badge&label=Test" alt="Tests">
</p>



<p align=center>
    <i>
        Laravel package that enhances Eloquent models to facilitate the management of structured, database-free content through Markdown files with Frontmatter.
    </i>
</p>

<p align="center">
    <img src="https://repository-images.githubusercontent.com/781827813/6cd3b16b-b318-4f90-8a03-77bbded91c02" alt="Laravel package that enhances Eloquent models to facilitate the management of structured, database-free content through Markdown files with Frontmatter.">
</p>

Fusion aids in **website development** by integrating the power of **Markdown and Frontmatter**, enabling developers to create content-driven Web sites without having to manage databases.

With Fusion, developers can leverage the simplicity of Markdown syntax combined with the flexibility of Frontmatter to seamlessly **organize and structure content**.

By parsing Frontmatter into **Eloquent models**, Fusion enables developers to create complex, structured websites with ease.
Say goodbye to the complexities of database management and welcome simplified website development with Fusion.

> [!WARNING]
> The package is under development (version 0.0.x), so the functions, classes and methods provided can be changed, especially for the `FusionBaseModel` model class. So, if you want to start using the package in order to provide feedback, you are welcome, but please don't use it in production until the version 1.0.0 will be released. Thank you

## Installation

You can install the package via the `composer` tool:

```shell
composer require hi-folks/fusion
```

## Usage

Once you installed Fusion you can start the process of creating content in Markdown files and querying the files through the Models.
For example, now we are going to create articles in Markdown format and we will parse and query them like you can do it with a database.

### Creating the content
In the `resources/content` directory, you can create the `article` directory.
In the `resources/content/article`, you can create your Markdown files with a frontmatter header like for example:

```markdown
---
date: 2024-04-05
title: Example title for article 1
excerpt: This will be a short excerpt from article number 1.
published: true
---

# Article 1

Markdown goes here
```

you can name this file as `resources/content/article/article-1.md`
You can create similarly the other Markdown files. These files represent your articles.

### Creating the Model
Similarly, you are doing with a database, you can create your model for loading the markdown files.
Because you are creating articles you can create your model as `app/Models/Article.php`.

You can fill the file in this way:

```php
<?php

namespace App\Models;


use HiFolks\Fusion\Models\FusionBaseModel;
use HiFolks\Fusion\Traits\FusionModelTrait;

class Article extends FusionBaseModel
{
    use FusionModelTrait;


    public function frontmatterFields(): array
    {
        return [
            "excerpt"
        ];
    }

}

```
Consider that:
- the class has to extend the FusionBaseModel with `extends FusionBaseModel`;
- you have to use the trait FusionModelTrait: `use FusionModelTrait;`
- you have to implement the `frontmatterFields()` function for returning the list of the field names used in the frontmatter header.

### Querying the content
Now in your Controllers or your Blade components, you can use the `Article` model with the usual method like `where()`, `orderBy()` etc:

```php
$articles = \App\Models\Article
    ::where('published', true)
    ->orderBy('date')
    ->get();
```


### Adding real-time reload
If you want the browser to automatically reload the pages when you change some content in the Markdown files you can set the `refresh` option in the `laravel` Vite plugin.
In the `vite.config.js` file add the `refresh` option with the list of the folder you want that Vite will "watch" for changes and reload the page.

```js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ["resources/css/app.css", "resources/js/app.js"],
            refresh: [
                {
                    paths: ["resources/content/**"],
                },
            ],
        }),
    ]
});
```

In the example, the `resources/content` directory is added.

> If you need to setup the Vite asset bundling you can take a look at the LAravel documentation: https://laravel.com/docs/11.x/vite


### Testing

```bash
composer test
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security-related issues, please email roberto.butti@gmail.com instead of using the issue tracker.

## Credits

-   [Roberto B](https://github.com/roberto-butti)
-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Laravel Package Boilerplate

This package was generated using the [Laravel Package Boilerplate](https://laravelpackageboilerplate.com).
