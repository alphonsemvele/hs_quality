import { cn } from '@/lib/utils';
import { useMemo, useState } from 'react';

export type ChartTone = 'brand' | 'sage' | 'warning' | 'danger' | 'neutral';

const TONE_STROKE: Record<ChartTone, string> = {
    brand: 'stroke-brand-500 dark:stroke-brand-400',
    sage: 'stroke-sage-500 dark:stroke-sage-400',
    warning: 'stroke-warning-500 dark:stroke-warning-400',
    danger: 'stroke-danger-500 dark:stroke-danger-400',
    neutral: 'stroke-ink-400 dark:stroke-ink-500',
};

const TONE_FILL: Record<ChartTone, string> = {
    brand: 'fill-brand-500/15 dark:fill-brand-400/15',
    sage: 'fill-sage-500/15 dark:fill-sage-400/15',
    warning: 'fill-warning-500/15 dark:fill-warning-400/15',
    danger: 'fill-danger-500/15 dark:fill-danger-400/15',
    neutral: 'fill-ink-400/15 dark:fill-ink-500/15',
};

const TONE_DOT: Record<ChartTone, string> = {
    brand: 'fill-brand-500 dark:fill-brand-400',
    sage: 'fill-sage-500 dark:fill-sage-400',
    warning: 'fill-warning-500 dark:fill-warning-400',
    danger: 'fill-danger-500 dark:fill-danger-400',
    neutral: 'fill-ink-400 dark:fill-ink-500',
};

const TONE_BAR: Record<ChartTone, string> = {
    brand: 'fill-brand-500 dark:fill-brand-400',
    sage: 'fill-sage-500 dark:fill-sage-400',
    warning: 'fill-warning-500 dark:fill-warning-400',
    danger: 'fill-danger-500 dark:fill-danger-400',
    neutral: 'fill-ink-400 dark:fill-ink-500',
};

const TONE_DONUT: Record<ChartTone, string> = TONE_BAR;

export interface SeriesPoint {
    label: string;
    value: number;
}

interface LineChartProps {
    data: SeriesPoint[];
    tone?: ChartTone;
    height?: number;
    showArea?: boolean;
    showDots?: boolean;
    valueFormatter?: (v: number) => string;
    yMin?: number;
    yMax?: number;
    ariaLabel?: string;
}

