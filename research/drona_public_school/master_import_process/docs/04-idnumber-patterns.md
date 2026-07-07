# ID Number Patterns

ID validation reads the configured values from the generated CSV files. It does not hardcode a school, board, medium or division.

## Core Tokens

| Token | Source |
|---|---|
| `<TRUST_CODE>` | `master/school.csv` |
| `<SCHOOL_CODE>` | `master/school.csv` |
| `<BOARD_CODE>` | `master/boards.csv` |
| `<MEDIUM_CODE>` | `master/mediums.csv` |
| `<GRADE_CODE>` | `master/grades.csv` |
| `<STREAM_CODE>` | `master/streams.csv` |
| `<DIVISION_CODE>` | `master/divisions.csv` |
| `<SUBJECT_CODE>` | `master/subjects.csv` |
| `<START_YEAR>` | first year from academic year |
| `<END_YEAR>` | second year from academic year |
| `<YYYY_YYYY>` | academic year with dash replaced by underscore |
| `<YY>` | last two digits of start year |

## Patterns

| Object | Formula |
|---|---|
| Category | `<TRUST_CODE>_<BOARD_CODE>_<SCHOOL_CODE>_<YYYY_YYYY>_<MEDIUM_CODE>_<GRADE_CODE>_<STREAM_CODE>` |
| Course idnumber | `<SCHOOL_CODE>-<BOARD_CODE>-<MEDIUM_CODE>-<GRADE_CODE>-<STREAM_CODE>-<SUBJECT_CODE>-<START_YEAR>` |
| Course shortname | `<SCHOOL_CODE>-<BOARD_CODE>-<MEDIUM_CODE>-<GRADE_CODE>-<STREAM_CODE>-<SUBJECT_CODE>-<YY>` |
| Cohort | `<SCHOOL_CODE>-<START_YEAR>-<BOARD_CODE>-<MEDIUM_CODE>-<GRADE_CODE>-<STREAM_CODE>-<DIVISION_CODE>` |
| Group | `<COURSE_IDNUMBER>-<DIVISION_CODE>` |
| Student username | `<school_code_lower>.stu.<5_digit_sequence>` |
| Student idnumber | `<SCHOOL_CODE><YY>-<5_DIGIT_SEQUENCE>` |
| Student GR number | `GR-<SCHOOL_CODE>-<START_YEAR>-<5_DIGIT_SEQUENCE>` |
| Parent username | `<school_code_lower>.par.<5_digit_sequence>` |
| Parent idnumber | `<SCHOOL_CODE>-PAR-<5_DIGIT_SEQUENCE>` |
| Teacher username | `<school_code_lower>.tch.<medium_lower>.<subject_lower>` |
| Teacher idnumber | `<SCHOOL_CODE>-TCH-<3_DIGIT_SEQUENCE>` |

## Reference Conditions

| Field | Condition |
|---|---|
| `board_code` | Must exist in `03_boards`. |
| `medium_code` | Must exist in `04_mediums`. |
| `grade_code` | Must exist in `05_grades`. |
| `stream_code` | Must exist in `06_streams`. |
| `division_code` | Must exist in `07_divisions`. |
| `subject_code` | Must exist in `08_subjects` and the `09_subject_matrix` row for the same board, medium, grade and stream. |
| `category_code` | Must exist in `10_categories` before courses, cohorts or role assignments reference it. |
| `course_code` | Must exist in `12_courses` before groups, enrolments, assessments or certificates reference it. |
| `cohort_code` | Must exist in `14_cohorts` before cohort members or enrolments reference it. |
| `username` | Must exist in the relevant user CSV before parent links or role assignments reference it. |

## Validation Command

```bash
./scripts/validate_idnumber_patterns.py \
  --source output/source_csv \
  --year 2026-2027
```

The validator also checks that referenced courses, cohorts, groups, users, roles and certificates exist.
