<?php

namespace HiFolks\Fusion\Console\Commands;

use HiFolks\Fusion\Models\FusionBaseModel;
use HiFolks\Fusion\Traits\FusionModelTrait;
use Illuminate\Console\Command;

class CheckModel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fusion:check-model {--model=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check the Model files';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {

        $this->output->title('Parsing the Model File');
        $model = $this->option('model');
        if (is_null($model)) {
            $model = 'App\Models\Article';
        }

        if (class_exists($model)) {
            $this->components->twoColumnDetail(
                '<info>'.$model.'</info>',
                '<info>Exists</info>');

        } else {
            $this->components->twoColumnDetail(
                '<info>'.$model.'</info>',
                "<error>Doesn't exist</error>");

            return Command::INVALID;
        }

        if (is_subclass_of($model, FusionBaseModel::class)) {
            $this->components->twoColumnDetail(
                '<info>'.$model.'</info>',
                '<info>Extends correctly the class '.FusionBaseModel::class.'</info>');

        } else {
            $this->components->twoColumnDetail(
                '<info>'.$model.'</info>',
                '<error>Does not extend correctly the class '.FusionBaseModel::class.'</error>');
        }

        if (trait_exists(FusionModelTrait::class) && in_array(FusionModelTrait::class, class_uses($model))) {
            $this->components->twoColumnDetail(
                '<info>'.$model.'</info>',
                '<info>Uses correctly the trait '.FusionModelTrait::class.'</info>');
        } else {
            $this->components->twoColumnDetail(
                '<info>'.$model.'</info>',
                '<error>Does not use correctly the trait '.FusionModelTrait::class.'</error>');

        }

        return Command::SUCCESS;

    }
}
