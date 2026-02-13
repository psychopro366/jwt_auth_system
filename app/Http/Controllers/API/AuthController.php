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
use DB;

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
}
