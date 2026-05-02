import { cn } from '@/lib/utils';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { PageProps as InertiaPageProps } from '@inertiajs/core';
import { ReactNode, useState } from 'react';

interface User {
    id: number;
    name: string;
    email: string;
    role?: string;
    is_platform_admin?: boolean;
}

interface PageProps extends InertiaPageProps {
    auth: { user: User };
}

interface NavLink {
    href: string;
    label: string;
    icon: ReactNode;
    danger?: boolean;
    badgeCount?: number;
}

interface NavSection {
    section: string | null;
    links: NavLink[];
}

export default function DashboardLayout({
    children,
    title,
    subtitle,
}: {
    children: ReactNode;
    title: string;
    subtitle?: string;
}) {
    const { url, props } = usePage<PageProps>();
    const auth = props.auth;
    const [showUserMenu, setShowUserMenu] = useState(false);
    const [sidebarOpen, setSidebarOpen] = useState(false);

    const isActive = (path: string) => {
        const current = url.split('?')[0].replace(/\/$/, '');
        const clean = path.replace(/\/$/, '');
        if (clean === '/dashboard') return current === '/dashboard';
        return current === clean || current.startsWith(clean + '/');
    };

    const navSections: NavSection[] = [
        {
            section: null,
            links: [{ href: '/dashboard', label: 'Tableau de bord', icon: <HomeIcon /> }],
        },
        {
            section: 'Terrain',
            links: [
                { href: '/interventions', label: 'Interventions', icon: <ClipboardIcon /> },
                { href: '/incidents', label: 'Incidents & EI', icon: <AlertIcon />, danger: true },
                { href: '/beneficiaries', label: 'Bénéficiaires', icon: <UserHeartIcon /> },
            ],
        },
        {
            section: 'Qualité',
            links: [
                { href: '/audits', label: 'Audits & conformité', icon: <BadgeIcon /> },
                { href: '/plans-amelioration', label: "Plans d'amélioration", icon: <CheckListIcon /> },
                { href: '/indicateurs', label: 'Indicateurs', icon: <ChartIcon /> },
            ],
        },
        {
            section: 'QVCT & RH',
            links: [
                { href: '/qvct', label: 'Baromètre QVCT', icon: <HeartIcon /> },
                { href: '/formations', label: 'Formations', icon: <AcademicIcon /> },
                { href: '/communication', label: 'Communication', icon: <ChatIcon /> },
            ],
        },
        {
            section: 'Administration',
            links: [
                { href: '/users', label: 'Utilisateurs', icon: <UsersIcon /> },
                ...(auth?.user?.is_platform_admin
                    ? [{ href: '/admin/structures', label: 'Structures (admin)', icon: <BuildingIcon /> }]
                    : []),
            ],
        },
    ];

    return (
        <>
            <Head title={`${title} — HS Quality`} />

            <div className="flex min-h-dvh bg-ink-50 font-sans dark:bg-ink-900">
                {/* Sidebar */}
                <aside
                    className={cn(
                        'fixed left-0 top-0 z-40 flex h-screen w-64 flex-col bg-ink-900 transition-transform duration-200 lg:translate-x-0',
                        sidebarOpen ? 'translate-x-0' : '-translate-x-full',
                    )}
                >
                    {/* Logo */}
                    <div className="flex h-16 shrink-0 items-center gap-3 border-b border-white/5 px-5">
                        <div className="flex size-9 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-brand-500 to-brand-700 font-serif text-base font-semibold text-white">
                            Q
                        </div>
                        <div className="min-w-0">
                            <div className="text-sm font-semibold leading-tight text-white">HS Quality</div>
                            <div className="mt-0.5 text-[11px] font-semibold uppercase tracking-wider text-brand-300">
                                Qualité & QVCT
                            </div>
                        </div>
                    </div>

                    {/* Nav */}
                    <nav className="sidebar-nav flex-1 overflow-y-auto px-3 py-3">
                        {navSections.map((group, gi) => (
                            <div key={gi} className={gi > 0 ? 'pt-5' : ''}>
                                {group.section && (
                                    <p className="mb-1.5 px-3 text-[11px] font-semibold uppercase tracking-wider text-white/30">
                                        {group.section}
                                    </p>
                                )}
                                <ul className="flex flex-col gap-0.5">
                                    {group.links.map((link) => {
                                        const active = isActive(link.href);
                                        return (
                                            <li key={link.href}>
                                                <Link
                                                    href={link.href}
                                                    onClick={() => setSidebarOpen(false)}
                                                    className={cn(
                                                        'group flex items-center gap-3 rounded-lg px-3 py-2 text-[13px] font-medium transition-colors',
                                                        active
                                                            ? 'bg-brand-600 text-white'
                                                            : 'text-white/60 hover:bg-white/5 hover:text-white',
                                                        !active && link.danger && 'text-danger-200 hover:text-danger-100',
                                                    )}
                                                >
                                                    <span
                                                        className={cn(
                                                            'shrink-0',
                                                            active ? 'text-white' : 'text-white/40 group-hover:text-white',
                                                            !active && link.danger && 'text-danger-200',
                                                        )}
                                                    >
                                                        {link.icon}
                                                    </span>
                                                    <span className="truncate">{link.label}</span>
                                                </Link>
                                            </li>
                                        );
                                    })}
                                </ul>
                            </div>
                        ))}
                    </nav>

                    {/* User footer */}
                    {auth?.user && (
                        <div className="shrink-0 border-t border-white/5 p-3">
                            <div className="flex items-center gap-3 rounded-lg px-2 py-2">
                                <Avatar name={auth.user.name} />
                                <div className="min-w-0 flex-1">
                                    <p className="truncate text-[13px] font-medium text-white">{auth.user.name}</p>
                                    <p className="truncate text-[11px] capitalize text-white/40">
                                        {auth.user.role ?? 'utilisateur'}
                                    </p>
                                </div>
                            </div>
                        </div>
                    )}
                </aside>

                {sidebarOpen && (
                    <div
                        onClick={() => setSidebarOpen(false)}
                        className="fixed inset-0 z-30 bg-ink-900/60 backdrop-blur-sm lg:hidden"
                        aria-hidden
                    />
                )}

                {/* Main */}
                <div className="flex min-w-0 flex-1 flex-col lg:ml-64">
                    {/* Header */}
                    <header className="sticky top-0 z-30 flex h-16 shrink-0 items-center justify-between border-b border-ink-100 bg-white/95 px-6 backdrop-blur dark:border-ink-700/60 dark:bg-ink-800/95">
                        <div className="flex min-w-0 items-center gap-4">
                            <button
                                type="button"
                                onClick={() => setSidebarOpen(true)}
                                aria-label="Ouvrir le menu"
                                className="rounded-lg p-2 text-ink-500 hover:bg-ink-100 lg:hidden"
                            >
                                <svg className="size-5" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                                </svg>
                            </button>
                            <div className="min-w-0">
                                <h1 className="truncate text-base font-semibold text-ink-900 dark:text-white">{title}</h1>
                                {subtitle && <p className="mt-0.5 truncate text-xs text-ink-500 dark:text-ink-400">{subtitle}</p>}
                            </div>
                        </div>

                        <div className="flex items-center gap-2">
                            <Link
                                href="/incidents/create"
                                className="hidden cursor-pointer items-center gap-1.5 rounded-lg bg-danger-600 px-3.5 py-2 text-xs font-semibold text-white shadow-sm transition-all duration-200 hover:bg-danger-700 hover:shadow-md active:scale-[0.97] md:inline-flex"
                            >
                                <svg className="size-3.5" fill="none" stroke="currentColor" strokeWidth={2.5} viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M12 5v14M5 12h14" />
                                </svg>
                                Déclarer un incident
                            </Link>

                            {auth?.user && (
                                <UserMenu
                                    user={auth.user}
                                    open={showUserMenu}
                                    onToggle={() => setShowUserMenu(!showUserMenu)}
                                    onClose={() => setShowUserMenu(false)}
                                />
                            )}
                        </div>
                    </header>

                    <main className="flex-1 px-4 py-6 sm:px-6 lg:px-8 lg:py-8">{children}</main>
                </div>
            </div>
        </>
    );
}

