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
    <img src="https://repository-images.githubusercontent.com/781827813/4b0e855d-4935-42d0-83d4-49cfa453b421" alt="Laravel package that enhances Eloquent models to facilitate the management of structured, database-free content through Markdown files with Frontmatter.">
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

### Configuring the content directory

By default, Fusion reads content from `resources/content`, then selects a subdirectory based on the model name. For example, `App\Models\Article` reads Markdown files from `resources/content/article`. This keeps content in Laravel's conventional resources directory without additional configuration.

To keep content somewhere else—for example, alongside project documentation or in a shared content folder—set `FUSION_CONTENT_DIRECTORY` to the root directory containing the model-specific subdirectories:

```dotenv
FUSION_CONTENT_DIRECTORY=content/markdown
```

Relative paths are resolved from the Laravel application's base directory. With this setting, an `Article` model reads from `content/markdown/article`. Absolute paths are also supported:

```dotenv
FUSION_CONTENT_DIRECTORY=/srv/my-app/content
```

In that case, Fusion reads articles from `/srv/my-app/content/article`. The configured value is a shared root; Fusion appends the model's directory name. If you use Laravel's configuration cache, rebuild it after changing the environment value with `php artisan config:cache`.

### Customizing a model's content folder

Choose the option that matches your setup:

| Option | How | Pros | Cons |
| --- | --- | --- | --- |
| Default | Put files in `resources/content/{model}`. | No configuration; follows Laravel conventions. | Content must live under `resources/content`. |
| Shared custom root | Set `FUSION_CONTENT_DIRECTORY`, as above. | One setting moves all model content; supports relative or absolute paths. | Fusion still appends each model's directory name. |
| Model-specific folder | Override `getResourceFolder()` in the model. | Lets a model read from a uniquely named or external folder. | Requires code in each customized model; the returned path must be a directory. |

For example, to read `Article` files from `docs/posts` regardless of the shared root:

```php
public function getResourceFolder(): string
{
    return base_path('docs/posts');
}
```

`getResourceFolder()` is evaluated when Fusion loads the model's rows. Because Fusion uses Sushi to build a table per model class, changing the returned path after that table has been initialized is not a supported way to switch content folders at runtime.

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

Run `fusion:sync-model` after creating a content directory and its Markdown files when you want Fusion to discover their Frontmatter fields and scaffold the Eloquent model for you. This saves you from copying the field names into `frontmatterFields()` by hand. The command is optional; you can create the model manually if you prefer.

```shell
php artisan fusion:sync-model --path=resources/content/article --create-model
```

The `--path` option points to one content-type directory, such as `resources/content/article`. If omitted, the command uses `resources/content/article`. The directory's name determines the model name (`article` becomes `Article`), and the generated class is placed at `app/Models/Article.php` in the `App\Models` namespace.

The command reads Frontmatter from files directly inside that directory (it does not scan nested directories), combines the discovered keys, and places them in the generated `frontmatterFields()` method. It creates a starter model using `FusionBaseModelTrait`; it does not infer casts or validation rules, so add `casts()` or `frontmatterRules()` yourself when needed.

Without `--create-model`, the command only reports the detected model name, destination path, and fields. With `--create-model`, it generates the model only if that file does not already exist; it will not overwrite an existing model. Review the generated file before using it, especially if your content has no Frontmatter fields.

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
date: "2023-01-26"
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

For a guided check, run the command with `--interactive`:

```shell
php artisan fusion:check --interactive
```

The guided flow uses Laravel Prompts: arrow-key selectors let you choose a quick field inventory or deep validation, and a searchable picker lets you find model classes under `app/Models` by their fully qualified names (for example, `App\Models\Article`). You can also enter a model class manually or skip model-based validation. For a selected model, yes/no prompts let you enable warnings for fields not declared in `frontmatterFields()` and choose whether to include valid files in the output. You can still pass `--dir` to select a content directory. Interactive mode is for a terminal and human-readable output; use the regular options (without `--interactive`) for scripts and CI. It cannot be combined with `--format=json`, because prompts would make the output invalid JSON.

