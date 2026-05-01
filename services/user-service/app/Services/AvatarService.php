<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Profile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

final readonly class AvatarService
{
    private const ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/webp'];
    private const MAX_BYTES     = 2_097_152; // 2 MB
    private const SIZE          = 400;

    public function __construct(private string $disk) {}

    public function upload(Profile $profile, UploadedFile $file): string
    {
        $mime = $file->getMimeType() ?? '';
        if (!in_array($mime, self::ALLOWED_MIMES, true)) {
            throw new \InvalidArgumentException('Avatar must be jpeg, png, or webp');
        }
        if ($file->getSize() > self::MAX_BYTES) {
            throw new \InvalidArgumentException('Avatar must be smaller than 2 MB');
        }

        $resized  = $this->resize($file, $mime);
        $filename = 'avatars/'.$profile->id.'.jpg';

        Storage::disk($this->disk)->put($filename, $resized);

        if ($profile->avatar_url) {
            $old = 'avatars/'.$profile->id.'_old.jpg';
            // previous file already overwritten; nothing extra to clean up
        }

        $url = Storage::disk($this->disk)->url($filename);
        $profile->update(['avatar_url' => $url]);

        return $url;
    }

    public function delete(Profile $profile): void
    {
        if ($profile->avatar_url === null) {
            return;
        }

        Storage::disk($this->disk)->delete('avatars/'.$profile->id.'.jpg');
        $profile->update(['avatar_url' => null]);
    }

    private function resize(UploadedFile $file, string $mime): string
    {
        $src = match ($mime) {
            'image/jpeg' => imagecreatefromjpeg($file->getRealPath()),
            'image/png'  => imagecreatefrompng($file->getRealPath()),
            'image/webp' => imagecreatefromwebp($file->getRealPath()),
        };

        $dst = imagescale($src, self::SIZE, self::SIZE);

        ob_start();
        imagejpeg($dst, null, 90);
        $data = ob_get_clean();

        imagedestroy($src);
        imagedestroy($dst);

        return (string) $data;
    }
}