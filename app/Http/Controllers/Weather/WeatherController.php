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
       $response = Http::baseUrl(config('services.openweather.base_url'))
        ->get('/weather', [
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
        ]);
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
