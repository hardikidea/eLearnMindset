# Indian School Demo Course And Activity Blueprint

This package represents eLearn Mindset as a Moodle demo site for two Indian K-12 levels:

- Primary School, Classes 1 and 3, with activity-based learning.
- Higher Secondary School, Class 11 Science and Class 11 Commerce, with board and entrance-exam preparation.

The matching raw CSV imports are:

- [users.csv](moodle/demo-data/indian-school/users.csv)
- [categories.csv](moodle/demo-data/indian-school/categories.csv)

Seed the full demo package with:

```bash
make demo-data
```

| Category | Course Full Name | Course Short Name | Chapter/Topic Title | Recommended Moodle Activity/Resource Type | Pedagogical Purpose |
| --- | --- | --- | --- | --- | --- |
| Class 1 | Class 1 English | c1_eng | My Family | H5P Interactive Video | Activity-based vocabulary building through familiar Indian family contexts |
| Class 1 | Class 1 Mathematics | c1_maths | Numbers 1 to 100 | H5P Drag and Drop | Foundational numeracy using visual counting and playful practice |
| Class 1 | Class 1 EVS | c1_evs | Our Festivals | Page + H5P Memory Game | Holistic learning through Indian festivals, culture, and observation |
| Class 3 | Class 3 EVS | c3_evs | Food We Eat | Glossary + Forum | Activity-based learning for local food habits, healthy eating, and discussion |
| Class 3 | Class 3 EVS | c3_evs | Our Festivals | Assignment | Connect classroom learning with home traditions and reflective writing |
| Class 3 | Class 3 Mathematics | c3_maths | Multiplication Tables | Quiz | Practice-based fluency building with instant feedback |
| Class 3 | Class 3 Mathematics | c3_maths | Money and Bills | H5P Branching Scenario | Practical numeracy using rupees, paise, market bills, and daily-life examples |
| Class 11 Science | Class 11 Physics | c11_phy | Units and Measurements | Quiz | NCERT-aligned concept checking and numerical accuracy practice |
| Class 11 Science | Class 11 Physics | c11_phy | Electrostatics Foundation | Quiz + Question Bank | Rigorous board exam and JEE/NEET MCQ preparation |
| Class 11 Science | Class 11 Chemistry | c11_chem | Some Basic Concepts of Chemistry | Lesson | Structured mole concept learning with stepwise remediation |
| Class 11 Science | Class 11 Chemistry | c11_chem | Chemical Bonding | H5P Interactive Presentation | Visual conceptual learning for diagrams, structures, and bond types |
| Class 11 Science | Class 11 Mathematics | c11_maths | Sets and Relations | Assignment | NCERT exercise practice with teacher feedback |
| Class 11 Science | Class 11 Mathematics | c11_maths | Trigonometric Functions | Quiz | Board and entrance exam style problem-solving practice |
| Class 11 Commerce | Class 11 Accountancy | c11_acc | Introduction to Accounting | Page + Glossary | Build commerce vocabulary using standard accounting terms |
| Class 11 Commerce | Class 11 Accountancy | c11_acc | Journal Entries | Assignment | Ledger-writing practice aligned with CBSE/State Board formats |
| Class 11 Commerce | Class 11 Business Studies | c11_bst | Nature and Purpose of Business | Forum | Case-based discussion using Indian business examples |
| Class 11 Commerce | Class 11 Business Studies | c11_bst | Forms of Business Organisation | Quiz | Board exam readiness through objective and short-answer checks |
| Class 11 Commerce | Class 11 Economics | c11_eco | Consumer Equilibrium | Lesson + Quiz | Conceptual clarity with graphs, examples, and exam-style questions |
| Class 11 Commerce | Class 11 Economics | c11_eco | Collection of Data | Assignment | Practical data handling using school/community survey examples |
| Class 11 Commerce | Class 11 Accountancy | c11_acc | Partnership Accounts | Quiz + Assignment | Rigorous board exam preparation with structured numerical practice |
