<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ScramblrService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class SettingsController
{
    public function __construct(
        protected ScramblrService $scramblrService
    ) {}

    private const PROFILE_PHOTO_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    public function index(Request $request)
    {
        $user = $request->user();
        $profilePhotoPath = $this->findProfilePhotoPath($user);
        $profilePhotoUrl = $profilePhotoPath
            ? route('blob.serve', [
                'scrambldToken' => $this->scramblrService->Scramble($profilePhotoPath),
            ])
            : null;

        return view('settings.index', compact('user', 'profilePhotoUrl'));
    }

    public function updateProfilePhoto(Request $request)
    {
        $request->validate([
            'profile_photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'profile_photo.required' => 'Pilih foto profil terlebih dahulu.',
            'profile_photo.image' => 'File yang dipilih harus berupa gambar.',
            'profile_photo.mimes' => 'Foto harus berformat JPG, PNG, atau WebP.',
            'profile_photo.max' => 'Ukuran foto maksimal 2 MB.',
        ]);

        $user = $request->user();
        $file = $request->file('profile_photo');
        $extension = strtolower($file->guessExtension() ?: $file->getClientOriginalExtension());

        if (! in_array($extension, self::PROFILE_PHOTO_EXTENSIONS, true)) {
            $extension = match ($file->getMimeType()) {
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
                default => 'jpg',
            };
        }

        $disk = Storage::disk('local');
        $directory = 'profile-photos/'.$user->getKey();

        // Each user's profile-photo directory is reserved for their current avatar.
        foreach (self::PROFILE_PHOTO_EXTENSIONS as $oldExtension) {
            $oldPath = $directory.'/avatar.'.$oldExtension;
            if ($disk->exists($oldPath)) {
                $disk->delete($oldPath);
            }
        }

        $disk->putFileAs($directory, $file, 'avatar.'.$extension);

        return redirect()
            ->route('settings.index')
            ->with('status', 'profile-photo-updated');
    }

    public function resetProfilePhoto(Request $request)
    {
        $user = $request->user();
        $disk = Storage::disk('local');
        $directory = 'profile-photos/'.$user->getKey();

        foreach (self::PROFILE_PHOTO_EXTENSIONS as $extension) {
            $path = $directory.'/avatar.'.$extension;
            if ($disk->exists($path)) {
                $disk->delete($path);
            }
        }

        return redirect()
            ->route('settings.index')
            ->with('status', 'profile-photo-reset');
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'current_password.required' => 'Masukkan kata sandi saat ini.',
            'current_password.current_password' => 'Kata sandi saat ini tidak sesuai.',
            'password.required' => 'Masukkan kata sandi baru.',
            'password.confirmed' => 'Konfirmasi kata sandi baru tidak cocok.',
            'password.min' => 'Kata sandi baru minimal 8 karakter.',
        ]);

        $user = $request->user();
        // The User model's hashed cast hashes the new plain-text password on save.
        $user->password = $validated['password'];
        $user->save();

        return redirect()
            ->route('settings.index')
            ->with('status', 'password-updated');
    }

    private function findProfilePhotoPath(User $user): ?string
    {
        $disk = Storage::disk('local');
        $directory = 'profile-photos/'.$user->getKey();

        foreach (self::PROFILE_PHOTO_EXTENSIONS as $extension) {
            $path = $directory.'/avatar.'.$extension;
            if ($disk->exists($path)) {
                return $path;
            }
        }

        return null;
    }
}
