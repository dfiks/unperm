<?php

declare(strict_types=1);

namespace DFiks\UnPerm\Console;

use DFiks\UnPerm\Services\ModelDiscovery;
use Illuminate\Console\Command;

class ListModelsCommand extends Command
{
    protected $signature = 'unperm:list-models';

    protected $description = 'Показать все модели с HasPermissions trait';

    public function handle(): int
    {
        $this->info('Поиск моделей с HasPermissions trait...');
        $this->newLine();

        $discovery = new ModelDiscovery();
        $models = $discovery->findModelsWithPermissions();

        if (empty($models)) {
            $this->warn('Не найдено моделей с HasPermissions trait.');
            $this->newLine();
            $this->line('Добавьте trait к вашей модели:');
            $this->line('');
            $this->line('  use DFiks\UnPerm\Traits\HasPermissions;');
            $this->line('');
            $this->line('  class User extends Model {');
            $this->line('      use HasPermissions;');
            $this->line('  }');
            $this->newLine();

            return self::FAILURE;
        }

        $this->info('Найдено моделей: ' . count($models));
        $this->newLine();

        $data = [];
        foreach ($models as $model) {
            $data[] = [
                $model['name'],
                $model['class'],
                $model['table'],
            ];
        }

        $this->table(
            ['Название', 'Класс', 'Таблица'],
            $data
        );

        $this->newLine();
        $this->comment('Совет: Вы можете явно указать модели в config/unperm.php:');
        $this->line("  'user_models' => [");
        foreach ($models as $model) {
            $this->line("      {$model['class']}::class,");
        }
        $this->line('  ],');
        $this->newLine();

        return self::SUCCESS;
    }
}
