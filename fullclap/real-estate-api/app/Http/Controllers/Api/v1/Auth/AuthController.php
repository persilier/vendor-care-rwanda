<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
	public function register(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'name' => 'required|string|max:255',
			'email' => 'required|string|email|max:255|unique:users',
			'password' => 'required|string|min:8|confirmed',
		]);

		if ($validator->fails()) {
			return response()->json([
				'success' => false,
				'message' => 'Validation error',
				'data' => $validator->errors()
			], 422);
		}

		$user = User::create([
			'name' => $request->name,
			'email' => $request->email,
			'password' => Hash::make($request->password),
		]);

		$token = JWTAuth::fromUser($user);

		return response()->json([
			'success' => true,
			'message' => 'User successfully registered',
			'data' => [
				'user' => $user,
				'token' => $token,
				'token_type' => 'bearer',
				'expires_in' => config('jwt.ttl') * 60 // Convert minutes to seconds
			]
		], 201);
	}

	public function login(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'email' => 'required|email',
			'password' => 'required|string',
		]);

		if ($validator->fails()) {
			return response()->json([
				'success' => false,
				'message' => 'Validation error',
				'data' => $validator->errors()
			], 422);
		}

		$credentials = $request->only('email', 'password');
		if (!$token = JWTAuth::attempt($credentials)) {
			return response()->json([
				'success' => false,
				'message' => 'Invalid credentials',
				'data' => null
			], 401);
		}

		return response()->json([
			'success' => true,
			'message' => 'Successfully logged in',
			'data' => [
				'token' => $token,
				'token_type' => 'bearer',
				'expires_in' => config('jwt.ttl') * 60 // Convert minutes to seconds
			]
		]);
	}

	public function me()
	{
		return response()->json([
			'success' => true,
			'message' => 'User profile retrieved successfully',
			'data' => [
				'user' => JWTAuth::user()
			]
		]);
	}

	public function logout()
	{
		JWTAuth::invalidate(JWTAuth::getToken());
		
		return response()->json([
			'success' => true,
			'message' => 'Successfully logged out',
			'data' => null
		]);
	}

	public function refresh()
	{
		return response()->json([
			'success' => true,
			'message' => 'Token refreshed successfully',
			'data' => [
				'token' => JWTAuth::refresh(),
				'token_type' => 'bearer',
				'expires_in' => config('jwt.ttl') * 60 // Convert minutes to seconds
			]
		]);
	}

	public function updateProfile(Request $request)
    {
        $user = JWTAuth::user();
        
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => [
                'sometimes',
                'required',
                'email',
                Rule::unique('users')->ignore($user->id),
            ],
            'photo' => 'sometimes|nullable|image|mimes:jpeg,png,jpg|max:2048',
            'status' => 'sometimes|required|in:active,inactive,suspended',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'data' => $validator->errors()
            ], 422);
        }

        $updateData = [];
        if ($request->has('name')) {
            $updateData['name'] = $request->name;
        }
        if ($request->has('email')) {
            $updateData['email'] = $request->email;
        }
        if ($request->has('status')) {
            $updateData['status'] = $request->status;
        }

        // Handle photo upload
        if ($request->hasFile('photo')) {
            $user->clearMediaCollection('profile_photo');
            $user->addMediaFromRequest('photo')
                ->toMediaCollection('profile_photo');
            $updateData['photo'] = $user->getFirstMediaUrl('profile_photo');
        }

        $user->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => [
                'user' => $user
            ]
        ]);
    }

public function updatePassword(Request $request)
{
	$user = JWTAuth::user();
	
	$validator = Validator::make($request->all(), [
		'current_password' => 'required|string',
		'new_password' => 'required|string|min:8',
		'new_password_confirmation' => 'required|same:new_password'
	], [
		'new_password_confirmation.same' => 'The new password confirmation does not match.',
		'new_password.min' => 'The new password must be at least 8 characters.',
		'current_password.required' => 'The current password is required.',
	]);

	if ($validator->fails()) {
		return response()->json([
			'success' => false,
			'message' => 'Validation error',
			'data' => $validator->errors()
		], 422);
	}

	if (!Hash::check($request->current_password, $user->password)) {
		return response()->json([
			'success' => false,
			'message' => 'Current password is incorrect',
			'data' => null
		], 422);
	}

	$user->update([
		'password' => Hash::make($request->new_password)
	]);

	return response()->json([
		'success' => true,
		'message' => 'Password updated successfully',
		'data' => null
	]);
}
}
