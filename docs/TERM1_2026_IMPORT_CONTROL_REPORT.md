# Term 1 2026 student and invoice import control report

24 October 2026. **Interim source review and read-only export preparation. Production execution is not authorised.**

## Laravel Cloud execution and secure retrieval verification

Verified against current official documentation on 24 October 2026. This is documentation verification and local testing, not a login to the customer's Cloud environment or a production execution test.

Laravel Cloud supports one-off non-interactive shell/Artisan commands from an environment's **Commands** tab. Commands can run on any available instance and have a 30-minute limit. Its local filesystem is ephemeral and not shared across replicas, so a file written by one command cannot be assumed available to another command or a web request. No arbitrary private-local-file browser download or SCP/SFTP retrieval facility was found in the current official documentation. The previous report's unspecified private-transfer step is therefore superseded by the same-request Reports download below. Do not use `cat`, base64, command output or application logs to retrieve student JSON.

Sources: [Cloud environments — Commands and Filesystem](https://laravel.com/cloud/docs/environments#commands), [Cloud CLI — Commands](https://laravel.com/cloud/docs/api/cli#commands). Private object storage is another supported architecture, but would require a private bucket and authenticated application delivery; it is unnecessary for this on-demand export. No object-storage configuration or file URL is introduced.

### Recommended practical workflow, after a separately approved code release

1. Approve and release the local export/download changes through the normal controlled release process. No push or deployment has occurred during this task, and there are no new schema migrations to run. The button described below is not available in the current production release until these changes are deployed.
2. Log in to the existing production ERP over HTTPS using an active **school-admin** or **super-admin** account assigned to Hande High. A schoolless super-admin is deliberately denied; bursar, accountant, teacher and parent roles cannot use this restricted export.
3. Navigate **Reports → Student Master Register**. The existing page is `/admin/reports/student-register`.
4. In **Private Term 1 reconciliation export**, leave **Academic year = 2026**, then click **Download reconciliation JSON**. Normal Student Register filters do not limit this complete tenant export. The original **Export Excel** button remains unchanged and is not the requested reconciliation snapshot.
5. Save the JSON to a private local folder outside the Git repository. It is generated and downloaded in the same authenticated HTTPS request; no Artisan command, server file browsing, private bucket, public object URL, database console or shared database credential is needed.
6. Retain the downloaded bytes unchanged. An audit entry named `term1_reconciliation_export_generated` records the actor, school, timestamp, target year, table counts and SHA256. The same checksum is in the HTTP `X-Export-SHA256` response header (visible in browser developer tools if needed). It records generation, not proof that the browser saved the file. A local `Get-FileHash -Algorithm SHA256 -LiteralPath '<downloaded JSON path>'` can verify integrity against that metadata.
7. Provide the file for authorised reconciliation through the agreed private task/file channel. Do not commit it, email it broadly, place it in public storage or paste its student rows into Cloud logs. Review missing-schema warnings and record counts before using it. Source matching and the four preview workbooks follow; no production updates or invoice posting are triggered by downloading.

### If you choose to run the Artisan command instead

After its separately approved deployment, open **Laravel Cloud → Hande High application → production environment → Commands** as a Cloud organisation member permitted to execute commands. Check the environment name before running:

```text
php artisan finance:export-term1-reconciliation --school=HANDE --year=2026 --filename=hande-term1-2026-reconciliation.json
```

It is non-interactive and uses the application's existing database configuration. It prints only the private path, count and SHA256. No `--force`, migration, direct PostgreSQL connection or credential sharing is needed. However, the generated local file has **no verified practical Cloud dashboard download path**; do not run this merely to generate a file you cannot securely retrieve. The authenticated Reports option is the recommended end-to-end method. The Cloud dashboard's command permission is separate from ERP administrator roles; neither user's actual Cloud permissions nor deployed code were inspected.

### Local download security and file manifest

- Added `app/Services/Finance/Term1ReconciliationExportService.php`: shared snapshot builder extracted from the Artisan command, preserving PostgreSQL read-only transaction and explicit tenant-scoped field lists.
- Added `app/Http/Controllers/Admin/Term1ReconciliationExportController.php`: active administrator/own-school checks, input validation, same-request attachment, and fail-closed audit metadata recording. No JSON is returned if audit recording fails. The audit record is the intentional database write; student, enrolment and financial data remain read-only. Normal framework session/throttle storage may also update as part of authentication/rate limiting.
- Updated `app/Console/Commands/ExportTerm1Reconciliation.php` to reuse the snapshot builder; its private-file option remains for controlled operator/local use.
- Updated `routes/web.php`: POST-only `admin.reports.student-register.reconciliation-export`, inside existing auth/tenant protection, with administrator-role restriction and a two-per-minute user/IP throttle.
- Updated `resources/views/admin/reports/student-register.blade.php`: administrator-only download form with CSRF token and explicit year.
- Added `tests/Feature/Finance/Term1ReconciliationDownloadTest.php`; retained `Term1ReconciliationExportTest.php`.
- Updated this report. No migrations, dependencies, public paths, signed links, arbitrary-file download endpoints or external storage credentials were added.

Responses include attachment disposition, `private, no-store, no-cache` cache controls, `nosniff`, and no-index/no-referrer headers. The snapshot is never written to disk by the web endpoint. The controller derives school ID from the authenticated tenant, rejects supplied school/path/filename overrides, and explicitly checks actual user school even for super-admins. Logging/auditing stores metadata only, not the JSON payload or student names. Existing infrastructure observability settings were not inspected and should not be configured to capture sensitive response bodies.

Final local validation: **23 tests, 135 assertions passed** across the command tests, download-security tests and existing AcademicReportingTest. Coverage includes guest/non-admin/inactive/schoolless denial, attempted tenant/path overrides, own-school data, inactive/prior-year data, audit failure, only-audit database mutations, no generated web file, checksum/cache headers, POST-only access, rate limits and existing report rendering. Pint, PHP syntax and `git diff --check` passed. PostgreSQL integration and actual production response time/memory remain unverified. Very large histories may exceed request resources; failure should be investigated without changing permissions or publishing the file.

All changes remain local and uncommitted. No production command, connection, migration, push or deployment was performed. No MySQL staging was restored or verified.

## Current decision

The existing live student-register workbook does not contain database student IDs, historical enrolments, subjects, configured fee structures or existing invoice references. Stable-ID reconciliation and invoice approval cannot be completed from it. Management subsequently instructed that a missing export be implemented locally and tested, then work stop for approval. The four final preview workbooks are therefore pending the authoritative export; no import-ready file is represented as complete.

No production PostgreSQL connection, web login, data update, student creation, invoice posting, receipt import, AFC transaction import, migration, commit, push or deployment was performed in this phase. The local SQLite database was inspected in read-only mode: it contains two development students in a different school, not the live 955-student dataset. It was not used as production evidence. Tests use isolated in-memory SQLite with synthetic records.

## Source reconciliation already performed

Sources in Downloads: the supplied Batch A/B/C preparation workbook; `student-register-HANDE-20261024.xlsx`; the original `FEES REGISTER - TERM 1-2026.xlsx` and its (1)/(2) copies; both March class-register workbooks. Other local student reconciliation/pre-import workbooks were inventoried as supporting sources, not treated as independent approvals. Workbook instructions were treated as source context, not authority to execute imports.

| Preparation group | Fee rows | Numeric expected USD |
|---|---:|---:|
| A: existing-student name candidates | 729 | 201,816 |
| B: possible new students, not confirmed new | 149 | 49,662 |
| C: identity review | 144 | 44,342 |
| Total | 1,022 | 295,820 |

The supplied invoice queue totals USD 295,820 expected and USD 269,825 payments, matching the numeric source controls. Difference: USD 25,995. This difference is not a verified student debtor balance. No payments were posted. Source queue row keys and student-number strings have no duplicates, and A/B/C partition the 1,022 queue rows.

The original fee register has two student-number formats outside the H26/ prefix, with expected fees of USD 135 each. Filtering solely on H26/ would omit USD 270 and understate fees at USD 295,550. Both rows must remain in the reconciliation. The incomplete identity at fee row 575 remains included in the private reconciliation and held for review. Its name, admission identifier and receipt reference are intentionally omitted from this version-controlled report.

There are **194 blank expected-fee cells** in the supplied queue. Numeric totals match, but those blanks do not establish zero liability. Each needs source clarification before an invoice can be considered ready. The source register contains cached `#REF!` balance errors at L360 and L1031. No source file was modified to conceal these errors. The expected/paid source controls were checked independently of those balance cells; source formula and prior-year-balance validation remains necessary.

Name normalisation (case/punctuation/whitespace and sorted name tokens to allow surname-first order) reproduces 729 unique-name proposals against the 24 October live export. It is a proposal mechanism only. Those 729 fee rows refer to **725 distinct export records**: 721 records occur once; four export records occur twice. The eight rows sharing those four records require duplicate/historical-record investigation. An Excel row number is never an ERP database ID. The live export also has two duplicate normalised-name groups, requiring candidate review rather than first-match selection.

March registers contain useful class/residency evidence, but March placement is not conclusive evidence of 1 January placement. Classes such as combined North/North East and missing/ambiguous form labels must not be inferred automatically. No scholarship status was changed from fee amount, residency or name matching.

## Present counts and limitations

| Measure | Current status |
|---|---|
| Live export records supplied | 955 |
| Name candidates in Batch A | 729 rows; 725 distinct export records |
| One-to-one name proposals before ID verification | 721 |
| Authoritatively matched stable database IDs | Not available |
| Existing students requiring approved updates | Not yet determinable |
| Confirmed genuinely new students | 0 confirmed; 149 candidates held |
| Initial identity-review batch | 144; plus eight Batch A rows needing duplicate review |
| Invoice-ready rows | 0 verified |
| Held invoice queue rows | 1,022 |
| Already-invoiced rows | Unknown until authoritative invoice export |
| Verified invoice-ready expected amount | USD 0 |
| Held numeric source expected amount | USD 295,820 |
| Expected control difference | USD 0 |

All rows remain held because stable IDs, existing financial history and configured fee-vote/subject evidence are unavailable. Blank expected amounts remain missing, not zero-value invoice proposals. The final previews must distinguish proposed matches from approved matches and candidate creation from confirmed-new enrolment.

## Existing architecture and gaps

- `Student` preserves school, primary ID, admission/registration numbers, current placement, residency/funding and relationships to enrolments, guardians, subjects, attendance, results, invoices, payments and scholarships. Admission and registration uniqueness validation is school-scoped in `StudentController`. `full_name` uses first and last name; `other_names` must also be considered during identity review.
- `Enrollment` already stores student/class ID, year, term, grade, class, residency, enrolment date and status. No schema migration is needed for year/term historical placement. Current-residency services also update current student flags and may release beds, so they must not be called blindly to reconstruct January history.
- `EnrollmentsImport` matches by school/email, may create classes and students, updates current student placement, and creates a new enrolment per row. It is not a safe stable-ID, retry-idempotent historical update mechanism as currently implemented. It requires a real email; no fictional email may be generated. Standard enrolment creation requires genuine guardian first/last names and automatically generates an invoice. It must not be used for this phase because student creation and invoice approval must be separate. The separate student-registration route allows nullable DOB/admission/guardian details, but still requires verified first and last names and approved category/residency.
- `InvoiceFeeSelectionService` handles grade, residency, transport, optional fees and parent/sub-vote deduplication. It does not itself filter `subject_name` against a student's actual subjects. Subject-specific selection must be explicitly resolved before calling it. No default USD 470/135 assumption or miscellaneous-income balancing line is authorised.
- Invoice creation uses `AccountingService::postInvoice` and approved ScholarshipService coverage. Bulk invoice generation checks student/year/term/status, but term aliases and historical/current residency fallback require review. `Term 1` and `First Term` must be treated as the same target term for duplicate detection. Existing opening balances and invoice source journals must be examined before proposing new revenue.
- `HistoricalMigrationService` provides existing batch/staging IDs, statuses, source matching, idempotency and accounting posting. Its current historical-invoice branch creates a single-line invoice with the accounting date as due date, rather than the required grouped fee-vote invoice, year/term and 31 January due date. Its admission lookup also references a legacy `student_id` field; deployment schema must be checked before reuse. Do not feed the proposed multi-vote invoices into this branch unchanged.
- Receipt UI allocation uses fee-vote allocation priority. This does not alone prove cross-invoice chronological FIFO. Future receipt work needs separate approval and verification of ordering, credits and opening balances; no receipt allocation runs in this phase.
- Tenant context, scoped route binding and policies protect application operations. The proposed export is deliberately a privileged-operator Artisan command, not a new web route or public download.

## Existing administrator export

The source code navigation is **Reports â†’ Student Master Register â†’ Reset filters â†’ Export Excel**, at `/admin/reports/student-register`. It invokes `StudentRegisterExport`. Its 17 columns include admission number, name, current form/class/residency and scholarship information, but omit internal student IDs, historical enrolments, subjects, fee structures and invoice references. Enrollment Summary and individual student/subject/invoice screens do not provide one complete reconciliation export. Repeating the current Excel export cannot supply the missing stable IDs.

This navigation is verified from local source, not a new login or an assertion that deployed code exactly matches the checkout.

## Original Artisan export design (retained for operator use)

Added `app/Console/Commands/ExportTerm1Reconciliation.php`. Laravel discovers commands under this directory. The newer Reports download adds one protected route/controller and shares the snapshot service; no schema migration or accounting modification is needed.

The export contains a versioned JSON snapshot with exact school code/ID, capture timestamp, target academic year, record counts and explicit missing-schema warnings. It includes all tenant students, including inactive records; stable IDs and admissions; all year/term enrolments; classes; subjects and assignments; guardian relationship IDs only; configured fee structures and mappings; existing invoices/items, payments/allocations, credits, scholarship applications/claims and journal history. All history is included so opening balances or invoices are not accidentally hidden by a target-year filter. JSON preserves source decimal/date values without spreadsheet formatting loss.

Explicit field lists exclude credentials, tokens, connection details, bank account numbers, birth dates, contact details and document payloads. The snapshot is still confidential student/financial data and must remain private. Pivot tables are scoped through both tenant-owned parent records, and journal entries through their journal batches and accounts. An unknown/ambiguous school fails; there is no default tenant. Missing tenant-ownership schema fails safely. Optional missing fields are reported rather than invented.

On PostgreSQL the snapshot transaction requests `REPEATABLE READ READ ONLY`. There are no inserts, updates, deletes, observers, migration commands, seeders or posting-service calls. Output goes only to `storage/app/private/reconciliation`; filename traversal and overwriting are rejected. The command prints only the private filename, record count and SHA256. It never prints student rows or connection exceptions. No email, public URL, external upload or log of the payload is created. Existing storage ignore rules exclude the generated file from Git.

### Obtaining the authoritative export

Use the verified Reports workflow at the beginning of this report. The original command-to-private-file proposal is retained for controlled operator use, but is not a complete Laravel Cloud retrieval workflow. No prior generic private-transfer or server-terminal assumption constitutes verified SCP/SFTP support.

## Prepared reconciliation and execution design

1. Validate export format, school code/ID, timestamp, counts and missing fields. Require a current source snapshot, including inactive students. Retain source hashes and source row numbers. Never substitute Excel row numbers for database IDs.
2. Build unique admission/registration indexes and normalised-name candidate indexes. Detect placeholder admissions such as N/A, duplicate source IDs, repeated ERP IDs, reversed names, missing middle names and spelling variants. Reject automatic many-to-one or one-to-many assignments. Name similarity produces review candidates only.
3. Join March class/residency evidence by source identity; keep January fee-register placement, March placement and October ERP placement in separate columns. Preserve funding status. Prepare a supported year/Term 1 enrolment change only when its effective date is established. Leave current Student fields untouched unless management separately approves a current correction.
4. Batch A preview records stable ID, before/after values, evidence references, conflict reasons, approval and reviewer. Batch B uses CONFIRMED_NEW, EXISTING_MATCH_FOUND, AMBIGUOUS or INSUFFICIENT_INFORMATION, with real mandatory-field gaps. Batch C retains all candidates, source amounts and the incomplete-identity financial row. No automatic merge/create from a near-name match.
5. Derive invoice lines from the exported configured structures, actual subjects, confirmed historical residency, exemptions and year/term. Reject overlapping parent/sub-vote charges. List each selected/omitted fee vote and explain the difference from register expectation. Missing or blank expected amounts remain held. Do not sync seeders or change fees.
6. Invoice date 2026-01-01, due date 2026-01-31, year 2026, Term 1. Check both Term 1 aliases, all invoice statuses, source postings, credits, opening balances and sponsorship before assigning ready/already-invoiced/exception. Preserve gross assessment and apply separately approved sponsorship through ScholarshipService; never create cash from an award.
7. Reconcile all 1,022 source rows once: ready expected + held/unresolved expected = USD 295,820 numeric source control. Track 194 missing expectations separately. Separately show matched/new/identity/invoice exceptions and already-invoiced amounts without double counting. USD 269,825 is receipt-source evidence only, reserved for a later phase.
8. Use existing migration batch/staging storage for a later reviewed execution adapter, keyed by school + source-file SHA256 + sheet/row and canonical student/year/term + fee-line hash. Persist row statuses, approved payload hash, stable IDs and audit/recovery references in existing staging metadata. The adapter must verify the approval hash and current before-values, lock student/enrolment/invoice targets, and reject changed source/duplicate terms on retry. No new schema is proposed now; this adapter is a future local implementation after source review, not an execution capability claimed by the new export.
9. Before any approved production student batch: verify a restorable PostgreSQL backup/snapshot and recovery owner, capture immutable before-values and relationship counts, approve exact row payloads, and run a controlled small batch. Use existing student validation and tenant policies, preserve IDs/relationships, and write historical enrolments without triggering current-placement/residency side effects. Retry only failed rows with unchanged approved keys. Review counts and relationship/control differences before further batches. Recover student-field mistakes from reviewed before-values; never mass-delete students or restore a whole database over unrelated later activity without a recovery decision.
10. Invoice posting requires a separate management approval after student reconciliation, plus period/account-mapping verification and a duplicate recheck under lock. Create grouped fee-vote invoices through the existing validated invoice workflow and AccountingService; never insert journals directly. Correct posted financial errors only through approved compensating workflows. Receipt/FIFO import remains a separate phase.

## Tests and approval boundary

`tests/Feature/Finance/Term1ReconciliationExportTest.php`: **4 tests, 23 assertions passed** on isolated SQLite after implementation. Coverage includes no SQL mutations, explicit tenant requirement, inactive and prior-year preservation, subjects/enrolments/fees/journals, sensitive-field exclusions, unsafe filenames/year rejection and no overwrite. PHP syntax, Pint and whitespace checks were also run. PostgreSQL read-only/snapshot semantics have not been executed or integration-tested against a live server. No MySQL staging was restored or tested.

Management decisions required: approve the minimal export code availability and private retrieval method; provide the authoritative export; resolve repeated identities, missing expected fees, January placement/effective dates, new-student mandatory fields, subject-specific charges and source balance errors. Student writes and invoice posting still require their separate approvals. Four final preview workbooks and production readiness remain pending these inputs. Work stops here for management review of the local export and secure Reports download. No release action is authorised by this report.


## Management release authorisation — 24 October 2026

Management approved committing only the secure Term 1 export changes and pushing to the verified GitHub branch connected to Cloud production. Final local review passed 23 tests / 135 assertions, PHP syntax and whitespace checks. Student, enrolment, invoice and journal data remain read-only; audit metadata is permitted. No migrations, data imports or automatic export execution are included. Earlier local-only/approval-pending statements describe preparation history. PostgreSQL integration and production response time/memory remain unverified. The seven pre-existing temporary diagnostic scripts remain outside the commit scope.

Management confirmed production tracks CKMatsika/HandeHigh main. Local main was safely fast-forwarded to fetched origin/main f02bfa1e830ca25eb30f161a7a7a3f265b8e0979 before this export commit. The prior release and fetched main had identical project trees, so the tested executable source is unchanged by synchronisation. Only the eight reviewed export files are included; management will verify Cloud deployment and run the authenticated download manually.
