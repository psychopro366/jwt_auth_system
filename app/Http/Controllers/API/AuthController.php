<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController as BaseController;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;
use DB;
use Illuminate\Support\Facades\Validator;

    class AuthController extends BaseController
    {
        /**
         * To register user
         */
        public function register(RegisterRequest $request): JsonResponse
        {
            DB::beginTransaction();

            try {

                //Get validated data 
                $data = $request->validated();

                // Retrived device info 
                $device = $this->deviceInfo();

                // Handle profile upload (validated in RegisterRequest)
                $path = $request->file('profile')->store('profiles', 'public');
                $data['profile'] = $path;

                // Handle hobbies safely
                $data['hobbies'] = isset($data['hobbies'])
                ? implode(',', $data['hobbies'])
                : null;

                $data['password'] = Hash::make($data['password']);
                $data['device_info'] = $device;

                // Create user
                $user = User::create($data);

                // Generate device_id
                $device['device_id'] = Str::uuid()->toString();

                $response = $this->createToken($user, $device);

                DB::commit();

                return $this->sendResponse($response, 'User registered successfully.', 201);

            } catch (\Throwable $e) {
                DB::rollBack();
                return $this->sendError($e->getMessage(), [], 500);
            }
        }


        /**
         * Login User through their credentials 
         */
        public function login(LoginRequest $request): JsonResponse
        {
            try {
                //Retrieves credentials fromm the user 
                $credentials = $request->safe()->only('email', 'password');

                // checks if user exists 
                $user = User::where('email', $credentials['email'])->first();

                // verify user password 
                if (!$user || !Hash::check($credentials['password'], $user->password)) {
                    return $this->sendError('Invalid credentials.', [], 401);
                }

                $device = $this->deviceInfo();
                $device['device_id'] = Str::uuid()->toString();

                $response = $this->createToken($user, $device);

                return $this->sendResponse($response, 'Logged in successfully.');

            } catch (\Throwable $e) {
                //\Log::error($e);
                return $this->sendError($e->getMessage(), [], 500);
            }
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











