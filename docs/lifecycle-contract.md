# Resident lifecycle contract (2026-10-19)

All POST forms receive CI CSRF fields from existing hooks. HTML post/redirect/get, no JSON API. Admin session `gh_admin_logged_in`; resident session `gh_resident_case={id,verified_at}` expires after one hour. Resident 64-hex capability is stored SHA256 only, shown once via flash `resident_access_code`, never in URL. Files are private under master/application/private_lifecycle/{databasehash}, MIME validated PDF/JPG/PNG <=10 MiB, through authorized controller only.

## Routes and views

- GET/POST landing `/resident/start` -> `resident/start.php`: rooms,error,title. Intake fields `full_name,phone,room_id,planned_start,source=whatsapp|walk_in|website,consent=1`, required `ktp_file`, optional `marriage_file`. Creates case, logs in, redirects portal.
- GET/POST `/resident/access` -> `resident/access.php`: error,title. POST `access_code`.
- GET `/resident/portal` -> `resident/portal.php`: detail payload below; finalized inspections only. POST `/resident/action`: action and fields below. POST `/resident/logout`.
- GET `/resident/file/{id}`: same case only, finalized inspection files only.
- GET master `/lifecycle` -> lifecycle/index.php: cases,stays,metrics,filters,title,admin_name.
- POST master `/lifecycle/enroll`: stay_id plus mandatory ktp_file, optional marriage_file; creates a case for an existing active/reserved stay explicitly, preserves original occupant/stay and displays capability once via resident_access_code. Index payload enroll_stays lists eligible unlinked stays. Reserved enrollment also ensures draft contract and initial invoice.
- GET `/lifecycle/detail/{caseid}` -> lifecycle/detail.php: detail payload plus stays,title,admin_name.
- POST `/lifecycle/action/{caseid}` -> action handlers below, flash success/error, redirect detail.
- GET `/lifecycle/file/{id}` private admin file; GET `/lifecycle/print/{caseid}?stage=check_in|routine|check_out` -> lifecycle/print.php with detail payload and stage.

## Detail payload (flat view variables)

`case,inspections,files,checkout,refunds,history,inventory_items,payment_verified,deposit_balance,maintenance`; case has id,full_name,phone,source,room_id,room_label,room_code,planned_start,status,tenant_id,stay_id,payment_verified_at,profile_completed_at,profile (decoded),ktp_file_id,marriage_file_id. Inspections have id,stage,check_date,period,status,officer_name,notes,revision_of,resident_signed,manager_signed,supervisor_signed,rows,files,ocr_status,ocr (decoded). Rows have item_id,item_name,standard_qty,actual_qty,condition_status,notes,maintenance_ticket_id. Files have id,kind,original_name. Never display storage_name, access_hash, profile_json or server file paths.

`payment_summary` is NULL before agreement/reservation. Otherwise: stay_start,stay_end,stay_status,monthly_price,deposit_required,deposit_balance,invoice_id,invoice_period_id,invoice_period,invoice_amount,invoice_due,rent_paid,rent_outstanding,deposit_outstanding,verified_at. Rent paid is actual stored rent transactions for the initial invoice, never an uploaded claim; deposit balance is the actual ledger. No payment account is invented.

Checkout: notice_date,planned_exit,late_notice,actual_date,refund_due,deposit_snapshot,deduction_amount,deduction_reason,disputed_amount,refundable_amount,paid_amount,refund_bank,refund_account,status. Refund H+2 from actual exit calendar date, undisputed available even while disputed portion outstanding. No automatic late-notice penalty.

## POST actions

- `reserve`: end_date,monthly_price,deposit. Creates tenant/reserved stay/draft contract and initial rent invoice. KTP inherited from intake.
- `link_stay`: stay_id; existing reserved same room+phone only. Generates initial rent invoice if absent.
- `verify_payment`: no extra fields. Requires existing real rent payments >= stay monthly price; uploaded claimed proof alone insufficient.
- `payment_proof` (resident allowed): mandatory private payment_file PDF/JPG/PNG <=10 MiB; optional invoice_id validated against own linked stay. Allowed while linked stay reserved/active, including paid/notice case stage, never after checkout. Stores file kind rent_proof, accessible to own resident/admin/finance; does not write payments, deposit ledger or verification flags. Admin must record/verify actual payment through existing payment module.
- `full_profile` (resident allowed): identity_number (16 digits),address,birth_date (not future),gender male/female,occupation,emergency_name,emergency_phone,emergency_relation,emergency_address,consent=1 required; birth_place,religion,marital_status,email,company,vehicle_type,vehicle_plate optional. Existing optional fields are preserved when omitted. Requires verified actual rent. Locks submitted profile.
- `inspection_save`: stage/check_date/period YYYY-MM/officer_name/notes, optional inspection_id draft or revision_of final, scan_file required for new inspections; photo_file optional. `items[ID][condition_status]` uninspected/good/needs_repair/damaged/missing/not_available; items[ID][actual_qty],items[ID][notes]. Final immutable; new revision needs reason.
- `inspection_extract`: inspection_id draft; calls real local OCR helper; suggestions only, admin must verify rows.
- `inspection_finalize`: inspection_id,resident_signed=1,manager_signed=1,supervisor_signed=1 (routine). Rejects uninspected rows/missing scan/signatures.
- `activate` (alias `check_in`): contract_signed=1,inspection_confirmed=1. Requires complete profile+KTP+verified actual rent+final check_in inspection+deposit. Activates existing draft contract. No deposit deferral for lifecycle.
- `maintenance`: inspection_id,item_id,issue_title -> ticket from damaged/needs_repair/missing item, no duplicate.
- `notice` (resident allowed): planned_exit,reason,refund_bank,refund_account; active only, one notice. H-15 calendar late flag.
- `notice_update` (admin only): planned_exit,reason,refund_bank,refund_account,mandatory revision_reason. Only before actual checkout; preserves original notice_date, recalculates planned-date compliance, audits old/new dates and revision reason. Resident direct invocation and post-checkout changes are rejected; no automatic penalty.
- `checkout`: actual_date,keys_returned=1,deduction_amount,deduction_reason,disputed_amount,refund_bank,refund_account; active, final same-day check_out inspection, zero arrears, closes stay and creates refund obligation. No fake transfer/ledger refund.
- `refund`: amount,transfer_date,reference_no,proof_file required; bounded unpaid undisputed balance, bank/account required, duplicate reference rejected, actual date no future. Inserts one ledger entry.
- `resolve_dispute`: released_amount,resolution_note; adds released portion to refund obligation, agreed remainder deduction. Does not erase original evidence/history.

## OCR

`proc_open([$python,$root.'/scripts/lifecycle-ocr.py','--input',$privateScanPath])`; python configured GULHOUSE_OCR_PYTHON or bundled runtime. JSON stdout `{ok,engine,requires_review,pages,text,suggested_fields,items,warnings}`. No external inference or paid API; unreadable documents retain manual workflow. OCR never finalizes or assigns authoritative conditions automatically.

## Facility source fidelity

`inventory_items` is the dedicated `gh_lifecycle_facility_catalog`: 19 items matching signed source PDFs, Bantal quantity 2 and other items quantity 1. The old gh_inventory_items catalogue is untouched. Finalization requires all current catalogue items, actual quantities, observed conditions, scan, and human signature checks. Current-month coverage includes existing active stays not yet enrolled as not_enrolled; they cannot disappear from the denominator.
