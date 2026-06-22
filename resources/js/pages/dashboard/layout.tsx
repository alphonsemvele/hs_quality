import { cn } from '@/lib/utils';
import { useAbilities, type Ability } from '@/lib/can';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { PageProps as InertiaPageProps } from '@inertiajs/core';
import { ReactNode, useState } from 'react';
import CommandPalette from '@/components/CommandPalette';
import HelpDrawer from '@/components/HelpDrawer';
import { GoToShortcuts } from '@/components/GoToShortcuts';
import { IdleTimeoutWatcher } from '@/components/IdleTimeoutWatcher';
import ImpersonationBanner from '@/components/ImpersonationBanner';
import { Lightbox } from '@/components/Lightbox';
import { OnboardingTour } from '@/components/OnboardingTour';
import { ScrollToTop } from '@/components/ScrollToTop';
import MfaSetupBanner from '@/components/MfaSetupBanner';
import NotificationsCenter from '@/components/NotificationsCenter';
import { ShortcutsCheatsheet } from '@/components/ShortcutsCheatsheet';
import SystemBanners from '@/components/SystemBanners';
import { QuickAddIncidentModal } from '@/components/quick-add';
import ThemeToggle from '@/components/ThemeToggle';
import { FlashToasts } from '@/components/ui';

interface User {
    id: number;
    name: string;
    email: string;
    role?: string;
    is_platform_admin?: boolean;
}

interface ImpersonationProp {
    structure_id: string;
    structure_name: string;
    started_at: string | null;
}

interface PageProps extends InertiaPageProps {
    auth: { user: User };
    impersonation?: ImpersonationProp | null;
}

interface NavLink {
    href: string;
    label: string;
    icon: ReactNode;
    danger?: boolean;
    badgeCount?: number;
    requires?: Ability;
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
    const abilities = useAbilities();
    const [showUserMenu, setShowUserMenu] = useState(false);
    const [sidebarOpen, setSidebarOpen] = useState(false);
    const [showQuickIncident, setShowQuickIncident] = useState(false);
    const [showHelp, setShowHelp] = useState(false);

    const current = url.split('?')[0].replace(/\/$/, '');
    const pathMatches = (path: string) => {
        const clean = path.replace(/\/$/, '');
        if (clean === '/dashboard') return current === '/dashboard';
        return current === clean || current.startsWith(clean + '/');
    };

    // Platform admins see a dedicated cross-tenant nav. Tenant-scoped users
    // see the operational nav. The two surfaces never overlap — UNLESS a
    // platform admin has opted into a "view as dirigeant" impersonation
    // session, in which case they see the tenant nav of the structure they
    // entered. The impersonation prop is set server-side by
    // HandleInertiaRequests + SuperAdminImpersonationService.
    const isPlatformAdmin = auth?.user?.is_platform_admin === true;
    const isImpersonating = props.impersonation != null;
    const showPlatformNav = isPlatformAdmin && !isImpersonating;

