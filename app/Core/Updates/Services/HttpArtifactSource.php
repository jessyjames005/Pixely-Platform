<?php

declare(strict_types=1);

namespace App\Core\Updates\Services;

use App\Core\Updates\Contracts\ArtifactHostPolicyInterface;
use App\Core\Updates\Contracts\ArtifactSourceInterface;
use App\Core\Updates\DTO\ArtifactReference;
use App\Core\Updates\Exceptions\UpdateVerificationException;
use RuntimeException;
use Throwable;

/** Downloads an artifact to a unique temporary file without extracting or executing it. */
final readonly class HttpArtifactSource implements ArtifactSourceInterface
{
    public function __construct(
        private ArtifactHostPolicyInterface $hostPolicy,
        private int $maxBytes = 104857600,
        private int $timeoutSeconds = 60,
    ) {
        if ($this->maxBytes < 1 || $this->timeoutSeconds < 1) {
            throw new RuntimeException('Artifact download limits must be positive.');
        }
    }

    public function download(ArtifactReference $artifact, string $stagingDirectory): string
    {
        if (!$this->hostPolicy->allows($artifact->provider, $artifact->uri)) {
            throw new UpdateVerificationException('Artifact URL is not allowed for its provider.');
        }
        if (!function_exists('curl_init')) {
            throw new RuntimeException('The PHP cURL extension is required for secure artifact downloads.');
        }
        if (!is_dir($stagingDirectory) && !mkdir($stagingDirectory, 0700, true) && !is_dir($stagingDirectory)) {
            throw new RuntimeException('Unable to create artifact staging directory.');
        }
        if (!is_writable($stagingDirectory)) {
            throw new RuntimeException('Artifact staging directory is not writable.');
        }

        $path = rtrim($stagingDirectory, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'artifact-'.bin2hex(random_bytes(16)).'.part';
        $stream = fopen($path, 'xb');
        if ($stream === false) {
            throw new RuntimeException('Unable to create staged artifact file.');
        }
        @chmod($path, 0600);

        $written = 0;
        $tooLarge = false;
        $maxBytes = $this->maxBytes;
        $curl = curl_init($artifact->uri);
        if ($curl === false) {
            fclose($stream);
            @unlink($path);
            throw new RuntimeException('Unable to initialise secure artifact download.');
        }

        try {
            curl_setopt_array($curl, [
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_MAXREDIRS => 0,
                CURLOPT_CONNECTTIMEOUT => min(10, $this->timeoutSeconds),
                CURLOPT_TIMEOUT => $this->timeoutSeconds,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
                CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTPS,
                CURLOPT_WRITEFUNCTION => static function ($handle, string $chunk) use ($stream, &$written, &$tooLarge, $maxBytes): int {
                    $length = strlen($chunk);
                    if ($written + $length > $maxBytes) {
                        $tooLarge = true;
                        return 0;
                    }
                    $count = fwrite($stream, $chunk);
                    if ($count === false) {
                        return 0;
                    }
                    $written += $count;
                    return $count;
                },
            ]);
            $success = curl_exec($curl);
            $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
            if ($tooLarge) {
                throw new RuntimeException('Artifact exceeds the configured download size limit.');
            }
            if ($success === false) {
                throw new RuntimeException('Artifact download failed: '.curl_error($curl));
            }
            if ($status < 200 || $status >= 300) {
                throw new RuntimeException('Artifact host returned HTTP '.$status.'.');
            }
            if ($written === 0) {
                throw new RuntimeException('Artifact host returned an empty artifact.');
            }
            fflush($stream);
        } catch (Throwable $exception) {
            fclose($stream);
            curl_close($curl);
            @unlink($path);
            throw $exception;
        }

        fclose($stream);
        curl_close($curl);

        return $path;
    }
}
