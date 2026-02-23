<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\API\BaseController as BaseController;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\User;
use App\Http\Resources\UserResource;
use Validator;
use Hash;
use Str;
use Storage;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;

class UserController extends BaseController
{
    /**
     * Show all users 
     */
    public function getAllUsers() 
    {
        $user = JWTAuth::parseToken()->authenticate();
        $users = User::query()
        ->latest()
        ->paginate(20);

        return UserResource::collection($users);
    }
    
    /**
     * SHow user by id
     */
    public function getAuthenticatedUser() {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            return new UserResource($user);
        } catch (\Exception $e) {
            return $this->sendError('Unauthorized', ['error' => 'Unauthorized'], 401);
        }
    }






    /**
     * Update user profile 
     */
    
    public function updateProfile(UpdateProfileRequest $request)
    {

        // Get authenticated user via JWT
        $user = JWTAuth::parseToken()->authenticate();

        $data = $request->validated();

        if ($request->hasFile('profile')) {

        //Delete old image (optional)
    

        //  1️⃣ Delete old file if exists
        if ($user->profile && Storage::disk('public')->exists('uploads/'.$user->profile)) {
            Storage::disk('public')->delete('uploads/'.$user->profile);
        }

            $tmp = $request->file('profile');
            $fileName = time().'_'.$tmp->getClientOriginalName();
            $filePath = $tmp->storeAs('uploads', $fileName, 'public');

           $data['profile'] = $fileName;
        }
        

        // Update user with given data 
        $user->update($data);

        return $this->sendResponse($user, 'Profile updated successfully.');
    }


































        /**
         * Logout authenticated user according to device_id
         * We need to:
         * Get the authenticated user from the JWT token.
         * Accept a device_id from the client.
         * Delete the refresh token associated with that device.
         * Invalidate the current JWT token.
         * Return a JSON response.
         */
        public function logout(Request $request)
        {
            // Validate that device_id is provided
            $validator = Validator::make($request->all(), [
                'device_id' => 'required|string'
            ]);

            if ($validator->fails()) {
                return $this->sendError('The device id must be required.', [], 401);
            }

            try {

                // Get the authenticated user from JWT
                $user = JWTAuth::parseToken()->authenticate();

                // Delete refresh token for this device
                $deleted = $user->refreshTokens()
                ->where('device_id', $request->device_id)
                ->delete();

                if (!$deleted) {
                    return $this->sendError('Invalid device_id or no token found for this device', [], 404);
                }

                // Invalidate current JWT token
                JWTAuth::invalidate(JWTAuth::getToken());

                return $this->sendResponse([], 'Logged out successfully from this device');

            } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
                return $this->sendError('Token has expired', [], 401);
            } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
                 return $this->sendError('Token is invalid', [], 401);
            } catch (\Throwable $e) {
                 return $this->sendError($e->getMessage(), [], 500);
            }
        }
}
