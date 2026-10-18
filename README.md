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
    <b>
    Markdown content. Laravel Eloquent. Working together.
    </b>
</p>    
<p align=center>
    <i>
    Fusion is a Laravel package that lets you manage Markdown files through familiar Eloquent models and queries, without requiring traditional database tables for your content.
    </i>
</p>

<p align="center">
    <img src="https://repository-images.githubusercontent.com/781827813/6cd3b16b-b318-4f90-8a03-77bbded91c02" alt="Laravel package that enhances Eloquent models to facilitate the management of structured, database-free content through Markdown files with Frontmatter.">
</p>

## Why Fusion?

Laravel developers love Eloquent because it provides a clean, expressive way to work with application data. But what happens when your content lives in Markdown files instead of database tables?

Imagine building a blog, documentation website, portfolio, or content-driven application where articles and pages are stored as Markdown files. Markdown is simple to write, easy to maintain, and works naturally with version control.

However, as your application grows, you still need to organize, filter, sort, and retrieve that content. You could implement your own file-reading and parsing logic, but why reinvent something when you're already familiar with Eloquent?

**That's where Fusion comes in.**

Fusion brings Markdown-based content into the Laravel ecosystem, allowing you to work with files using the Eloquent patterns you already know.

## How does it work?

Fusion combines three familiar concepts:

- **Markdown** stores your content in readable, portable files.
- **Frontmatter** provides structured metadata such as titles, dates, tags, and publication status.
- **Eloquent** gives you a familiar interface for querying and working with that content.

For example, imagine having articles stored as Markdown files:

`resources/content/article/hello-laravel.md`

```markdown
---
title: Hello Laravel
date: 2026-10-17
published: true
---

# Hello Laravel

Welcome to my article!
```

With Fusion, you can create an Eloquent model backed by these files and query your content using familiar Laravel methods:

```php
$articles = Article::where('published', true)
    ->orderBy('date', 'desc')
    ->get();
```

No custom Markdown parsing in your controllers. No manually scanning directories to find published articles. Just familiar Eloquent-style queries.

## When should you use Fusion?

Fusion is particularly useful when your content is naturally file-based and you want to keep the development experience close to Laravel conventions.

Typical examples include:

- **Blogs and personal websites** — Manage articles as Markdown files while using Eloquent to retrieve and organize them.
- **Documentation websites** — Keep documentation in version-controlled Markdown files.
- **Portfolios and project showcases** — Store project descriptions and metadata without creating dedicated database tables.
- **Static or content-driven websites** — Build Laravel-powered experiences with content maintained directly in your repository.

Fusion doesn't aim to replace relational databases or full-featured content management systems. Instead, it offers a practical alternative for content that benefits from being stored as files while remaining accessible through Laravel's familiar development patterns.

**Keep the simplicity of Markdown. Enjoy the developer experience of Eloquent.**

## Installation

You can install the package via the `composer` tool:

```shell
composer require hi-folks/fusion
```

## Usage

Once you installed Fusion you can start the process of creating content in Markdown files and querying the files through the Models.
For example, now we are going to create articles in Markdown format and we will parse and query them like you can do it with a database.

Use `fusion:start` when setting up Fusion or inspecting a content directory; it shows what content is present and suggests the next command to run:

```shell
php artisan fusion:start
```

By default, Fusion uses `resources/content`. You can inspect a different directory with the `--dir` option:

```shell
php artisan fusion:start --dir=path/to/content
```

If the directory does not exist, the command can create it for you. It then reports the available content types and Markdown files and suggests the relevant check or model-generation command.

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

use HiFolks\Fusion\Traits\FusionBaseModelTrait;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use FusionBaseModelTrait;

    public function frontmatterFields(): array
    {
        return [
            "excerpt"
        ];
    }

}