    const rawSections: NavSection[] = showPlatformNav
        ? [
              {
                  section: null,
                  links: [{ href: '/admin', label: 'Console plateforme', icon: <HomeIcon /> }],
              },
              {
                  section: 'Plateforme',
                  links: [
                      { href: '/admin/structures', label: 'Structures', icon: <BuildingIcon /> },
                      { href: '/admin/feature-flags', label: 'Feature flags', icon: <FlagIcon /> },
                      { href: '/admin/system-health', label: 'Santé système', icon: <BoltIcon /> },
                  ],
              },
          ]
        : [
              {
                  section: null,
                  links: [{ href: '/dashboard', label: 'Tableau de bord', icon: <HomeIcon /> }],
              },
              {
                  section: 'Terrain',
                  links: [
                      { href: '/interventions', label: 'Interventions', icon: <ClipboardIcon />, requires: 'interventions.view' },
                      { href: '/incidents', label: 'Incidents & EI', icon: <AlertIcon />, danger: true, requires: 'incidents.view' },
                      { href: '/beneficiaries', label: 'Bénéficiaires', icon: <UserHeartIcon />, requires: 'beneficiaries.view' },
                  ],
              },
              {
                  section: 'Qualité',
                  links: [
                      { href: '/audits', label: 'Audits & conformité', icon: <BadgeIcon />, requires: 'audits.view' },
                      { href: '/plans-amelioration', label: "Plans d'amélioration", icon: <CheckListIcon />, requires: 'plans_amelioration.view' },
                      { href: '/indicateurs', label: 'Indicateurs', icon: <ChartIcon />, requires: 'indicateurs.view' },
                      { href: '/audits/grids', label: 'Référentiels', icon: <BookIcon />, requires: 'audits.view' },
                      { href: '/audits/has-preparation', label: 'Préparation HAS', icon: <BadgeIcon />, requires: 'audits.view' },
                      { href: '/audit-log', label: 'Registre d\'audit', icon: <DatabaseIcon />, requires: 'audits.view' },
                  ],
              },
              {
                  section: 'QVCT & RH',
                  links: [
                      { href: '/qvct', label: 'Baromètre QVCT', icon: <HeartIcon />, requires: 'qvct.view' },
                      { href: '/qvct/weak-signals', label: 'Signaux faibles', icon: <RadarIcon />, requires: 'qvct.view' },
                      { href: '/qvct/indicators', label: 'Cartographie RPS', icon: <LayersIcon />, requires: 'qvct.view' },
                      { href: '/qvct/action-plans', label: "Plans d'action", icon: <CheckListIcon />, requires: 'qvct.view' },
                      { href: '/qvct/journal', label: 'Mon journal', icon: <BookIcon />, requires: 'qvct.view' },
                      { href: '/qvct/exchanges', label: "Demandes d'échange", icon: <InboxIcon />, requires: 'qvct.view' },
                      { href: '/formations', label: 'Formations', icon: <AcademicIcon />, requires: 'formations.view' },
                      { href: '/communication', label: 'Communication', icon: <ChatIcon />, requires: 'communication.view' },
                  ],
              },
              {
                  section: 'Administration',
                  links: [
                      { href: '/users', label: 'Utilisateurs', icon: <UsersIcon />, requires: 'users.manage' },
                  ],
              },
          ];

    const navSections: NavSection[] = rawSections
        .map((group) => ({
            ...group,
            links: group.links.filter((link) => !link.requires || abilities[link.requires] === true),
        }))
        .filter((group) => group.links.length > 0);

    // Pick the single most specific (longest-prefix) matching href so that
    // visiting /audits/grids highlights only "Référentiels", not also "Audits".
    const activeHref = navSections
        .flatMap((g) => g.links.map((l) => l.href))
        .filter((href) => pathMatches(href))
        .reduce((best, href) => (href.length > best.length ? href : best), '');

    const canDeclareIncident = abilities['incidents.create'] === true;

