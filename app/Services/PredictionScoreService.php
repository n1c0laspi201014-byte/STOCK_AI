<?php
declare(strict_types=1);

namespace App\Services;

final class PredictionScoreService
{
    public function combine(?float $technical, ?float $news, ?float $market): array
    {
        $components = ['technical' => [$technical, (float) config('prediction.technical_weight', .5)], 'news' => [$news, (float) config('prediction.news_weight', .3)], 'market' => [$market, (float) config('prediction.market_weight', .2)]];
        $weighted = 0.0; $weight = 0.0; $missing = [];
        foreach ($components as $name => [$score, $componentWeight]) {
            if ($score === null) { $missing[] = $name; continue; }
            $weighted += max(0, min(100, $score)) * $componentWeight;
            $weight += $componentWeight;
        }
        if ($weight <= 0) return ['available' => false, 'probability_up' => null, 'probability_down' => null, 'missing' => array_keys($components)];
        $up = max(5, min(95, $weighted / $weight));
        return ['available' => true, 'probability_up' => round($up, 2), 'probability_down' => round(100 - $up, 2), 'missing' => $missing];
    }

    public function signal(float $up, float $confidence, string $risk, bool $owned): string
    {
        $down = 100 - $up;
        if ($up >= (float) config('prediction.buy_probability', 70) && $confidence >= (float) config('prediction.minimum_confidence', 60) && $risk !== 'high') return 'buy';
        if ($down >= (float) config('prediction.sell_probability_down', 70) && $confidence >= (float) config('prediction.minimum_confidence', 60)) return 'sell';
        if ($owned && $up >= 45 && $up <= 57) return 'hold';
        return 'watch';
    }
}

