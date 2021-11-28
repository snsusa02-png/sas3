<?php
namespace App\Traits;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

trait DeleteFileTrait
{
    public function deleteOne($folder = null, $disk = 'public', $filename = null)
    {
        Storage::disk($disk)->delete($folder . $filename);
    }
}