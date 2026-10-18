<?php

declare(strict_types=1);

namespace HiFolks\Fusion\Console\Commands;

use HiFolks\Fusion\Traits\FusionBaseModelTrait;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\Filesystem;

/** @package HiFolks\Fusion\Console\Commands */
class CheckModel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fusion:check-model {--model=} {--dir= : Directory to scan when --model is not provided}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List available models or check a specific model for Fusion compatibility';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $model = $this->option('model');
        if ($model === null || $model === '') {
            return $this->listModels($this->option('dir') ?: app_path('Models'));
        }

        return $this->checkModel($model);
    }

    private function checkModel(string $model): int
    {
        $this->output->title('Checking the Model');

        if (class_exists($model)) {
            $this->components->twoColumnDetail(
                '<info>' . $model . '</info>',
                '<info>Exists</info>',
            );

        } else {
            $this->components->twoColumnDetail(
                '<info>' . $model . '</info>',
                "<error>Doesn't exist</error>",
            );

            return Command::INVALID;
        }

        $isEloquentModel = is_subclass_of($model, Model::class);
        if ($isEloquentModel) {
            $this->components->twoColumnDetail(
                '<info>' . $model . '</info>',
                '<info>Extends correctly the class ' . Model::class . '</info>',
            );

        } else {
            $this->components->twoColumnDetail(
                '<info>' . $model . '</info>',
                '<error>Does not extend correctly the class ' . Model::class . '</error>',
            );
        }

        $usesFusionTrait = trait_exists(FusionBaseModelTrait::class)
            && in_array(FusionBaseModelTrait::class, class_uses_recursive($model));
        if ($usesFusionTrait) {
            $this->components->twoColumnDetail(
                '<info>' . $model . '</info>',
                '<info>Uses correctly the trait ' . FusionBaseModelTrait::class . '</info>',
            );
        } else {
            $this->components->twoColumnDetail(
                '<info>' . $model . '</info>',
                '<error>Does not use correctly the trait ' . FusionBaseModelTrait::class . '</error>',
            );

        }

        return $isEloquentModel && $usesFusionTrait
            ? Command::SUCCESS
            : Command::FAILURE;

    }

    private function listModels(string $modelsDirectory): int
    {
        $this->output->title('Models available in ' . $modelsDirectory);

        if (! is_dir($modelsDirectory)) {
            $this->info('Model directory not found: ' . $modelsDirectory);

            return Command::SUCCESS;
        }

        $files = new Filesystem()->allFiles($modelsDirectory);
        usort($files, static fn($left, $right): int => $left->getRelativePathname() <=> $right->getRelativePathname());

        $models = [];
        foreach ($files as $file) {
            if (strtolower($file->getExtension()) !== 'php') {
                continue;
            }

            foreach ($this->declaredClasses($file->getPathname()) as $model) {
                if (! class_exists($model)) {
                    continue;
                }

                $isEloquentModel = is_subclass_of($model, Model::class);
                $usesFusionTrait = in_array(FusionBaseModelTrait::class, class_uses_recursive($model));
                $status = match (true) {
                    ! $isEloquentModel => 'Not an Eloquent model',
                    ! $usesFusionTrait => 'Missing FusionBaseModelTrait',
                    default => 'Fusion compatible',
                };

                $models[] = [$model, $status];
            }
        }

        if ($models === []) {
            $this->info('No model classes were found in ' . $modelsDirectory . '.');
            $this->line('Use --dir=path/to/models to scan another directory.');
            $this->line('Use --model="App\\Models\\YourModel" to check a specific class.');

            return Command::SUCCESS;
        }

        foreach ($models as [$model, $status]) {
            $statusLine = $status === 'Fusion compatible'
                ? '<info>' . $status . '</info>'
                : '<comment>' . $status . '</comment>';
            $this->components->twoColumnDetail($model, $statusLine);
        }

        $this->newLine();
        $this->line('Use --dir=path/to/models to scan another directory.');
        $this->line('Use --model="App\\Models\\YourModel" to check a specific class. Quote the class name because backslashes can be interpreted by the shell.');

        return Command::SUCCESS;
    }

    /**
     * @return array<int, string>
     */
    private function declaredClasses(string $path): array
    {
        $source = file_get_contents($path);
        if ($source === false) {
            return [];
        }

        try {
            $tokens = token_get_all($source, TOKEN_PARSE);
        } catch (\ParseError) {
            return [];
        }

        $classes = [];
        $namespace = '';
        $tokenCount = count($tokens);

        for ($index = 0; $index < $tokenCount; ++$index) {
            $token = $tokens[$index];
            if (! is_array($token)) {
                continue;
            }

            if ($token[0] === T_NAMESPACE) {
                $namespace = '';
                for ($namespaceIndex = $index + 1; $namespaceIndex < $tokenCount; ++$namespaceIndex) {
                    $namespaceToken = $tokens[$namespaceIndex];
                    if ($namespaceToken === ';' || $namespaceToken === '{') {
                        $index = $namespaceIndex;
                        break;
                    }

                    if (is_array($namespaceToken) && ! in_array($namespaceToken[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                        $namespace .= $namespaceToken[1];
                    }
                }

                continue;
            }

            if ($token[0] !== T_CLASS || $this->isAnonymousClassOrClassConstant($tokens, $index)) {
                continue;
            }

            for ($classIndex = $index + 1; $classIndex < $tokenCount; ++$classIndex) {
                $classToken = $tokens[$classIndex];
                if (is_array($classToken) && in_array($classToken[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                    continue;
                }

                if (is_array($classToken) && $classToken[0] === T_STRING) {
                    $classes[] = ($namespace === '' ? '' : $namespace . '\\') . $classToken[1];
                }

                break;
            }
        }

        return $classes;
    }

    /**
     * @param  array<int, array{0: int, 1: string, 2: int}|string>  $tokens
     */
    private function isAnonymousClassOrClassConstant(array $tokens, int $classIndex): bool
    {
        for ($index = $classIndex - 1; $index >= 0; --$index) {
            $token = $tokens[$index];
            if (is_array($token) && in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }

            return $token === '::'
                || (is_array($token) && in_array($token[0], [T_DOUBLE_COLON, T_NEW], true));
        }

        return false;
    }
}
