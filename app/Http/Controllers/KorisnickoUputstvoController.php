<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class KorisnickoUputstvoController extends Controller
{
    protected string $pdfPath = 'uputstvo/КПРМ корисничко упутство.pdf';

    protected string $videoPath = 'uputstvo/Обука о коришћењу платформе за унос података о конкурсним поступцима.mp4';

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
     * response()->download (BinaryFileResponse) шаље фајл директно — без учитавања
     * целог видеа у меморију (важно за велике фајлове).
     */
    public function videoDownload(): BinaryFileResponse
    {
        abort_unless(Storage::disk('local')->exists($this->videoPath), 404);

        return response()->download(
            Storage::disk('local')->path($this->videoPath),
            'Обука о коришћењу платформе за унос података о конкурсним поступцима.mp4'
        );
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
