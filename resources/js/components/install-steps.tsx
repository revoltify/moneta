import { usePage } from '@inertiajs/react';
import { Check } from 'lucide-react';
import { Fragment } from 'react';
import { cn } from '@/lib/utils';

const steps = [
    { component: 'install/requirements', label: 'Requirements' },
    { component: 'install/database', label: 'Database' },
    { component: 'install/migrations', label: 'Migrations' },
    { component: 'install/admin', label: 'Admin' },
];

export default function InstallSteps() {
    const { component } = usePage();
    const currentIndex = steps.findIndex(
        (step) => step.component === component,
    );

    return (
        <div className="flex items-center justify-center gap-2">
            {steps.map((step, index) => (
                <Fragment key={step.component}>
                    {index > 0 && (
                        <div
                            className={cn(
                                'h-px w-8 sm:w-12',
                                index <= currentIndex
                                    ? 'bg-primary'
                                    : 'bg-border',
                            )}
                        />
                    )}
                    <div className="flex items-center gap-2">
                        <div
                            className={cn(
                                'flex size-7 items-center justify-center rounded-full text-xs font-medium',
                                index < currentIndex &&
                                    'bg-primary text-primary-foreground',
                                index === currentIndex &&
                                    'bg-primary text-primary-foreground ring-2 ring-primary/30',
                                index > currentIndex &&
                                    'bg-muted-foreground/10 text-muted-foreground',
                            )}
                        >
                            {index < currentIndex ? (
                                <Check className="size-4" />
                            ) : (
                                index + 1
                            )}
                        </div>
                        <span
                            className={cn(
                                'hidden text-sm sm:inline',
                                index === currentIndex
                                    ? 'font-medium text-foreground'
                                    : 'text-muted-foreground',
                            )}
                        >
                            {step.label}
                        </span>
                    </div>
                </Fragment>
            ))}
        </div>
    );
}
