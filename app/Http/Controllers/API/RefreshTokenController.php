<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\RefreshTokenRequest;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\RefreshToken;
use Carbon\Carbon;
use DB;
use Hash;

class RefreshTokenController extends BaseController
{
    /**
     * Refresh token when current access token expired.
     */
    
    public function refreshToken(RefreshTokenRequest $request)
    {
        DB::beginTransaction();

        try {
            $data = $request->validated();

            $refreshToken = $data['refresh_token'];
            $deviceId = $data['device_id'];

        // Find token by device first
            $refreshModel = RefreshToken::where('device_id', $deviceId)->first();

            if (!$refreshModel) {
                DB::rollBack();
                return $this->sendError('Invalid device id.');
            }
            
        // Verify hashed token
            if (!Hash::check($refreshToken, $refreshModel->refresh_token)) {
                DB::rollBack();
                return $this->sendError('Invalid refresh token.');
            }

        // Check expiration
            if ($refreshModel->expired_at->isPast()) {
                $refreshModel->delete();
                DB::rollBack();
                return $this->sendError('Refresh token expired.');
            }

            $user = $refreshModel->user;
            $device = $this->deviceInfo();

            $device['device_id'] = $data['device_id'];
        // TOKEN ROTATION (Important)
            $refreshModel->delete();

        // Create new tokens
            $tokens = $this->createToken($user, $device);

            DB::commit();

            return $this->sendResponse($tokens, 'Token refreshed successfully.');

        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->sendError('Something went wrong.', ['error' => $e->getMessage()]);
        }
    }
}
