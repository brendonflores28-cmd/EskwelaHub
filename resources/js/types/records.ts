/** Shapes returned by the Laravel resources and controllers for school records. */

export type Option = { value: string; label: string };
export type SchoolYearOption = { id: number; label: string };

export type Enrollment = {
    id: number;
    school_year_id: number;
    school_year: string | null;
    grade_level: number;
    strand: string | null;
    strand_short: string | null;
    section: string | null;
    curriculum: string;
    status: string;
    general_average: string | null;
};

export type Graduation = {
    school_year_id: number;
    school_year: string | null;
    date_graduated: string | null;
    honors: string | null;
    diploma_no: string | null;
    general_average: string | null;
};

export type Student = {
    id: number;
    lrn: string | null;
    full_name: string;
    last_name: string;
    first_name: string;
    middle_name: string | null;
    suffix: string | null;
    sex: 'M' | 'F';
    birthdate: string;
    birthplace: string | null;
    status: string;
    is_draft: boolean;
    latest_enrollment?: Enrollment | null;
    enrollments?: Enrollment[];
    graduation?: Graduation | null;
};

/** Laravel paginator as serialized by a resource collection. */
export type Paginated<T> = {
    data: T[];
    links: { prev: string | null; next: string | null };
    meta: { current_page: number; last_page: number; total: number; from: number | null; to: number | null };
};

export type HistoryEntry = { to_status: string; by: string; note: string | null; at: string };

export type DocumentRequest = {
    id: number;
    request_no: string;
    document_type: string;
    document_label: string;
    purpose: string;
    copies: number;
    status: string;
    status_label: string;
    remarks: string | null;
    created_at: string;
    released_at: string | null;
    student?: { id: number; full_name: string; lrn: string | null };
    requester?: { name: string; email: string };
    has_document?: boolean;
    history?: HistoryEntry[];
};

export type EnrollmentForm = {
    id: number;
    reference_no: string;
    type: string;
    type_label: string;
    grade_level: number;
    strand: string | null;
    strand_label: string | null;
    status: string;
    status_label: string;
    decision_note: string | null;
    full_name: string;
    lrn: string | null;
    sex: string;
    birthdate: string;
    birthplace: string | null;
    address: string;
    guardian_name: string;
    guardian_contact: string;
    previous_school: string | null;
    relationship: string;
    school_year?: string;
    student_id: number | null;
    created_at: string;
    applicant?: { name: string; email: string };
    documents?: { id: number; kind: string; original_name: string; size_kb: number }[];
};

/** A senior high strand or track the school offers (Grades 11 and 12). */
export type StrandOption = { value: string; label: string; short: string };