function Avatar({ name, size = 'sm' }: { name: string; size?: 'sm' | 'md' }) {
    const initials = name
        .split(' ')
        .map((n) => n[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
    return (
        <div
            className={cn(
                'flex shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-brand-500 to-brand-700 font-semibold text-white',
                size === 'sm' ? 'size-8 text-[11px]' : 'size-10 text-sm',
            )}
        >
            {initials}
        </div>
    );
}

function UserMenu({
    user,
    open,
    onToggle,
    onClose,
}: {
    user: User;
    open: boolean;
    onToggle: () => void;
    onClose: () => void;
}) {
    return (
        <div className="relative">
            <button
                type="button"
                onClick={onToggle}
                className="flex cursor-pointer items-center gap-2.5 rounded-lg border border-ink-200 bg-white px-2 py-1.5 transition-colors hover:border-ink-300 dark:border-ink-600 dark:bg-ink-700 dark:hover:border-ink-500"
            >
                <Avatar name={user.name} />
                <span className="hidden text-sm font-medium text-ink-900 md:block dark:text-white">{user.name}</span>
                <svg
                    className={cn('size-3.5 text-ink-400 transition-transform', open && 'rotate-180')}
                    fill="none"
                    stroke="currentColor"
                    strokeWidth={2}
                    viewBox="0 0 24 24"
                >
                    <path strokeLinecap="round" strokeLinejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            {open && (
                <>
                    <button
                        type="button"
                        aria-label="Fermer le menu"
                        className="fixed inset-0 z-40 cursor-default"
                        onClick={onClose}
                    />
                    <div className="absolute right-0 z-50 mt-2 w-64 overflow-hidden rounded-2xl border border-ink-200 bg-white shadow-xl dark:border-ink-600 dark:bg-ink-800 dark:shadow-[0_8px_32px_rgba(0,0,0,0.4)]">
                        <div className="flex items-center gap-3 border-b border-ink-100 bg-ink-50/60 px-4 py-3 dark:border-ink-700 dark:bg-ink-900/50">
                            <Avatar name={user.name} size="md" />
                            <div className="min-w-0 flex-1">
                                <p className="truncate text-sm font-semibold text-ink-900 dark:text-white">{user.name}</p>
                                <p className="truncate text-xs text-ink-500 dark:text-ink-400">{user.email}</p>
                            </div>
                        </div>
                        <div className="p-1.5">
                            <Link
                                href="/dashboard/profile"
                                onClick={onClose}
                                className="flex cursor-pointer items-center gap-2.5 rounded-lg px-3 py-2 text-sm text-ink-700 transition-colors hover:bg-ink-50 dark:text-ink-200 dark:hover:bg-ink-700"
                            >
                                <UserIcon />
                                Mon profil
                            </Link>
                            <div className="my-1.5 h-px bg-ink-100 dark:bg-ink-700" />
                            <button
                                type="button"
                                onClick={() => router.post('/logout')}
                                className="flex w-full cursor-pointer items-center gap-2.5 rounded-lg px-3 py-2 text-sm text-danger-600 transition-colors hover:bg-danger-50 dark:text-danger-400 dark:hover:bg-danger-900/30"
                            >
                                <LogoutIcon />
                                Se déconnecter
                            </button>
                        </div>
                    </div>
                </>
            )}
        </div>
    );
}

// ─── Icons (SVG inline, hérité du précédent layout) ─────────────────────────
function Icon({ children }: { children: ReactNode }) {
    return (
        <svg className="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.75} strokeLinecap="round" strokeLinejoin="round">
            {children}
        </svg>
    );
}

function HomeIcon() {
    return (
        <Icon>
            <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
            <polyline points="9 22 9 12 15 12 15 22" />
        </Icon>
    );
}
function ClipboardIcon() {
    return (
        <Icon>
            <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
        </Icon>
    );
}
function AlertIcon() {
    return (
        <Icon>
            <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
            <line x1="12" y1="9" x2="12" y2="13" />
            <line x1="12" y1="17" x2="12.01" y2="17" />
        </Icon>
    );
}
function UserHeartIcon() {
    return (
        <Icon>
            <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2" />
            <circle cx="12" cy="7" r="4" />
        </Icon>
    );
}
function BadgeIcon() {
    return (
        <Icon>
            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
            <path d="M9 12l2 2 4-4" />
        </Icon>
    );
}
function CheckListIcon() {
    return (
        <Icon>
            <path d="M9 11l3 3L22 4" />
            <path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11" />
        </Icon>
    );
}
function ChartIcon() {
    return (
        <Icon>
            <path d="M18 20V10M12 20V4M6 20v-6" />
        </Icon>
    );
}
function HeartIcon() {
    return (
        <Icon>
            <path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z" />
        </Icon>
    );
}
function AcademicIcon() {
    return (
        <Icon>
            <path d="M22 10v6M2 10l10-5 10 5-10 5z" />
            <path d="M6 12v5c3 3 9 3 12 0v-5" />
        </Icon>
    );
}
function ChatIcon() {
    return (
        <Icon>
            <path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z" />
        </Icon>
    );
}
function BuildingIcon() {
    return (
        <Icon>
            <path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16M3 21h18M9 7h1M9 11h1M9 15h1M14 7h1M14 11h1M14 15h1" />
        </Icon>
    );
}
function UsersIcon() {
    return (
        <Icon>
            <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2" />
            <circle cx="9" cy="7" r="4" />
            <path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75" />
        </Icon>
    );
}
function UserIcon() {
    return (
        <Icon>
            <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2" />
            <circle cx="12" cy="7" r="4" />
        </Icon>
    );
}
function LogoutIcon() {
    return (
        <Icon>
            <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9" />
        </Icon>
    );
}
