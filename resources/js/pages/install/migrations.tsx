import { Form, Head } from '@inertiajs/react';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { run } from '@/routes/install/migrations';

export default function Migrations() {
    return (
        <>
            <Head title="Install — Migrations" />

            <Form {...run.form()} className="flex flex-col gap-6">
                {({ processing, errors }) => (
                    <>
                        <Alert>
                            <AlertDescription>
                                This creates all database tables and generates
                                the API encryption keys. It only needs to run
                                once and may take a minute.
                            </AlertDescription>
                        </Alert>

                        {errors.setup && (
                            <Alert variant="destructive">
                                <AlertDescription>
                                    {errors.setup}
                                </AlertDescription>
                            </Alert>
                        )}

                        <Button
                            type="submit"
                            className="w-full"
                            disabled={processing}
                        >
                            {processing && <Spinner />}
                            {processing
                                ? 'Setting up the database…'
                                : 'Run database setup'}
                        </Button>
                    </>
                )}
            </Form>
        </>
    );
}

Migrations.layout = {
    title: 'Database setup',
    description: 'Create the tables Moneta needs to run',
};
