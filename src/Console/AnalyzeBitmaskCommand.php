<?php

declare(strict_types=1);

namespace DFiks\UnPerm\Console;

use DFiks\UnPerm\Models\Action;
use DFiks\UnPerm\Models\Group;
use DFiks\UnPerm\Models\Role;
use DFiks\UnPerm\Support\BitmaskOptimizer;
use Illuminate\Console\Command;

class AnalyzeBitmaskCommand extends Command
{
    protected $signature = 'unperm:analyze-bitmask';

    protected $description = 'Analyze bitmask storage efficiency and show optimization potential';

    public function handle(): int
    {
        $this->info('Analyzing bitmask storage efficiency...');
        $this->newLine();

        $actionsData = $this->analyzeTable(Action::class, 'Actions');
        $rolesData = $this->analyzeTable(Role::class, 'Roles');
        $groupsData = $this->analyzeTable(Group::class, 'Groups');

        $this->newLine();
        $this->displaySummary($actionsData, $rolesData, $groupsData);

        return self::SUCCESS;
    }

    protected function analyzeTable(string $model, string $label): array
    {
        $records = $model::all();

        if ($records->isEmpty()) {
            $this->warn("No {$label} found.");

            return [
                'count' => 0,
                'total_size' => 0,
                'compressed_size' => 0,
                'savings' => 0,
            ];
        }

        $totalOriginal = 0;
        $totalCompressed = 0;
        $details = [];

        foreach ($records as $record) {
            if ($record->bitmask === '0') {
                continue;
            }

            $stats = BitmaskOptimizer::getStats($record->bitmask);
            $totalOriginal += $stats['total_size'];
            $totalCompressed += $stats['compressed_size'];

            $details[] = [
                'name' => $record->name,
                'slug' => $record->slug,
                'bits_set' => $stats['bits_set'],
                'original' => $stats['total_size'],
                'compressed' => $stats['compressed_size'],
                'ratio' => round($stats['compression_ratio'], 1),
            ];
        }

        // Сортируем по экономии
        usort($details, fn ($a, $b) => $b['ratio'] <=> $a['ratio']);

        $this->displayTable($label, $details);

        $savings = $totalOriginal > 0
            ? round((1 - ($totalCompressed / $totalOriginal)) * 100, 1)
            : 0;

        return [
            'count' => $records->count(),
            'total_size' => $totalOriginal,
            'compressed_size' => $totalCompressed,
            'savings' => $savings,
        ];
    }

    protected function displayTable(string $label, array $details): void
    {
        if (empty($details)) {
            return;
        }

        $this->line("<fg=cyan>═══ {$label} ═══</>");

        // Показываем топ 10 или все если меньше
        $limit = min(10, count($details));
        $display = array_slice($details, 0, $limit);

        $this->table(
            ['Name', 'Slug', 'Permissions', 'Original', 'Compressed', 'Savings %'],
            array_map(fn ($d) => [
                $d['name'],
                $d['slug'],
                $d['bits_set'],
                $this->formatBytes($d['original']),
                $this->formatBytes($d['compressed']),
                $this->colorizeRatio($d['ratio']),
            ], $display)
        );

        if (count($details) > $limit) {
            $this->line('  ... and ' . (count($details) - $limit) . ' more');
        }

        $this->newLine();
    }

    protected function displaySummary(array $actions, array $roles, array $groups): void
    {
        $totalOriginal = $actions['total_size'] + $roles['total_size'] + $groups['total_size'];
        $totalCompressed = $actions['compressed_size'] + $roles['compressed_size'] + $groups['compressed_size'];

        $overallSavings = $totalOriginal > 0
            ? round((1 - ($totalCompressed / $totalOriginal)) * 100, 1)
            : 0;

        $this->line('<fg=yellow>═══════════════════════════════════════════════</>');
        $this->line('<fg=yellow>             SUMMARY                          </>');
        $this->line('<fg=yellow>═══════════════════════════════════════════════</>');
        $this->newLine();

        $this->table(
            ['Type', 'Count', 'Original Size', 'Compressed Size', 'Savings %'],
            [
                ['Actions', $actions['count'], $this->formatBytes($actions['total_size']), $this->formatBytes($actions['compressed_size']), $this->colorizeRatio($actions['savings'])],
                ['Roles', $roles['count'], $this->formatBytes($roles['total_size']), $this->formatBytes($roles['compressed_size']), $this->colorizeRatio($roles['savings'])],
                ['Groups', $groups['count'], $this->formatBytes($groups['total_size']), $this->formatBytes($groups['compressed_size']), $this->colorizeRatio($groups['savings'])],
                ['<fg=cyan>TOTAL</>', '<fg=cyan>' . ($actions['count'] + $roles['count'] + $groups['count']) . '</>', '<fg=cyan>' . $this->formatBytes($totalOriginal) . '</>', '<fg=cyan>' . $this->formatBytes($totalCompressed) . '</>', $this->colorizeRatio($overallSavings, true)],
            ]
        );

        $this->newLine();

        if ($overallSavings > 50) {
            $this->info('💡 Significant savings possible! Consider implementing BitmaskOptimizer.');
            $this->comment('   For sparse permissions (few assigned), compression can save ' . round($overallSavings) . '% of storage.');
        } elseif ($overallSavings > 20) {
            $this->line('✓ Moderate savings available with optimization.');
        } else {
            $this->line('✓ Current storage is already efficient for dense permission assignments.');
        }
    }

    protected function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }

        if ($bytes < 1024 * 1024) {
            return round($bytes / 1024, 1) . ' KB';
        }

        return round($bytes / (1024 * 1024), 1) . ' MB';
    }

    protected function colorizeRatio(float $ratio, bool $bold = false): string
    {
        $formatted = $ratio . '%';

        if ($bold) {
            $formatted = "<fg=cyan;options=bold>{$formatted}</>";
        } elseif ($ratio > 75) {
            $formatted = "<fg=green>{$formatted}</>";
        } elseif ($ratio > 50) {
            $formatted = "<fg=yellow>{$formatted}</>";
        } elseif ($ratio > 25) {
            $formatted = "<fg=white>{$formatted}</>";
        } else {
            $formatted = "<fg=gray>{$formatted}</>";
        }

        return $formatted;
    }
}
