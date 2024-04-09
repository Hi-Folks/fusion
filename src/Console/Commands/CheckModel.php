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
        $this->info('Parsing the Model File');
        $model = $this->option('model');
        if (is_null($model)) {
            $model = 'App\Models\Article';
        }

        if (class_exists($model)) {
            $this->info(sprintf('Class %s exists', $model));
        } else {
            $this->error(sprintf('Class %s does not exist', $model));

            return Command::INVALID;
        }

        if (is_subclass_of($model, FusionBaseModel::class)) {
            $this->info($model.' extends correctly the class '.FusionBaseModel::class);
        } else {
            $this->warn($model." doesn't extend correctly the class ".FusionBaseModel::class);
        }

        if (trait_exists(FusionModelTrait::class) && in_array(FusionModelTrait::class, class_uses($model))) {
            $this->info($model.' uses '.FusionModelTrait::class);
        } else {
            $this->warn($model.' does not use '.FusionModelTrait::class);

        }

        return Command::SUCCESS;

    }
}
