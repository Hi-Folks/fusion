# Changelog

## 0.0.11 - WIP
- Adding check if content directory is present with the `php artisan fusion:check` command

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
