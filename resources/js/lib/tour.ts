/**
 * Guided tour: highlights an element and explains it in one short sentence.
 *
 * - Built-in tours per role (learner/parent, registrar, principal) start by themselves
 *   the first time someone opens the dashboard, and can be started again from the "?" button.
 * - The help assistant starts small tours too ("show me how to request Form 137").
 *
 * An element is highlighted when it has data-tour="name". Steps whose element is not on the
 * screen are skipped, so one list works on desktop (sidebar visible) and on a phone (menu hidden).
 */

export type TourStep = {
    /** data-tour="..." of the element to highlight. No target: centred message. */
    target?: string;
    title?: string;
    text: string;
    /** Same text in Tagalog. */
    text_tl?: string;
    title_tl?: string;
    /** Page to open first (the tour continues there). */
    url?: string;
    /** Show only on a phone or only on a bigger screen. */
    only?: 'mobile' | 'desktop';
};

export type TourDetail = { steps: TourStep[]; id?: string; lang?: 'en' | 'tl' };

export const TOUR_EVENT = 'eskwelahub:tour';

/** Start a tour from anywhere. */
export function startTour(detail: TourDetail): void {
    window.dispatchEvent(new CustomEvent<TourDetail>(TOUR_EVENT, { detail }));
}

const key = (userId: number | string) => `eskwelahub-tour-done:${userId}`;

export function tourDone(userId: number | string): boolean {
    try {
        return localStorage.getItem(key(userId)) === '1';
    } catch {
        return true; // storage blocked: do not nag
    }
}

export function markTourDone(userId: number | string): void {
    try {
        localStorage.setItem(key(userId), '1');
    } catch {
        /* ignore */
    }
}

export function preferredLanguage(): 'en' | 'tl' {
    try {
        return localStorage.getItem('eskwelahub-lang') === 'tl' ? 'tl' : 'en';
    } catch {
        return 'en';
    }
}

export function savePreferredLanguage(lang: 'en' | 'tl'): void {
    try {
        localStorage.setItem('eskwelahub-lang', lang);
    } catch {
        /* ignore */
    }
}

const welcome = (who: string, tl: string): TourStep => ({
    title: 'Welcome to EskwelaHub',
    title_tl: 'Maligayang pagdating sa EskwelaHub',
    text: who,
    text_tl: tl,
});

const menu: TourStep = {
    target: 'menu-button',
    only: 'mobile',
    text: 'Tap here to open the menu and move between pages.',
    text_tl: 'I-tap ito para buksan ang menu at lumipat ng pahina.',
};

const chat: TourStep = {
    target: 'chat-button',
    title: 'Need help?',
    title_tl: 'Kailangan ng tulong?',
    text: 'Ask the assistant in English or Tagalog. It can also show you where to click.',
    text_tl: 'Magtanong sa assistant sa English o Tagalog. Maipapakita rin nito kung saan magki-click.',
};

/** The tour for a role. Learners and parents, the registrar and the principal each see their own. */
export function tourFor(role: 'student' | 'staff' | 'admin'): TourStep[] {
    if (role === 'student') {
        return [
            welcome('A one-minute tour of what you can do.', 'Isang minutong tour ng mga magagawa mo.'),
            menu,
            { target: 'act-records', text: 'See the school record of your child. Link it first with the LRN and birthdate.', text_tl: 'Tingnan ang school record ng anak mo. I-link muna gamit ang LRN at birthdate.' },
            { target: 'act-request', text: 'Ask for Form 137 or a certificate.', text_tl: 'Humiling ng Form 137 o certificate.' },
            { target: 'act-enroll', text: 'Enroll a new Grade 7 learner or a transferee online.', text_tl: 'Mag-enroll online ng bagong Grade 7 o transferee.' },
            { target: 'dash-status', text: 'These numbers show what the registrar is still working on.', text_tl: 'Ipinapakita ng mga numerong ito ang inaasikaso pa ng registrar.' },
            { target: 'nav-requests', only: 'desktop', text: 'Follow your requests here and download documents when released.', text_tl: 'Dito mo masusubaybayan ang requests at madi-download ang documents kapag na-release na.' },
            chat,
        ];
    }

    if (role === 'staff') {
        return [
            welcome('A short tour of your daily work.', 'Maikling tour ng araw-araw mong trabaho.'),
            menu,
            { target: 'dash-todo', text: 'Start here. Each line is work waiting for you. Click one to open it.', text_tl: 'Dito magsimula. Bawat linya ay trabahong naghihintay. I-click para buksan.' },
            { target: 'nav-records', only: 'desktop', text: 'Search any learner by LRN or name. Typos are fine.', text_tl: 'Maghanap ng learner gamit ang LRN o pangalan. Okay lang ang typo.' },
            { target: 'nav-sections', only: 'desktop', text: 'Class sections, class lists and moving learners.', text_tl: 'Class sections, class lists at paglilipat ng learners.' },
            { target: 'nav-requests', only: 'desktop', text: 'Review, approve and release document requests.', text_tl: 'I-review, i-approve at i-release ang document requests.' },
            { target: 'nav-enrollments', only: 'desktop', text: 'Approve online enrollment forms.', text_tl: 'I-approve ang online enrollment forms.' },
            { target: 'nav-ocr', only: 'desktop', text: 'Turn scanned old records into digital records.', text_tl: 'Gawing digital ang scanned na lumang records.' },
            { target: 'dash-overview', text: 'The main school numbers at a glance.', text_tl: 'Mabilis na tingin sa mahahalagang numero ng school.' },
            chat,
        ];
    }

    return [
        welcome('A short tour of the principal dashboard.', 'Maikling tour ng principal dashboard.'),
        menu,
        { target: 'kpis', text: 'The five main numbers. Click one to open the list behind it.', text_tl: 'Ang limang pangunahing numero. I-click para makita ang listahan.' },
        { target: 'enrollment-chart', text: 'Enrollment of past years, this year and the forecast.', text_tl: 'Enrollment ng nakaraang taon, ngayong taon at ang forecast.' },
        { target: 'export', text: 'Download everything as an Excel file.', text_tl: 'I-download ang lahat bilang Excel file.' },
        { target: 'nav-audit', only: 'desktop', text: 'Audit trail: what people did inside the system.', text_tl: 'Audit trail: ang ginawa ng mga tao sa loob ng system.' },
        { target: 'nav-sessions', only: 'desktop', text: 'Session logs: who logged in, and failed tries.', text_tl: 'Session logs: sino ang nag-login, at mga bigong subok.' },
        { target: 'nav-surveys', only: 'desktop', text: 'What parents and learners say about the service.', text_tl: 'Ang sinasabi ng mga magulang at learners tungkol sa serbisyo.' },
        chat,
    ];
}

/** Which tour fits the signed-in person (from the permissions the server shared with the page). */
export function roleFromAuth(can: { viewStudents: boolean; viewReports: boolean }): 'student' | 'staff' | 'admin' {
    return can.viewReports ? 'admin' : can.viewStudents ? 'staff' : 'student';
}
