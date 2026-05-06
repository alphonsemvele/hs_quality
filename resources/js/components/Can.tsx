import { useCan, type Ability } from '@/lib/can';
import type { ReactNode } from 'react';

export function Can({
    ability,
    children,
    fallback = null,
}: {
    ability: Ability | Ability[];
    children: ReactNode;
    fallback?: ReactNode;
}) {
    return useCan(ability) ? <>{children}</> : <>{fallback}</>;
}
