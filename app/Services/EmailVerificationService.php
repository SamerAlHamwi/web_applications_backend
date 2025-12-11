<?php
// app/Services/EmailVerificationService.php
namespace App\Services;

use App\Models\User;
use App\Models\EmailVerification;
use App\Repositories\EmailVerificationRepository;
use App\Repositories\UserRepository;
use App\Mail\VerifyEmailMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class EmailVerificationService
{
    public function __construct(
        private EmailVerificationRepository $verificationRepository,
        private UserRepository $userRepository
    ) {}

    /**
     * Generate and send verification email
     */
    public function sendVerificationEmail(User $user): EmailVerification
    {
        // Revoke any existing verification tokens for this user
        $this->verificationRepository->revokeAllForUser($user->id);

        // Generate new verification token
        $verification = $this->verificationRepository->create([
            'user_id' => $user->id,
            'code' => EmailVerification::generateCode(),

            'expires_at' => Carbon::now()->addHours(24), // Valid for 24 hours
        ]);

        // Send verification email
        Mail::to($user->email)->send(new VerifyEmailMail($user, $verification));

        return $verification;
    }

    /**
     * Verify email with token
     */
    public function verifyEmail(string $token): User
    {
        // Find verification token
        $verification = $this->verificationRepository->findByCode($token);


        if (!$verification) {
            throw ValidationException::withMessages([
                'token' => ['Invalid verification token.']
            ]);
        }

        if (!$verification->isValid()) {
            throw ValidationException::withMessages([
                'token' => ['Verification token has expired or already been used.']
            ]);
        }

        // Get user
        $user = $verification->user;

        // Mark email as verified
        $user->markEmailAsVerified();

        // Mark verification token as used
        $this->verificationRepository->markAsUsed($verification);

        return $user;
    }

    /**
     * Resend verification email
     */
    public function resendVerificationEmail(string $email): EmailVerification
    {
        $user = $this->userRepository->findByEmail($email);

        if (!$user) {
            throw ValidationException::withMessages([
                'email' => ['User not found.']
            ]);
        }

        if ($user->hasVerifiedEmail()) {
            throw ValidationException::withMessages([
                'email' => ['Email already verified.']
            ]);
        }

        return $this->sendVerificationEmail($user);
    }
}
