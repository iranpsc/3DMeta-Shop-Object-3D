<?php

namespace Tests\Unit;

use App\Http\Controllers\FileUploadController;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Pion\Laravel\ChunkUpload\Exceptions\UploadMissingFileException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class FileUploadControllerTest extends TestCase
{
    private function controllerWithReceiver(object $receiver): FileUploadController
    {
        return new class($receiver) extends FileUploadController
        {
            public function __construct(private object $fakeReceiver) {}

            protected function makeReceiver(Request $request)
            {
                return $this->fakeReceiver;
            }

            public function exposeSaveFile(UploadedFile $file)
            {
                return $this->saveFile($file);
            }

            public function exposeCreateFilename(UploadedFile $file): string
            {
                return $this->createFilename($file);
            }

            public function exposeFormatSizeUnits(int $bytes): string
            {
                return $this->formatSizeUnits($bytes);
            }
        };
    }

    private function plainController(): FileUploadController
    {
        return new class extends FileUploadController
        {
            public function exposeSaveFile(UploadedFile $file)
            {
                return $this->saveFile($file);
            }

            public function exposeCreateFilename(UploadedFile $file): string
            {
                return $this->createFilename($file);
            }

            public function exposeFormatSizeUnits(int $bytes): string
            {
                return $this->formatSizeUnits($bytes);
            }
        };
    }

    public function test_upload_throws_when_file_missing(): void
    {
        $receiver = new class
        {
            public function isUploaded(): bool
            {
                return false;
            }
        };

        $this->expectException(UploadMissingFileException::class);

        $this->controllerWithReceiver($receiver)->upload(Request::create('/upload', 'POST'));
    }

    public function test_upload_returns_progress_when_chunk_incomplete(): void
    {
        $handler = new class
        {
            public function getPercentageDone(): int
            {
                return 40;
            }
        };

        $save = new class($handler)
        {
            public function __construct(private object $handler) {}

            public function isFinished(): bool
            {
                return false;
            }

            public function handler(): object
            {
                return $this->handler;
            }
        };

        $receiver = new class($save)
        {
            public function __construct(private object $save) {}

            public function isUploaded(): bool
            {
                return true;
            }

            public function receive(): object
            {
                return $this->save;
            }
        };

        $response = $this->controllerWithReceiver($receiver)->upload(Request::create('/upload', 'POST'));

        $this->assertSame(40, $response->getData(true)['done']);
    }

    public function test_upload_saves_file_when_finished(): void
    {
        $file = UploadedFile::fake()->create('model.glb', 100, 'model/gltf-binary');

        $save = new class($file)
        {
            public function __construct(private UploadedFile $file) {}

            public function isFinished(): bool
            {
                return true;
            }

            public function getFile(): UploadedFile
            {
                return $this->file;
            }
        };

        $receiver = new class($save)
        {
            public function __construct(private object $save) {}

            public function isUploaded(): bool
            {
                return true;
            }

            public function receive(): object
            {
                return $this->save;
            }
        };

        $response = $this->controllerWithReceiver($receiver)->upload(Request::create('/upload', 'POST'));
        $payload = $response->getData(true);

        $this->assertArrayHasKey('path', $payload);
        $this->assertStringStartsWith('upload/', $payload['path']);
    }

    public function test_save_file_stores_allowed_upload(): void
    {
        $controller = $this->plainController();
        $file = UploadedFile::fake()->create('model.glb', 100, 'model/gltf-binary');

        $response = $controller->exposeSaveFile($file);
        $payload = $response->getData(true);

        $this->assertArrayHasKey('path', $payload);
        $this->assertArrayHasKey('name', $payload);
        $this->assertStringStartsWith('upload/', $payload['path']);
        $this->assertFileExists(storage_path('app/'.$payload['path'].$payload['name']));
    }

    public function test_save_file_rejects_disallowed_extension(): void
    {
        $controller = $this->plainController();
        $file = UploadedFile::fake()->create('shell.php', 10, 'application/x-php');

        $this->expectException(HttpException::class);

        $controller->exposeSaveFile($file);
    }

    public function test_create_filename_includes_extension(): void
    {
        $file = UploadedFile::fake()->create('My Model.glb', 10, 'model/gltf-binary');
        $filename = $this->plainController()->exposeCreateFilename($file);

        $this->assertStringEndsWith('.glb', $filename);
        $this->assertStringContainsString('my-model_', $filename);
    }

    public function test_format_size_units_covers_all_branches(): void
    {
        $controller = $this->plainController();

        $this->assertStringContainsString('GB', $controller->exposeFormatSizeUnits(1073741824));
        $this->assertStringContainsString('MB', $controller->exposeFormatSizeUnits(1048576));
        $this->assertStringContainsString('KB', $controller->exposeFormatSizeUnits(2048));
        $this->assertSame('2 bytes', $controller->exposeFormatSizeUnits(2));
        $this->assertSame('1 byte', $controller->exposeFormatSizeUnits(1));
        $this->assertSame('0 bytes', $controller->exposeFormatSizeUnits(0));
    }
}
