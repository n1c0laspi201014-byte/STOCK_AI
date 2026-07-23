<?php
declare(strict_types=1);

namespace App\Services;

final class TechnicalIndicatorService
{
    public function calculate(array $points): array
    {
        $closes = array_values(array_filter(array_map(static fn(array $point): float => (float) ($point['close'] ?? 0), $points), static fn(float $value): bool => $value > 0));
        if (count($closes) < 2) return ['available' => false, 'score' => null, 'confidence' => 0, 'indicators' => [], 'positive_factors' => [], 'negative_factors' => ['Not enough historical price points.']];
        $last = end($closes);
        $first = reset($closes);
        $momentum = (($last - $first) / $first) * 100;
        $sma20 = $this->average(array_slice($closes, -20));
        $sma50 = $this->average(array_slice($closes, -50));
        $rsi = $this->rsi($closes, 14);
        $shortEma = $this->ema($closes, 12);
        $longEma = $this->ema($closes, 26);
        $macd = $shortEma - $longEma;
        $returns = [];
        for ($i = 1, $count = count($closes); $i < $count; $i++) $returns[] = (($closes[$i] - $closes[$i - 1]) / $closes[$i - 1]) * 100;
        $volatility = $this->stdDev($returns);
        $recent = array_slice($closes, -50);
        $high = max($recent); $low = min($recent);
        $position = $high > $low ? (($last - $low) / ($high - $low)) * 100 : 50;

        $score = 50 + max(-15, min(15, $momentum * 2));
        $score += $sma20 >= $sma50 ? 8 : -8;
        $score += $rsi < 30 ? 8 : ($rsi > 70 ? -8 : (($rsi - 50) * .15));
        $score += $macd >= 0 ? 5 : -5;
        $score += ($position - 50) * .08;
        $score -= max(0, $volatility - 2) * 2;
        $score = max(5, min(95, $score));
        $confidence = min(90, 30 + min(50, count($closes)) + (count($closes) >= 50 ? 10 : 0));
        $positive = []; $negative = [];
        if ($momentum >= 0) {
            $positive[] = sprintf('Momentum over the available period is %.2f%%.', $momentum);
        } else {
            $negative[] = sprintf('Momentum over the available period is %.2f%%.', $momentum);
        }
        if ($sma20 >= $sma50) {
            $positive[] = 'Short average is above the longer average.';
        } else {
            $negative[] = 'Short average is below the longer average.';
        }
        if ($rsi <= 70) {
            $positive[] = sprintf('RSI is %.1f.', $rsi);
        } else {
            $negative[] = sprintf('RSI is %.1f and may indicate an overextended move.', $rsi);
        }
        return ['available' => true, 'score' => round($score, 2), 'confidence' => round($confidence, 2), 'indicators' => ['momentum_percent' => round($momentum, 4), 'sma20' => round($sma20, 4), 'sma50' => round($sma50, 4), 'rsi' => round($rsi, 2), 'macd_direction' => $macd >= 0 ? 'positive' : 'negative', 'volatility' => round($volatility, 4), 'recent_range_position' => round($position, 2)], 'positive_factors' => $positive, 'negative_factors' => $negative];
    }

    private function average(array $values): float { return $values === [] ? 0 : array_sum($values) / count($values); }
    private function ema(array $values, int $period): float { $k = 2 / ($period + 1); $ema = (float) reset($values); foreach (array_slice($values, 1) as $value) $ema = ((float) $value * $k) + ($ema * (1 - $k)); return $ema; }
    private function rsi(array $values, int $period): float { $gains = 0.0; $losses = 0.0; $slice = array_slice($values, -($period + 1)); for ($i=1,$c=count($slice);$i<$c;$i++){ $change=$slice[$i]-$slice[$i-1]; if($change>=0)$gains+=$change;else$losses+=abs($change); } if($losses===0.0)return 100; $rs=($gains/max(1,count($slice)-1))/($losses/max(1,count($slice)-1)); return 100-(100/(1+$rs)); }
    private function stdDev(array $values): float { if(count($values)<2)return 0; $average=$this->average($values); $variance=array_sum(array_map(static fn(float $value):float=>($value-$average)**2,$values))/count($values); return sqrt($variance); }
}
