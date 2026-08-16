<?php

namespace App\Http\Controllers;

use App\Rules\SecureFile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Pion\Laravel\ChunkUpload\Exceptions\UploadFailedException;
use Pion\Laravel\ChunkUpload\Exceptions\UploadMissingFileException;
use Pion\Laravel\ChunkUpload\Handler\HandlerFactory;
use Pion\Laravel\ChunkUpload\Receiver\FileReceiver;

class FileUploadController extends Controller
{
    /**
     * Handles the file upload
     *
     *
     * @return JsonResponse
     *
     * @throws UploadMissingFileException
     * @throws UploadFailedException
     */
    public function upload(Request $request)
    {
        // create the file receiver
        $receiver = $this->makeReceiver($request);

        // check if the upload is success, throw exception or return response you need
        if ($receiver->isUploaded() === false) {
            throw new UploadMissingFileException;
        }

        // receive the file
        $save = $receiver->receive();

        // check if the upload has finished (in chunk mode it will send smaller files)
        if ($save->isFinished()) {
            // save the file and return any response you need, current example uses `move` function. If you are
            // not using move, you need to manually delete the file by unlink($save->getFile()->getPathname())
            return $this->saveFile($save->getFile());
        }

        // we are in chunk mode, lets send the current progress
        /** @var AbstractHandler $handler */
        $handler = $save->handler();

        return response()->json([
            'done' => $handler->getPercentageDone(),
        ]);
    }

    /**
     * @return FileReceiver
     */
    protected function makeReceiver(Request $request)
    {
        return new FileReceiver('file', $request, HandlerFactory::classFromRequest($request));
    }

    /**
     * Saves the file
     *
     *
     * @return JsonResponse
     */
    protected function saveFile(UploadedFile $file)
    {
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'fbx', 'gltf', 'glb', 'bin'];
        $maxSizeKb = 100 * 1024; // 100MB

        $validator = Validator::make(['file' => $file], [
            'file' => ['required', 'file', new SecureFile($allowedExtensions, $maxSizeKb)],
        ]);

        if ($validator->fails()) {
            abort(422, $validator->errors()->first());
        }

        $fileName = $this->createFilename($file);
        $fileSize = $this->formatSizeUnits($file->getSize());
        // Group files by mime type
        $mime = str_replace('/', '-', $file->getMimeType());
        // Group files by the date week
        $dateFolder = date('Y-m-d');

        // Build the file path
        $filePath = "upload/{$mime}/{$dateFolder}/";
        $finalPath = storage_path('app/'.$filePath);

        // move the file name
        $file->move($finalPath, $fileName);

        return response()->json([
            'path' => $filePath,
            'name' => $fileName,
            'mime_type' => $mime,
            'size' => $fileSize,
        ]);
    }

    /**
     * Create unique filename for uploaded file
     *
     * @return string
     */
    protected function createFilename(UploadedFile $file)
    {
        $extension = $file->getClientOriginalExtension();
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $filename = Str::slug($originalName);

        // Add timestamp hash to name of the file
        $filename .= '_'.md5(time()).'.'.$extension;

        return $filename;
    }

    /**
     * Format file size to human readable format
     *
     * @param  int  $bytes
     * @return string
     */
    protected function formatSizeUnits($bytes)
    {
        if ($bytes >= 1073741824) {
            $bytes = number_format($bytes / 1073741824, 2).' GB';
        } elseif ($bytes >= 1048576) {
            $bytes = number_format($bytes / 1048576, 2).' MB';
        } elseif ($bytes >= 1024) {
            $bytes = number_format($bytes / 1024, 2).' KB';
        } elseif ($bytes > 1) {
            $bytes = $bytes.' bytes';
        } elseif ($bytes == 1) {
            $bytes = $bytes.' byte';
        } else {
            $bytes = '0 bytes';
        }

        return $bytes;
    }
}
