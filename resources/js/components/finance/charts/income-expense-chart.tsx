import {
    Bar,
    BarChart,
    CartesianGrid,
    Legend,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';
import ChartTooltip from '@/components/finance/charts/chart-tooltip';
import { useCurrency } from '@/hooks/use-currency';

type Point = {
    income: number;
    expense: number;
    [key: string]: string | number;
};

export default function IncomeExpenseChart({
    data,
    xKey = 'month',
}: {
    data: Point[];
    xKey?: string;
}) {
    const { symbol } = useCurrency();

    return (
        <ResponsiveContainer width="100%" height={280}>
            <BarChart
                data={data}
                margin={{ top: 8, right: 8, left: 8, bottom: 0 }}
                barGap={2}
            >
                <CartesianGrid
                    vertical={false}
                    stroke="var(--border)"
                    strokeDasharray="3 3"
                />
                <XAxis
                    dataKey={xKey}
                    tickLine={false}
                    axisLine={false}
                    tick={{ fill: 'var(--muted-foreground)', fontSize: 12 }}
                />
                <YAxis
                    tickLine={false}
                    axisLine={false}
                    tick={{ fill: 'var(--muted-foreground)', fontSize: 12 }}
                    tickFormatter={(value: number) =>
                        `${symbol}${(value / 100000).toFixed(0)}k`
                    }
                />
                <Tooltip
                    content={<ChartTooltip />}
                    cursor={{ fill: 'var(--muted)', opacity: 0.4 }}
                />
                <Legend
                    iconType="circle"
                    iconSize={8}
                    formatter={(value: string) => (
                        <span className="text-sm text-muted-foreground">
                            {value}
                        </span>
                    )}
                />
                <Bar
                    dataKey="income"
                    name="Income"
                    fill="var(--chart-2)"
                    radius={[4, 4, 0, 0]}
                    maxBarSize={28}
                />
                <Bar
                    dataKey="expense"
                    name="Expense"
                    fill="var(--chart-1)"
                    radius={[4, 4, 0, 0]}
                    maxBarSize={28}
                />
            </BarChart>
        </ResponsiveContainer>
    );
}
