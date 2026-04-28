<?php

namespace GhanaCompliance\Act843SDK\Services\Security;

use Illuminate\Support\Facades\Cache;
use Rubix\ML\PersistentModel;
use Rubix\ML\Persisters\Filesystem;

class AnomalyScorer
{
    protected $model;

    public function __construct()
    {
        $persister = new Filesystem(storage_path('app/anomaly.model'));
        if (file_exists(storage_path('app/anomaly.model'))) {
            $this->model = PersistentModel::load($persister);
        }
    }

    public function score(): float
    {
        if (!$this->model) return 0;

        $sample = $this->collectCurrentFeatures();
        $outlierScore = $this->model->predictSample($sample);

        // Convert to 0-100 score, where higher = more anomalous
        return min(100, max(0, $outlierScore * 100));
    }

    protected function collectCurrentFeatures(): array
    {
        $ip = request()->ip();
        return [
            now()->hour,
            now()->dayOfWeek,
            ord(hash_hmac('sha256', request()->userAgent(), config('app.key'))[0]) % 10,
            $this->classifyIp($ip),
            $this->getRequestRate($ip),
        ];
    }

    protected function classifyIp($ip): int
    {
        if (str_starts_with($ip, '127.') || str_starts_with($ip, '192.168.')) return 0;
        return 1;
    }

    protected function getRequestRate($ip): float
    {
        $key = "rate_{$ip}";
        $times = Cache::get($key, []);
        $now = now()->timestamp;
        $times = array_filter($times, fn($t) => ($now - $t) <= 60);
        $times[] = $now;
        Cache::put($key, $times, now()->addMinutes(1));
        return count($times);
    }
}
