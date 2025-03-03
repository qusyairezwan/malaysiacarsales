<?php

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;

Route::get('/', function () {
    ini_set('memory_limit', '-1'); // Set unlimited memory for safety

    $year       = request()->input('year', date('Y'));
    $month      = request()->input('month', '');
    $makerInput = request()->input('maker', '');
    $modelInput = request()->input('model', '');
    $state      = request()->input('state', '');
    Log::info("Memory usage before processing: " . memory_get_usage(true));

    $url = "https://storage.data.gov.my/transportation/cars_{$year}.csv";

    try {
        $response = Http::get($url);
        if (! $response->successful()) {
            return View::make('welcome', ['error' => 'Data not available for selected year', 'year' => $year]);
        }

        $filename = "cars_{$year}.csv";
        Storage::disk('local')->put($filename, $response->body());

        Log::info("Memory usage after downloading CSV: " . memory_get_usage(true));

        if (!Storage::disk('local')->exists($filename) || Storage::disk('local')->size($filename) == 0) {
            return View::make('welcome', ['error' => 'Downloaded CSV file is empty or corrupt', 'year' => $year]);
        }

        $filePath = Storage::disk('local')->path($filename);

        // Process CSV file and apply filters
        $handle   = fopen($filePath, "r");
        $data     = [];
        $batchSize = 5000; // Process in chunks
        $counter = 0;
        $rowCount = 0;
        $headers  = fgetcsv($handle, 1000, ","); // Extract headers

        // Convert maker and model filters into arrays
        $makers = collect(explode(',', strtolower($makerInput)))->map(fn($maker) => trim($maker))->filter()->all();
        $models = collect(explode(',', strtolower($modelInput)))->map(fn($model) => trim($model))->filter()->all();

        while (($row = fgetcsv($handle, 1000, ",")) !== false) {
            $rowData = array_combine($headers, $row);

            // Apply filters
            if (($month && ! str_contains($rowData['date_reg'], "-$month-")) ||
                (! empty($makers) && ! in_array(strtolower($rowData['maker']), $makers)) ||
                (! empty($models) && ! in_array(strtolower($rowData['model']), $models)) ||
                ($state && strtolower($rowData['state']) !== strtolower($state))
            ) {
                continue;
            }

            $data[] = $rowData;
            $counter++;

            // Process in batches to reduce memory usage
            if ($counter >= $batchSize) {
                Log::info("Processed $counter rows, freeing memory.");
                $counter = 0;
                gc_collect_cycles(); // Free memory
            }
        }
        fclose($handle);

        Log::info("Memory usage after processing CSV: " . memory_get_usage(true));

        // Group data for visualization by Maker, Model, and Colour
        $groupedByMaker      = [];
        $groupedByMakerModel = [];
        $groupedByColour     = [];
        foreach ($data as $row) {
            $maker  = $row['maker'];
            $model  = $row['model'];
            $colour = $row['colour'];

            if (! isset($groupedByMaker[$maker])) {
                $groupedByMaker[$maker]      = 0;
                $groupedByMakerModel[$maker] = [];
            }
            $groupedByMaker[$maker]++;

            if (! isset($groupedByMakerModel[$maker][$model])) {
                $groupedByMakerModel[$maker][$model] = 0;
            }
            $groupedByMakerModel[$maker][$model]++;

            if (! isset($groupedByColour[$colour])) {
                $groupedByColour[$colour] = 0;
            }
            $groupedByColour[$colour]++;
        }

        // Consolidate brands below threshold into 'Others'
        arsort($groupedByMaker);
        $filteredMakers       = [];
        $othersCount          = 0;
        $filteredMakersModels = [];
        foreach ($groupedByMaker as $maker => $count) {
            if ($count >= 10) {
                $filteredMakers[$maker]       = $count;
                $filteredMakersModels[$maker] = $groupedByMakerModel[$maker];
            } else {
                $othersCount += $count;
                foreach ($groupedByMakerModel[$maker] as $model => $modelCount) {
                    if (! isset($filteredMakersModels['Others'])) {
                        $filteredMakersModels['Others'] = [];
                    }
                    $filteredMakersModels['Others'][$model] = ($filteredMakersModels['Others'][$model] ?? 0) + $modelCount;
                }
            }
        }
        if ($othersCount > 0) {
            $filteredMakers['Others'] = $othersCount;
        }

        return View::make('welcome', [
            'data'                => $data,
            'year'                => $year,
            'month'               => $month,
            'maker'               => $makerInput,
            'model'               => $modelInput,
            'state'               => $state,
            'groupedByMaker'      => $filteredMakers,
            'groupedByMakerModel' => $filteredMakersModels,
            'groupedByColour'     => $groupedByColour,
        ]);
    } catch (Exception $e) {
        Log::error("Error: " . $e->getMessage());
        return View::make('welcome', ['error' => $e->getMessage(), 'year' => $year]);
    }
});
