# Additional thoughts for a better Indian school Moodle setup

## 1. Keep Moodle as LMS, not full ERP

Moodle should manage learning workflows:

- Course content
- Quizzes
- Assignments
- Grades
- Learning activity
- Parent visibility
- Student cohorts and groups
- Teachers and academic roles

Keep these in ERP/SIS or external modules unless you intentionally build integrations:

- Fees
- Payroll
- Transport billing
- Hostel management
- Inventory
- Library circulation
- Government compliance reports
- Full attendance biometric device integration

## 2. Use one stable hierarchy

Recommended:

```text
Trust / Organisation
└── Board
    └── School
        └── Medium
            └── Class / Std
                └── Stream
                    └── Subject Course
                        └── Division Group
```

Do not create a separate Moodle site per school unless there is a legal or operational need for hard separation.

## 3. Use academic-year records, not renamed courses

Do not rename old courses and cohorts to the new year. Create new academic-year records and promote students into next-year cohorts.

Good:

```text
GVS-2026-GSEB-GUJ-STD10-GEN-B
GVS-2027-GSEB-GUJ-STD11-SCI-A
```

Avoid:

```text
Rename 2026 courses to 2027 and lose historical clarity
```

## 4. Treat Aadhaar as sensitive

Do not store full Aadhaar in Moodle. Use masked Aadhaar, last four digits, consent status, or external vault reference only.

Recommended fields:

```text
aadhaar_last4
aadhaar_masked
aadhaar_consent
aadhaar_vault_ref
```

## 5. Make reports easier

Use consistent codes across all files:

```text
board_code
school_code
medium_code
grade_code
stream_code
division_code
academic_year
```

These make cohort, course, and reporting filters predictable.

## 6. Keep divisions as groups

Division should remain a group inside each course. This gives teacher-level separation without creating duplicate courses for every division.

Use group mode:

```text
Separate groups = strict division separation
Visible groups = teachers can compare divisions
```

## 7. Separate common subjects only when needed

For Std 11/12 common subjects such as English or Computer:

- Use separate courses by stream when teachers, tests, timetable, or reporting differ.
- Use one common course with stream-division groups only when the content and grading are exactly the same.

## 8. Use staging first

Never run the first import on production. Import into a staging clone, verify counts and permissions, then repeat on production.

## 9. Archive old courses

At year end:

- Keep old courses hidden or archived.
- Keep gradebook and submissions.
- Keep cohort membership until reports are confirmed.
- Only remove old cohort membership after final approval.

## 10. Add integrations later

After the baseline is stable, consider integrations in this order:

1. Authentication: Google Workspace, Microsoft Entra ID, LDAP, or SSO.
2. Notifications: Moodle messaging, email, SMS provider, WhatsApp bridge where legally allowed.
3. Attendance plugin.
4. Report builder dashboards.
5. SIS/ERP sync through Moodle web services.
6. Content import from DIKSHA/NCERT/GSSTB as linked resources with attribution.
