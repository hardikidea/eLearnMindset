# Registration Runbook

Students are stored by grade/division for school staff convenience.

Examples:

```text
registration/users/students/grade-05/division-a.csv
registration/users/students/grade-11/science/division-a.csv
```

Rules:

- Do not create a new student user every academic year.
- Keep username stable, for example `dps.stu.00001`.
- Use yearly cohort membership to move the student into the correct academic year, grade and division.
- Parent accounts live in `registration/users/parents/parents.csv`.
- Parent-child visibility is controlled by `registration/parent_links.csv`.
