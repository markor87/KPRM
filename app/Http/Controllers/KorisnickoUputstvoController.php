<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class KorisnickoUputstvoController extends Controller
{
    protected string $pdfPath = 'uputstvo/КПРМ корисничко упутство.pdf';

    protected string $videoPath = 'uputstvo/КПРМ корисничко упутство.mp4';

    /**
     * Преузимање PDF упутства.
     */
    public function pdf(): StreamedResponse
    {
        abort_unless(Storage::disk('local')->exists($this->pdfPath), 404);

        return Storage::disk('local')->download($this->pdfPath, 'КПРМ корисничко упутство.pdf');
    }

    /**
     * Преузимање видео упутства (.mp4).
     */
    public function videoDownload(): StreamedResponse
    {
        abort_unless(Storage::disk('local')->exists($this->videoPath), 404);

        return Storage::disk('local')->download($this->videoPath, 'КПРМ корисничко упутство.mp4');
    }

    /**
     * Стриминг видеа за гледање у апликацији.
     * BinaryFileResponse сам подржава HTTP Range (премотавање у <video>).
     */
    public function videoStream(): BinaryFileResponse
    {
        abort_unless(Storage::disk('local')->exists($this->videoPath), 404);

        return response()->file(Storage::disk('local')->path($this->videoPath));
    }
}
