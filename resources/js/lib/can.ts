import { usePage } from '@inertiajs/react';
import type { PageProps as InertiaPageProps } from '@inertiajs/core';
import type { ReactNode } from 'react';

export type Ability =
    | 'interventions.view'
    | 'interventions.create'
    | 'interventions.update'
    | 'interventions.delete'
    | 'incidents.view'
    | 'incidents.create'
    | 'incidents.analyze'
    | 'beneficiaries.view'
    | 'beneficiaries.create'
    | 'beneficiaries.update'
    | 'audits.view'
    | 'audits.manage'
    | 'plans_amelioration.view'
    | 'plans_amelioration.manage'
    | 'indicateurs.view'
    | 'qvct.view'
    | 'qvct.manage'
    | 'communication.view'
    | 'communication.post'
    | 'formations.view'
    | 'formations.manage'
    | 'users.manage'
    | 'admin.structures';

export type Abilities = Partial<Record<Ability, boolean>>;

interface SharedAuthProps extends InertiaPageProps {
    auth?: {
        user?: unknown;
        abilities?: Abilities;
    };
}

export function useAbilities(): Abilities {
    const { props } = usePage<SharedAuthProps>();
    return props.auth?.abilities ?? {};
}

export function useCan(ability: Ability | Ability[]): boolean {
    const abilities = useAbilities();
    const list = Array.isArray(ability) ? ability : [ability];
    return list.some((a) => abilities[a] === true);
}
