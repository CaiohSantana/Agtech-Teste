<?php

namespace App\Http\Controllers;

use App\Models\Client;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index(): JsonResponse
    {
        $clients = Client::query()->orderBy('id')->get(['id', 'name']);

        return response()->json($clients);
    }
}
