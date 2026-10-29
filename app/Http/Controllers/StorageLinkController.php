<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class StorageLinkController extends Controller
{
    /**
     * Hit https://your-domain/storage-link to create public/storage → storage/app/public
     */
    public function __invoke(Request $request)
    {
        $result = self::ensureStorageLink();

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json($result, $result['ok'] ? 200 : 500);
        }

        $color = $result['ok'] ? '#166534' : '#991b1b';
        $bg = $result['ok'] ? '#f0fdf4' : '#fef2f2';

        return response(
            '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Storage Link</title></head>'
            .'<body style="font-family:sans-serif;background:'.$bg.';color:'.$color.';padding:40px;">'
            .'<h1>Storage Link</h1>'
            .'<p>'.e($result['message']).'</p>'
            .'<pre style="background:#fff;padding:12px;border-radius:8px;color:#111;">'
            .'link: '.e($result['link'])."\n"
            .'target: '.e($result['target'])."\n"
            .'status: '.e($result['status'])
            .'</pre>'
            .'<p><a href="/">Go to app</a></p>'
            .'</body></html>',
            $result['ok'] ? 200 : 500
        )->header('Content-Type', 'text/html');
    }

    public static function ensureStorageLink(): array
    {
        $link = public_path('storage');
        $target = storage_path('app/public');

        File::ensureDirectoryExists($target);

        if (is_link($link)) {
            $resolved = @realpath($link);
            $targetReal = @realpath($target);

            if ($resolved && $targetReal && $resolved === $targetReal) {
                return [
                    'ok' => true,
                    'status' => 'already_linked',
                    'message' => 'Storage link already established.',
                    'link' => $link,
                    'target' => $target,
                ];
            }

            @unlink($link);
        }

        if (file_exists($link) && ! is_link($link)) {
            return [
                'ok' => false,
                'status' => 'blocked',
                'message' => 'public/storage already exists as a real folder. Delete it on the server, then open /storage-link again.',
                'link' => $link,
                'target' => $target,
            ];
        }

        $relativeTarget = '../storage/app/public';

        try {
            $created = @symlink($relativeTarget, $link);
            if (! $created) {
                $created = @symlink($target, $link);
            }

            if ($created || is_link($link)) {
                return [
                    'ok' => true,
                    'status' => 'created',
                    'message' => 'Storage link created successfully.',
                    'link' => $link,
                    'target' => $target,
                ];
            }
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'status' => 'error',
                'message' => 'Symlink failed: '.$e->getMessage().'. Enable symlink() or create it manually in cPanel File Manager.',
                'link' => $link,
                'target' => $target,
            ];
        }

        return [
            'ok' => false,
            'status' => 'failed',
            'message' => 'Could not create symlink. In cPanel File Manager open public/ and create a symlink named "storage" → ../storage/app/public',
            'link' => $link,
            'target' => $target,
        ];
    }
}
