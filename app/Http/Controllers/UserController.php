<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class UserController extends Controller
{
    /**
     * Check if the request content type is application/json.
     * Return a JSON error response with 415 status if not.
     */
    private function checkJsonContentType(Request $request)
    {
        if ($request->header('Content-Type') !== 'application/json') {
            return response()->json([
                'status' => false,
                'message' => 'Content-Type must be application/json'
            ], 415);
        }
        return null;
    }

    // Register
    public function register(Request $request)
    {
        $contentTypeCheck = $this->checkJsonContentType($request);
        if ($contentTypeCheck) {
            return $contentTypeCheck;
        }

        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users',
            'number'    => 'required|string|max:15|min:10', // requires password_confirmation
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
 

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email, 
            'number'=> $request->number,
            'role' => 2
        ]);

        // Generate OTP
        $otp =  123456;
        $user->otp = $otp;
        $user->otp_created_at = getCurrentDateTimeIndia();
        $user->save();

        // Send OTP email
        // Mail::raw("Your OTP for registration is: $otp", function ($message) use ($user) {
        //     $message->to($user->email)
        //             ->subject('Registration OTP');
        // });

        return response()->json([
            'status' => true,
            'otpRequired' => true,  
            'message' => 'Registration successful, OTP sent to your email',
            'otp' => $otp,
            'number' => $request->number,
        ], 201);
    }

    // Login
    public function login(Request $request)
    {
        $contentTypeCheck = $this->checkJsonContentType($request);
        if ($contentTypeCheck) {
            return $contentTypeCheck;
        }

        $request->validate([
            'email_or_number' => 'required|string',
        ]);

        $user = User::where('email', $request->email_or_number)
                    ->orWhere('number', $request->email_or_number)
                    ->first();

        if (!$user) {
            return response()->json(['status' => false, 'message' => 'User not found'], 404);
        }

        // Generate OTP
        // $otp = rand(100000, 999999);
        $otp =  123456;
        $user->otp = $otp;
        $user->otp_created_at = getCurrentDateTimeIndia();
        $user->save();
        // Send OTP email
        // Mail::raw("Your OTP for login is: $otp", function ($message) use ($user) {
        //     $message->to($user->email)
        //             ->subject('Login OTP');
        // });

        return response()->json([
            'status'=> true,
            'otpRequired' => true, 
            'message' => 'OTP sent to your email',
            'otp' => $otp,
            'number' => $user->number,
        ]);
    }

    // Verify OTP
    public function verifyOtp(Request $request)
    {
        $contentTypeCheck = $this->checkJsonContentType($request);
        if ($contentTypeCheck) {
            return $contentTypeCheck;
        }

        $request->validate([
            'number' => 'required|string',
            'otp'   => 'required|digits:6',
        ]);

        $user = User::where('number', $request->number)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Check OTP validity (assuming otp and otp_created_at columns exist)
        $otpValidDuration = 10; // minutes
        $otpCreated = Carbon::parse($user->otp_created_at);
        if (!$user->otp || $user->otp != $request->otp || !$otpCreated || $otpCreated->diffInMinutes(now()) > $otpValidDuration) {
            return response()->json(['message' => 'Invalid or expired OTP'], 401);
        }

        // OTP is valid, clear OTP fields
        $user->otp = null;
        $user->otp_created_at = null;
        $user->save();

        // Create auth token
        $token = $user->createToken('auth_token')->plainTextToken;
       
        // User plan
        $plan = false;
        $UserPlan = DB::table('user_plans')->select('plan_id', 'plan_name', 'start_date', 'expiry_date')->where(['user_id' => $user->id, 'status' => 0])->orderBy('id', 'desc')->get(); 
        if($UserPlan->count() > 0)
        {
            $plan = $UserPlan;
        }
        
        $user = ['id' => $user->id, 
                 'name' => $user->name,
                 'email' => $user->email,
                 'number' => $user->number];

        return response()->json([
            'status' => true,
            'message' => 'OTP verified successfully',
            'user'  => $user,
            'token'   => $token,
            'plan' => $plan,
        ]);
    }

    // Get Profile (Authenticated)
    public function profile(Request $request)
    {
        $contentTypeCheck = $this->checkJsonContentType($request);
        if ($contentTypeCheck) {
            return $contentTypeCheck;
        }
        return response()->json($request->user());
    }

    // Logout (Invalidate Token)
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }
}
