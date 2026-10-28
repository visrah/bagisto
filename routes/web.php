<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

/*
 * Public media from the s3 disk, for deployments where the bucket has no public hostname. Only the directories Bagisto
 * stores as public are served; downloadable files, RMA attachments and imports stay private.
 */
Route::get('s3/{path}', function (string $path) {
    abort_if(str_contains($path, '..') || str_contains($path, 'downloadable') || ! Storage::disk('s3')->exists($path), 404);

    return Storage::disk('s3')->response($path, null, ['Cache-Control' => 'public, max-age=86400']);
})->where('path', '(themes|products|categories|channels|reviews|attribute-options|configurations)/.+');
