<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:10240|mimes:jpg,jpeg,png,webp,pdf,heic,heif',
        ]);

        $path = $request->file('file')->store('uploads', 'public');

        return response()->json([
            'message' => 'File berhasil diunggah.',
            'data' => [
                'path' => 'storage/' . $path,
                'name' => $request->file('file')->getClientOriginalName(),
            ],
        ], 201);
    }
}
