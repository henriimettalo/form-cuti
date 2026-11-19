<?php

namespace App\Rules;

use App\Services\ClamavScanner;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;
use RuntimeException;
use ZipArchive;

class SafeUpload implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile || ($path = $value->getRealPath()) === false) {
            $fail('File tidak dapat diperiksa.');

            return;
        }

        if (in_array(strtolower($value->getClientOriginalExtension()), ['xlsx', 'docx'], true)
            && ! $this->isBoundedArchive($path)) {
            $fail('Paket dokumen rusak, terenkripsi, atau melebihi batas aman.');

            return;
        }

        if (! config('security.scan_uploads')) {
            return;
        }

        try {
            if (! app(ClamavScanner::class)->isClean($path)) {
                $fail('File tidak lolos pemeriksaan keamanan.');
            }
        } catch (RuntimeException $exception) {
            report($exception);
            $fail('Pemeriksaan keamanan file belum tersedia. Coba lagi nanti.');
        }
    }

    private function isBoundedArchive(string $path): bool
    {
        $archive = new ZipArchive;
        if ($archive->open($path, ZipArchive::RDONLY) !== true) {
            return false;
        }

        try {
            if ($archive->numFiles > 2000) {
                return false;
            }

            $total = 0;
            for ($index = 0; $index < $archive->numFiles; $index++) {
                $entry = $archive->statIndex($index);
                if ($entry === false || ($entry['encryption_method'] ?? 0) !== 0 || $entry['size'] > 25 * 1024 * 1024) {
                    return false;
                }
                $total += $entry['size'];
                if ($total > 80 * 1024 * 1024) {
                    return false;
                }
            }

            return true;
        } finally {
            $archive->close();
        }
    }
}
