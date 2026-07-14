import { Head, router, usePage } from '@inertiajs/react';
import Money from '@/components/finance/money';
import Heading from '@/components/heading';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Separator } from '@/components/ui/separator';
import { balanceSheet, index as reportsIndex } from '@/routes/reports';

type Row = { id: number; name: string; amount: number };

type Props = {
    report: {
        assets: Row[];
        equity: Row[];
        retainedEarnings: number;
        totalAssets: number;
        totalEquity: number;
    };
    asOf: string;
};

function Section({
    title,
    rows,
    total,
    extraRows = [],
}: {
    title: string;
    rows: Row[];
    total: number;
    extraRows?: Array<{ name: string; amount: number }>;
}) {
    return (
        <div className="space-y-1">
            <h3 className="font-semibold">{title}</h3>
            {rows.map((row) => (
                <div
                    key={row.id}
                    className="flex items-center justify-between py-1"
                >
                    <span>{row.name}</span>
                    <Money amount={row.amount} />
                </div>
            ))}
            {extraRows.map((row) => (
                <div
                    key={row.name}
                    className="flex items-center justify-between py-1"
                >
                    <span>{row.name}</span>
                    <Money amount={row.amount} />
                </div>
            ))}
            {rows.length === 0 && extraRows.length === 0 ? (
                <p className="py-2 text-sm text-muted-foreground">
                    Nothing recorded.
                </p>
            ) : null}
            <Separator />
            <div className="flex items-center justify-between py-1 font-semibold">
                <span>Total {title.toLowerCase()}</span>
                <Money amount={total} />
            </div>
        </div>
    );
}

export default function BalanceSheetPage({ report, asOf }: Props) {
    const { currentCompany } = usePage().props;

    if (!currentCompany) {
        return null;
    }

    const balanced = report.totalAssets === report.totalEquity;

    return (
        <>
            <Head title="Balance Sheet" />

            <div className="flex flex-col space-y-6 p-4">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <Heading
                        title="Balance Sheet"
                        description={`As of ${asOf}`}
                    />
                    <Input
                        type="date"
                        value={asOf}
                        onChange={(event) =>
                            router.get(
                                balanceSheet.url({
                                    current_company: currentCompany.slug,
                                }),
                                { as_of: event.target.value },
                                { preserveState: true, preserveScroll: true },
                            )
                        }
                        className="w-40"
                        aria-label="As of date"
                    />
                </div>

                <Card className="max-w-2xl">
                    <CardContent className="space-y-6 pt-6">
                        <Section
                            title="Assets"
                            rows={report.assets}
                            total={report.totalAssets}
                        />
                        <Section
                            title="Equity"
                            rows={report.equity}
                            total={report.totalEquity}
                            extraRows={[
                                {
                                    name: 'Retained earnings',
                                    amount: report.retainedEarnings,
                                },
                            ]}
                        />

                        <div
                            className={`flex items-center justify-between rounded-lg p-3 text-sm font-medium ${balanced ? 'bg-emerald-50 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-red-50 text-red-800 dark:bg-red-950 dark:text-red-300'}`}
                        >
                            <span>Assets = Equity</span>
                            <span>
                                {balanced ? 'Balanced ✓' : 'Out of balance!'}
                            </span>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

BalanceSheetPage.layout = (props: {
    currentCompany?: { slug: string } | null;
}) => ({
    breadcrumbs: props.currentCompany
        ? [
              {
                  title: 'Reports',
                  href: reportsIndex({
                      current_company: props.currentCompany.slug,
                  }),
              },
              { title: 'Balance Sheet', href: '' },
          ]
        : [],
});
