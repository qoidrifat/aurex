<?php

namespace App\Http\Controllers;

use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\ResendVerificationRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // ==================== REGISTRATION ====================

    /**
     * Register user baru.
     *
     * Membuat akun baru, mengirim email verifikasi,
     * dan mengembalikan token autentikasi.
     *
     * @param RegisterRequest $request Validasi terpusat dari FormRequest
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(RegisterRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Kirim email verifikasi
        $user->sendEmailVerificationNotification();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => new UserResource($user),
            'message' => 'Registrasi berhasil. Silakan cek email untuk verifikasi.',
        ]);
    }

    // ==================== LOGIN ====================

    /**
     * Login user dengan email dan password.
     *
     * Mendukung flag `require_verified` untuk memeriksa
     * apakah email sudah diverifikasi sebelum login.
     *
     * @param LoginRequest $request Validasi terpusat dari FormRequest
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function login(LoginRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
        }

        // Cek apakah email sudah diverifikasi
        if ($request->has('require_verified') && !$user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email belum diverifikasi. Silakan cek email Anda.',
                'needs_verification' => true,
                'email' => $user->email,
            ], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => new UserResource($user),
        ]);
    }

    // ==================== LOGOUT ====================

    /**
     * Logout user — hapus token saat ini.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }

    // ==================== EMAIL VERIFICATION ====================

    /**
     * Verifikasi email user.
     */
    public function verifyEmail(Request $request, $id, $hash)
    {
        $user = User::findOrFail($id);

        // Validasi hash
        if (!hash_equals($hash, sha1($user->getEmailForVerification()))) {
            return response()->json(['message' => 'Link verifikasi tidak valid.'], 400);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email sudah diverifikasi.']);
        }

        $user->markEmailAsVerified();

        return response()->json(['message' => 'Email berhasil diverifikasi!']);
    }

    /**
     * Kirim ulang email verifikasi.
     */
    public function resendVerification(ResendVerificationRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email sudah diverifikasi.']);
        }

        $user->sendEmailVerificationNotification();

        return response()->json(['message' => 'Email verifikasi telah dikirim ulang.']);
    }

    // ==================== PASSWORD RESET ====================

    /**
     * Kirim link reset password ke email.
     */
    public function forgotPassword(ForgotPasswordRequest $request)
    {
        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json([
                'message' => 'Link reset password telah dikirim ke email Anda.',
            ]);
        }

        return response()->json([
            'message' => 'Gagal mengirim link reset password. Silakan coba lagi.',
        ], 500);
    }

    /**
     * Reset password dengan token.
     */
    public function resetPassword(ResetPasswordRequest $request)
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'message' => 'Password berhasil direset. Silakan login dengan password baru.',
            ]);
        }

        return response()->json([
            'message' => 'Token reset password tidak valid atau sudah kadaluarsa.',
        ], 400);
    }
}