```
Consider that:
- the class has to extend Laravel's Eloquent `Model` and use `FusionBaseModelTrait`;
- you have to implement the `frontmatterFields()` function for returning the list of the field names used in the frontmatter header.

### Creating automatically the Model

Use `fusion:sync-model` when you want a starter Eloquent model generated from the content directory and its discovered Frontmatter fields, rather than writing the model boilerplate by hand:

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

### Content caching

Fusion caches the rows it builds from Markdown files. This includes parsed frontmatter fields and the rendered HTML body, along with the file paths used by the model. The cache is used while Fusion loads the model's content rows; it does not cache the results of Eloquent queries.

Fusion uses Laravel's configured default cache store, so the cache backend follows your application's normal `config/cache.php` settings. Cached rows expire after one hour by default. You can change the lifetime in seconds with the `cache_ttl` option in `config/fusion.php` or the `FUSION_CACHE_TTL` environment variable:

```dotenv
FUSION_CACHE_TTL=7200
```

This example keeps cached rows for two hours.

To keep the cached content in sync with your files, Fusion fingerprints the content directory on each row-cache lookup. Adding, removing, renaming, or editing a file produces a different cache key, so Fusion resolves the rows again. A warm cache avoids reparsing frontmatter and converting/highlighting Markdown, but it still scans and hashes the files. Eloquent filtering and sorting, such as `where()` and `orderBy()`, also still run normally and are not cached by Fusion.

This cache is most useful when loading a content set repeatedly, especially when parsing and rendering many Markdown files. The actual improvement depends on the size and complexity of your content and the cache store in use; the fingerprinting work means a cache hit is not a zero-cost lookup.

## Advanced Usage
Fusion integrates with Laravel's existing features. The following sections show how to use them with Markdown content:

- Refreshing the browser when Markdown content changes during development;
- Casting Frontmatter dates and structured values on Eloquent models;
- Inspecting Frontmatter fields with the `fusion:check` command.

### Refreshing the page when Markdown changes

Laravel's Vite plugin can trigger a full-page browser refresh when a watched file changes. This happens during development while the Vite dev server is running with `npm run dev`; it is not a production feature. Add a `refresh` path in `vite.config.js` for the directory containing your Markdown files:

```js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: [
                {
                    paths: ['resources/content/**'],
                },
            ],
        }),
    ],
});
```

The `paths` are relative to your project root. This example watches `resources/content`; if you configured Fusion to use a different content directory, update the path to match it.

> If you need to set up Vite asset bundling, see the [Laravel 13 Vite documentation](https://laravel.com/docs/13.x/vite).

### Casting frontmatter values

Fusion models are Eloquent models, so you can use Laravel's normal attribute casts to get convenient PHP values when reading Frontmatter fields. Fusion does not infer a field's type: declare the field in `frontmatterFields()` and add the corresponding cast to your model's `casts()` method.

### Casting dates

Add the date to the Markdown Frontmatter, using a format such as `YYYY-MM-DD`:

```markdown
---
date: 2023-01-26
title: Example article
---
```

Declare `date` in `frontmatterFields()` and cast it in the model:

```php
public function frontmatterFields(): array
{
    return ['title', 'date'];
}

protected function casts(): array
{
    return [
        'date' => 'date:Y-m-d',
    ];
}
```

When you read `$article->date`, Laravel returns a Carbon instance. The `:Y-m-d` suffix controls how the value is serialized to an array or JSON; to format it in a Blade view, use `{{ $article->date->format('Y-m-d') }}`. For Frontmatter values that include a time, use the `datetime` or `immutable_datetime` cast instead.

### Casting nested frontmatter as a collection

For structured values such as a list of tags or metadata, use a YAML array in the Frontmatter:

```markdown
---
title: Example article
head:
  - tag: title
    content: Custom about title
  - tag: title2
    content: Custom about title2
---
```

Add `head` to `frontmatterFields()` and cast it as a collection:

```php
public function frontmatterFields(): array
{
    return ['title', 'head'];
}

protected function casts(): array
{
    return [
        'head' => 'collection',
    ];
}
```

Now `$article->head` is an `Illuminate\Support\Collection`; each item is an array. In a Blade view, loop through it and access its fields:

```blade
@foreach ($article->head as $item)
    <div>{{ $item['tag'] }}: {{ $item['content'] }}</div>
@endforeach
```

In both examples, the field names in `casts()` must match the Markdown Frontmatter and be included in `frontmatterFields()`. Fusion encodes Frontmatter arrays for its in-memory model, and Laravel applies the declared casts when you access the Eloquent attributes. Casts affect attribute access; they do not add type or required-field validation to `fusion:check`.

For more cast types and options, see [Laravel's Eloquent attribute casting documentation](https://laravel.com/docs/13.x/eloquent-mutators#attribute-casting).

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

Use `fusion:check` to see which Frontmatter fields are present in your Markdown files before defining or debugging a model. By default, it gives a quick inventory:

```shell
php artisan fusion:check
```

For a deeper check, use `--validate`. Fusion will also render each Markdown body using its Markdown and syntax-highlighting extensions. This takes more work than the default inventory and is useful when checking content in CI:

```shell
php artisan fusion:check --validate
```

Deep validation reports frontmatter parsing and Markdown rendering errors. It does not enforce required fields or field types, since those rules are application-specific and Fusion does not define a content schema.

For CI scripts and AI agents, add `--format=json`. JSON mode emits only the report, with a summary and findings. When `--validate` is used, the report lists invalid files by default instead of repeating every successful file. Use `--include-valid` to include valid file records as well. Without `--validate`, JSON mode continues to list every inspected file and its Frontmatter fields. Errors produce a non-zero exit code.

```shell
php artisan fusion:check --validate --format=json
```

To run deep validation on every pull request, add a step like this to your GitHub Actions workflow after installing your application's Composer dependencies. The command exits with a non-zero status when it finds invalid Markdown, so GitHub Actions will fail the job:

```yaml
name: Validate Markdown

