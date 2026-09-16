<?php

namespace App\Http\Controllers;

use App\Models\Farm;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class FarmController extends Controller
{
    public function select(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'farm_id' => ['required', 'integer'],
        ]);

        $user = $request->user();

        $farm = $user->isAdmin()
            ? Farm::query()->find($validated['farm_id'])
            : $user->farms()->find($validated['farm_id']);

        if (! $farm) {
            return response()->json(['ok' => false, 'message' => 'Farm tidak ditemukan.'], 422);
        }

        Session::put('active_farm_id', $farm->id);

        return response()->json(['ok' => true, 'farm_id' => $farm->id]);
    }
}
