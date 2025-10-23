<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TwitchUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Handles bulk import of Twitch user data from external sources
 */
class TwitchUserController extends Controller
{
    /**
     * Bulk import/update Twitch users from users.dat
     */
    public function bulkImport(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'users' => 'required|array|min:0',
            'users.*.id' => 'required|string',
            'users.*.name' => 'nullable|string',
            'users.*.display' => 'nullable|string',
            'users.*.role' => 'nullable|integer',
            'users.*.subscribed' => 'nullable|boolean',
            'users.*.type' => 'nullable|string',
            'users.*.present' => 'nullable|boolean',
            'users.*.lastActive' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $users = $request->input('users');
        $imported = 0;
        $updated = 0;
        $errors = [];

        DB::beginTransaction();

        try {
            foreach ($users as $userData) {
                $twitchUser = TwitchUser::where('twitch_id', $userData['id'])->first();

                $data = [
                    'twitch_id' => $userData['id'],
                    'name' => $userData['name'] ?? null,
                    'display_name' => $userData['display'] ?? null,
                    'role' => $userData['role'] ?? null,
                    'subscribed' => $userData['subscribed'] ?? false,
                    'type' => $userData['type'] ?? 'twitch',
                    'present' => $userData['present'] ?? false,
                    'last_active' => isset($userData['lastActive']) ? $userData['lastActive'] : null,
                ];

                if ($twitchUser) {
                    $twitchUser->update($data);
                    $updated++;
                } else {
                    TwitchUser::create($data);
                    $imported++;
                }
            }

            DB::commit();

            Log::info('Twitch users bulk import completed', [
                'imported' => $imported,
                'updated' => $updated,
                'total' => count($users),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Twitch users imported successfully',
                'imported' => $imported,
                'updated' => $updated,
                'total' => count($users),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Twitch users bulk import failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to import Twitch users',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
