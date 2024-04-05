# Fusion

[![Latest Version on Packagist](https://img.shields.io/packagist/v/hi-folks/fusion.svg?style=flat-square)](https://packagist.org/packages/hi-folks/fusion)
[![Total Downloads](https://img.shields.io/packagist/dt/hi-folks/fusion.svg?style=flat-square)](https://packagist.org/packages/hi-folks/fusion)
![GitHub Actions](https://github.com/hi-folks/fusion/actions/workflows/main.yml/badge.svg)

Fusion revolutionizes **website development** by integrating the power of **Markdown and Frontmatter**, enabling developers to effortlessly create content-driven websites without the hassle of managing databases. With Fusion, developers can harness the simplicity of Markdown syntax combined with the flexibility of Frontmatter to **organize and structure content** seamlessly.
By parsing Frontmatter into **Eloquent models**, Fusion empowers developers to build complex, structured websites with ease. Say goodbye to database management complexities and hello to streamlined website development with Fusion.

## Installation

You can install the package via composer:

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
use Sushi\Sushi;

class Article extends FusionBaseModel
{
    use Sushi;

    public function getRows()
    {
        return parent::getRows();

    }

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
- you have to use the trait Sushi: `use Sushi;`
- you have to implement the `getRows()` function returning `return parent::getRows();`
- you have to implement the `frontmatterFields()` function for returning the list of the field names used in the frontmatter header.

### Querying the content
Now in your Controllers or your Blade components, you can use the `Article` model with the usual method like `where()`, `orderBy()` etc:

```php
$articles = \App\Models\Article
    ::where('published', true)
    ->orderBy('date')
    ->get();
```



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
