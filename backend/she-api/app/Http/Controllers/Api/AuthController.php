<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateOwnProfileRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Throwable;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required',
        ]);

        $user = User::where('username', $request->username)->first();

        if (!$user || !Hash::check($request->password, $user->password_hash)) {
            throw ValidationException::withMessages([
                'username' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (!$user->is_active) {
            return response()->json(['message' => 'Account is deactivated'], 403);
        }

        $token = $user->createToken('she-inspection', ['*'], now()->addDays(30))->plainTextToken;

        $user->update(['last_login' => now()]);

        return response()->json([
            'token' => $token,
            'user' => array_merge($user->toArray(), ['permissions' => $user->permissionNames()]),
        ]);
    }

    public function register(Request $request)
    {
        if (app()->environment('production')) {
            abort(404);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username',
            'password' => 'required|min:8',
            'role' => 'in:inspector,viewer',
        ]);

        $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'password_hash' => Hash::make($request->password),
            'role' => $request->role ?? 'inspector',
            'is_active' => false,
        ]);

        $token = $user->createToken('she-inspection', ['*'], now()->addDays(30))->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user,
        ], 201);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully']);
    }

    public function me(Request $request)
    {
        $user = $request->user();

        return response()->json(array_merge($user->toArray(), [
            'permissions' => $user->permissionNames(),
        ]));
    }

    public function updateProfile(UpdateOwnProfileRequest $request)
    {
        $user = $request->user();
        $data = $request->validated();
        $newSignaturePath = null;
        $oldSignaturePath = $user->signature_path;

        if (filled($data['password'] ?? null)) {
            if (!Hash::check((string) ($data['current_password'] ?? ''), $user->password_hash)) {
                throw ValidationException::withMessages([
                    'current_password' => ['Password saat ini tidak sesuai.'],
                ]);
            }

            $data['password_hash'] = Hash::make($data['password']);
        }

        unset($data['password'], $data['password_confirmation'], $data['current_password'], $data['signature']);

        try {
            if ($request->hasFile('signature')) {
                $targetPath = public_path('images');
                File::ensureDirectoryExists($targetPath);
                $file = $request->file('signature');
                $extension = $file->getClientOriginalExtension() ?: 'png';
                $filename = 'user_signature_' . now()->format('YmdHisv') . '_' . uniqid() . '.' . $extension;
                $file->move($targetPath, $filename);
                $newSignaturePath = 'images/' . $filename;
                $data['signature_path'] = $newSignaturePath;
            }

            DB::transaction(fn () => $user->update($data));
        } catch (Throwable $exception) {
            if ($newSignaturePath) {
                $this->deleteProfileSignature($newSignaturePath);
            }
            report($exception);

            return response()->json([
                'message' => 'Profil gagal diperbarui. Silakan coba lagi.',
            ], 500);
        }

        if ($newSignaturePath && $oldSignaturePath && $oldSignaturePath !== $newSignaturePath) {
            $this->deleteProfileSignature($oldSignaturePath);
        }

        $user = $user->fresh();

        return response()->json([
            'message' => 'Profil berhasil diperbarui.',
            'user' => array_merge($user->toArray(), [
                'permissions' => $user->permissionNames(),
            ]),
        ]);
    }

    private function deleteProfileSignature(string $path): void
    {
        $normalized = str_replace('\\', '/', ltrim($path, '/'));

        if (!str_starts_with($normalized, 'images/')) {
            return;
        }

        $filename = substr($normalized, strlen('images/'));
        if ($filename === '' || basename($filename) !== $filename) {
            return;
        }

        File::delete(public_path('images/' . $filename));
    }
}
