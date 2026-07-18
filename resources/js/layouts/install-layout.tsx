import AppLogoIcon from '@/components/app-logo-icon';
import InstallSteps from '@/components/install-steps';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

export default function InstallLayout({
    children,
    title = '',
    description = '',
}: {
    title?: string;
    description?: string;
    children: React.ReactNode;
}) {
    return (
        <div className="flex min-h-svh flex-col items-center justify-center gap-6 bg-muted p-6 md:p-10">
            <div className="flex w-full max-w-2xl flex-col gap-6">
                <div className="flex items-center justify-center gap-2 font-medium">
                    <div className="flex h-9 w-9 items-center justify-center">
                        <AppLogoIcon className="size-9 fill-current text-black dark:text-white" />
                    </div>
                </div>

                <InstallSteps />

                <Card className="rounded-xl">
                    <CardHeader className="px-10 pt-8 pb-0 text-center">
                        <CardTitle className="text-xl">{title}</CardTitle>
                        <CardDescription>{description}</CardDescription>
                    </CardHeader>
                    <CardContent className="px-10 py-8">{children}</CardContent>
                </Card>
            </div>
        </div>
    );
}
