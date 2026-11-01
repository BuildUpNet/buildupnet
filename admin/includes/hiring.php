<?php
// ==========================================================
// Buildupnet Admin — Hiring: statuses, workflow rules, letter text + PDF.
// Flow: manager fills the form (draft) -> submits -> OWNER approves or rejects -> offer letter -> candidate accepts -> joins
//       (login + work timing + project links are created) -> joining letter.
// ==========================================================
if (basename($_SERVER['SCRIPT_NAME'] ?? '') === 'hiring.php') { http_response_code(403); exit; }

const HIRE_STATUSES = [          // status => [label, badge class]
    'draft'        => ['Draft',                'abadge-grey'],
    'pending'      => ['Waiting for owner',    'abadge-orange'],
    'approved'     => ['Approved',             'abadge-green'],
    'rejected'     => ['Rejected',             'abadge-red'],
    'offer_issued' => ['Offer issued',         'abadge-blue'],
    'accepted'     => ['Offer accepted',       'abadge-green'],
    'declined'     => ['Offer declined',       'abadge-red'],
    'joined'       => ['Joined',               'abadge-green'],
    'cancelled'    => ['Cancelled',            'abadge-grey'],
];
const HIRE_OPEN = ['draft', 'pending', 'approved', 'offer_issued', 'accepted'];     // still in progress
const HIRE_TYPES = ['full_time' => 'Full time', 'part_time' => 'Part time', 'intern' => 'Intern / trainee', 'contract' => 'Contract'];
const HIRE_ROLES = ['developer' => 'Developer', 'manager' => 'Manager'];
const WEEKDAYS_LONG = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
const LETTERHEAD_TOP_MM = 66;      // text area on the letterhead (mm from top of the A4 page)
const LETTERHEAD_BOTTOM_MM = 245;

// Which status may follow which (anything else is refused, even if somebody forges a request).
const HIRE_FLOW = [
    'draft'        => ['pending', 'cancelled'],
    'pending'      => ['approved', 'rejected', 'draft'],
    'rejected'     => ['draft', 'cancelled'],
    'approved'     => ['offer_issued', 'cancelled'],
    'offer_issued' => ['accepted', 'declined', 'cancelled'],
    'accepted'     => ['joined', 'cancelled'],
];

function hireBadge(string $status): string {
    [$label, $cls] = HIRE_STATUSES[$status] ?? [$status, 'abadge-grey'];
    return '<span class="abadge ' . $cls . '">' . clean($label) . '</span>';
}

/** Number shown on the owner's sidebar. Never breaks a page if the hiring tables are not installed yet. */
function hirePendingCount(): int {
    if (!can('hiring.approve')) return 0;
    try { return (int)(dbRow("SELECT COUNT(*) AS c FROM hire_requests WHERE status = 'pending'")['c'] ?? 0); }
    catch (Throwable $e) { return 0; }
}

function hireLog(int $hireId, string $action, string $note = ''): void {
    global $conn;
    $uid = currentUserId(); $note = mb_substr($note, 0, 500);
    $st = $conn->prepare("INSERT INTO hire_log (hire_id, user_id, action, note) VALUES (?,?,?,?)");
    $st->bind_param('iiss', $hireId, $uid, $action, $note); $st->execute();
}

/** The person who created the request, or the owner, may change it. Other managers can only look. */
function hireOwns(array $h): bool { return currentRole() === 'owner' || (int)$h['created_by_id'] === currentUserId(); }
function hireEditable(array $h): bool { return in_array($h['status'], ['draft', 'rejected'], true) && hireOwns($h); }

/**
 * Moves a request to a new status. Returns null on success or an error text.
 * The UPDATE repeats the old status in its WHERE, so two people clicking at the same moment cannot both win.
 */
function hireMove(array $h, string $to, array $set = []): ?string {
    global $conn;
    if (!in_array($to, HIRE_FLOW[$h['status']] ?? [], true)) return 'This step is not possible from "' . (HIRE_STATUSES[$h['status']][0] ?? $h['status']) . '".';
    $cols = ['status = ?']; $types = 's'; $vals = [$to];
    foreach ($set as $col => [$t, $v]) { $cols[] = $col . ' = ?'; $types .= $t; $vals[] = $v; }     // column names come from this code only
    $types .= 'is'; $vals[] = (int)$h['id']; $vals[] = $h['status'];
    $st = $conn->prepare("UPDATE hire_requests SET " . implode(', ', $cols) . " WHERE id = ? AND status = ?");
    $st->bind_param($types, ...$vals); $st->execute();
    return $st->affected_rows === 1 ? null : 'Somebody else changed this request just now. Reload and check.';
}

