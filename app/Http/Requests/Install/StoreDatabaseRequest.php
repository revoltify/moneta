<?php

declare(strict_types=1);

namespace App\Http\Requests\Install;

use Closure;
use Illuminate\Foundation\Http\FormRequest;

final class StoreDatabaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<Closure|string>>
     */
    public function rules(): array
    {
        return [
            'connection' => [
                'required',
                'string',
                'in:sqlite,mysql,mariadb,pgsql,sqlsrv',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $extension = config("installer.drivers.{$value}");

                    if (is_string($extension) && ! extension_loaded($extension)) {
                        $fail(__('The :extension PHP extension is not enabled on this server.', ['extension' => $extension]));
                    }
                },
            ],
            'host' => ['required_unless:connection,sqlite', 'nullable', 'string', 'max:255'],
            'port' => ['required_unless:connection,sqlite', 'nullable', 'integer', 'between:1,65535'],
            'database' => ['required_unless:connection,sqlite', 'nullable', 'string', 'max:255'],
            'username' => ['required_unless:connection,sqlite', 'nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
        ];
    }
}
