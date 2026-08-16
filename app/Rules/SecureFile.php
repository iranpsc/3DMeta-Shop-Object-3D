<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

class SecureFile implements ValidationRule
{
    protected $allowedExtensions;

    protected $maxSize; // in Kilobytes

    /**
     * @param  array  $allowedExtensions  List of allowed file extensions (e.g. ['jpg', 'png'])
     * @param  int|null  $maxSize  Max size in KB (optional, but recommended if not handled elsewhere)
     */
    public function __construct(array $allowedExtensions = [], ?int $maxSize = null)
    {
        $this->allowedExtensions = array_map('strtolower', $allowedExtensions);
        $this->maxSize = $maxSize;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile) {
            $fail('The :attribute must be a valid file.');

            return;
        }

        // 1. Check upload errors
        if ($value->getError() !== UPLOAD_ERR_OK) {
            $fail("The :attribute failed to upload (Error code: {$value->getError()}).");

            return;
        }

        // 2. Check Size (if configured)
        if ($this->maxSize && $value->getSize() > $this->maxSize * 1024) {
            $fail("The :attribute must not be greater than {$this->maxSize} kilobytes.");

            return;
        }

        $extension = strtolower($value->getClientOriginalExtension());
        $filename = $value->getClientOriginalName();

        // 3. Allowed Extensions Whitelist
        if (! empty($this->allowedExtensions)) {
            if (! in_array($extension, $this->allowedExtensions)) {
                $fail("The :attribute extension (.$extension) is not allowed.");

                return;
            }
        }

        // 4. Double Extension & Forbidden Filename Check
        // Prevents bypassing Apache/Nginx rules (e.g., shell.php.jpg, .htaccess)
        if (preg_match('/\.(php|phtml|phar|pl|py|cgi|asp|aspx|jsp|exe|sh|bat|cmd|vbs|htaccess|htpasswd)\./i', $filename)) {
            $fail('The :attribute filename contains forbidden characters or extensions.');

            return;
        }

        // 5. Null Byte Injection
        if (strpos($filename, "\0") !== false || strpos($value->getRealPath(), "\0") !== false) {
            $fail('The :attribute contains invalid characters.');

            return;
        }

        // 6. MIME Type & Magic Bytes Check
        // We trust getMimeType() which uses fileinfo (magic bytes)
        $mime = $value->getMimeType();

        // Strict mapping for common types to prevent content-type spoofing
        $strictMimeMap = [
            'jpg' => ['image/jpeg', 'image/pjpeg'],
            'jpeg' => ['image/jpeg', 'image/pjpeg'],
            'png' => ['image/png'],
            'gif' => ['image/gif'],
            'webp' => ['image/webp'],
            'pdf' => ['application/pdf'],
            'zip' => ['application/zip', 'application/x-zip-compressed', 'multipart/x-zip'],
            'txt' => ['text/plain'],
        ];

        if (isset($strictMimeMap[$extension])) {
            if (! in_array($mime, $strictMimeMap[$extension])) {
                $fail("The :attribute content type ($mime) does not match its extension (.$extension).");

                return;
            }
        }

        // 7. Malicious Content Scan (Polyglot / Webshell detection)
        // We scan for PHP tags and suspicious script tags embedded in the file.
        // This is crucial for "valid" images that contain PHP code.

        // Only scan if the file is reasonable size to avoid memory exhaustion (e.g. < 10MB)
        // If larger, we assume server execution policies prevent it, or scan chunks.
        if ($value->getSize() < 10 * 1024 * 1024) {
            try {
                $content = file_get_contents($value->getRealPath());

                $blacklist = [
                    '<?php',
                    '<?=',
                    '<script language="php">',
                ];

                // If it's an image, also check for JS injection
                if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'])) {
                    $blacklist[] = '<script';
                    $blacklist[] = 'javascript:';
                    $blacklist[] = 'vbscript:';
                }

                foreach ($blacklist as $pattern) {
                    if (stripos($content, $pattern) !== false) {
                        $fail('The :attribute contains malicious code or forbidden patterns.');

                        return;
                    }
                }
            } catch (\Exception $e) {
                // If we can't read the file, fail safe
                $fail('The :attribute could not be scanned for security.');

                return;
            }
        }
    }
}
