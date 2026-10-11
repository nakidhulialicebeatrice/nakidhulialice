# AI Support Statement and Prompt Log

**Important:** This is a template. Keep only prompts and results that genuinely reflect your work. Add dates, the actual output you tested, and corrections you personally made.

## AI support statement (adapt honestly)
Generative AI was used as a development assistant to help plan the two ERP modules, propose an initial relational schema, explain foreign keys and SQL views, and identify possible validation and setup issues. I reviewed the generated code, ran it in my local environment, and checked the results using sample records. AI output was treated as a draft rather than automatically assumed correct. The final statement must accurately describe the tools and assistance actually used.

## Prompt log template
| Date | Tool | Prompt actually used | Output used | My test/review and changes |
|---|---|---|---|---|
| [date] | ChatGPT | Help design two integrated ERP modules for a school using one MySQL database. | Initial module and data-flow plan | [describe your review] |
| [date] | ChatGPT | Propose MySQL tables for students, fee charges and payments with keys and constraints. | Draft schema | [run schema and record outcome] |
| [date] | ChatGPT | Explain how to calculate each student's balance from charges and payments without duplicating data. | SQL view approach | [test with sample transactions] |
| [date] | ChatGPT | Help troubleshoot this exact error: [paste error]. | [summary] | [what fixed it] |

## Critical evaluation notes to complete after testing
- Which generated suggestion worked as expected?
- What error or incomplete assumption did you find?
- What test did you run to detect it?
- What did you change yourself?
- What real-world ERP features remain missing?
