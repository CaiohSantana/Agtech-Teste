<?php

namespace App\Http\Controllers;

use App\Models\Machine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MachineController extends Controller
{
    public function index(): JsonResponse
    { 
        $machines = Machine::query()->orderBy('id')->get(['id', 'name']);

        return response()->json($machines);
    }
}
