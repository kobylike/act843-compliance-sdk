<?php

namespace GhanaCompliance\Act843SDK\Console\Commands;

use Illuminate\Console\Command;
use GhanaCompliance\Act843SDK\Models\AnomalyTrainingData;
use Rubix\ML\PersistentModel;
use Rubix\ML\Datasets\Unlabeled;
use Rubix\ML\AnomalyDetectors\IsolationForest;
use Rubix\ML\Persisters\Filesystem;

class TrainAnomalyModel extends Command
{
    protected $signature = 'compliance:train-anomaly';
    protected $description = 'Train Isolation Forest model on login metadata';

    public function handle()
    {
        $records = AnomalyTrainingData::all(['hour', 'day_of_week', 'user_agent_hash', 'ip_class', 'request_rate']);

        if ($records->isEmpty()) {
            $this->warn('No training data yet. Please log in successfully at least 20 times.');
            return;
        }

        // Convert each record to a feature vector
        $samples = $records->map(fn($row) => [
            $row->hour,
            $row->day_of_week,
            ord($row->user_agent_hash[0]) % 10,  // hash to int
            $this->encodeIpClass($row->ip_class),
            $row->request_rate,
        ])->toArray();

        // Remove any columns with zero variance (to avoid division by zero)
        $sampleCount = count($samples);
        if ($sampleCount < 10) {
            $this->warn("Not enough samples ($sampleCount). Need at least 10 distinct login events.");
            return;
        }

        $featureCount = count($samples[0]);
        $columns = [];
        for ($i = 0; $i < $featureCount; $i++) {
            $values = array_column($samples, $i);
            $unique = count(array_unique($values));
            if ($unique > 1) {
                $columns[] = $i;
            } else {
                $this->info("Feature $i is constant, removing it from training.");
            }
        }

        // If no columns left, abort
        if (empty($columns)) {
            $this->error('All features are constant – cannot train Isolation Forest.');
            return;
        }

        // Filter samples to keep only non-constant columns
        $filteredSamples = array_map(function ($sample) use ($columns) {
            return array_intersect_key($sample, array_flip($columns));
        }, $samples);

        // Convert to indexed arrays (reset keys)
        $filteredSamples = array_values($filteredSamples);

        $dataset = new Unlabeled($filteredSamples);
        $estimator = new IsolationForest(100, 0.1);
        $estimator->train($dataset);

        $persister = new Filesystem(storage_path('app/anomaly.model'));
        $model = new PersistentModel($estimator, $persister);
        $model->save();

        $this->info('Anomaly detection model trained and saved successfully.');
    }

    private function encodeIpClass(string $class): int
    {
        return match ($class) {
            'private' => 0,
            'public' => 1,
            default => 2,
        };
    }
}
