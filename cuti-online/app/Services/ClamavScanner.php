<?php

namespace App\Services;

use RuntimeException;

class ClamavScanner
{
    public function isClean(string $path): bool
    {
        $host = config('security.clamd_host');
        $port = config('security.clamd_port');
        $socket = @stream_socket_client("tcp://{$host}:{$port}", timeout: 3);

        if ($socket === false) {
            throw new RuntimeException('Upload scanner unavailable.');
        }

        $file = @fopen($path, 'rb');

        try {
            if ($file === false) {
                throw new RuntimeException('Could not open upload for scanning.');
            }

            stream_set_timeout($socket, 10);
            $this->send($socket, "zINSTREAM\0");

            while (! feof($file)) {
                $chunk = fread($file, 65536);
                if ($chunk === false || ($chunk === '' && ! feof($file))) {
                    throw new RuntimeException('Could not read upload for scanning.');
                }

                if ($chunk !== '') {
                    $this->send($socket, pack('N', strlen($chunk)).$chunk);
                }
            }

            $this->send($socket, pack('N', 0));
            $result = stream_get_line($socket, 4096, "\0");
            if (! is_string($result) || ! str_starts_with($result, 'stream: ')) {
                throw new RuntimeException('Upload scan did not complete.');
            }

            if (str_ends_with($result, ' FOUND')) {
                return false;
            }

            if ($result !== 'stream: OK') {
                throw new RuntimeException('Upload scan could not verify the file.');
            }

            return true;
        } finally {
            if (is_resource($file)) {
                fclose($file);
            }
            fclose($socket);
        }
    }

    private function send(mixed $socket, string $data): void
    {
        while ($data !== '') {
            $written = @fwrite($socket, $data);
            if ($written === false || $written === 0) {
                throw new RuntimeException('Could not send upload to scanner.');
            }

            $data = substr($data, $written);
        }
    }
}
