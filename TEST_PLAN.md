# Manual Test Plan

Use fictional records only and write down your actual outcomes.

| Test | Action | Expected result |
|---|---|---|
| 1 | Register student `TEST-001` | Student appears in the list |
| 2 | Register `TEST-001` again | Duplicate rejected |
| 3 | Add Term 1 2026 charge of UGX 300,000 | Charge saved |
| 4 | Add payment receipt `RCPT-001` of UGX 100,000 | Payment saved; balance shows UGX 200,000 |
| 5 | Add same receipt number again | Duplicate rejected |
| 6 | Add payment with zero/negative amount | Validation rejects it |
| 7 | Search `TEST-001` | Matching student appears |
| 8 | Edit the student's class | Updated class appears |
| 9 | Try deleting a student with finance records | Database blocks deletion |
| 10 | Delete a payment through demo interface | Payment disappears and balance recalculates |

Record actual results, any error messages, and the fixes you made before using this as evidence in your submission.
