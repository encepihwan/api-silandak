<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DownloadController extends Controller
{
    public function download($filename)
    {
        // Tentukan path ke file yang akan didownload
        $pathToFile = storage_path('app/template/' . $filename);
        // Periksa apakah file ada
        if (!Storage::exists('template/',$filename)) {
            abort(404);
        }
        // Lakukan proses download file
        return response()->download($pathToFile);
    }
}
