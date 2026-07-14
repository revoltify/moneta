import { Form, Head } from '@inertiajs/react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/install/database';

const connections = [
    { value: 'sqlite', label: 'SQLite', port: null },
    { value: 'mysql', label: 'MySQL', port: '3306' },
    { value: 'mariadb', label: 'MariaDB', port: '3306' },
    { value: 'pgsql', label: 'PostgreSQL', port: '5432' },
    { value: 'sqlsrv', label: 'SQL Server', port: '1433' },
];

export default function Database() {
    const [connection, setConnection] = useState('sqlite');
    const port = connections.find((c) => c.value === connection)?.port;

    return (
        <>
            <Head title="Install — Database" />

            <Form {...store.form()} className="flex flex-col gap-6">
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-2">
                            <Label htmlFor="connection">Database type</Label>
                            <Select
                                name="connection"
                                value={connection}
                                onValueChange={setConnection}
                            >
                                <SelectTrigger id="connection">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {connections.map((option) => (
                                        <SelectItem
                                            key={option.value}
                                            value={option.value}
                                        >
                                            {option.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.connection} />
                        </div>

                        {connection === 'sqlite' ? (
                            <Alert>
                                <AlertDescription>
                                    SQLite needs no configuration — the
                                    database file is created automatically in
                                    the storage directory.
                                </AlertDescription>
                            </Alert>
                        ) : (
                            <>
                                <div className="grid gap-6 sm:grid-cols-2">
                                    <div className="grid gap-2">
                                        <Label htmlFor="host">Host</Label>
                                        <Input
                                            id="host"
                                            name="host"
                                            defaultValue="127.0.0.1"
                                            required
                                        />
                                        <InputError message={errors.host} />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="port">Port</Label>
                                        <Input
                                            key={connection}
                                            id="port"
                                            name="port"
                                            type="number"
                                            defaultValue={port ?? ''}
                                            required
                                        />
                                        <InputError message={errors.port} />
                                    </div>
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="database">
                                        Database name
                                    </Label>
                                    <Input
                                        id="database"
                                        name="database"
                                        required
                                    />
                                    <InputError message={errors.database} />
                                </div>

                                <div className="grid gap-6 sm:grid-cols-2">
                                    <div className="grid gap-2">
                                        <Label htmlFor="username">
                                            Username
                                        </Label>
                                        <Input
                                            id="username"
                                            name="username"
                                            required
                                        />
                                        <InputError
                                            message={errors.username}
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="password">
                                            Password
                                        </Label>
                                        <PasswordInput
                                            id="password"
                                            name="password"
                                            autoComplete="new-password"
                                        />
                                        <InputError
                                            message={errors.password}
                                        />
                                    </div>
                                </div>
                            </>
                        )}

                        <Button
                            type="submit"
                            className="w-full"
                            disabled={processing}
                        >
                            {processing && <Spinner />}
                            {processing
                                ? 'Testing connection…'
                                : 'Test connection & continue'}
                        </Button>
                    </>
                )}
            </Form>
        </>
    );
}

Database.layout = {
    title: 'Database configuration',
    description:
        'Choose your database and enter the connection details — Moneta tests the connection before saving',
};
