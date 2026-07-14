<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class KorisnickoUputstvoController extends Controller
{
    public function __invoke(): StreamedResponse
    {
        $fileName = 'КПРМ корисничко упутство.pdf';
        $path = "uputstvo/{$fileName}";

        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->download($path, $fileName);
    }
}