For a deeper check, use `--validate`. Fusion will also render each Markdown body using its Markdown and syntax-highlighting extensions. This takes more work than the default inventory and is useful when checking content in CI:

```shell
php artisan fusion:check --validate
```

Deep validation reports Frontmatter parsing and Markdown rendering errors. To also check whether Frontmatter conforms to your application's content schema—for example, whether required fields are present or values have the expected type—pass a Fusion model with `--model` and define `frontmatterRules()` on that model. This is useful for checking editorial content before publishing or catching invalid content in CI. The method is optional: if the model does not define it, Fusion still performs the normal deep check but does not enforce required fields or value types. It reports a non-blocking `frontmatter.rules_not_defined` warning so it is clear that schema validation was skipped. Those rules are application-specific.

To apply schema validation, define a public `frontmatterRules()` method and return standard Laravel validation rules:

```php
public function frontmatterRules(): array
{
    return [
        'title' => ['required', 'string', 'max:255'],
        'date' => ['required', 'date'],
        'tags' => ['sometimes', 'array'],
        'tags.*' => ['string'],
    ];
}
```

Quote date values in YAML when you want Laravel's `date` rule to validate them as date strings (for example, `date: "2026-10-19"`). Unquoted YAML date scalars may be parsed as timestamps before validation.

Then pass that model to the deep check:

```shell
php artisan fusion:check --validate --model="App\Models\Article" --format=json
```

When `frontmatterRules()` is present, Fusion parses each Markdown file's Frontmatter and passes the resulting data and the model's rules to Laravel's `Validator`. Validation messages are reported against the file that failed, and any validation error produces a non-zero exit code. The `frontmatterRules()` method is a Fusion convention for supplying rules; Laravel provides the validation engine and rule syntax. This is separate from Eloquent casts: casts transform values when you access model attributes, while validation rules check the parsed Frontmatter values. Database-backed rules such as `unique` can be used too, but may query the database for each file.

To catch misspelled or unexpected Frontmatter keys, add `--warn-unknown-fields`:

```shell
php artisan fusion:check --validate --model="App\Models\Article" --warn-unknown-fields --format=json
```

Fusion compares each file's top-level Frontmatter keys with the model's `frontmatterFields()` list. For example, if the model declares `published` but a file contains `publised`, the report includes a `frontmatter.unknown_field` warning that identifies the file and field and suggests correcting the key or declaring it. Warnings do not make a file invalid or cause a non-zero exit code; this is a heads-up for content typos, not a required-field check. Missing required values should be handled with Laravel rules such as `required` in `frontmatterRules()`. This option requires both `--model` and `--validate`, but does not require `frontmatterRules()`; it is off by default.

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

To limit a check to Markdown files Git currently reports as modified, staged, or untracked in your working tree, add `--changed`:

```shell
php artisan fusion:check --validate --changed --format=json
```

This is useful during local development when you only want to inspect files you are currently editing. Deleted files are skipped. By default, `--changed` looks at the working tree; it does not include changes that have already been committed to a branch.

For CI, such as a pull-request workflow, use `--base` to check Markdown files committed on the current branch since it diverged from a base ref. This avoids scanning a large content library when a pull request changes only a few files:

```shell
php artisan fusion:check --validate --changed --base="origin/main" --model="App\Models\Article" --format=json
```

Git compares `origin/main...HEAD` (the changes since the merge base). The base ref must be available in the checkout; in GitHub Actions, use `actions/checkout` with sufficient history (for example, `fetch-depth: 0`) or fetch the base branch before running the command. This mode checks committed branch changes only: uncommitted and untracked files are not included. Deleted files are skipped because there is no Markdown file left to validate. Use `--changed` without `--base` for local working-tree changes.

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

To choose and check a model interactively, use `--interactive`:

```shell
php artisan fusion:check-model --interactive
```

Fusion provides a searchable picker of fully qualified model class names discovered under `app/Models`. You can also enter a class name manually or choose to print the full model list instead. Use `--dir` with `--interactive` to search a different directory. This mode requires a terminal; for scripts and CI, pass `--model` directly or run the command without `--interactive` to list models.

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