on: [pull_request]

jobs:
  validate-markdown:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'
      - run: composer install --no-progress --prefer-dist
      - run: php artisan fusion:check --validate --format=json
```

To include valid files in that validation report:

```shell
php artisan fusion:check --validate --include-valid --format=json
```

To limit a check to Markdown files Git currently reports as modified, staged, or untracked, add `--changed`:

```shell
php artisan fusion:check --validate --changed --format=json
```

This is useful during local development when you only want to inspect your current changes. Deleted files are skipped. It uses the working tree status, so it does not select files based on commits in a pull request; CI jobs that need branch-based filtering should provide that comparison separately.

The report includes a status (`ok` or `failed`), per-file Frontmatter fields, and findings with a severity, rule, and message. For example:

```json
{
  "status": "failed",
  "summary": { "files": 1, "valid": 0, "invalid": 1, "errors": 1, "warnings": 0 },
  "files": [
    { "file": "article/example.md", "fields": [], "valid": false }
  ],
  "findings": [
    {
      "file": "article/example.md",
      "severity": "error",
      "rule": "frontmatter.parse",
      "message": "Frontmatter could not be parsed."
    }
  ]
}
```

### Using the Check Model command

Use `fusion:check-model` to find the model class to use and catch setup issues before querying content. Without options, it lists classes discovered under `app/Models` and shows whether each is Fusion-compatible:

```shell
php artisan fusion:check-model
```

To scan a different model directory, pass it with `--dir`. Fusion reads the namespace declared in each PHP file, so the classes must be autoloadable by your application:

```shell
php artisan fusion:check-model --dir=app/Domain/Models
```

To check a specific class, pass its fully qualified name with `--model`:


```shell
php artisan fusion:check-model --model="App\Models\Article"
```

The specific-model check verifies that the class exists, extends Eloquent's `Model`, and uses `FusionBaseModelTrait`. It exits with a non-zero status if any check fails, so it can also be used in scripts and CI.

For a correctly configured model, the output looks like this:

```text
Checking the Model
App\Models\Article Exists
App\Models\Article Extends correctly the class Illuminate\Database\Eloquent\Model
App\Models\Article Uses correctly the trait HiFolks\Fusion\Traits\FusionBaseModelTrait
```

## Upgrading from version 0.* to 1.*

With the version 1 we simplified and we reduced the requirements of a Model class.
Now you can extend the classic Model sub class for Eloquent Models and just use the traits FusionBaseModelTrait for enabling the "hidrate" logic provided by Fusion.

```php
<?php
namespace App\Models;
use HiFolks\Fusion\Traits\FusionBaseModelTrait;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    use FusionBaseModelTrait;

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
- [Tempest Highlight](https://github.com/tempestphp/highlight): Fast, extensible, server-side syntax highlighting;
- [yaml-front-matter](https://github.com/spatie/yaml-front-matter): A to the point yaml front matter parser;
- [PestPHP](https://github.com/pestphp/pest): Pest is an elegant PHP testing Framework with a focus on simplicity, meticulously designed to bring back the joy of testing in PHP. Under the hood PestPHP uses [PHPUnit, the PHP testing framework](https://phpunit.de);
- [RectorPHP](https://github.com/rectorphp/rector): Instant Upgrades and Automated Refactoring of any PHP 5.3+ code;
- [Laravel Pint](https://github.com/laravel/pint): an opinionated PHP code style fixer for minimalists. Under the hood, Laravel Pint uses [PHP-CS-Fixer](https://github.com/PHP-CS-Fixer/PHP-CS-Fixer) the open source tool to automatically fix PHP Coding Standards issues.



## Laravel Package Boilerplate

This package was generated using the [Laravel Package Boilerplate](https://laravelpackageboilerplate.com).
