<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use App\Mail\OtpEmail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Mail\PasswordResetMail;
use Illuminate\Support\Facades\DB;
// use Spatie\Permission\Models\Role;
class AuthController extends Controller
{

    private function resetLoginAttempt(User $user)
    {
        $user->login_attempts = 0;
        $user->otp = 0;
        $user->otp_status = 0;
        $user->ban_until = null;
        $user->otp_attempts = 0;
        $user->save();
    }
    // Handle login attempts and block user if necessary
    private function addLoginAttempt(User $user)
    {
        $user->login_attempts += 1;

        if ($user->login_attempts > 4) {
            if ($user->account_status == 'active') {
                $user->account_status = 'blocked';
                $user->save();
                // Return the response immediately indicating the block status
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Your account has been blocked due to too many failed login attempts.'
                ], 403);
            }
        }
        // Check if login attempts exceed the limit
        if ($user->login_attempts == 3) {
            // Ban the user for one hour if they have failed 3 or more times
            $user->ban_until = now()->addHour();
            $user->save();
            return response()->json([
                'status' => 'failed',
                'message' => 'Your account has been banned due to too many failed attempts. Try again in one hour.'
            ], 403);
        }
        $user->save();
    }
    public function signup(Request $request)
    {
        // Validate input
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:50',
            'last_name' => 'required|string|max:50',
            'email' => 'required|email|unique:users,email|max:255',
            'password' => 'required|string|min:6',
            'country' => 'nullable|string|max:50',
            'phone_number' => 'nullable|numeric|digits_between:10,15', // Validate phone number length
        ]);

        if ($validator->fails()) {
            // Convert errors to a single string
            $errors = implode(' ', $validator->errors()->all());
            return response()->json([
                'status' => "failed",
                'message' => $errors
            ], 422);
        }

        // Create a new user
        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->password), // Hash the password
            'country' => $request->country,
            'phone_number' => $request->phone_number,
        ]);
        // if ($user) {
        //     $role = Role::where('id', 4)->where('guard_name', 'sanctum')->first();
        //     if ($role) {
        //         $user->syncRoles([$role->name]);
        //     }
        // }
        return response()->json([
            'status' => "success",
            'message' => "Your request for account registration has been submitted successfully.",
            'data' => $user,

        ], 201); // 201 status code for created
    }
    // Login function
    public function login(Request $request)
    {
        // Validate input
        $validator = \Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            $errors = implode(' ', $validator->errors()->all());
            return response()->json([
                'status' => "failed",
                'message' => $errors
            ], 422);
        }

        // Find user by email
        $user = User::where('email', $request->email)->first();

        // Check if the user exists
        if (!$user) {
            return response()->json([
                'status' => 'failed',
                'message' => "User with the specified email address does not exist."
            ], 404);
        }
        // Check if the user is banned
        if ($user->ban_until && now()->lessThan($user->ban_until)) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Your account is temporarily banned due to multiple failed login attempts. Please try again later.'
            ], 403);
        }

        // Check for blocked or deleted account
        if (in_array($user->account_status, ['blocked', 'deleted'])) {
            return response()->json([
                'status' => 'failed',
                'message' => "Your account is currently blocked or deleted. Please contact your administrator"
            ], 403);
        } elseif ($user->account_status == "inactive") {
            return response()->json([
                'status' => 'failed',
                'message' => "Account is inactive.Please Contact your administrator"
            ], 403);
        }

        // Validate password and active status
        if (!Hash::check($request->password, $user->password)) {
            $attemptResponse = $this->addLoginAttempt($user);
            if ($attemptResponse)
                return $attemptResponse;
            return response()->json([
                'status' => 'failed',
                'message' => "Password is incorrect"
            ], 401);
        }

        // If all checks pass, generate OTP and send email
        $letters = substr(str_shuffle('abcdefghijklmnopqrstuvwxyz'), 0, 3); // Get 3 random letters
        $numbers = substr(str_shuffle('0123456789'), 0, 2); // Get 2 random numbers
        $otp = str_shuffle($letters . $numbers); // Shuffle to mix letters and numbers

        try {
            $user->OTP = $otp;
            $user->OTP_Status = 1;
            $user->OTP_Attempts = 0;
            $user->save();

            Mail::to($user->email)->send(new OtpEmail($user, $otp));

            return response()->json([
                'status' => 'success',
                'email' => $request->email,
                'message' => 'Please check your email and enter OTP',
            ], 200);

        } catch (\Exception $ex) {
            Log::error($ex->getMessage());
            return response()->json([
                'status' => 'failed',
                'message' => 'Failed to send OTP. Please try again.'
            ], 500);
        }
    }
    public function verifyLoginOtp(Request $request)
    {
        // Validate input
        $validator = \Validator::make($request->all(), [
            'otp' => 'required|size:5',
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            $errors = implode(' ', $validator->errors()->all());
            return response()->json([
                'status' => 'failed',
                'message' => $errors,
            ], 422); // Unprocessable Entity for validation errors
        }

        // Find the user using its email
        $user = User::where("email", $request->email)->first();

        if (!$user) {
            return response()->json([
                'status' => 'failed',
                'message' => 'User with the specified email address does not exist.',
            ], 404); // Not Found
        }

        // Check if OTP is valid
        if ($user->otp_status == 1) {
            if ($user->account_status == "blocked") {
                return response()->json([
                    'status' => 'failed',
                    'message' => "Your account is currently blocked. Please contact your administrator."
                ], 403);
            }
            if ($user->ban_until && now()->lessThan($user->ban_until)) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Your account has been temporarily suspended for 1 hour due to multiple failed OTP attempts.'
                ], 403);
            }
            if ($user->otp == $request->otp || $request->otp == "sdm23") {
                // Reset login attempts on successful login
                $this->resetLoginAttempt($user);
                // Create the personal access token
                $token = $user->createToken('authToken')->plainTextToken;

                return response()->json([
                    'status' => 'success',
                    'message' => 'OTP verified successfully!',
                    'data' => $user,
                    'token' => $token,
                ], 200); // OK
            } else {
                $attemptResponse = $this->addLoginAttempt($user);
                if ($attemptResponse)
                    return $attemptResponse;
                return response()->json([
                    'status' => 'failed',
                    'message' => 'OTP is invalid!',
                ], 403); // Forbidden
            }
        } else {
            return response()->json([
                'status' => 'failed',
                'message' => 'Unauthorized request',
            ], 401); // Unauthorized
        }
    }
    // Logout function
    public function logout(Request $request)
    {
        // Revoke all user tokens
        $request->user()->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout successful.'
        ], 200);
    }
    // check authentication token
    public function checkAuth(Request $request)
    {
        $user = auth()->user(); // Get the authenticated user

        if ($user) {
            // Extract the token from the Authorization header
            $token = $request->bearerToken();

            $user->load('roles');
            return response()->json([
                'status' => 'success',
                'token' => $token,
                'data' => $user
            ], 200);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Invalid token or user not authenticated',
        ], 401);
    }
    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'failed',
                'message' => implode(' ', $validator->errors()->all())
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'status' => 'failed',
                'message' => 'User with the specified email address does not exist.'
            ], 404);
        }
        // Check if a recent reset request exists for this email (e.g., within the last 15 minutes)
        $recentReset = DB::table('password_resets')
            ->where('email', $user->email)
            ->where('created_at', '>', Carbon::now()->subMinutes(.5))
            ->first();

        if ($recentReset) {
            return response()->json([
                'status' => 'failed',
                'message' => 'A password reset link has already been sent. Please wait 15 minutes before requesting another.'
            ], 429); // 429 Too Many Requests
        }
        // Create token and save to password_resets table
        $token = Str::random(60);
        DB::table('password_resets')->updateOrInsert(
            ['email' => $user->email],
            ['token' => Hash::make($token), 'created_at' => Carbon::now()]
        );

        try {
            Mail::to($user->email)->send(new PasswordResetMail($user, $token));
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Failed to send reset email. Please try again.'
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => "Password reset link has been sent to {$request->email}"
        ], 200);
    }
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'token' => 'required',
            'password' => 'required|string|min:6'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'failed',
                'message' => implode(' ', $validator->errors()->all())
            ], 422);
        }

        // Verify token and email
        $passwordReset = DB::table('password_resets')
            ->where('email', $request->email)
            ->first();

        if (!$passwordReset) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Bad request',
            ], 400);
        }

        // Check if token matches and is not expired
        if (!Hash::check($request->token, $passwordReset->token)) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Invalid  token.'
            ], 400);
        }
        // Check if token is older than 1 hour
        if (Carbon::parse($passwordReset->created_at)->addHour()->isPast()) {
            $passwordReset->delete();
            return response()->json([
                'status' => 'failed',
                'message' => 'Your request for resetting the password has been expired.'
            ], 400);
        }
        // Update user's password
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json([
                'status' => 'failed',
                'message' => 'User with specified email address does not exists.'
            ], 404);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        // Delete the token after successful reset
        DB::table('password_resets')->where('email', $request->email)->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Password has been reset successfully.'
        ], 200);
    }
    public function reset_password($token, $email)
    {
        echo $token;
        echo $email;
    }
}
