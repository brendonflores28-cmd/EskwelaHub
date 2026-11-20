<?php

namespace App\Support;

/**
 * Ordinary least squares line y = intercept + slope * x. Plain PHP, no libraries.
 *
 *   slope     = sum((x - mean_x)(y - mean_y)) / sum((x - mean_x)^2)
 *   intercept = mean_y - slope * mean_x
 *   r2        = 1 - residual sum of squares / total sum of squares  (1 = a perfect line)
 */
final class LinearRegression
{
    private function __construct(
        public readonly float $slope,
        public readonly float $intercept,
        public readonly float $r2,
        public readonly int $n,
    ) {}

    /**
     * @param  list<int|float>  $xs
     * @param  list<int|float>  $ys
     */
    public static function fit(array $xs, array $ys): self
    {
        $n = count($xs);
        if ($n !== count($ys) || $n === 0) {
            throw new \InvalidArgumentException('x and y need the same, non-zero number of points.');
        }

        $meanX = array_sum($xs) / $n;
        $meanY = array_sum($ys) / $n;

        $sxx = 0.0;
        $sxy = 0.0;
        $syy = 0.0;
        foreach ($xs as $i => $x) {
            $sxx += ($x - $meanX) ** 2;
            $sxy += ($x - $meanX) * ($ys[$i] - $meanY);
            $syy += ($ys[$i] - $meanY) ** 2;
        }

        // One point, or all x equal: no trend can be found, predict the average.
        if ($sxx == 0.0) {
            return new self(0.0, $meanY, 0.0, $n);
        }

        $slope = $sxy / $sxx;
        $intercept = $meanY - $slope * $meanX;
        $r2 = $syy == 0.0 ? 1.0 : ($sxy ** 2) / ($sxx * $syy);

        return new self($slope, $intercept, $r2, $n);
    }

    public function predict(int|float $x): float
    {
        return $this->intercept + $this->slope * $x;
    }
}
