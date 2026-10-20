# Changelog

## 1.2.3 - 2026-10-20
- Fixed `FUSION_CONTENT_DIRECTORY` so relative paths resolve from the Laravel application base directory; absolute paths are also supported
- Documented the default, shared-root, and model-specific content folder options, including overriding `getResourceFolder()` and its runtime limitation

## 1.2.2 - 2026-10-19
- Added `--changed` to `fusion:check` to inspect modified, staged, and untracked Markdown files reported by Git
- Added `--base` to `fusion:check --changed` to validate committed Markdown changes relative to a Git ref, including in CI
- Added opt-in Frontmatter validation to `fusion:check` using Laravel validation rules defined by a Fusion model
- Added `--warn-unknown-fields` to report undeclared Frontmatter keys as non-blocking warnings
- Made `frontmatterRules()` optional; `fusion:check` applies model validation rules only when the model defines the method
- Added a non-blocking report warning when model-based Frontmatter validation is skipped because `frontmatterRules()` is not defined
- Added `--interactive` to guide users through quick inventory, deep validation, model selection, and unknown-field warnings
- Used Laravel Prompts selectors, a searchable fully qualified model picker, and yes/no confirmations to make `fusion:check --interactive` easier to navigate
- Added `--interactive` to `fusion:check-model` to search discovered classes, enter a model class manually, or list models
- Documented changed-file checking and its working-tree scope in the README
- Documented base-ref checking for pull-request CI workflows
- Documented model-based Frontmatter validation and its use in CI
- Documented optional warnings for undeclared Frontmatter keys

## 1.2.1 - 2026-10-18
- Added caching for parsed Markdown content rows using Laravel's configured cache store
- Added configurable cache lifetime via `FUSION_CACHE_TTL` (defaults to one hour)
- Documented cache behavior, invalidation, and configuration in the README
- Added opt-in deep Markdown validation and JSON output to `fusion:check`, with concise validation reports and `--include-valid` for full listings
- Fixed `fusion:check-model` to return a failure status when a model is incompatible with Fusion
- Changed `fusion:check-model` without `--model` to list classes discovered under `app/Models`
- Added `--dir` to `fusion:check-model` to scan a custom model directory

## 1.2.0 - 2026-10-17
- Added the interactive `php artisan fusion:start` command to create or inspect the content directory and guide users through the next setup steps
- Added support for selecting the content directory with the `--dir` option
- Improved command output and content-directory handling
- Added support for Laravel 13 and PHP 8.4–8.5
- Replaced the abandoned `spatie/commonmark-highlighter` package with `tempest/highlight`

## 1.1.0 - 2025-10-19
- Introducing the trait `FusionBaseModelTrait`

## 1.0.1 - 2025-10-18
- Adding Static Code Analysis via PHPStan + Larastan (Level 6)
- Adding env variable FUSION_CONTENT_DIRECTORY for setting the path of content folder (default is app/resources/content)
- Adding tests for hydrated model
- Simplify and improving the DX for creating Models

## 1.0.0 - 2025-10-17
- Upgrading the support for Laravel 12
- Adding check if content directory is present with the `php artisan fusion:check` command
- Upgrading Rector 2

## 0.0.10 - 2024-05-01
- Adding support for code syntax highlights

## 0.0.9 - 2024-04-20
- Additional check for Sync Model command:
  - check if no front matter fields are found in the Markdown folder
  - show the full qualified name for the Model class
  - check if the model class already exists
- Introducing tests via PestPHP

## 0.0.8 - 2024-04-17
- Improved Check Model command output

## 0.0.7 - 2024-04-14
- customized slug

## 0.0.6 - 2024-04-12
- Improved Check Markdown command output
- Improved Sync Model command output

## 0.0.5 - 2024-04-10
- Added Sync Model command for automatically generating the Model based on Markdown content

## 0.0.4 - 2024-04-09
- Added Check Model command via `php artisan fusion:check-model`

## 0.0.3 - 2024-04-08
- Added Check Markdown command via `php artisan fusion:check`
- Improved Frontmatter field management, adding dates field and array fields
- Updated documentation in the Readme file

## 0.0.2 - 2024-04-07
- Added Rector PHP https://github.com/rectorphp/rector
- Updated Readme for real-time updates
- Improved GitHub Actions workflows by adding style checks and rector check

## 0.0.1 - 2024-04-06

- Welcome Fusion! First *preview* release
