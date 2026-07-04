# Aadhaar and Sensitive Student Data Guidelines

## Do not store full Aadhaar in Moodle baseline

Use only masked or last-four Aadhaar fields in Moodle:

- profile_field_aadhaar_last4
- profile_field_aadhaar_masked
- profile_field_aadhaar_consent
- profile_field_aadhaar_vault_ref

## Why

School Moodle contains many users and many roles. Full Aadhaar, medical, disability, caste/category, income and address data should be treated as sensitive. Keep visibility admin-only where possible and collect only fields that the school really needs.

## Recommended Moodle visibility

Sensitive fields should be:

- locked
- not visible publicly
- not displayed on signup
- visible only to administrators/authorized school staff

## Operational policy

- Collect consent where required.
- Do not deny learning access just because Aadhaar is not available.
- Keep full Aadhaar, if legally needed, outside Moodle in a secured compliant registry.
- Use masked Aadhaar in Moodle for reference only.
- Review data access for Trustee Manager, Principal, Teacher and Parent roles before production.
