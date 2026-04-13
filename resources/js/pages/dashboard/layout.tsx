import { Head, Link, usePage, router } from '@inertiajs/react';
import { ReactNode, useState } from 'react';
import { PageProps as InertiaPageProps } from '@inertiajs/core';

interface DashboardLayoutProps {
    children: ReactNode;
    title: string;
    subtitle?: string;
}

interface User {
    id: number;
    name: string;
    email: string;
    role?: string;
}

interface PageProps extends InertiaPageProps {
    auth: { user: User };
}

export default function DashboardLayout({ children, title, subtitle }: DashboardLayoutProps) {
    const { url, props } = usePage<PageProps>();
    const { auth } = props;
    const [showUserMenu, setShowUserMenu] = useState(false);
    const [sidebarOpen, setSidebarOpen] = useState(false);

    const isActive = (path: string) => {
        const current = url.split('?')[0].replace(/\/$/, '');
        const clean   = path.replace(/\/$/, '');
        const exactRoutes = [
            '/dashboard', '/interventions', '/incidents', '/qvct',
            '/communication', '/formations', '/audits', '/indicateurs',
            '/beneficiaires', '/structures', '/utilisateurs',
            '/rapports', '/parametres',
        ];
        if (exactRoutes.includes(clean)) return current === clean;
        return current === clean || current.startsWith(clean + '/');
    };

    const handleLogout = () => router.post('/logout');

    const getInitials = (name: string) =>
        name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);

    const navItems = [
        {
            section: null,
            links: [
                { href: '/dashboard', label: 'Tableau de bord', icon: <HomeIcon /> },
            ],
        },
        {
            section: 'Terrain',
            links: [
                { href: '/interventions',         label: 'Interventions',        icon: <ClipboardIcon /> },
                { href: '/incidents',             label: 'Incidents & EI',        icon: <AlertIcon />, danger: true },
                { href: '/beneficiaires',         label: 'Bénéficiaires',         icon: <UserHeartIcon /> },
            ],
        },
        {
            section: 'Qualité',
            links: [
                { href: '/audits',                label: 'Audits & Conformité',   icon: <BadgeIcon /> },
                { href: '/plans-amelioration',    label: 'Plans d\'amélioration', icon: <CheckListIcon /> },
                { href: '/indicateurs',           label: 'Indicateurs & KPIs',    icon: <ChartIcon /> },
            ],
        },
        {
            section: 'QVCT & RH',
            links: [
                { href: '/qvct',                  label: 'Baromètre QVCT',        icon: <HeartIcon /> },
                { href: '/formations',            label: 'Formations',             icon: <AcademicIcon /> },
                { href: '/communication',         label: 'Communication',          icon: <ChatIcon /> },
            ],
        },
        {
            section: 'Administration',
            links: [
                { href: '/structures',            label: 'Structures',             icon: <BuildingIcon /> },
                { href: '/utilisateurs',          label: 'Utilisateurs',           icon: <UsersIcon /> },
                { href: '/rapports',              label: 'Rapports',               icon: <DocumentIcon /> },
                { href: '/parametres',            label: 'Paramètres',             icon: <CogIcon /> },
            ],
        },
    ];

    return (
        <>
            <Head title={`${title} — HS Quality`}>
                <link rel="preconnect" href="https://fonts.googleapis.com" />
                <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet" />
            </Head>

            <style>{`
                :root { --navy:#0B1628; --gold:#F59E0B; --gold2:#FCD34D; }
                body { font-family: 'DM Sans', sans-serif; }
                .serif { font-family: 'DM Serif Display', serif; }
                .mono  { font-family: 'DM Mono', monospace; }
                /* Scrollbar sidebar */
                .sidebar-nav::-webkit-scrollbar { width: 4px; }
                .sidebar-nav::-webkit-scrollbar-track { background: transparent; }
                .sidebar-nav::-webkit-scrollbar-thumb { background: rgba(255,255,255,.08); border-radius: 4px; }
            `}</style>

            <div style={{ display: 'flex', minHeight: '100vh', background: '#F8FAFC', fontFamily: "'DM Sans', sans-serif" }}>

                {/* ══════════════════════════════════════════════════
                    SIDEBAR
                ══════════════════════════════════════════════════ */}
                <aside style={{
                    position: 'fixed', left: 0, top: 0, zIndex: 40,
                    height: '100vh', width: 256,
                    background: 'var(--navy)',
                    display: 'flex', flexDirection: 'column',
                    transition: 'transform .3s',
                    transform: sidebarOpen ? 'translateX(0)' : undefined,
                }}
                    className={`${sidebarOpen ? '' : '-translate-x-full lg:translate-x-0'}`}
                >
                    {/* Logo */}
                    <div style={{ height: 64, display: 'flex', alignItems: 'center', gap: 12, padding: '0 20px', borderBottom: '1px solid rgba(255,255,255,.06)', flexShrink: 0 }}>
                        <div style={{
                            width: 34, height: 34, flexShrink: 0,
                            background: 'linear-gradient(135deg, var(--gold) 0%, #D97706 100%)',
                            borderRadius: 9, display: 'flex', alignItems: 'center', justifyContent: 'center',
                            fontFamily: "'DM Serif Display', serif", fontWeight: 700, fontSize: 15, color: 'var(--navy)',
                        }}>Q</div>
                        <div>
                            <div style={{ fontSize: 14, fontWeight: 800, color: 'white', lineHeight: 1 }}>HS Quality</div>
                            <div style={{ fontSize: 9, fontWeight: 700, letterSpacing: '.12em', textTransform: 'uppercase', color: 'var(--gold)', lineHeight: 1, marginTop: 3 }}>Qualité & QVCT</div>
                        </div>
                    </div>

                    {/* Nav */}
                    <nav className="sidebar-nav" style={{ flex: 1, overflowY: 'auto', padding: '12px 10px' }}>
                        {navItems.map((group, gi) => (
                            <div key={gi} style={{ paddingTop: gi > 0 ? 16 : 0 }}>
                                {group.section && (
                                    <p style={{ padding: '0 10px', marginBottom: 6, fontSize: 10, fontWeight: 700, letterSpacing: '.1em', textTransform: 'uppercase', color: 'rgba(255,255,255,.25)' }}>
                                        {group.section}
                                    </p>
                                )}
                                <ul style={{ listStyle: 'none', padding: 0, margin: 0, display: 'flex', flexDirection: 'column', gap: 2 }}>
                                    {group.links.map(link => {
                                        const active = isActive(link.href);
                                        const isDanger = 'danger' in link && link.danger;
                                        return (
                                            <li key={link.href}>
                                                <Link
                                                    href={link.href}
                                                    onClick={() => setSidebarOpen(false)}
                                                    style={{
                                                        display: 'flex', alignItems: 'center', gap: 10,
                                                        borderRadius: 10, padding: '9px 10px',
                                                        fontSize: 13, fontWeight: active ? 600 : 400,
                                                        textDecoration: 'none', transition: 'all .15s',
                                                        background: active ? 'var(--gold)' : 'transparent',
                                                        color: active ? 'var(--navy)' : isDanger && !active ? '#FCA5A5' : 'rgba(255,255,255,.55)',
                                                    }}
                                                    onMouseEnter={e => { if (!active) (e.currentTarget as HTMLAnchorElement).style.background = isDanger ? 'rgba(239,68,68,.1)' : 'rgba(255,255,255,.06)'; if (!active) (e.currentTarget as HTMLAnchorElement).style.color = isDanger ? '#FCA5A5' : 'white'; }}
                                                    onMouseLeave={e => { if (!active) (e.currentTarget as HTMLAnchorElement).style.background = 'transparent'; if (!active) (e.currentTarget as HTMLAnchorElement).style.color = isDanger ? '#FCA5A5' : 'rgba(255,255,255,.55)'; }}
                                                >
                                                    <span style={{ color: active ? 'var(--navy)' : isDanger && !active ? '#FCA5A5' : 'rgba(255,255,255,.4)', flexShrink: 0 }}>{link.icon}</span>
                                                    {link.label}
                                                    {isDanger && (
                                                        <span style={{ marginLeft: 'auto', height: 18, minWidth: 18, borderRadius: 9, background: '#EF4444', fontSize: 10, fontWeight: 700, color: 'white', display: 'flex', alignItems: 'center', justifyContent: 'center', padding: '0 5px' }}>
                                                            !
                                                        </span>
                                                    )}
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
                        <div style={{ borderTop: '1px solid rgba(255,255,255,.06)', padding: 10, flexShrink: 0 }}>
                            <div style={{ display: 'flex', alignItems: 'center', gap: 10, padding: '8px 10px', borderRadius: 10 }}>
                                <div style={{
                                    width: 32, height: 32, borderRadius: 9, flexShrink: 0,
                                    background: 'linear-gradient(135deg, var(--gold) 0%, #D97706 100%)',
                                    display: 'flex', alignItems: 'center', justifyContent: 'center',
                                    fontSize: 11, fontWeight: 800, color: 'var(--navy)',
                                }}>
                                    {getInitials(auth.user.name)}
                                </div>
                                <div style={{ flex: 1, minWidth: 0 }}>
                                    <p style={{ fontSize: 12, fontWeight: 600, color: 'white', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>{auth.user.name}</p>
                                    <p style={{ fontSize: 10, color: 'rgba(255,255,255,.3)', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap', textTransform: 'capitalize' }}>{auth.user.role ?? 'Référent qualité'}</p>
                                </div>
                            </div>
                        </div>
                    )}
                </aside>

                {/* Mobile overlay */}
                {sidebarOpen && (
                    <div
                        onClick={() => setSidebarOpen(false)}
                        style={{ position: 'fixed', inset: 0, zIndex: 30, background: 'rgba(0,0,0,.5)' }}
                        className="lg:hidden"
                    />
                )}

                {/* ══════════════════════════════════════════════════
                    MAIN
                ══════════════════════════════════════════════════ */}
                <div className="lg:ml-64 flex flex-col flex-1 min-w-0">

                    {/* Header */}
                    <header style={{
                        position: 'sticky', top: 0, zIndex: 30, height: 64,
                        background: 'white', borderBottom: '1px solid #F1F5F9',
                        display: 'flex', alignItems: 'center', justifyContent: 'space-between',
                        padding: '0 24px', flexShrink: 0,
                    }}>
                        <div style={{ display: 'flex', alignItems: 'center', gap: 16 }}>
                            {/* Mobile burger */}
                            <button
                                onClick={() => setSidebarOpen(true)}
                                className="lg:hidden"
                                style={{ padding: 8, borderRadius: 10, background: 'transparent', border: 'none', cursor: 'pointer', color: '#64748B' }}
                            >
                                <svg width={20} height={20} fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                                </svg>
                            </button>
                            <div>
                                <h1 style={{ fontSize: 15, fontWeight: 700, color: 'var(--navy)', lineHeight: 1 }}>{title}</h1>
                                {subtitle && <p style={{ fontSize: 12, color: '#94A3B8', marginTop: 3 }}>{subtitle}</p>}
                            </div>
                        </div>

                        <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
                            {/* CTA */}
                            <Link href="/incidents/create" className="hidden md:inline-flex" style={{
                                display: 'inline-flex', alignItems: 'center', gap: 6,
                                background: 'var(--navy)', color: 'white',
                                fontSize: 12, fontWeight: 600, padding: '8px 16px', borderRadius: 10,
                                textDecoration: 'none', transition: 'opacity .15s',
                            }}
                                onMouseEnter={e => (e.currentTarget.style.opacity = '.85')}
                                onMouseLeave={e => (e.currentTarget.style.opacity = '1')}
                            >
                                <svg width={13} height={13} fill="none" stroke="currentColor" strokeWidth={2.5} viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" d="M12 5v14M5 12h14" /></svg>
                                Déclarer un incident
                            </Link>

                            {/* Incidents badge */}
                            <Link href="/incidents" style={{
                                position: 'relative', width: 36, height: 36,
                                borderRadius: 10, background: '#FFF7ED', border: '1px solid #FDE68A',
                                display: 'flex', alignItems: 'center', justifyContent: 'center',
                                color: 'var(--gold)', textDecoration: 'none',
                            }}>
                                <svg width={16} height={16} fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                <span style={{ position: 'absolute', top: -4, right: -4, height: 16, width: 16, borderRadius: '50%', background: '#EF4444', fontSize: 9, fontWeight: 700, color: 'white', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>3</span>
                            </Link>

                            {/* Notifications */}
                            <button style={{
                                position: 'relative', width: 36, height: 36,
                                borderRadius: 10, background: '#FAFAFA', border: '1px solid #E2E8F0',
                                display: 'flex', alignItems: 'center', justifyContent: 'center',
                                color: '#94A3B8', cursor: 'pointer', transition: 'all .15s',
                            }}
                                onMouseEnter={e => { (e.currentTarget as HTMLButtonElement).style.borderColor = 'var(--gold)'; (e.currentTarget as HTMLButtonElement).style.color = 'var(--gold)'; }}
                                onMouseLeave={e => { (e.currentTarget as HTMLButtonElement).style.borderColor = '#E2E8F0'; (e.currentTarget as HTMLButtonElement).style.color = '#94A3B8'; }}
                            >
                                <svg width={16} height={16} fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                </svg>
                                <span style={{ position: 'absolute', top: 7, right: 7, width: 6, height: 6, borderRadius: '50%', background: 'var(--gold)' }} />
                            </button>

                            {/* User menu */}
                            {auth?.user && (
                                <div style={{ position: 'relative' }}>
                                    <button
                                        onClick={() => setShowUserMenu(!showUserMenu)}
                                        style={{
                                            display: 'flex', alignItems: 'center', gap: 10,
                                            borderRadius: 10, border: '1px solid #E2E8F0', background: '#FAFAFA',
                                            padding: '6px 12px', cursor: 'pointer', transition: 'all .15s',
                                        }}
                                        onMouseEnter={e => (e.currentTarget as HTMLButtonElement).style.borderColor = 'var(--gold)'}
                                        onMouseLeave={e => (e.currentTarget as HTMLButtonElement).style.borderColor = '#E2E8F0'}
                                    >
                                        <div style={{
                                            width: 28, height: 28, borderRadius: 8,
                                            background: 'linear-gradient(135deg, var(--gold) 0%, #D97706 100%)',
                                            display: 'flex', alignItems: 'center', justifyContent: 'center',
                                            fontSize: 11, fontWeight: 800, color: 'var(--navy)',
                                        }}>
                                            {getInitials(auth.user.name)}
                                        </div>
                                        <span className="hidden md:block" style={{ fontSize: 13, fontWeight: 600, color: 'var(--navy)' }}>{auth.user.name}</span>
                                        <svg width={14} height={14} fill="none" stroke="#94A3B8" strokeWidth={2} viewBox="0 0 24 24" style={{ transition: 'transform .2s', transform: showUserMenu ? 'rotate(180deg)' : 'none' }}>
                                            <path strokeLinecap="round" strokeLinejoin="round" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>

                                    {showUserMenu && (
                                        <>
                                            <div style={{ position: 'fixed', inset: 0, zIndex: 40 }} onClick={() => setShowUserMenu(false)} />
                                            <div style={{
                                                position: 'absolute', right: 0, marginTop: 8, width: 240, zIndex: 50,
                                                background: 'white', border: '1px solid #E2E8F0',
                                                borderRadius: 16, boxShadow: '0 16px 48px rgba(11,22,40,.1)',
                                                overflow: 'hidden',
                                            }}>
                                                <div style={{ padding: '14px 16px', borderBottom: '1px solid #F1F5F9', background: '#FAFAFA', display: 'flex', alignItems: 'center', gap: 10 }}>
                                                    <div style={{ width: 38, height: 38, borderRadius: 10, background: 'linear-gradient(135deg, var(--gold) 0%, #D97706 100%)', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 13, fontWeight: 800, color: 'var(--navy)' }}>
                                                        {getInitials(auth.user.name)}
                                                    </div>
                                                    <div style={{ flex: 1, minWidth: 0 }}>
                                                        <p style={{ fontSize: 13, fontWeight: 700, color: 'var(--navy)', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>{auth.user.name}</p>
                                                        <p style={{ fontSize: 11, color: '#94A3B8', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>{auth.user.email}</p>
                                                    </div>
                                                </div>
                                                <div style={{ padding: 6 }}>
                                                    {[
                                                        { href: '/profile',    label: 'Mon profil',  icon: <UserIcon /> },
                                                        { href: '/parametres', label: 'Paramètres',  icon: <CogIcon /> },
                                                    ].map(item => (
                                                        <Link key={item.href} href={item.href} onClick={() => setShowUserMenu(false)}
                                                            style={{ display: 'flex', alignItems: 'center', gap: 10, padding: '9px 10px', borderRadius: 9, fontSize: 13, color: '#475569', textDecoration: 'none', transition: 'all .15s' }}
                                                            onMouseEnter={e => { (e.currentTarget as HTMLAnchorElement).style.background = '#F8FAFC'; (e.currentTarget as HTMLAnchorElement).style.color = 'var(--navy)'; }}
                                                            onMouseLeave={e => { (e.currentTarget as HTMLAnchorElement).style.background = 'transparent'; (e.currentTarget as HTMLAnchorElement).style.color = '#475569'; }}
                                                        >
                                                            <span style={{ color: '#94A3B8' }}>{item.icon}</span>{item.label}
                                                        </Link>
                                                    ))}
                                                    <div style={{ margin: '4px 0', height: 1, background: '#F1F5F9' }} />
                                                    <button onClick={handleLogout}
                                                        style={{ display: 'flex', width: '100%', alignItems: 'center', gap: 10, padding: '9px 10px', borderRadius: 9, fontSize: 13, color: '#EF4444', background: 'transparent', border: 'none', cursor: 'pointer', transition: 'background .15s' }}
                                                        onMouseEnter={e => (e.currentTarget as HTMLButtonElement).style.background = '#FFF5F5'}
                                                        onMouseLeave={e => (e.currentTarget as HTMLButtonElement).style.background = 'transparent'}
                                                    >
                                                        <LogoutIcon />Se déconnecter
                                                    </button>
                                                </div>
                                            </div>
                                        </>
                                    )}
                                </div>
                            )}
                        </div>
                    </header>

                    {/* Page content */}
                    <main style={{ flex: 1, padding: '28px 28px' }}>{children}</main>
                </div>
            </div>
        </>
    );
}

// ─── Icons ────────────────────────────────────────────────────────────────────
function HomeIcon()       { return <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>; }
function ClipboardIcon()  { return <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>; }
function AlertIcon()      { return <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>; }
function UserHeartIcon()  { return <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>; }
function BadgeIcon()      { return <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l2 2 4-4"/></svg>; }
function CheckListIcon()  { return <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg>; }
function ChartIcon()      { return <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round"><path d="M18 20V10M12 20V4M6 20v-6"/></svg>; }
function HeartIcon()      { return <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg>; }
function AcademicIcon()   { return <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>; }
function ChatIcon()       { return <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>; }
function BuildingIcon()   { return <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round"><path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16M3 21h18M9 7h1M9 11h1M9 15h1M14 7h1M14 11h1M14 15h1"/></svg>; }
function UsersIcon()      { return <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>; }
function DocumentIcon()   { return <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>; }
function CogIcon()        { return <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/></svg>; }
function UserIcon()       { return <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>; }
function LogoutIcon()     { return <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9"/></svg>; }