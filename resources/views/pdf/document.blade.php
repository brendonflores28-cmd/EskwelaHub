<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $request->request_no }}</title>
    <style>
        @page { margin: 36px 48px; }
        /* Helvetica is built into PDF readers, so no font is embedded (a PDF is about 20 KB, not 900 KB).
           It covers Filipino names with n-tilde. */
        body { font-family: Helvetica, Arial, sans-serif; font-size: 11px; color: #111; }
        .center { text-align: center; }
        .school { font-size: 15px; font-weight: bold; margin: 0; }
        .muted { color: #555; }
        h1 { font-size: 14px; text-transform: uppercase; margin: 22px 0 14px; letter-spacing: 1px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #999; padding: 4px 6px; text-align: left; }
        th { background: #eee; }
        table.plain td { border: 0; padding: 2px 0; }
        .section { margin-top: 16px; }
        .footer { position: fixed; bottom: 0; left: 0; right: 0; }
        .qr { width: 90px; height: 90px; }
        .sign { margin-top: 48px; }
        .line { border-top: 1px solid #111; width: 220px; padding-top: 3px; }
    </style>
</head>
<body>
    <table class="plain" style="margin-bottom:4px">
        <tr>
            <td width="80"><img src="{{ $logo }}" alt="School seal" style="width:68px;height:68px"></td>
            <td class="center">
                <p class="muted" style="margin:0">Republic of the Philippines - Department of Education</p>
                <p class="school">{{ $school }}</p>
                <p class="muted" style="margin:0">{{ $address }}</p>
                <p class="muted" style="margin:0">{{ $district }} - School ID {{ $schoolId }}</p>
            </td>
            <td width="80"></td>
        </tr>
    </table>

    <h1 class="center">{{ $request->document_type->pdfTitle() }}</h1>

    <table class="plain">
        <tr><td width="25%">Name</td><td><strong>{{ $student->full_name }}</strong></td></tr>
        <tr><td>LRN</td><td>{{ $student->lrn ?? 'Not assigned' }}</td></tr>
        <tr><td>Sex</td><td>{{ $student->sex === 'M' ? 'Male' : 'Female' }}</td></tr>
        <tr><td>Date of birth</td><td>{{ $student->birthdate->format('F j, Y') }}</td></tr>
        @if ($student->birthplace)
            <tr><td>Place of birth</td><td>{{ $student->birthplace }}</td></tr>
        @endif
    </table>

    @if (in_array($request->document_type->value, ['form_137', 'enrollment_certification', 'other'], true))
        <div class="section">
            <strong>{{ $request->document_type->value === 'form_137' ? 'Scholastic record' : 'Enrollment record' }}</strong>
            <table style="margin-top:6px">
                <thead>
                    <tr><th>School year</th><th>Grade</th><th>Section</th><th>General average</th><th>Status</th></tr>
                </thead>
                <tbody>
                    @php
                        $rows = $student->enrollments->sortBy('school_year_id');
                        if ($request->document_type->value !== 'form_137') {
                            $rows = $rows->slice(-1); // only the latest school year
                        }
                    @endphp
                    @forelse ($rows as $e)
                        <tr>
                            <td>{{ $e->schoolYear?->label }}</td>
                            <td>{{ $e->grade_level }}@if ($e->strand) - {{ $e->strand->short() }}@endif</td>
                            <td>{{ $e->section?->name ?? '-' }}</td>
                            <td>{{ $e->general_average ?? '-' }}</td>
                            <td>{{ ucfirst($e->status->value) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5">No enrollment on file.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif

    @if ($request->document_type->value === 'diploma_certification' || $request->document_type->value === 'form_137')
        <div class="section">
            @if ($student->graduation)
                This is to certify that <strong>{{ $student->full_name }}</strong> graduated in school year
                <strong>{{ $student->graduation->schoolYear?->label }}</strong>
                @if ($student->graduation->date_graduated)
                    (graduation date {{ $student->graduation->date_graduated->format('F j, Y') }})
                @endif
                @if ($student->graduation->honors), {{ $student->graduation->honors }}@endif.
                @if ($student->graduation->diploma_no)
                    Diploma no. {{ $student->graduation->diploma_no }}.
                @endif
            @elseif ($request->document_type->value === 'diploma_certification')
                No graduation record is on file for this learner.
            @endif
        </div>
    @endif

    <div class="section">
        Issued upon request of the learner or the learner's parent or guardian for the purpose of:
        <strong>{{ $request->purpose }}</strong>.
    </div>

    <div class="sign">
        <div class="line">School Registrar</div>
        <div class="muted">Issued {{ $issuedAt->format('F j, Y') }}</div>
    </div>

    <div class="footer">
        <table class="plain">
            <tr>
                <td width="100"><img class="qr" src="{{ $qr }}" alt="QR code"></td>
                <td class="muted">
                    Request no. <strong>{{ $request->request_no }}</strong><br>
                    Copy {{ $request->copies }} requested. Scan the QR code or open<br>
                    {{ route('verify', $request->verification_token) }}<br>
                    to check that this document is genuine. A printed copy without a valid QR code is not official.
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
