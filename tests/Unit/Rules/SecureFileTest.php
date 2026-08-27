<?php

namespace Tests\Unit\Rules;

use App\Rules\SecureFile;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class SecureFileTest extends TestCase
{
    public function test_rejects_non_uploaded_file(): void
    {
        $rule = new SecureFile(['jpg']);
        $failed = false;

        $rule->validate('attachment', 'not-a-file', function () use (&$failed) {
            $failed = true;
        });

        $this->assertTrue($failed);
    }

    public function test_rejects_oversized_file(): void
    {
        $rule = new SecureFile(['jpg'], 1);
        $file = UploadedFile::fake()->image('big.jpg')->size(2048);
        $failed = false;

        $rule->validate('attachment', $file, function () use (&$failed) {
            $failed = true;
        });

        $this->assertTrue($failed);
    }

    public function test_rejects_disallowed_extension(): void
    {
        $rule = new SecureFile(['jpg']);
        $file = UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf');
        $failed = false;

        $rule->validate('attachment', $file, function () use (&$failed) {
            $failed = true;
        });

        $this->assertTrue($failed);
    }

    public function test_rejects_double_extension_filename(): void
    {
        $rule = new SecureFile(['jpg']);
        $file = UploadedFile::fake()->image('shell.php.jpg');
        $failed = false;

        $rule->validate('attachment', $file, function () use (&$failed) {
            $failed = true;
        });

        $this->assertTrue($failed);
    }

    public function test_rejects_mime_mismatch(): void
    {
        $rule = new SecureFile(['jpg']);
        $file = UploadedFile::fake()->create('photo.jpg', 10, 'application/pdf');
        $failed = false;

        $rule->validate('attachment', $file, function () use (&$failed) {
            $failed = true;
        });

        $this->assertTrue($failed);
    }

    public function test_accepts_valid_image(): void
    {
        $rule = new SecureFile(['jpg', 'jpeg', 'png']);
        $file = UploadedFile::fake()->image('photo.jpg');
        $failed = false;

        $rule->validate('attachment', $file, function () use (&$failed) {
            $failed = true;
        });

        $this->assertFalse($failed);
    }

    public function test_rejects_null_byte_in_filename(): void
    {
        $rule = new SecureFile(['jpg']);
        $path = tempnam(sys_get_temp_dir(), 'nullbyte');
        file_put_contents($path, 'x');
        $file = new UploadedFile($path, "evil\0.jpg", 'image/jpeg', null, true);
        $failed = false;

        $rule->validate('attachment', $file, function () use (&$failed) {
            $failed = true;
        });

        $this->assertTrue($failed);
        @unlink($path);
    }

    public function test_accepts_extension_without_strict_mime_map(): void
    {
        $rule = new SecureFile(['glb']);
        $file = UploadedFile::fake()->create('model.glb', 10, 'model/gltf-binary');
        $failed = false;

        $rule->validate('attachment', $file, function () use (&$failed) {
            $failed = true;
        });

        $this->assertFalse($failed);
    }

    public function test_skips_extension_whitelist_when_not_configured(): void
    {
        $rule = new SecureFile([]);
        $file = UploadedFile::fake()->create('model.glb', 10, 'model/gltf-binary');
        $failed = false;

        $rule->validate('attachment', $file, function () use (&$failed) {
            $failed = true;
        });

        $this->assertFalse($failed);
    }

    public function test_skips_malicious_content_scan_for_large_files(): void
    {
        $rule = new SecureFile(['glb']);
        $path = tempnam(sys_get_temp_dir(), 'large');
        file_put_contents($path, '<?php'.str_repeat('a', 11 * 1024 * 1024));
        $file = new UploadedFile($path, 'large.glb', 'model/gltf-binary', null, true);
        $failed = false;

        $rule->validate('attachment', $file, function () use (&$failed) {
            $failed = true;
        });

        $this->assertFalse($failed);
        @unlink($path);
    }
}
