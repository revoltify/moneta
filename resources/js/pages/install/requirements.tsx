import { Head, Link } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableRow } from '@/components/ui/table';
import install from '@/routes/install';

type Requirements = {
    php: { version: string; required: string; passes: boolean };
    extensions: { name: string; loaded: boolean }[];
    drivers: { connection: string; extension: string; loaded: boolean }[];
    paths: { path: string; writable: boolean }[];
    passes: boolean;
};

type Props = {
    requirements: Requirements;
};

function StatusBadge({ passes, label }: { passes: boolean; label?: string }) {
    return (
        <Badge variant={passes ? 'secondary' : 'destructive'}>
            {label ?? (passes ? 'OK' : 'Missing')}
        </Badge>
    );
}

export default function Requirements({ requirements }: Props) {
    return (
        <>
            <Head title="Install — Requirements" />

            <div className="flex flex-col gap-6">
                <section className="grid gap-2">
                    <h2 className="text-sm font-medium">PHP</h2>
                    <Table>
                        <TableBody>
                            <TableRow>
                                <TableCell>
                                    PHP {requirements.php.required} or higher
                                </TableCell>
                                <TableCell className="text-right">
                                    <StatusBadge
                                        passes={requirements.php.passes}
                                        label={requirements.php.version}
                                    />
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </section>

                <section className="grid gap-2">
                    <h2 className="text-sm font-medium">PHP extensions</h2>
                    <Table>
                        <TableBody>
                            {requirements.extensions.map((extension) => (
                                <TableRow key={extension.name}>
                                    <TableCell>{extension.name}</TableCell>
                                    <TableCell className="text-right">
                                        <StatusBadge
                                            passes={extension.loaded}
                                        />
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </section>

                <section className="grid gap-2">
                    <h2 className="text-sm font-medium">
                        Database drivers (at least one required)
                    </h2>
                    <Table>
                        <TableBody>
                            {requirements.drivers.map((driver) => (
                                <TableRow key={driver.connection}>
                                    <TableCell>
                                        {driver.connection}
                                        <span className="ml-2 text-muted-foreground">
                                            {driver.extension}
                                        </span>
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <StatusBadge passes={driver.loaded} />
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </section>

                <section className="grid gap-2">
                    <h2 className="text-sm font-medium">Writable paths</h2>
                    <Table>
                        <TableBody>
                            {requirements.paths.map((path) => (
                                <TableRow key={path.path}>
                                    <TableCell className="font-mono text-xs">
                                        {path.path}
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <StatusBadge
                                            passes={path.writable}
                                            label={
                                                path.writable
                                                    ? 'Writable'
                                                    : 'Not writable'
                                            }
                                        />
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </section>

                <Button
                    asChild={requirements.passes}
                    disabled={!requirements.passes}
                    className="w-full"
                >
                    {requirements.passes ? (
                        <Link href={install.database()}>Continue</Link>
                    ) : (
                        <span>Fix the failing checks, then reload</span>
                    )}
                </Button>
            </div>
        </>
    );
}

Requirements.layout = {
    title: 'Server requirements',
    description: 'Make sure your server can run Moneta before continuing',
};
