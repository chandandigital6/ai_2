<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ImportIndiaCities extends Command
{
    protected $signature = 'growya:import-india-cities {file=storage/app/imports/india_cities_growya.csv}';
    protected $description = 'Import Indian cities mapped to existing Growya state IDs';

    public function handle(): int
    {
        $path = $this->argument('file');
        $path = is_file($path) ? $path : base_path($path);
        if (!is_file($path) || !is_readable($path)) {
            $this->error("CSV not found/readable: {$path}");
            return self::FAILURE;
        }

        $states = DB::table('states')
            ->join('countries', 'countries.id', '=', 'states.country_id')
            ->where('countries.iso_code', 'IND')
            ->select('states.id', 'states.name')
            ->get();
        $stateMap = [];
        foreach ($states as $state) {
            $stateMap[mb_strtolower(trim($state->name), 'UTF-8')] = $state->id;
        }
        if (count($stateMap) !== 36) {
            $this->warn('Expected 36 Indian states/UTs; found ' . count($stateMap) . '. Missing state rows will be skipped.');
        }

        $handle = fopen($path, 'r');
        if (!$handle) {
            $this->error('Unable to open CSV.');
            return self::FAILURE;
        }

        try {
            $header = fgetcsv($handle);
            if (!$header) {
                throw new RuntimeException('Empty CSV.');
            }
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
            $required = ['state_name', 'name', 'latitude', 'longitude'];
            if (array_diff($required, $header)) {
                throw new RuntimeException('CSV headers must be: ' . implode(', ', $required));
            }
            $positions = array_flip($header);
            $batch = [];
            $count = 0;
            $skipped = 0;
            $unknown = [];
            while (($values = fgetcsv($handle)) !== false) {
                $stateName = trim($values[$positions['state_name']] ?? '');
                $name = trim($values[$positions['name']] ?? '');
                $stateId = $stateMap[mb_strtolower($stateName, 'UTF-8')] ?? null;
                if (!$stateId || $name === '') {
                    $skipped++;
                    if (!$stateId && $stateName !== '') $unknown[$stateName] = true;
                    continue;
                }
                $lat = trim($values[$positions['latitude']] ?? '');
                $long = trim($values[$positions['longitude']] ?? '');
                $batch[] = [
                    'state_id' => $stateId,
                    'name' => $name,
                    'latitude' => ($lat !== '' && is_numeric($lat)) ? $lat : null,
                    'longitude' => ($long !== '' && is_numeric($long)) ? $long : null,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                if (count($batch) >= 250) {
                    DB::table('cities')->upsert($batch, ['state_id', 'name'], ['latitude', 'longitude', 'is_active', 'updated_at']);
                    $count += count($batch);
                    $batch = [];
                }
            }
            if ($batch) {
                DB::table('cities')->upsert($batch, ['state_id', 'name'], ['latitude', 'longitude', 'is_active', 'updated_at']);
                $count += count($batch);
            }
            $this->info("Processed {$count} India city rows; skipped {$skipped}.");
            if ($unknown) $this->warn('Unmatched states: ' . implode(', ', array_keys($unknown)));
            return $unknown ? self::FAILURE : self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        } finally {
            fclose($handle);
        }
    }
}
