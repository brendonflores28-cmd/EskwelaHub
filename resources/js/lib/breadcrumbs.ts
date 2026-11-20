import type { BreadcrumbItem } from '@/types';

/**
 * Builds the breadcrumb trail ("Dashboard > Student records > SANTOS, Juan > Edit")
 * from the address of the page and, for a single record, from its page data.
 * One place to maintain, so every page gets a full trail with links back.
 */

type Props = Record<string, unknown>;

type Section = {
    title: string;
    /** Label for the "create" page of this section. */
    create?: string;
    /** Name of one record, taken from the page data (for example the student's name). */
    record?: (props: Props) => string | undefined;
    /** Extra level above the section, for example "Settings". */
    parent?: BreadcrumbItem;
};

const get = (props: Props, path: string): string | undefined => {
    const value = path.split('.').reduce<unknown>((o, key) => (o && typeof o === 'object' ? (o as Props)[key] : undefined), props);

    return typeof value === 'string' ? value : undefined;
};

/** First path segments (without a slash at the start) that have a trail. */
const sections: Record<string, Section> = {
    'staff/students': { title: 'Student records', create: 'New record', record: (p) => get(p, 'student.data.full_name') },
    'staff/sections': {
        title: 'Class sections',
        record: (p) => {
            const grade = get(p, 'section.grade_level') ?? (p.section as { grade_level?: number } | undefined)?.grade_level;
            const name = get(p, 'section.name');

            return name ? `Grade ${grade} - ${name}` : undefined;
        },
    },
    'staff/requests': { title: 'Document requests', record: (p) => get(p, 'request.data.request_no') },
    'staff/enrollments': { title: 'Enrollment forms', record: (p) => get(p, 'application.data.reference_no') },
    'staff/ocr': { title: 'Digitize records', create: 'Upload a scan', record: (p) => get(p, 'draft.file') },
    'staff/links': { title: 'Account link approvals' },
    'staff/alerts': { title: 'Record alerts' },
    'my-records': { title: 'My records', record: (p) => get(p, 'student.data.full_name') },
    'my-requests': { title: 'My requests', create: 'New request', record: (p) => get(p, 'request.data.request_no') },
    'my-enrollments': { title: 'Enrollment', create: 'New form', record: (p) => get(p, 'application.data.reference_no') },
    'admin/audit-log': { title: 'Audit trail' },
    'admin/session-logs': { title: 'Session logs' },
    'admin/surveys': { title: 'Surveys' },
    'settings/profile': { title: 'Profile', parent: { title: 'Settings', href: '/settings/profile' } },
    'settings/security': { title: 'Security', parent: { title: 'Settings', href: '/settings/profile' } },
    'settings/appearance': { title: 'Appearance', parent: { title: 'Settings', href: '/settings/profile' } },
};

const root: BreadcrumbItem = { title: 'Dashboard', href: '/dashboard' };

/** Returns null for pages without a rule, so the page's own breadcrumbs are used. */
export function buildBreadcrumbs(url: string, props: Props): BreadcrumbItem[] | null {
    const path = url.split('?')[0].replace(/^\/+|\/+$/g, '');
    const key = Object.keys(sections).find((k) => path === k || path.startsWith(k + '/'));

    if (!key) {
        return null;
    }

    const section = sections[key];
    const trail: BreadcrumbItem[] = [root];
    if (section.parent) {
        trail.push(section.parent);
    }
    trail.push({ title: section.title, href: '/' + key });

    const rest = path.slice(key.length).split('/').filter(Boolean);
    let href = '/' + key;

    for (const part of rest) {
        href += '/' + part;

        if (part === 'create') {
            trail.push({ title: section.create ?? 'New', href });
        } else if (part === 'edit') {
            trail.push({ title: 'Edit', href });
        } else if (/^\d+$/.test(part)) {
            trail.push({ title: section.record?.(props) ?? `#${part}`, href });
        }
    }

    return trail;
}