/** Checklist: every active document type with the date it was received (or null). */
function hireDocs(int $hireId): array {
    return dbRows("SELECT t.id, t.label, t.is_required, d.received_on FROM hire_doc_types t
                   LEFT JOIN hire_documents d ON d.doc_type_id = t.id AND d.hire_id = ?
                   WHERE t.is_active = 1 ORDER BY t.sort_order, t.id", 'i', [$hireId]);
}

function hireMissingRequired(int $hireId): array {
    return array_values(array_map(fn($d) => $d['label'], array_filter(hireDocs($hireId), fn($d) => (int)$d['is_required'] === 1 && !$d['received_on'])));
}

function hireProjects(int $hireId): array {
    return dbRows("SELECT p.id, p.name, p.project_code FROM hire_projects hp JOIN client_projects p ON p.id = hp.project_id WHERE hp.hire_id = ? ORDER BY p.name", 'i', [$hireId]);
}

function hireTimeText(string $t): string { return date('g:i A', strtotime($t)); }

// ---------------------------------------------------------------- letters

function hireFilled(array $h): array {
    $reports = $h['reports_to_id'] ? (dbRow("SELECT name FROM admin_users WHERE id = ?", 'i', [(int)$h['reports_to_id']])['name'] ?? '') : '';
    $off = array_filter(array_map(fn($d) => WEEKDAYS_LONG[(int)$d] ?? null, array_filter(explode(',', (string)$h['weekly_off']), 'strlen')));
    $projects = implode(', ', array_column(hireProjects((int)$h['id']), 'name'));
    $offerDate = $h['offer_date'] ?: date('Y-m-d');
    $valid = $h['offer_valid_until'] ?: date('Y-m-d', strtotime($offerDate . ' +7 days'));
    $joinDate = $h['joined_on'] ?: $h['joining_date'];
    $m = (int)$h['probation_months'];
    return [
        'name' => $h['full_name'], 'position' => $h['position'], 'department' => $h['department'] ?: 'Development',
        'employment_type' => HIRE_TYPES[$h['employment_type']] ?? 'Full time',
        'reports_to' => $reports ?: 'Management', 'work_location' => $h['work_location'] ?: 'Sangrur, Punjab',
        'joining_date' => $h['joining_date'] ? fmtDate($h['joining_date']) : 'To be confirmed',
        'joined_date' => $joinDate ? fmtDate($joinDate) : 'To be confirmed',
        'salary' => inr($h['salary_monthly']),
        'probation' => $m > 0 ? $m . ' month' . ($m > 1 ? 's' : '') : 'None',
        'notice' => (int)$h['notice_days'] . ' days',
        'work_timing' => hireTimeText($h['work_start']) . ' to ' . hireTimeText($h['work_end']) . ' (lunch ' . hireTimeText($h['lunch_start']) . ' to ' . hireTimeText($h['lunch_end']) . ')',
        'weekly_off' => $off ? implode(', ', $off) : 'As per company calendar',
        'valid_until' => fmtDate($valid), 'offer_date' => fmtDate($offerDate), 'offer_ref' => (string)($h['offer_ref'] ?: '-'),
        'employee_code' => (string)($h['employee_code'] ?: '-'), 'projects' => $projects !== '' ? $projects : 'As assigned by your manager',
    ];
}

function hireFillText(string $text, array $vars): string {
    return preg_replace_callback('/\{([a-z_]+)\}/', fn($m) => $vars[$m[1]] ?? '', $text);
}

/** Letters a request may produce in its current state (the joining letter exists only once the person has joined). */
function hireLetterAllowed(array $h, string $type): bool {
    if ($type === 'offer')   return in_array($h['status'], ['approved', 'offer_issued', 'accepted', 'joined'], true);
    if ($type === 'joining') return $h['status'] === 'joined';
    return false;
}

/** Builds the letter as a PDF string on the BuildUpNet letterhead. Wording comes from the letter_templates table. */
function hireLetterPdf(array $h, string $type): string {
    $tpl = dbRow("SELECT * FROM letter_templates WHERE letter_type = ?", 's', [$type]);
    if (!$tpl) throw new RuntimeException('Letter template missing - run upgrade-hiring.sql.');
    $v = hireFilled($h);
    $isOffer = $type === 'offer';
    $p = new MiniPdf(__DIR__ . '/letterhead.jpg', LETTERHEAD_TOP_MM, LETTERHEAD_BOTTOM_MM, ($isOffer ? 'Offer letter - ' : 'Joining letter - ') . $h['full_name']);
    $x = MiniPdf::mm(22); $w = MiniPdf::mm(166); $lh = 14.2; $bl = ($lh - 10) / 2 + 1;     // $bl = distance from the bottom of a text line to its baseline
    $ink = [0.1, 0.1, 0.12]; $blue = [0.0, 0.33, 0.5]; $rule = [0.8, 0.85, 0.9];
    $c = fn(array $k) => $p->colour(...$k);
    $c($ink);

    // reference + date
    $date = $isOffer ? ($h['offer_date'] ?: date('Y-m-d')) : ($h['joined_on'] ?: date('Y-m-d'));
    $ref  = $isOffer ? $h['offer_ref'] : $h['joining_ref'];
    $p->font('R', 9.5); $p->y += 4; $yy = $p->y + 10;
    $p->text($x, $yy, 'Ref: ' . ($ref ?: '-')); $p->text($x, $yy, 'Date: ' . fmtDate($date), 'R', $w);
    $p->y += 24;
    $p->font('B', 13); $c($blue); $p->text($x, $p->y + 12, $tpl['title'], 'C', $w); $c($ink);
    $p->y += 28;

    // addressee
    $p->font('R', 10); $p->para('To,', $x, $w, $lh);
    $p->font('B', 10.5); $p->para($h['full_name'], $x, $w, $lh);
    $p->font('R', 10);
    if ($h['address'])  $p->para($h['address'], $x, $w * 0.7, $lh);
    if ($h['phone'])    $p->para('Phone: ' . $h['phone'], $x, $w, $lh);
    if ($h['email'])    $p->para('Email: ' . $h['email'], $x, $w, $lh);
    $p->y += 6;
    $p->font('B', 10); $p->para('Subject: ' . hireFillText($tpl['subject'], $v), $x, $w, $lh);
    $p->y += 6; $p->font('R', 10);

    // body: blank line = gap, "## " = heading, "- " = bullet, "Label :: value" = table row, anything else = paragraph
    foreach (preg_split('/\R/u', hireFillText((string)$tpl['body'], $v)) as $line) {
        $line = rtrim($line);
        if ($line === '') { $p->y += 5; continue; }
        if (strncmp($line, '## ', 3) === 0) {
            $p->ensure(40); $p->y += 6; $p->font('B', 10.5); $c($blue); $p->para(substr($line, 3), $x, $w, $lh); $c($ink); $p->font('R', 10); $p->y += 2;
        } elseif (strncmp($line, '- ', 2) === 0) {
            $p->ensure($lh * 2); $yb = $p->y + $lh - $bl;
            $p->text($x + 6, $yb, "\u{2022}"); $p->para(substr($line, 2), $x + 18, $w - 18, $lh); $p->y += 1.5;
        } elseif (strpos($line, ' :: ') !== false) {
            [$label, $val] = explode(' :: ', $line, 2);
            $labelW = MiniPdf::mm(52);
            $p->font('R', 10); $vl = $p->wrap($val, $w - $labelW - 6);
            $p->ensure($lh * max(1, count($vl)) + 4);
            $p->font('B', 10); $p->text($x, $p->y + $lh - $bl, $label);
            $p->font('R', 10);
            foreach ($vl as $ln) { $p->y += $lh; $p->text($x + $labelW, $p->y - $bl, $ln); }
            $p->y += 3; $c($rule); $p->line($x, $p->y, $x + $w, $p->y, 0.4); $c($ink);
        } else {
            $p->para($line, $x, $w, $lh);
        }
    }

    // signature
    $p->ensure(125); $p->y += 14; $p->font('R', 10);
    $p->para('Yours sincerely,', $x, $w, $lh);
    $p->font('B', 10); $p->para('For BuildUpNet', $x, $w, $lh);
    $p->y += 34;
    $p->font('B', 10); $p->para((string)$tpl['signatory_name'], $x, $w, $lh);
    $p->font('R', 10); $p->para((string)$tpl['signatory_title'], $x, $w, $lh);

    // acceptance / acknowledgement
    $p->ensure(110); $p->y += 18;
    $c($rule); $p->line($x, $p->y, $x + $w, $p->y, 0.6); $c($ink); $p->y += 4;
    $p->font('B', 10.5); $c($blue); $p->para($isOffer ? 'Acceptance' : 'Acknowledgement', $x, $w, $lh); $c($ink);
    $p->font('R', 10);
    $p->para($isOffer
        ? 'I, ' . $h['full_name'] . ', accept this offer of employment on the terms stated above and I will join on ' . $v['joining_date'] . '.'
        : 'I, ' . $h['full_name'] . ', have received this joining letter and I accept its terms.', $x, $w, $lh);
    $p->y += 28; $yl = $p->y;
    $p->text($x, $yl, 'Signature: ________________________'); $p->text($x, $yl, 'Date: ________________', 'R', $w);
    return $p->output();
}
