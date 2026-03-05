<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class ImageLibraryController extends Controller
{
    public function index()
    {
        // корневая папка с картинками
        $dir = public_path('img');

        if (!is_dir($dir)) {
            return response()->json([
                'images' => [],
            ]);
        }

        $files = File::allFiles($dir);

        // вернём относительные пути относительно public
        $images = [];
        foreach ($files as $file) {
            $fullPath = $file->getPathname();
            $relative = str_replace(public_path() . DIRECTORY_SEPARATOR, '', $fullPath);
            // только изображения
            if (preg_match('/\.(jpe?g|png|gif|webp|svg)$/i', $relative)) {
                $images[] = $relative; // например: "img/doom1.png"
            }
        }

        sort($images);

        return response()->json([
            'images' => $images,
        ]);
    }
}
