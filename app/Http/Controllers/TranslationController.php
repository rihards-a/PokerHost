<?php
// app/Http/Controllers/TranslationController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class TranslationController extends Controller
{
    public function getTranslations($locale)
    {
        $supportedLocales = ['en', 'lv'];
        
        if (!in_array($locale, $supportedLocales)) {
            return response()->json(['error' => 'Locale not supported'], 404);
        }
        
        $filePath = resource_path("lang/{$locale}.json");
        
        if (!File::exists($filePath)) {
            return response()->json(['error' => 'Translation file not found'], 404);
        }
        
        $translations = json_decode(File::get($filePath), true);
        
        return response()->json([
            'locale' => $locale,
            'translations' => $translations
        ]);
    }
    
    public function getAvailableLocales()
    {
        return response()->json([
            'locales' => [
                'en' => 'English',
                'lv' => 'Latviešu'
            ]
        ]);
    }
}