import { Head, Link, usePage } from '@inertiajs/react';
import {
    CalendarDays,
    CalendarRange,
    ChartColumn,
    ChartPie,
    Scale,
    TrendingUp,
} from 'lucide-react';
import Heading from '@/components/heading';
import { Card, CardContent } from '@/components/ui/card';
import {
    balanceSheet,
    cashFlow,
    categoryBreakdown,
    dailySummary,
    incomeStatement,
    index,
    monthlySummary,
} from '@/routes/reports';

export default function ReportsIndex() {
    const { currentCompany } = usePage().props;

    if (!currentCompany) {
        return null;
    }

    const slug = { current_company: currentCompany.slug };

    const reports = [
        {
            title: 'Income Statement',
            description:
                'Income and expenses by category for any period, with net profit.',
            href: incomeStatement(slug),
            icon: ChartColumn,
        },
        {
            title: 'Balance Sheet',
            description:
                'What the business owns and how it was funded, at any date.',
            href: balanceSheet(slug),
            icon: Scale,
        },
        {
            title: 'Cash Flow',
            description:
                'Money in and out of your wallets, split into operating and capital activity.',
            href: cashFlow(slug),
            icon: TrendingUp,
        },
        {
            title: 'Category Breakdown',
            description:
                'Where money goes — spending or income share per category with a chart.',
            href: categoryBreakdown(slug),
            icon: ChartPie,
        },
        {
            title: 'Daily Summary',
            description:
                'Last 30 days side by side: income, expense and profit per day.',
            href: dailySummary(slug),
            icon: CalendarDays,
        },
        {
            title: 'Monthly Summary',
            description:
                'Last 12 months side by side: income, expense and profit per month.',
            href: monthlySummary(slug),
            icon: CalendarRange,
        },
    ];

    return (
        <>
            <Head title="Reports" />

            <div className="flex flex-col space-y-6 p-4">
                <Heading
                    title="Reports"
                    description="Everything the numbers can tell you, in one place"
                />

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {reports.map((report) => {
                        const Icon = report.icon;

                        return (
                            <Link key={report.title} href={report.href}>
                                <Card className="h-full transition-colors hover:bg-muted/50">
                                    <CardContent className="flex gap-3 pt-6">
                                        <span className="flex size-10 shrink-0 items-center justify-center rounded-lg bg-muted">
                                            <Icon className="size-5" />
                                        </span>
                                        <span>
                                            <span className="font-medium">
                                                {report.title}
                                            </span>
                                            <span className="mt-1 block text-sm text-muted-foreground">
                                                {report.description}
                                            </span>
                                        </span>
                                    </CardContent>
                                </Card>
                            </Link>
                        );
                    })}
                </div>
            </div>
        </>
    );
}

ReportsIndex.layout = (props: {
    currentCompany?: { slug: string } | null;
}) => ({
    breadcrumbs: props.currentCompany
        ? [
              {
                  title: 'Reports',
                  href: index({ current_company: props.currentCompany.slug }),
              },
          ]
        : [],
});
