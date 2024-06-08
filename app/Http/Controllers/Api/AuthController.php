<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\OTPMailController;
use Exception;
use Illuminate\Support\Facades\Validator;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    protected $user;
    public function __construct(User $user)
    {
        // model as dependency injection
        $this->user = $user;
    }

    public function register(Request $request)
    {
        // validate the incoming request
        // set every field as required
        // set email field so it only accept the valid email format

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:2|max:255',
            'email' => 'required|string|email:rfc,dns|max:255|unique:users',
            'password' => 'required|numeric|min:6|max:255',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'meta' => [
                    'code' => 422,
                    'status' => 'error',
                    'message' => 'Name,Email and Password are required.',
                ],
                'errors' => $validator->errors(),
            ], 422);
        }

        // if the request valid, create user

        $user = $this->user::create([
            'name' => $request['name'],
            'email' => $request['email'],
            'password' => bcrypt($request['password']),
        ]);


        $mail = new OTPMailController();
        $mail = $mail->sendMail($user->id);
        // return the response as json
        if ($mail) {
            return response()->json([
                'meta' => [
                    'code' => 200,
                    'status' => 'success',
                    'message' => 'OTP has been sent successfully!',
                ],
                'data' => [
                    'user' => $user,

                ],
            ]);
        } else {
            return response()->json([
                'meta' => [
                    'code' => 500,
                    'status' => 'error',
                    'message' => 'Email not sent!',
                ],
            ], 500);
        }
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'meta' => [
                    'code' => 422,
                    'status' => 'error',
                    'message' => 'Email and Password are required.',
                ],
                'errors' => $validator->errors(),
            ], 422);
        }


        $user = User::where('email', $request->email)->first();


        if (empty($user)) {
            return response()->json([
                'meta' => [
                    'code' => 404,
                    'status' => 'error',
                    'message' => 'User not found!',
                ],
            ], 404);
        }

        if ($user->email_verified_at == null || empty($user->email_verified_at)) {
            $this->resendOTP($request);
            return response()->json([
                'meta' => [
                    'code' => 200,
                    'status' => 'success',
                    'message' => 'User not verified but OTP has sent to your email.',
                    'key' => 'verify',
                ],
            ], 200);
        }


        if ($user->email_verified_at != null || !empty($user->email_verified_at)) {
            // attempt a login (validate the credentials provided)
            $token = auth()->attempt([
                'email' => $request->email,
                'password' => $request->password,
            ]);

            // if token successfully generated then display success response
            // if attempt failed then "unauthenticated" will be returned automatically
            if ($token) {
                return response()->json([
                    'meta' => [
                        'code' => 200,
                        'status' => 'success',
                        'message' => 'Quote fetched successfully.',
                    ],
                    'data' => [
                        'user' => auth()->user(),
                        'access_token' => [
                            'token' => $token,
                            'type' => 'Bearer',
                            'expires_in' => auth()->factory()->getTTL() * 60,
                            //'expires_in' => Factory::getTTL() * 60,    // get token expires in seconds
                        ],
                    ],
                ]);
            } else {
                return response()->json([
                    'meta' => [
                        'code' => 401,
                        'status' => 'error',
                        'message' => 'Invalid credentials.',
                    ],
                ], 401);
            }
        } else {
            $mail = new OTPMailController();
            $mail = $mail->sendMail($user->id);
            if ($mail == false) {
                return response()->json([
                    'meta' => [
                        'code' => 500,
                        'status' => 'error',
                        'message' => 'Email server error.',
                    ],
                ], 500);
            }
            return response()->json([
                'meta' => [
                    'code' => 401,
                    'status' => 'error',
                    'message' => 'User is not verified. OTP has been sent at his email',
                ],
            ], 401);
        }
    }

    public function resendOTP(Request $request) //Same can be use for forgot password
    {
        $user_email = $request->input('email');
        if (!$user_email) {
            return response()->json([
                'meta' => [
                    'code' => 422,
                    'status' => 'error',
                    'message' => 'User email is missing.',
                ],
            ], 422);
        }

        $user = User::where('email', $user_email)->first();
        if (empty($user)) {
            return response()->json([
                'meta' => [
                    'code' => 404,
                    'status' => 'error',
                    'message' => 'User not found.',
                ],
            ], 404);
        } else {
            $mailSent = (new OTPMailController())->sendMail($user->id);
            if ($mailSent) {
                return response()->json([
                    'meta' => [
                        'code' => 200,
                        'status' => 'success',
                        'message' => 'OTP has been sent to your email.',
                    ],
                ]);
            } else {
                return response()->json([
                    'meta' => [
                        'code' => 500,
                        'status' => 'error',
                        'message' => 'Mail server error.',
                    ],
                ], 500);
            }
        }
    }

    public function verifyOTP(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string',
            'otp' => 'required|numeric',
            'key' => ['required', 'string', function ($attribute, $value, $fail) {
                if (!in_array($value, ['forgot', 'verify'])) {
                    $fail('Invalid key.');
                }
            }],
        ], [
            'email.required' => 'Email is required.',
            'otp.required' => 'OTP is required.',
            'key.required' => 'Key is required.',
            'key.in' => 'Invalid key.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'meta' => [
                    'code' => 422,
                    'status' => 'error',
                    'message' => 'Invalid input.',
                ],
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json([
                'meta' => [
                    'code' => 404,
                    'status' => 'error',
                    'message' => 'Email doesn\'t exist.',
                ],
            ], 404);
        }

        if ($user->OTP != $request->otp) {
            return response()->json([
                'meta' => [
                    'code' => 401,
                    'status' => 'error',
                    'message' => 'Invalid OTP.',
                ],
            ], 401);
        }

        if (date('Y-m-d H:i:s', strtotime($user->otp_expiry_time)) < date('Y-m-d H:i:s')) {
            $this->resendOTP($request);
            return response()->json([
                'meta' => [
                    'code' => 401,
                    'status' => 'error',
                    'message' => 'OTP has expired and email has been sent to your email.',
                ],
            ], 401);
        }

        $user->otp = null;
        $user->otp_expiry_time = null;
        if ($request->key == 'verify') {
            $user->email_verified_at = now();
        } else {
            if ($user->save()) {
                return response()->json([
                    'meta' => [
                        'code' => 200,
                        'status' => 'success',
                        'message' => 'redirect to new password page',
                    ],
                ]);
            } else {
                return response()->json([
                    'meta' => [
                        'code' => 500,
                        'status' => 'error',
                        'message' => 'Server error.',
                    ],
                ], 500);
            }
        }
        if ($user->save()) {
            return response()->json([
                'meta' => [
                    'code' => 200,
                    'status' => 'success',
                    'message' => 'User has been verified.',
                ],
            ]);
        } else {
            return response()->json([
                'meta' => [
                    'code' => 500,
                    'status' => 'error',
                    'message' => 'Failed to save user.',
                ],
            ], 500);
        }
    }

    public function updatePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'meta' => [
                    'code' => 422,
                    'status' => 'error',
                    'message' => 'Invalid input.',
                ],
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::where('email', $request->email)->first();
        if ($user) {
            $user->password = bcrypt($request->password);
            $user->save();
            return response()->json([
                'meta' => [
                    'code' => 200,
                    'status' => 'success',
                    'message' => 'Password has been updated.',
                ],
            ]);
        } else {
            return response()->json([
                'meta' => [
                    'code' => 404,
                    'status' => 'error',
                    'message' => 'User not found.',
                ],
            ], 404);
        }
    }
    public function logout()
    {
        try {
            // get token
            $token = JWTAuth::getToken();

            // invalidate token
            JWTAuth::invalidate($token);
            return response()->json([
                'meta' => [
                    'code' => 200,
                    'status' => 'success',
                    'message' => 'Successfully logged out',
                ],
                'data' => [],
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'meta' => [
                    'code' => 500,
                    'status' => 'error',
                    'message' => 'Invalid token',
                ],
                'data' => [],
            ]);
        }
    }
}
