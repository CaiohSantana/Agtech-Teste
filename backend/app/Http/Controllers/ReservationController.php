<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReservationController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        // 1. Validar os dados recebidos.
        $data = $request->validate([
            'machine_id' => ['required', 'integer', 'exists:machines,id'],
            'client_id' => ['required', 'integer', 'exists:clients,id'],
            'starts_at' => ['required', 'date', 'after_or_equal:now'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'status' => ['prohibited'],
        ]);

        $start = CarbonImmutable::parse($data['starts_at'])->utc();
        $end = CarbonImmutable::parse($data['ends_at'])->utc();

        // 2. Conferir a duração.
        if ($end->gt($start->addDays(7))) {
            return response()->json([
                'message' => 'A reserva pode durar no máximo 7 dias.',
            ], 422);
        }

        return DB::transaction(function () use ($data, $start, $end) {
            // 3. Procurar conflito.
            $hasConflict = Reservation::query()
                ->where('machine_id', $data['machine_id'])
                ->whereIn('status', ['pending', 'confirmed'])
                ->where('starts_at', '<', $end)
                ->where('ends_at', '>', $start)
                ->exists();

            if ($hasConflict) {
                return response()->json([
                    'message' => 'A máquina já possui uma reserva nesse período.',
                ], 422);
            }

            // 4. Salvar como pendente.
            $reservation = Reservation::create([
                'machine_id' => $data['machine_id'],
                'client_id' => $data['client_id'],
                'starts_at' => $start,
                'ends_at' => $end,
                'status' => 'pending',
            ]);

            return response()->json($reservation, 201);
        }, 3);
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'machine_id' => [
                'sometimes',
                'required',
                'integer',
                'exists:machines,id',
            ],
            'status' => [
                'sometimes',
                'required',
                'in:pending,confirmed,cancelled,completed',
            ],
        ]);

        $query = Reservation::query()
            ->with(['machine', 'client'])
            ->orderBy('starts_at');

        if (isset($filters['machine_id'])) {
            $query->where('machine_id', $filters['machine_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return response()->json($query->get());
    }

    public function updateStatus(
        Request $request,
        Reservation $reservation
    ): JsonResponse {
        $data = $request->validate([
            'status' => ['required', 'in:confirmed,cancelled,completed'],
        ]);

        $status = $data['status'];

        return DB::transaction(function () use ($reservation, $status) {
            $reservation = Reservation::query()
                ->lockForUpdate()
                ->findOrFail($reservation->id);

            $now = CarbonImmutable::now('UTC');
            $allowed = false;

            if ($reservation->status === 'pending') {
                $allowed = in_array(
                    $status,
                    ['confirmed', 'cancelled'],
                    true
                );
            } elseif ($reservation->status === 'confirmed') {
                if ($status === 'completed') {
                    $allowed = $now->gt($reservation->ends_at);
                } elseif ($status === 'cancelled') {
                    $allowed = $now->addHours(24)
                        ->lte($reservation->starts_at);
                }
            }

            if (!$allowed) {
                return response()->json([
                    'message' => 'Mudança de status não permitida para esta reserva neste momento.',
                ], 422);
            }

            $reservation->status = $status;
            $reservation->save();

            return response()->json($reservation);
        }, 3);
    }
}
