<?php

namespace Database\Seeders;

use App\Models\Barangay;
use App\Models\BarangayDistance;
use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BarangayDistanceSeeder extends Seeder
{
    // public function run()
    // {
    //     $this->command->info('Loading barangays.json ...');
    //     $jsonPath = database_path('seeders/data/barangays.json');
    //     $data = json_decode(file_get_contents($jsonPath), true);

    //     DB::statement('SET FOREIGN_KEY_CHECKS=0;');
    //     BarangayDistance::truncate();
    //     Barangay::truncate();
    //     DB::statement('SET FOREIGN_KEY_CHECKS=1;');

    //     DB::transaction(function () use ($data) {
    //         foreach ($data as $row) {
    //             Barangay::create([
    //                 'name' => $row['name'],
    //                 'lat' => $row['lat'],
    //                 'lng' => $row['lng'],
    //                 'municipality' => $row['municipality'] ?? null,
    //             ]);
    //         }
    //     });


    //     $this->command->info('Inserted ' . Barangay::count() . ' barangays.');

    //     // Now compute distances
    //     $barangays = Barangay::all()->values(); // collection, indexable
    //     $n = $barangays->count();

    //     if ($n < 2) {
    //         $this->command->info('Not enough barangays to compute distances.');
    //         return;
    //     }

    //     $graphhopperUrl = env('GRAPHOPPER_URL', 'http://localhost:8989');
    //     $profile = env('GRAPHOPPER_PROFILE', 'car');
    //     $concurrency = (int) env('GRAPHOPPER_CONCURRENCY', 12);

    //     $client = new Client([
    //         'base_uri' => $graphhopperUrl,
    //         'timeout' => 30,
    //         'connect_timeout' => 10,
    //     ]);

    //     $this->command->info("Computing distances using GraphHopper at $graphhopperUrl (profile: $profile), concurrency: $concurrency");

    //     // Build generator that yields Request objects for every unordered pair (i < j)
    //     $requests = function () use ($barangays, $n, $profile) {
    //         for ($i = 0; $i < $n; $i++) {
    //             $a = $barangays[$i];
    //             for ($j = $i + 1; $j < $n; $j++) {
    //                 $b = $barangays[$j];

    //                 // GraphHopper expects point=lat,lng (latitude,longitude)
    //                 // Example: /route?point=13.94,124.28&point=13.95,124.29&profile=car&calc_points=false
    //                 $url = '/route?point=' . $a->lat . ',' . $a->lng
    //                     . '&point=' . $b->lat . ',' . $b->lng
    //                     . '&profile=' . $profile
    //                     . '&calc_points=false&instructions=false';

    //                 yield new Request('GET', $url);
    //             }
    //         }
    //     };

    //     $totalPairs = ($n * ($n - 1)) / 2;
    //     $this->command->info("Total pairs to compute: $totalPairs");

    //     $progress = 0;
    //     $failed = [];

    //     $pool = new Pool($client, $requests(), [
    //         'concurrency' => $concurrency,
    //         'fulfilled' => function ($response, $index) use (&$progress, $totalPairs, $barangays, $n) {
    //             // $index is the sequence index (0..totalPairs-1)
    //             // We need to map index back to pair (i,j). We'll compute it deterministically.
    //             $pair = $this->indexToPair($index, $n);
    //             [$i, $j] = $pair;
    //             $a = $barangays[$i];
    //             $b = $barangays[$j];

    //             $body = json_decode((string)$response->getBody(), true);

    //             $distance = null;
    //             $time = null;

    //             if (isset($body['paths'][0]['distance'])) {
    //                 $distance = (int) round($body['paths'][0]['distance']); // meters
    //             }
    //             if (isset($body['paths'][0]['time'])) {
    //                 $time = (int) round($body['paths'][0]['time']); // ms
    //             }

    //             // store in DB; ensure a_id < b_id
    //             BarangayDistance::create([
    //                 'barangay_a_id' => $a->id,
    //                 'barangay_b_id' => $b->id,
    //                 'distance_meters' => $distance,
    //                 'time_ms' => $time,
    //                 'route_raw' => $body,
    //             ]);

    //             $progress++;
    //             if ($progress % 50 === 0 || $progress === $totalPairs) {
    //                 echo "Progress: $progress / $totalPairs\n";
    //             }
    //         },
    //         'rejected' => function ($reason, $index) use (&$failed, $barangays, $n) {
    //             // store indexes to retry later
    //             $failed[] = $index;
    //             // optionally log
    //             Log::warning("GraphHopper request failed for index $index: " . (string)$reason);
    //         },
    //     ]);

    //     // Initiate the transfers and wait for the pool of requests to complete.
    //     $promise = $pool->promise();
    //     $promise->wait();

    //     $this->command->info('Initial pass complete. Failed requests: ' . count($failed));

    //     // Retry failed sequentially (with up to 3 retries each) to keep it simple
    //     if (count($failed) > 0) {
    //         $this->command->info('Retrying failed requests sequentially (up to 3 retries each)...');
    //         foreach ($failed as $index) {
    //             $pair = $this->indexToPair($index, $n);
    //             [$i, $j] = $pair;
    //             $a = $barangays[$i];
    //             $b = $barangays[$j];

    //             $attempt = 0;
    //             $success = false;
    //             while ($attempt < 3 && !$success) {
    //                 $attempt++;
    //                 try {
    //                     $uri = $graphhopperUrl . '/route?point=' . $a->lat . ',' . $a->lng
    //                          . '&point=' . $b->lat . ',' . $b->lng
    //                          . '&profile=' . $profile
    //                          . '&calc_points=false&instructions=false';

    //                     $res = (new Client(['timeout' => 30]))->get($uri);
    //                     $body = json_decode((string)$res->getBody(), true);
    //                     $distance = isset($body['paths'][0]['distance']) ? (int) round($body['paths'][0]['distance']) : null;
    //                     $time = isset($body['paths'][0]['time']) ? (int) round($body['paths'][0]['time']) : null;

    //                     BarangayDistance::create([
    //                         'barangay_a_id' => $a->id,
    //                         'barangay_b_id' => $b->id,
    //                         'distance_meters' => $distance,
    //                         'time_ms' => $time,
    //                         'route_raw' => $body,
    //                     ]);
    //                     $success = true;
    //                 } catch (\Exception $ex) {
    //                     Log::warning("Retry $attempt failed for pair $a->id-$b->id: " . $ex->getMessage());
    //                     sleep(1);
    //                 }
    //             }
    //             if (!$success) {
    //                 $this->command->error("Failed to compute distance for pair $a->id - $b->id after retries.");
    //             }
    //         }
    //     }

    //     $this->command->info('All done.');
    // }

    public function run()
    {
        $this->command->info('Seeding barangay distances...');

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        BarangayDistance::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $barangays = Barangay::all()->values();
        $n = $barangays->count();

        if ($n < 2) {
            $this->command->info('Not enough barangays to compute distances.');
            return;
        }

        $graphhopperUrl = env('GRAPHOPPER_URL', 'http://localhost:8989');
        $profile = env('GRAPHOPPER_PROFILE', 'car');
        $concurrency = (int) env('GRAPHOPPER_CONCURRENCY', 12);

        $client = new Client([
            'base_uri' => $graphhopperUrl,
            'timeout' => 30,
            'connect_timeout' => 10,
        ]);

        $this->command->info("Computing distances using GraphHopper at $graphhopperUrl (profile: $profile), concurrency: $concurrency");

        // Build generator that yields Request objects for every unordered pair (i < j)
        $requests = function () use ($barangays, $n, $profile) {
            for ($i = 0; $i < $n; $i++) {
                $a = $barangays[$i];
                for ($j = $i + 1; $j < $n; $j++) {
                    $b = $barangays[$j];

                    // GraphHopper expects point=lat,lng (latitude,longitude)
                    // Example: /route?point=13.94,124.28&point=13.95,124.29&profile=car&calc_points=false
                    $url = '/route?point=' . $a->lat . ',' . $a->lng
                        . '&point=' . $b->lat . ',' . $b->lng
                        . '&profile=' . $profile
                        . '&calc_points=false&instructions=false';

                    yield new Request('GET', $url);
                }
            }
        };

        $totalPairs = ($n * ($n - 1)) / 2;
        $this->command->info("Total pairs to compute: $totalPairs");

        $progress = 0;
        $failed = [];

        $pool = new Pool($client, $requests(), [
            'concurrency' => $concurrency,
            'fulfilled' => function ($response, $index) use (&$progress, $totalPairs, $barangays, $n) {
                // $index is the sequence index (0..totalPairs-1)
                // We need to map index back to pair (i,j). We'll compute it deterministically.
                $pair = $this->indexToPair($index, $n);
                [$i, $j] = $pair;
                $a = $barangays[$i];
                $b = $barangays[$j];

                $body = json_decode((string)$response->getBody(), true);

                $distance = null;
                $time = null;

                if (isset($body['paths'][0]['distance'])) {
                    $distance = (int) round($body['paths'][0]['distance']); // meters
                }
                if (isset($body['paths'][0]['time'])) {
                    $time = (int) round($body['paths'][0]['time']); // ms
                }

                // store in DB; ensure a_id < b_id
                BarangayDistance::create([
                    'barangay_a_id' => $a->id,
                    'barangay_b_id' => $b->id,
                    'distance_meters' => $distance,
                    'time_ms' => $time,
                    'route_raw' => $body,
                ]);

                $progress++;
                if ($progress % 50 === 0 || $progress === $totalPairs) {
                    echo "Progress: $progress / $totalPairs\n";
                }
            },
            'rejected' => function ($reason, $index) use (&$failed, $barangays, $n) {
                // store indexes to retry later
                $failed[] = $index;
                // optionally log
                Log::warning("GraphHopper request failed for index $index: " . (string)$reason);
            },
        ]);

        // Initiate the transfers and wait for the pool of requests to complete.
        $promise = $pool->promise();
        $promise->wait();

        $this->command->info('Initial pass complete. Failed requests: ' . count($failed));

        // Retry failed sequentially (with up to 3 retries each) to keep it simple
        if (count($failed) > 0) {
            $this->command->info('Retrying failed requests sequentially (up to 3 retries each)...');
            foreach ($failed as $index) {
                $pair = $this->indexToPair($index, $n);
                [$i, $j] = $pair;
                $a = $barangays[$i];
                $b = $barangays[$j];

                $attempt = 0;
                $success = false;
                while ($attempt < 3 && !$success) {
                    $attempt++;
                    try {
                        $uri = $graphhopperUrl . '/route?point=' . $a->lat . ',' . $a->lng
                             . '&point=' . $b->lat . ',' . $b->lng
                             . '&profile=' . $profile
                             . '&calc_points=false&instructions=false';

                        $res = (new Client(['timeout' => 30]))->get($uri);
                        $body = json_decode((string)$res->getBody(), true);
                        $distance = isset($body['paths'][0]['distance']) ? (int) round($body['paths'][0]['distance']) : null;
                        $time = isset($body['paths'][0]['time']) ? (int) round($body['paths'][0]['time']) : null;

                        BarangayDistance::create([
                            'barangay_a_id' => $a->id,
                            'barangay_b_id' => $b->id,
                            'distance_meters' => $distance,
                            'time_ms' => $time,
                            'route_raw' => $body,
                        ]);
                        $success = true;
                    } catch (\Exception $ex) {
                        Log::warning("Retry $attempt failed for pair $a->id-$b->id: " . $ex->getMessage());
                        sleep(1);
                    }
                }
                if (!$success) {
                    $this->command->error("Failed to compute distance for pair $a->id - $b->id after retries.");
                }
            }
        }

        $this->command->info('All done.');
        
    }

    private function indexToPair($index, $n)
    {
        // We find smallest i such that cumulative pairs up to i > index
        $i = 0;
        $cumulative = 0;
        while ($i < $n) {
            $pairsWithI = $n - $i - 1; // number of pairs starting with i
            if ($index < $cumulative + $pairsWithI) {
                $j = $i + 1 + ($index - $cumulative);
                return [$i, $j];
            }
            $cumulative += $pairsWithI;
            $i++;
        }
        throw new \RuntimeException("Invalid index $index for n=$n");
    }
}
