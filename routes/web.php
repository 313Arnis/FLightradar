<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;

Route::get('/', function () {
    return view('flight');
});

Route::get('/flights-data', function () {
    try {
        $response = Http::withoutVerifying()->get('https://deskplan.lv/flight/all.json');
        $data = $response->json();

        // Atgriežam tikai lidojumu masīvu (states)
        return response()->json($data['states'] ?? []);
    } catch (\Exception $e) {
        return response()->json([]);
    }
});