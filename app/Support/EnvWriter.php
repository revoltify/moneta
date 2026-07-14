<?php

declare(strict_types=1);

namespace App\Support;

use RuntimeException;

final class EnvWriter
{
    /**
     * @param  array<string, string|int|null>  $values
     */
    public function set(array $values, ?string $path = null): void
    {
        $path ??= app()->environmentFilePath();

        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new RuntimeException("Unable to read environment file at {$path}.");
        }

        foreach ($values as $key => $value) {
            $line = $key.'='.$this->format($value);
            $pattern = '/^#?\s*'.preg_quote($key, '/').'\s*=\s*.*$/m';

            $contents = preg_match($pattern, $contents)
                ? (preg_replace($pattern, $line, $contents, 1) ?? $contents)
                : rtrim($contents, "\n")."\n".$line."\n";
        }

        if (file_put_contents($path, $contents) === false) {
            throw new RuntimeException("Unable to write environment file at {$path}.");
        }
    }

    private function format(string|int|null $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $value = (string) $value;

        if (preg_match('/^[A-Za-z0-9_.\-:\/]+$/', $value)) {
            return $value;
        }

        return "'".str_replace(['\\', "'"], ['\\\\', "\\'"], $value)."'";
    }
}
