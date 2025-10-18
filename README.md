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
Fusion is a Laravel package designed to manage Markdown content via Eloquent Models, eliminating the necessity for a conventional database setup. It achieves this by leveraging Markdown files enhanced with Frontmatter.
    </i>
</p>

<p align="center">
    <img src="https://repository-images.githubusercontent.com/781827813/6cd3b16b-b318-4f90-8a03-77bbded91c02" alt="Laravel package that enhances Eloquent models to facilitate the management of structured, database-free content through Markdown files with Frontmatter.">
</p>

Fusion aids in **website development** by integrating the power of **Markdown and Frontmatter**, enabling developers to create content-driven Web sites without having to manage databases.

With Fusion, developers can leverage the simplicity of Markdown syntax combined with the flexibility of Frontmatter to seamlessly **organize and structure content**.

By parsing Frontmatter into **Eloquent models**, Fusion enables developers to create complex, structured websites with ease.
Say goodbye to the complexities of database management and welcome simplified website development with Fusion.

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

class Article extends FusionBaseModel
{

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
- you have to implement the `frontmatterFields()` function for returning the list of the field names used in the frontmatter header.

### Creating automatically the Model

If you want to create automatically the Model file, based on the structure and the content of the Markdown files you can use the `fusion:sync-model` command:

```shell
php artisan fusion:sync-model --path=resources/content/article --create-model
```

The `path` parameter sets the **directory** of the markdown files for a specific model (like article, page, post, project etc.).
The `--create-model` option will generate automatically the Model file based on the content and the frontmatter section of the Markdown files.
Without the `--create-model` option the `fusion:sync-model` command will list some information detected from Markdown like the name of the Model and the list of the fields.




### Querying the content
Now in your Controllers or your Blade components, you can use the `Article` model with the usual method like `where()`, `orderBy()` etc:

```php
$articles = \App\Models\Article
    ::where('published', true)
    ->orderBy('date')
    ->get();
```

## Advanced Usage
Once you understand the basics of using Fusion with your Markdown files, you can use some of the advanced features. Consider that Fusion is a LAravel Package and during the design and the implementation of the Package we try to use and adhere to as much as possible the Laravel functionalities.
With this approach, we think that we can inherit all the benefits of some Laravel features.
Some of these feature that we explore and explain in the next sections are:

- Reloading the page of the website you are visualizing in the Browser when you change the Markdown content;
- Casting date in the Model so you can use the date in the Frontmatter headers;
- Casting collection in the Model so you can use complex and nested Frontmatter headers;
- Using the Check Markdown command for detecting the Frontmatter fields in the Markdown files.

### Adding real-time page reload
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

### Using dates in the Markdown files

If you want to use date (or date-time) in your front matter you can use the format YYYY-MM-DD in the Frontmatter for example:

```markdown
---
date: 2023-01-26
title: Example title for article 1
---
This is the **Markdown**.
```

Then in the Model you can set the casting in this way:

```php
    protected function casts(): array
    {
        return [
            'date' => 'datetime:Y-m-d',
        ];
    }
```

Where `date` is the field name, the same name you are using in the Markdown.

If you are adding a new field, remember to add the field name in the list of the frontmatter field in the `frontmatterFields()` function in the Model:

```php
    public function frontmatterFields(): array
    {
        return [
            "title", "date", "excerpt", "published", "highlight", "head"
        ];
    }
```



> If you are interested into exploring more the attribute casting in the Laravel Model you can take a look at [The Laravel Eloquent attribute casting documentation](https://laravel.com/docs/11.x/eloquent-mutators#attribute-casting).

### Using collections in the Markdown files

If you need to manage complex data in the Markdown, like for example a list of tags you can use the arrays in the Frontmatter:

```markdown
---
date: 2023-01-26
title: Example title for article 1
excerpt: This will be a short excerpt from article number 1.
published: true
highlight: true
head:
  - tag: title
    content: Custom about title
  - tag: title2
    content: Custom about title2
---

# Article 1

Markdown goes here
```

In the example, we are adding a field named `head` which is an array of values for tag and content.

In the Model file, you have to cast properly the `head` field as `collection`.

```php
    protected function casts(): array
    {
        return [
            'head' => 'collection',
            'date' => 'datetime:Y-m-d',
        ];
    }
```

So that in your blade files, you can loop through the `head` field and access to `tag` or `content` sub-fields:

```php
@if (! is_null($article->head))
    @foreach ($article->head as $headItems)
    <div class="mx-3 px-8 badge badge-neutral">{{ $headItems["tag"] }}</div>
    @endforeach
@else
    <div class="mx-3 px-8 badge badge-ghost">No Tag</div>
@endif
```

### Customizing the slug

By default, the slug of a content is the filename without the extension.
Now you can customize the slug using the frontmatter attribute `slug`.
 For example:
```markdown
---
date: 2023-04-13
slug: fusion
name: Fusion
claim:  build Website with Markdown files
excerpt: Fusion integrates Markdown into Laravel Models, simplifying Website development.
published: true
highlight: true
image: /img/fusion-cover-website.webp
tags:
  - tag: Laravel
  - tag: Website Development
---

# Fusion

Fusion aids in website development by integrating the power of Markdown and Frontmatter, enabling developers to create content-driven Web sites without having to manage databases.
```

### Using the Check Markdown command

To inspect the Markdown files and show and list the Frontmatter fields you can use the Artisan command `fusion:check`.

```shell
php artisan fusion:check
```

### Using the Check Model command

To inspect the Model file and check if it extends the right class and uses the proper Traits to be a Model compatible with Markdown files, you can use the `fusion:check-model` command:


```shell
php artisan fusion:check-model --model="App\Models\Article"
```

The output will tell you if your model is correct or not:

```text
Parsing the Model File
Class App\Models\Article exists
App\Models\Article extends correctly the class HiFolks\Fusion\Models\FusionBaseModel
App\Models\Article uses HiFolks\Fusion\Traits\FusionModelTrait
```


## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security-related issues, please open an issue in the tracker [using the "New issue" functionality](https://github.com/Hi-Folks/fusion/issues/new?labels=security&title=SECURITY).

## Credits

-   [Roberto B](https://github.com/roberto-butti)
-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## References
We built this Open Source project using some nice and powerful Open Source libraries like:
- [Sushi](https://github.com/calebporzio/sushi): Eloquent's missing "array" driver;
- [league/commonmark](https://github.com/thephpleague/commonmark): Highly-extensible PHP Markdown parser which fully supports the CommonMark and GFM specs;
- [yaml-front-matter](https://github.com/spatie/yaml-front-matter): A to the point yaml front matter parser;
- [PestPHP](https://github.com/pestphp/pest): Pest is an elegant PHP testing Framework with a focus on simplicity, meticulously designed to bring back the joy of testing in PHP. Under the hood PestPHP uses [PHPUnit, the PHP testing framework](https://phpunit.de);
- [RectorPHP](https://github.com/rectorphp/rector): Instant Upgrades and Automated Refactoring of any PHP 5.3+ code;
- [Laravel Pint](https://github.com/laravel/pint): an opinionated PHP code style fixer for minimalists. Under the hood, Laravel Pint uses [PHP-CS-Fixer](https://github.com/PHP-CS-Fixer/PHP-CS-Fixer) the open source tool to automatically fix PHP Coding Standards issues.



## Laravel Package Boilerplate

This package was generated using the [Laravel Package Boilerplate](https://laravelpackageboilerplate.com).