export function LineChart({
    data,
    tone = 'brand',
    height = 220,
    showArea = true,
    showDots = false,
    valueFormatter = (v) => v.toLocaleString('fr-FR'),
    yMin,
    yMax,
    ariaLabel,
}: LineChartProps) {
    const padding = { top: 16, right: 12, bottom: 28, left: 36 };
    const width = 600;
    const innerW = width - padding.left - padding.right;
    const innerH = height - padding.top - padding.bottom;
    const [hover, setHover] = useState<number | null>(null);

    const { points, areaPath, linePath, yTicks, xTicks } = useMemo(() => {
        if (data.length === 0) {
            return { points: [] as Array<{ x: number; y: number; raw: SeriesPoint }>, areaPath: '', linePath: '', yTicks: [], xTicks: [] };
        }
        const values = data.map((d) => d.value);
        const dataMin = Math.min(...values);
        const dataMax = Math.max(...values);
        const min = yMin ?? Math.min(0, dataMin);
        const max = yMax ?? (dataMax === min ? dataMax + 1 : dataMax);
        const range = max - min || 1;

        const pts = data.map((d, i) => ({
            x: padding.left + (data.length === 1 ? innerW / 2 : (innerW * i) / (data.length - 1)),
            y: padding.top + innerH - ((d.value - min) / range) * innerH,
            raw: d,
        }));

        const line = pts.map((p, i) => `${i === 0 ? 'M' : 'L'}${p.x.toFixed(2)},${p.y.toFixed(2)}`).join(' ');
        const area = pts.length
            ? `${line} L${pts[pts.length - 1].x.toFixed(2)},${(padding.top + innerH).toFixed(2)} L${pts[0].x.toFixed(2)},${(padding.top + innerH).toFixed(2)} Z`
            : '';

        const yTickCount = 4;
        const yt = Array.from({ length: yTickCount + 1 }, (_, i) => {
            const v = min + (range * i) / yTickCount;
            return { value: v, y: padding.top + innerH - (i * innerH) / yTickCount };
        });

        const maxXTicks = Math.min(data.length, 8);
        const step = Math.max(1, Math.ceil(data.length / maxXTicks));
        const xt = data
            .map((d, i) => ({ label: d.label, x: pts[i].x, idx: i }))
            .filter((_, i) => i % step === 0 || i === data.length - 1);

        return { points: pts, areaPath: area, linePath: line, yTicks: yt, xTicks: xt };
    }, [data, innerH, innerW, padding.left, padding.top, yMax, yMin]);

    if (data.length === 0) {
        return <div className="flex h-40 items-center justify-center text-xs text-ink-400">Pas de données</div>;
    }

    return (
        <div className="relative w-full" role="img" aria-label={ariaLabel ?? 'Graphique linéaire'}>
            <svg viewBox={`0 0 ${width} ${height}`} preserveAspectRatio="none" className="w-full" style={{ height }}>
                {/* gridlines */}
                {yTicks.map((t, i) => (
                    <line
                        key={i}
                        x1={padding.left}
                        x2={padding.left + innerW}
                        y1={t.y}
                        y2={t.y}
                        className="stroke-ink-100 dark:stroke-ink-700/60"
                        strokeWidth={1}
                        strokeDasharray={i === yTicks.length - 1 ? '0' : '2 3'}
                    />
                ))}
                {/* y-axis labels */}
                {yTicks.map((t, i) => (
                    <text
                        key={`yl-${i}`}
                        x={padding.left - 8}
                        y={t.y + 3}
                        textAnchor="end"
                        className="fill-ink-400 text-[10px] tabular-nums dark:fill-ink-500"
                    >
                        {valueFormatter(t.value)}
                    </text>
                ))}
                {/* x-axis labels */}
                {xTicks.map((t, i) => (
                    <text
                        key={`xl-${i}`}
                        x={t.x}
                        y={padding.top + innerH + 18}
                        textAnchor="middle"
                        className="fill-ink-400 text-[10px] dark:fill-ink-500"
                    >
                        {t.label}
                    </text>
                ))}
                {/* area */}
                {showArea && (
                    <path d={areaPath} className={TONE_FILL[tone]} />
                )}
                {/* line */}
                <path
                    d={linePath}
                    fill="none"
                    strokeWidth={2}
                    strokeLinejoin="round"
                    strokeLinecap="round"
                    className={TONE_STROKE[tone]}
                />
                {/* dots */}
                {(showDots || hover !== null) &&
                    points.map((p, i) => (
                        <circle
                            key={i}
                            cx={p.x}
                            cy={p.y}
                            r={hover === i ? 5 : showDots ? 3 : 0}
                            className={cn(TONE_DOT[tone], 'transition-all')}
                            stroke="white"
                            strokeWidth={hover === i ? 2 : 0}
                        />
                    ))}
                {/* hover crosshair */}
                {hover !== null && points[hover] && (
                    <line
                        x1={points[hover].x}
                        x2={points[hover].x}
                        y1={padding.top}
                        y2={padding.top + innerH}
                        className="stroke-ink-300 dark:stroke-ink-600"
                        strokeWidth={1}
                        strokeDasharray="3 3"
                    />
                )}
                {/* hit areas */}
                {points.map((p, i) => {
                    const segmentW = innerW / Math.max(points.length - 1, 1);
                    return (
                        <rect
                            key={`hit-${i}`}
                            x={p.x - segmentW / 2}
                            y={padding.top}
                            width={segmentW}
                            height={innerH}
                            fill="transparent"
                            onMouseEnter={() => setHover(i)}
                            onMouseLeave={() => setHover(null)}
                        />
                    );
                })}
            </svg>
            {hover !== null && points[hover] && (
                <Tooltip x={(points[hover].x / width) * 100} label={points[hover].raw.label} value={valueFormatter(points[hover].raw.value)} />
            )}
        </div>
    );
}

interface BarChartProps {
    data: SeriesPoint[];
    tone?: ChartTone;
    height?: number;
    valueFormatter?: (v: number) => string;
    ariaLabel?: string;
}