    return (
        <>
            <Head title={`${title} — HS Quality`} />
            <FlashToasts />
            <IdleTimeoutWatcher />
            <ShortcutsCheatsheet />
            <OnboardingTour />
            <ScrollToTop />
            <GoToShortcuts />
            <Lightbox />

            <a
                href="#dashboard-main"
                className="sr-only z-[100] rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white focus:not-sr-only focus:fixed focus:left-4 focus:top-4"
            >
                Aller au contenu principal
            </a>

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
                                        const active = link.href === activeHref;
                                        return (
                                            <li key={link.href}>
                                                <Link
                                                    href={link.href}
                                                    onClick={() => setSidebarOpen(false)}
                                                    aria-current={active ? 'page' : undefined}
                                                    className={cn(
                                                        'group flex items-center gap-3 rounded-lg px-3 py-2 text-[13px] font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-400/60',
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
                                className="rounded-lg p-2 text-ink-500 hover:bg-ink-100 lg:hidden dark:text-ink-400 dark:hover:bg-ink-700"
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
                            <CommandPalette />

                            {canDeclareIncident && (
                                <button
                                    type="button"
                                    onClick={() => setShowQuickIncident(true)}
                                    className="hidden cursor-pointer items-center gap-1.5 rounded-lg bg-danger-600 px-3.5 py-2 text-xs font-semibold text-white shadow-sm transition-all duration-200 hover:bg-danger-700 hover:shadow-md active:scale-[0.97] md:inline-flex"
                                >
                                    <svg className="size-3.5" fill="none" stroke="currentColor" strokeWidth={2.5} viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M12 5v14M5 12h14" />
                                    </svg>
                                    Déclarer un incident
                                </button>
                            )}

                            <button
                                type="button"
                                onClick={() => setShowHelp(true)}
                                aria-label="Ouvrir l'aide contextuelle"
                                className="flex size-9 cursor-pointer items-center justify-center rounded-lg text-ink-500 transition-colors hover:bg-ink-100 hover:text-ink-700 dark:text-ink-400 dark:hover:bg-ink-700 dark:hover:text-white"
                            >
                                <svg className="size-[18px]" fill="none" stroke="currentColor" strokeWidth={1.75} viewBox="0 0 24 24">
                                    <circle cx="12" cy="12" r="10" />
                                    <path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3" />
                                    <line x1="12" y1="17" x2="12.01" y2="17" />
                                </svg>
                            </button>

                            <NotificationsCenter />

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

                    <ImpersonationBanner />
                    <SystemBanners />
                    <MfaSetupBanner />

                    <main id="dashboard-main" className="flex-1 px-4 py-6 sm:px-6 lg:px-8 lg:py-8">{children}</main>
                </div>
            </div>

            {canDeclareIncident && (
                <QuickAddIncidentModal open={showQuickIncident} onClose={() => setShowQuickIncident(false)} />
            )}

            <HelpDrawer open={showHelp} onClose={() => setShowHelp(false)} />
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
                aria-label={`Menu utilisateur (${user.name})`}
                aria-haspopup="menu"
                aria-expanded={open}
                className="flex cursor-pointer items-center gap-2.5 rounded-lg border border-ink-200 bg-white px-2 py-1.5 transition-colors hover:border-ink-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500/40 dark:border-ink-600 dark:bg-ink-700 dark:hover:border-ink-500"
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
                    <div className="absolute right-0 z-50 mt-2 w-72 overflow-hidden rounded-2xl border border-ink-200 bg-white shadow-xl dark:border-ink-700/80 dark:bg-ink-800 dark:shadow-[0_8px_32px_rgba(0,0,0,0.4)]">
                        {/* User identity header */}
                        <div className="flex items-center gap-3 border-b border-ink-100 bg-ink-50/60 px-4 py-3.5 dark:border-ink-700/60 dark:bg-ink-900/50">
                            <Avatar name={user.name} size="md" />
                            <div className="min-w-0 flex-1">
                                <p className="truncate text-sm font-semibold text-ink-900 dark:text-white">{user.name}</p>
                                <p className="truncate text-xs text-ink-500 dark:text-ink-400">{user.email}</p>
                            </div>
                        </div>

                        {/* Navigation items */}
                        <div className="p-1.5">
                            <Link
                                href="/dashboard/profile"
                                onClick={onClose}
                                className="flex cursor-pointer items-center gap-2.5 rounded-lg px-3 py-2 text-sm text-ink-700 transition-colors hover:bg-ink-50 dark:text-ink-200 dark:hover:bg-ink-700/50"
                            >
                                <UserIcon />
                                Mon profil
                            </Link>
                            <Link
                                href="/billing"
                                onClick={onClose}
                                className="flex cursor-pointer items-center gap-2.5 rounded-lg px-3 py-2 text-sm text-ink-700 transition-colors hover:bg-ink-50 dark:text-ink-200 dark:hover:bg-ink-700/50"
                            >
                                <CardIcon />
                                Abonnement & facturation
                            </Link>
                            <Link
                                href="/settings/structure"
                                onClick={onClose}
                                className="flex cursor-pointer items-center gap-2.5 rounded-lg px-3 py-2 text-sm text-ink-700 transition-colors hover:bg-ink-50 dark:text-ink-200 dark:hover:bg-ink-700/50"
                            >
                                <SettingsIcon />
                                Paramètres de la structure
                            </Link>
                            <Link
                                href="/dashboard/profile/notifications"
                                onClick={onClose}
                                className="flex cursor-pointer items-center gap-2.5 rounded-lg px-3 py-2 text-sm text-ink-700 transition-colors hover:bg-ink-50 dark:text-ink-200 dark:hover:bg-ink-700/50"
                            >
                                <BellIcon />
                                Préférences notifications
                            </Link>
                            <Link
                                href="/dashboard/profile/sessions"
                                onClick={onClose}
                                className="flex cursor-pointer items-center gap-2.5 rounded-lg px-3 py-2 text-sm text-ink-700 transition-colors hover:bg-ink-50 dark:text-ink-200 dark:hover:bg-ink-700/50"
                            >
                                <DevicesIcon />
                                Sessions actives
                            </Link>
                            <Link
                                href="/dashboard/profile/api-tokens"
                                onClick={onClose}
                                className="flex cursor-pointer items-center gap-2.5 rounded-lg px-3 py-2 text-sm text-ink-700 transition-colors hover:bg-ink-50 dark:text-ink-200 dark:hover:bg-ink-700/50"
                            >
                                <KeyIcon />
                                Tokens d'API
                            </Link>
                            <Link
                                href="/dashboard/aide/glossaire"
                                onClick={onClose}
                                className="flex cursor-pointer items-center gap-2.5 rounded-lg px-3 py-2 text-sm text-ink-700 transition-colors hover:bg-ink-50 dark:text-ink-200 dark:hover:bg-ink-700/50"
                            >
                                <BookIcon />
                                Glossaire métier
                            </Link>
                        </div>

                        {/* Theme toggle section */}
                        <div className="border-t border-ink-100 dark:border-ink-700/60">
                            <ThemeToggle />
                        </div>

                        {/* Destructive action — separated */}
                        <div className="border-t border-ink-100 p-1.5 dark:border-ink-700/60">
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
function RadarIcon() {
    return (
        <Icon>
            <circle cx="12" cy="12" r="9" />
            <circle cx="12" cy="12" r="5" />
            <circle cx="12" cy="12" r="1.5" />
            <line x1="12" y1="3" x2="12" y2="21" />
        </Icon>
    );
}
function LayersIcon() {
    return (
        <Icon>
            <polygon points="12 2 2 7 12 12 22 7 12 2" />
            <polyline points="2 17 12 22 22 17" />
            <polyline points="2 12 12 17 22 12" />
        </Icon>
    );
}
function BookIcon() {
    return (
        <Icon>
            <path d="M4 19.5A2.5 2.5 0 016.5 17H20" />
            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5V4.5A2.5 2.5 0 016.5 2z" />
        </Icon>
    );
}
function InboxIcon() {
    return (
        <Icon>
            <polyline points="22 12 16 12 14 15 10 15 8 12 2 12" />
            <path d="M5.45 5.11L2 12v6a2 2 0 002 2h16a2 2 0 002-2v-6l-3.45-6.89A2 2 0 0016.76 4H7.24a2 2 0 00-1.79 1.11z" />
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
function CardIcon() {
    return (
        <Icon>
            <rect x="2" y="6" width="20" height="13" rx="2" />
            <line x1="2" y1="11" x2="22" y2="11" />
        </Icon>
    );
}
function DatabaseIcon() {
    return (
        <Icon>
            <ellipse cx="12" cy="5" rx="9" ry="3" />
            <path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5" />
        </Icon>
    );
}
function SettingsIcon() {
    return (
        <Icon>
            <circle cx="12" cy="12" r="3" />
            <path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 008.91 19a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 005 8.91a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z" />
        </Icon>
    );
}
function BellIcon() {
    return (
        <Icon>
            <path d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
        </Icon>
    );
}
function DevicesIcon() {
    return (
        <Icon>
            <rect x="2" y="3" width="20" height="14" rx="2" />
            <line x1="8" y1="21" x2="16" y2="21" />
            <line x1="12" y1="17" x2="12" y2="21" />
        </Icon>
    );
}
function KeyIcon() {
    return (
        <Icon>
            <path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 11-7.778 7.778 5.5 5.5 0 017.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4" />
        </Icon>
    );
}
function FlagIcon() {
    return (
        <Icon>
            <path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1zM4 22V15" />
        </Icon>
    );
}
function BoltIcon() {
    return (
        <Icon>
            <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z" />
        </Icon>
    );
}
