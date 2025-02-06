<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Mail\OtpMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Str;

class AuthController extends Controller
{

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:' . User::class,
            'gender' => 'required|string',
            'phoneNumber' => 'required|string|max:11',
            'password' => ['sometimes', 'nullable', 'confirmed', \Illuminate\Validation\Rules\Password::defaults()],
            'avatar' => ['nullable'],
        ]);

        if (!str_ends_with($request->email, '@student.uniten.edu.my')) {
            return response()->json(['error' => 'You can only register with a valid Uniten student email.'], 422);
        }

        $role = strpos($request->email, '@unilyft') !== false ? 'admin' : 'user';
        $otp = rand(100000, 999999);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'gender' => $request->gender,
            'phoneNumber' => $request->phoneNumber,
            'password' => Hash::make($request->password),
            'role' => $role,
            'otp' => $otp,
        ]);

        if ($request->hasFile('avatar')) {
            $user->clearMediaCollection('user_avatar');
            $file = $request->file('avatar');
            $user
                ->addMedia($file)
                ->toMediaCollection('user_avatar');
        }

        // Send OTP email
        Mail::to($user->email)->send(new OtpMail($otp));

        $user->save();

        return response()->json([
            'token' => $user->createToken('auth-token')->plainTextToken,
            'user' => $user,
        ]);
    }

    public function login(LoginRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (is_null($user->email_verified_at)) {
            return response()->json(['message' => 'Please verify your email.'], 403);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user,
        ]);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'otp' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->where('otp', $request->otp)->first();

        if (!$user) {
            return response()->json(['message' => 'Invalid OTP.'], 403);
        }

        $user->email_verified_at = now();
        $user->otp = null;
        $user->save();

        return response()->json(['message' => 'Email verified successfully.']);
    }

    public function resendEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        if ($user->email_verified_at) {
            return response()->json(['message' => 'Email already verified.'], 400);
        }

        $otp = rand(100000, 999999);
        $user->otp = $otp;
        $user->save();

        Mail::to($user->email)->send(new OtpMail($otp));

        return response()->json(['message' => 'Verification email resent successfully.']);
    }

    public function logout(Request $request)
    {
        auth()->user()->tokens()->delete();
    }

    public function me(Request $request)
    {
        $token = $request->bearerToken();
        if (!$token) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Verify the token is valid and not expired
        // if (!$this->verifyToken($token)) {
        //     return response()->json(['error' => 'Invalid token'], 401);
        // }

        // Return the user data
        return response()->json(['user' => auth()->user()]);
    }
}
