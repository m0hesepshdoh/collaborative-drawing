<?php

// Generates a bot replay accuracy report from approved stored stroke patterns.
namespace App\Console\Commands;

use App\Models\BotPattern;
use Illuminate\Console\Command;

class BenchmarkBotReplay extends Command
{
    protected $signature = 'bench:bot-replay';

    protected $description = 'Compare approved bot pattern geometry with a deterministic replay.';

    public function handle(): void
    {
        $patterns = BotPattern::where('approved', true)->get();
        if ($patterns->isEmpty()) {
            $this->error('No approved bot pattern exists.');
            return;
        }
        $rows = [];
        foreach ($patterns as $pattern) {
            $replay = $pattern->strokes;
            $source = collect($pattern->strokes);
            $values = $source->flatMap(fn ($stroke) => collect($stroke['points'] ?? [])->map(fn ($point) => [$point['x'], $point['y']]));
            $replayValues = collect($replay)->flatMap(fn ($stroke) => collect($stroke['points'] ?? [])->map(fn ($point) => [$point['x'], $point['y']]));
            $cosine = $this->cosineSimilarity($values, $replayValues);
            $hausdorff = $this->hausdorffDistance($values, $replayValues);
            $lengthDiff = $source->map(fn ($stroke) => $this->strokeLength($stroke))->average() - collect($replay)->map(fn ($stroke) => $this->strokeLength($stroke))->average();
            $rows[] = [
                'pattern_id' => $pattern->id,
                'strokes' => $source->count(),
                'cosine_sim' => round($cosine, 6),
                'hausdorff' => round($hausdorff, 6),
                'mean_length_diff' => round($lengthDiff, 6),
            ];
        }
        $path = base_path('benchmarks/bot_replay_'.now()->format('Ymd_His').'.csv');
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }
        $handle = fopen($path, 'wb');
        fputcsv($handle, ['pattern_id', 'strokes', 'cosine_sim', 'hausdorff', 'mean_length_diff']);
        foreach ($rows as $row) fputcsv($handle, $row);
        fclose($handle);
        $this->info("Wrote {$path}");
    }

    private function cosineSimilarity($left, $right): float
    {
        $left = $left->flatten()->values();
        $right = $right->flatten()->values();
        $dot = $left->zip($right)->sum(fn ($pair) => $pair[0] * $pair[1]);
        $denominator = sqrt($left->sum(fn ($value) => $value * $value)) * sqrt($right->sum(fn ($value) => $value * $value));
        return $denominator ? $dot / $denominator : 0.0;
    }

    private function hausdorffDistance($left, $right): float
    {
        $distance = fn ($a, $b) => sqrt(($a[0] - $b[0]) ** 2 + ($a[1] - $b[1]) ** 2);
        $leftDistances = $left->map(fn ($point) => $right->map(fn ($candidate) => $distance($point, $candidate))->min());
        $rightDistances = $right->map(fn ($point) => $left->map(fn ($candidate) => $distance($point, $candidate))->min());
        return max($leftDistances->max(), $rightDistances->max());
    }

    private function strokeLength(array $stroke): float
    {
        $points = $stroke['points'] ?? [];
        return collect($points)->reduce(fn ($total, $point, $index) => $total + ($index ? sqrt(($point['x'] - $points[$index - 1]['x']) ** 2 + ($point['y'] - $points[$index - 1]['y']) ** 2) : 0), 0);
    }
}
