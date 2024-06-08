<?php

namespace App\Http\Controllers;

use App\Mail\OtpMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class OTPMailController extends Controller
{
    public function sendMail($user_id)
    {
        $otp = rand(100000, 999999);

        $email = \App\Models\User::where('id', $user_id)->update([
            'otp' => $otp,
            'otp_expiry_time' => now()->addMinutes(30),
        ]);
        $user = User::find($user_id);

        $details = [
            'title' => 'Your New OTP ' . $otp . ' sent by Lazim Application',

        ];

        // Customize the logic for sending the email
        $recipient = $user->email;

        // Send the email using the Mailable class
        $sent = Mail::to($recipient)->send(new OtpMail($details));

        return $sent ? true : false;
    }
}