export function BarChart({
    data,
    tone = 'brand',
    height = 220,
    valueFormatter = (v) => v.toLocaleString('fr-FR'),
    ariaLabel,
}: BarChartProps) {
    const padding = { top: 16, right: 12, bottom: 28, left: 36 };
    const width = 600;
    const innerW = width - padding.left - padding.right;
    const innerH = height - padding.top - padding.bottom;
    const [hover, setHover] = useState<number | null>(null);

    const { bars, yTicks, max } = useMemo(() => {
        if (data.length === 0) {
            return { bars: [], yTicks: [], max: 0 };
        }
        const values = data.map((d) => d.value);
        const dataMax = Math.max(...values, 1);
        const m = dataMax + dataMax * 0.1;
        const gap = 4;
        const bw = Math.max(8, innerW / data.length - gap);

        const bs = data.map((d, i) => {
            const x = padding.left + (innerW * i) / data.length + (innerW / data.length - bw) / 2;
            const h = (d.value / m) * innerH;
            const y = padding.top + innerH - h;
            return { x, y, width: bw, height: h, raw: d, idx: i };
        });

        const yTickCount = 4;
        const yt = Array.from({ length: yTickCount + 1 }, (_, i) => {
            const v = (m * i) / yTickCount;
            return { value: v, y: padding.top + innerH - (i * innerH) / yTickCount };
        });

        return { bars: bs, yTicks: yt, max: m };
    }, [data, innerH, innerW, padding.left, padding.top]);

    if (data.length === 0 || max === 0) {
        return <div className="flex h-40 items-center justify-center text-xs text-ink-400">Pas de données</div>;
    }

    return (
        <div className="relative w-full" role="img" aria-label={ariaLabel ?? 'Graphique à barres'}>
            <svg viewBox={`0 0 ${width} ${height}`} preserveAspectRatio="none" className="w-full" style={{ height }}>
                {yTicks.map((t, i) => (
                    <line
                        key={i}
                        x1={padding.left}
                        x2={padding.left + innerW}
                        y1={t.y}
                        y2={t.y}
                        className="stroke-ink-100 dark:stroke-ink-700/60"
                        strokeWidth={1}
                        strokeDasharray={i === yTicks.length - 1 ? '0' : '2 3'}
                    />
                ))}
                {yTicks.map((t, i) => (
                    <text
                        key={`yl-${i}`}
                        x={padding.left - 8}
                        y={t.y + 3}
                        textAnchor="end"
                        className="fill-ink-400 text-[10px] tabular-nums dark:fill-ink-500"
                    >
                        {valueFormatter(Math.round(t.value))}
                    </text>
                ))}
                {bars.map((b, i) => (
                    <text
                        key={`xl-${i}`}
                        x={b.x + b.width / 2}
                        y={padding.top + innerH + 18}
                        textAnchor="middle"
                        className="fill-ink-400 text-[10px] dark:fill-ink-500"
                    >
                        {b.raw.label}
                    </text>
                ))}
                {bars.map((b, i) => (
                    <g key={i}>
                        <rect
                            x={b.x}
                            y={b.y}
                            width={b.width}
                            height={Math.max(b.height, 1)}
                            rx={3}
                            className={cn(TONE_BAR[tone], hover === i ? 'opacity-100' : 'opacity-90')}
                            onMouseEnter={() => setHover(i)}
                            onMouseLeave={() => setHover(null)}
                        />
                    </g>
                ))}
            </svg>
            {hover !== null && bars[hover] && (
                <Tooltip x={((bars[hover].x + bars[hover].width / 2) / width) * 100} label={bars[hover].raw.label} value={valueFormatter(bars[hover].raw.value)} />
            )}
        </div>
    );
}

interface DonutSegment {
    label: string;
    value: number;
    tone: ChartTone;
}

interface DonutChartProps {
    data: DonutSegment[];
    size?: number;
    centerLabel?: string;
    centerValue?: string;
    ariaLabel?: string;
}

