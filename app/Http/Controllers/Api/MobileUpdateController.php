<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MobileUpdateController extends Controller
{
    private const MANIFEST_PATH = 'mobile-updates/android.json';

    private const APK_PATH = 'mobile-updates/latest.apk';

    public function show(): JsonResponse
    {
        $manifest = $this->manifest();

        return response()->json([
            'version_code' => (int) $manifest['version_code'],
            'version_name' => (string) $manifest['version_name'],
            'release_notes' => (string) ($manifest['release_notes'] ?? ''),
            'sha256' => (string) ($manifest['sha256'] ?? ''),
            'mandatory' => (bool) ($manifest['mandatory'] ?? false),
        ])->header('Cache-Control', 'no-store');
    }

    public function download(Request $request): BinaryFileResponse
    {
        $manifest = $this->manifest();
        $path = Storage::disk('local')->path(self::APK_PATH);
        abort_unless(is_file($path), 404, 'Le fichier de mise à jour est introuvable.');

        $version = preg_replace('/[^0-9.]/', '', (string) $manifest['version_name']) ?: 'latest';

        return response()->download(
            $path,
            'Marches-Maraichers-'.$version.'.apk',
            [
                'Content-Type' => 'application/vnd.android.package-archive',
                'Cache-Control' => 'private, no-store',
            ],
        );
    }

    /** @return array<string, mixed> */
    private function manifest(): array
    {
        $storage = Storage::disk('local');
        abort_unless($storage->exists(self::MANIFEST_PATH), 503, 'Les mises à jour Android ne sont pas configurées.');

        $manifest = json_decode($storage->get(self::MANIFEST_PATH), true);
        abort_unless(
            is_array($manifest)
                && isset($manifest['version_code'], $manifest['version_name'])
                && is_numeric($manifest['version_code'])
                && (int) $manifest['version_code'] > 0,
            503,
            'Le fichier de mise à jour Android est invalide.',
        );

        return $manifest;
    }
}