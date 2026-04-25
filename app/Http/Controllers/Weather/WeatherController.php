<?php

namespace App\Http\Controllers\Weather;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;


class WeatherController extends Controller
{
    public function show($city)
    {
        // Step 1: Call the third-party API
       $response = Http::get(config('services.openweather.base_url') . '/weather', [
            'q'     => $city,
            'appid' => config('services.openweather.key'),
            'units' => 'metric',
        ]);

        // return response()->json(['message'=>$response,
        //   'base_url'=> config('services.openweather.base_url'),
        //   'appid' => config('services.openweather.key')]);

        // Step 2: Check if it worked
        if ($response->failed()) {
            return response()->json([
                'values' => 'testing not approved',
                'message' => 'Could not get weather data',
                'status'  => $response->status(),
                'body'    => $response->json(), 
            ], $response->status());
        }

        // Step 3: Get the JSON data from the response
        $data = $response->json();

        // Step 4: Return only what we care about
        return response()->json([
            'city'        => $data['name'],
            'temperature' => $data['main']['temp'],
            'condition'   => $data['weather'][0]['description'],
            'lat'         => $data['coord']['lat'],
            'lon'         => $data['coord']['lon'],
        ]);
    }


    public function showByCoords(Request $request)
    {
        // Step A: make sure lat and lon are present and numeric
        $request->validate([
            'lat' => 'required|numeric',
            'lon' => 'required|numeric',
        ]);

        // Step B: call OpenWeather with lat/lon instead of q
        $response = Http::get(config('services.openweather.base_url') . '/weather', [
            'lat'   => $request->query('lat'),
            'lon'   => $request->query('lon'),
            'appid' => config('services.openweather.key'),
            'units' => 'metric',
        ]);

        // Step C: same failure handling as show()
        if ($response->failed()) {
            return response()->json([
                'message' => 'Could not get weather data',
                'status'  => $response->status(),
            ], $response->status());
        }

        // Step D: same response shape as show(), plus lat/lon for the map later
        $data = $response->json();
        return response()->json([
            'city'        => $data['name'],
            'temperature' => $data['main']['temp'],
            'condition'   => $data['weather'][0]['description'],
            'lat'         => $data['coord']['lat'],
            'lon'         => $data['coord']['lon'],
        ]);
    }


    public function searchPlaces(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:2',
        ]);

        // Geocoding is a different OpenWeather endpoint family — geo/1.0, not data/2.5.
        // It finds smaller places (towns, districts, villages) that the weather endpoint can't.
        $response = Http::get('https://api.openweathermap.org/geo/1.0/direct', [
            'q'     => $request->query('q'),
            'limit' => 5,
            'appid' => config('services.openweather.key'),
        ]);

        if ($response->failed()) {
            return response()->json([
                'message' => 'Could not search places',
                'status'  => $response->status(),
            ], $response->status());
        }

        $places = collect($response->json())->map(fn($p) => [
            'name'    => $p['name'] ?? null,
            'state'   => $p['state'] ?? null,
            'country' => $p['country'] ?? null,
            'lat'     => $p['lat'] ?? null,
            'lon'     => $p['lon'] ?? null,
        ])->values();

        return response()->json($places);
    }


    public function forecast($city)
    {
        $response = Http::get(config('services.openweather.base_url') . '/forecast', [
            'q'     => $city,
            'appid' => config('services.openweather.key'),
            'units' => 'metric',
        ]);

        if ($response->failed()) {
            return response()->json([
                'message' => 'Could not get forecast data',
                'status'  => $response->status(),
            ], $response->status());
        }

        return response()->json($this->formatForecast($response->json()));
    }


    public function forecastByCoords(Request $request)
    {
        $request->validate([
            'lat' => 'required|numeric',
            'lon' => 'required|numeric',
        ]);

        $response = Http::get(config('services.openweather.base_url') . '/forecast', [
            'lat'   => $request->query('lat'),
            'lon'   => $request->query('lon'),
            'appid' => config('services.openweather.key'),
            'units' => 'metric',
        ]);

        if ($response->failed()) {
            return response()->json([
                'message' => 'Could not get forecast data',
                'status'  => $response->status(),
            ], $response->status());
        }

        return response()->json($this->formatForecast($response->json()));
    }


    // OpenWeather /forecast returns 40 entries at 3-hour intervals (8 per day x 5 days).
    // Group them by date: take the day's min/max temp, and pick the entry closest to
    // noon as the day's representative icon/condition.
    private function formatForecast(array $data): array
    {
        $byDay = [];
        foreach ($data['list'] ?? [] as $row) {
            $date = substr($row['dt_txt'], 0, 10);
            $hour = (int) substr($row['dt_txt'], 11, 2);

            if (! isset($byDay[$date])) {
                $byDay[$date] = [
                    'date'      => $date,
                    'temp_min'  => $row['main']['temp_min'],
                    'temp_max'  => $row['main']['temp_max'],
                    'condition' => $row['weather'][0]['description'] ?? null,
                    'icon'      => $row['weather'][0]['icon'] ?? null,
                    'noon_diff' => abs($hour - 12),
                ];
                continue;
            }

            $byDay[$date]['temp_min'] = min($byDay[$date]['temp_min'], $row['main']['temp_min']);
            $byDay[$date]['temp_max'] = max($byDay[$date]['temp_max'], $row['main']['temp_max']);

            $diff = abs($hour - 12);
            if ($diff < $byDay[$date]['noon_diff']) {
                $byDay[$date]['condition'] = $row['weather'][0]['description'] ?? null;
                $byDay[$date]['icon']      = $row['weather'][0]['icon'] ?? null;
                $byDay[$date]['noon_diff'] = $diff;
            }
        }

        $days = array_values(array_map(function ($d) {
            unset($d['noon_diff']);
            $d['temp_min'] = round($d['temp_min'], 1);
            $d['temp_max'] = round($d['temp_max'], 1);
            return $d;
        }, $byDay));

        return [
            'city'    => $data['city']['name'] ?? null,
            'country' => $data['city']['country'] ?? null,
            'days'    => array_slice($days, 0, 5),
        ];
    }


    public function index()
    {
        $response = Http::baseUrl(config('services.jsonplaceholder.url'))
            ->get('/posts');

        if ($response->failed()) {
            return response()->json(['message' => 'Could not fetch posts'], $response->status());
        }

        // Take only first 10 so it's not overwhelming
        $posts = collect($response->json())->take(10)->map(fn($post) => [
            'id'      => $post['id'],
            'user_id' => $post['userId'],
            'title'   => $post['title'],
            'body'    => $post['body'],
        ]);

        return response()->json(['data' => $posts]);
    }

    // GET — fetch one post by id
    public function showdetail($id)
    {
        $response = Http::baseUrl(config('services.jsonplaceholder.url'))
            ->get("/posts/{$id}");

        if ($response->failed()) {
            return response()->json(['message' => 'Post not found'], 404);
        }

        return response()->json(['data' => $response->json()]);
    }

}