export function DonutChart({ data, size = 200, centerLabel, centerValue, ariaLabel }: DonutChartProps) {
    const radius = size / 2;
    const stroke = size * 0.18;
    const innerR = radius - stroke;
    const C = 2 * Math.PI * innerR;
    const total = data.reduce((s, d) => s + d.value, 0);
    const [hover, setHover] = useState<number | null>(null);

    if (total === 0) {
        return <div className="flex items-center justify-center text-xs text-ink-400" style={{ height: size }}>Pas de données</div>;
    }

    let accumulated = 0;
    return (
        <div className="flex items-center gap-6" role="img" aria-label={ariaLabel ?? 'Graphique en anneau'}>
            <div className="relative shrink-0" style={{ width: size, height: size }}>
                <svg width={size} height={size} viewBox={`0 0 ${size} ${size}`}>
                    <g transform={`rotate(-90 ${radius} ${radius})`}>
                        {data.map((seg, i) => {
                            const portion = seg.value / total;
                            const offset = C - accumulated;
                            const dash = `${C * portion} ${C}`;
                            accumulated += C * portion;
                            return (
                                <circle
                                    key={i}
                                    cx={radius}
                                    cy={radius}
                                    r={innerR}
                                    fill="none"
                                    strokeWidth={stroke}
                                    strokeDasharray={dash}
                                    strokeDashoffset={offset}
                                    className={cn(
                                        TONE_DONUT[seg.tone].replace('fill-', 'stroke-'),
                                        hover === null || hover === i ? 'opacity-100' : 'opacity-40',
                                        'transition-opacity',
                                    )}
                                    onMouseEnter={() => setHover(i)}
                                    onMouseLeave={() => setHover(null)}
                                />
                            );
                        })}
                    </g>
                </svg>
                <div className="pointer-events-none absolute inset-0 flex flex-col items-center justify-center text-center">
                    <span className="font-mono text-2xl font-bold text-ink-900 dark:text-white">
                        {hover !== null ? data[hover].value : centerValue ?? total}
                    </span>
                    {(hover !== null || centerLabel) && (
                        <span className="mt-0.5 text-[11px] font-medium uppercase tracking-wider text-ink-500 dark:text-ink-400">
                            {hover !== null ? data[hover].label : centerLabel}
                        </span>
                    )}
                </div>
            </div>
            <ul className="flex-1 space-y-2">
                {data.map((seg, i) => {
                    const pct = ((seg.value / total) * 100).toFixed(0);
                    return (
                        <li
                            key={i}
                            className={cn(
                                'flex items-center gap-3 rounded-lg px-2 py-1 transition-colors',
                                hover === i && 'bg-ink-50 dark:bg-ink-700/40',
                            )}
                            onMouseEnter={() => setHover(i)}
                            onMouseLeave={() => setHover(null)}
                        >
                            <span className={cn('size-2.5 shrink-0 rounded-sm', TONE_BAR[seg.tone])} />
                            <span className="flex-1 truncate text-xs text-ink-700 dark:text-ink-200">{seg.label}</span>
                            <span className="font-mono text-xs font-semibold tabular-nums text-ink-900 dark:text-white">{seg.value}</span>
                            <span className="font-mono text-[11px] tabular-nums text-ink-400 dark:text-ink-500">{pct}%</span>
                        </li>
                    );
                })}
            </ul>
        </div>
    );
}

interface SparklineProps {
    data: number[];
    tone?: ChartTone;
    width?: number;
    height?: number;
    ariaLabel?: string;
}

export function Sparkline({ data, tone = 'brand', width = 80, height = 24, ariaLabel }: SparklineProps) {
    if (data.length < 2) return null;
    const min = Math.min(...data);
    const max = Math.max(...data);
    const range = max - min || 1;
    const path = data
        .map((v, i) => {
            const x = (i / (data.length - 1)) * width;
            const y = height - ((v - min) / range) * height;
            return `${i === 0 ? 'M' : 'L'}${x.toFixed(2)},${y.toFixed(2)}`;
        })
        .join(' ');
    return (
        <svg width={width} height={height} viewBox={`0 0 ${width} ${height}`} className="shrink-0" role="img" aria-label={ariaLabel ?? 'Tendance'}>
            <path d={path} fill="none" strokeWidth={1.5} strokeLinecap="round" strokeLinejoin="round" className={TONE_STROKE[tone]} />
        </svg>
    );
}

function Tooltip({ x, label, value }: { x: number; label: string; value: string }) {
    return (
        <div
            className="pointer-events-none absolute top-2 -translate-x-1/2 rounded-lg border border-ink-200 bg-white px-2.5 py-1.5 text-[11px] shadow-md dark:border-ink-700 dark:bg-ink-800"
            style={{ left: `${Math.max(8, Math.min(92, x))}%` }}
        >
            <div className="font-medium text-ink-900 dark:text-white">{value}</div>
            <div className="text-ink-500 dark:text-ink-400">{label}</div>
        </div>
    );
}
