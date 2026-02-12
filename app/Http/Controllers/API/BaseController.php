<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\RefreshToken;
use Carbon\Carbon;
use Illuminate\Support\Str;

class BaseController extends Controller
{
    /**
     * Send Success Response to the client 
     * @param $result 
     * @param $message
     * @return $response
     * 
     */
    protected function sendResponse(
        mixed $result,
        string $message,
        int $code = 200
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'data' => $result,
            'message' => $message,
        ], $code);
    }


    /**
     * To send error messages to the client
     * @param $error
     * @param $errorMsg = []
     * @param $code = 404
     * @return $response
     */
    
    protected function sendError(
        string $message,
        array $errors = [],
        int $code = 400
    ): JsonResponse {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }


    /**
     * Get device info through Jessegers/agent 
     * @return [type] [description]
     */
    protected function deviceInfo(): array
    {
        $agent = app('agent');

        if ($agent->isDesktop()) {
            $deviceType = 'Desktop';
        } elseif ($agent->isTablet()) {
            $deviceType = 'Tablet';
        } elseif ($agent->isPhone()) {
            $deviceType = 'Phone';
        } else {
            $deviceType = 'Unknown Device';
        }

        return [
            'device_name' => $agent->device() ?? 'Unknown',
            'device_type' => $deviceType,
            'browser' => $agent->browser() ?: 'Unknown',
            'operating_system' => $agent->platform() ?: 'Unknown',
        ];
    }

    /**
     * Create access token and refresh token 
     * @param $user
     * @param $device
     * @return $response [Token response]
     */
    protected function createToken($user, $device): array
    {
        $deviceId = $device['device_id'] ?? null;

        if (!$deviceId) {
            throw new \Exception('Device ID is required');
        }

        $access_token = JWTAuth::fromUser($user);
        $refresh_token = Str::random(60);

        $accessExpiry = Carbon::now()->addMinutes(config('jwt.ttl'));
    $refreshExpiry = Carbon::now()->addDays(30); // better practice

    $refreshModel = RefreshToken::firstOrNew([
        'device_id' => $deviceId,
        'user_id' => $user->id
    ]);

    $refreshModel->fill([
        'refresh_token' => $refresh_token,
        'expired_at' => $refreshExpiry
    ]);

    $refreshModel->save();

    return [
        'access_token' => $access_token,
        'token_type' => 'bearer',
        'refresh_token' => $refresh_token,
        'expires_in' => config('jwt.ttl') * 60
    ];
}
}
