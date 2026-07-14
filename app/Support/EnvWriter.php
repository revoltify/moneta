<?php

namespace App\Support;

use RuntimeException;

class EnvWriter
{
    /**
     * @param  array<string, string|null>  $values
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
            $pattern = '/^#?\s*'.preg_quote($key, '/').'=.*$/m';

            $contents = preg_match($pattern, $contents)
                ? (preg_replace($pattern, $line, $contents, 1) ?? $contents)
                : rtrim($contents, "\n")."\n".$line."\n";
        }

        file_put_contents($path, $contents);
    }

    private function format(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if (preg_match('/^[A-Za-z0-9_.\-:\/]+$/', $value)) {
            return $value;
        }

        return '"'.str_replace(['\\', '"'], ['\\\\', '\\"'], $value).'"';
    }
}
