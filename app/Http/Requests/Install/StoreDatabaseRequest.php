<?php

declare(strict_types=1);

namespace App\Http\Requests\Install;

use Illuminate\Foundation\Http\FormRequest;

final class StoreDatabaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'connection' => ['required', 'string', 'in:sqlite,mysql,mariadb,pgsql,sqlsrv'],
            'host' => ['required_unless:connection,sqlite', 'nullable', 'string', 'max:255'],
            'port' => ['required_unless:connection,sqlite', 'nullable', 'integer', 'between:1,65535'],
            'database' => ['required_unless:connection,sqlite', 'nullable', 'string', 'max:255'],
            'username' => ['required_unless:connection,sqlite', 'nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
        ];
    }
}
