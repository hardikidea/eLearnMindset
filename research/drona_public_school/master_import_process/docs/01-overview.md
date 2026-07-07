# Overview

The master import process adds a school-facing Excel input layer above the existing CSV and Moodle CLI import engine.

## Why This Exists

School operators are comfortable maintaining structured data in Excel. Moodle imports are safer and more repeatable with CSV files. This process keeps both needs separate:

```text
Excel = human-maintained input
source CSV = generated normalized input
assembled CSV = Moodle-ready import files
Moodle database = final imported state
```

## What It Creates

The process can generate the same source folder structure used by the current school pack:

```text
master/
registration/
years/<academic-year>/
templates/
operations/
```

After that, the existing assembler and Moodle CLI importer continue the process.

## What It Does Not Do

- It does not make Moodle read Excel directly.
- It does not bypass CSV validation.
- It does not recreate users every academic year.
- It does not protect filled workbooks from being committed; operators must keep real PII out of Git.
