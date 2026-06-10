import { cn } from '@/lib/utils';
import { useMemo } from 'react';

interface PasswordStrengthProps {
    password: string;
    /** Hide the meter when the password is empty. Default true. */
    hideWhenEmpty?: boolean;
    className?: string;
}

interface Score {
    /** 0 (worst) — 4 (excellent), zxcvbn-style. */
    value: number;
    label: string;
    tone: 'danger' | 'warning' | 'sage' | 'brand';
    hints: string[];
}

/**
 * Heuristic password strength meter — no external library.
 *
 * Why not zxcvbn? It ships ~800 KB of dictionaries. For an admin-side
 * password creation form we just want directional feedback, not a
 * crypto-grade estimator. Server-side rules (config/auth.php password
 * defaults) remain the source of truth for acceptance.
 *
 * Score model (rough):
 *   - Each ≥1 character class (lower / upper / digit / symbol) → +1
 *   - Length 8+ → +1, length 12+ → +1, length 16+ → +1
 *   - Common patterns (12345, password, qwerty) → −2 floor 0
 */
export function PasswordStrength({ password, hideWhenEmpty = true, className }: PasswordStrengthProps) {
    const score = useMemo<Score>(() => evaluate(password), [password]);

    if (hideWhenEmpty && password.length === 0) {
        return null;
    }

    return (
        <div className={cn('mt-2', className)} aria-live="polite">
            <div className="flex gap-1">
                {[0, 1, 2, 3].map((i) => (
                    <span
                        key={i}
                        className={cn(
                            'h-1 flex-1 rounded-full transition-colors',
                            i < score.value ? barColor(score.tone) : 'bg-ink-200 dark:bg-ink-700',
                        )}
                    />
                ))}
            </div>
            <div className="mt-1.5 flex items-baseline justify-between text-[11px]">
                <span className={cn('font-semibold', textColor(score.tone))}>{score.label}</span>
                {score.hints.length > 0 && (
                    <span className="text-ink-500 dark:text-ink-400">{score.hints[0]}</span>
                )}
            </div>
        </div>
    );
}

const COMMON_PATTERNS = [
    'password',
    '123456',
    'azerty',
    'qwerty',
    'motdepasse',
    'admin',
    'welcome',
    'iloveyou',
];

function evaluate(password: string): Score {
    if (password.length === 0) {
        return { value: 0, label: '—', tone: 'danger', hints: [] };
    }

    let raw = 0;
    const hints: string[] = [];

    if (/[a-z]/.test(password)) raw++;
    else hints.push('Ajoutez une lettre minuscule.');
    if (/[A-Z]/.test(password)) raw++;
    else hints.push('Ajoutez une lettre majuscule.');
    if (/\d/.test(password)) raw++;
    else hints.push('Ajoutez un chiffre.');
    if (/[^a-zA-Z0-9]/.test(password)) raw++;
    else hints.push('Ajoutez un caractère spécial.');

    if (password.length >= 8) raw++;
    else hints.unshift(`Encore ${8 - password.length} caractère(s).`);
    if (password.length >= 12) raw++;
    if (password.length >= 16) raw++;

    const lower = password.toLowerCase();
    if (COMMON_PATTERNS.some((p) => lower.includes(p))) {
        raw -= 3;
        hints.unshift('Évitez les mots courants.');
    }

    const value = Math.max(0, Math.min(4, raw - 2));

    const mapping: Record<number, { label: string; tone: Score['tone'] }> = {
        0: { label: 'Très faible', tone: 'danger' },
        1: { label: 'Faible', tone: 'danger' },
        2: { label: 'Moyen', tone: 'warning' },
        3: { label: 'Bon', tone: 'sage' },
        4: { label: 'Excellent', tone: 'brand' },
    };

    return { value, ...mapping[value], hints };
}

function barColor(tone: Score['tone']): string {
    switch (tone) {
        case 'danger':
            return 'bg-danger-500 dark:bg-danger-400';
        case 'warning':
            return 'bg-warning-500 dark:bg-warning-400';
        case 'sage':
            return 'bg-sage-500 dark:bg-sage-400';
        case 'brand':
            return 'bg-brand-500 dark:bg-brand-400';
    }
}

function textColor(tone: Score['tone']): string {
    switch (tone) {
        case 'danger':
            return 'text-danger-700 dark:text-danger-400';
        case 'warning':
            return 'text-warning-700 dark:text-warning-400';
        case 'sage':
            return 'text-sage-700 dark:text-sage-400';
        case 'brand':
            return 'text-brand-700 dark:text-brand-400';
    }
}
