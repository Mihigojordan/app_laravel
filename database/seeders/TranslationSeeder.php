<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class TranslationSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/translations');
        $files = File::files($path);

        foreach ($files as $file) {
            $locale = pathinfo($file, PATHINFO_FILENAME); // 'en', 'ar', etc.
            $translations = require $file;
            $now = now();

            $rows = [];
            foreach ($translations as $key => $value) {
                $rows[] = [
                    'locale' => $locale,
                    'key' => $key,
                    'value' => $value,
                    'is_default' => $locale === 'en' ? 1 : 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            // Batch upsert instead of one round-trip per key - a single
            // locale file can have ~1000 keys, and 24 locales at one
            // query per row made this seeder take tens of minutes.
            foreach (array_chunk($rows, 500) as $chunk) {
                DB::table('translations')->upsert(
                    $chunk,
                    ['locale', 'key'],
                    ['value', 'is_default', 'updated_at']
                );
            }
        }
    }
}
