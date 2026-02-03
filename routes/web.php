<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;

Route::get('/', function () {
    return view('flight');
});

Route::get('/flights-data', function () {
    $response = Http::withoutVerifying()
        ->get('https://opensky-network.org/api/states/all');

    return response()->json(
        json_decode((string) $response, true)['states'] ?? []
    );

});