# TukodTrack revision presentation notes

## Contents
1. Deployment status
2. Sales and inventory changes
3. Access and database backup
4. Presentation checklist

## 1. Deployment status
TukodTrack's pre-revision application is deployed to Render with Neon PostgreSQL. The revision pull request is **not deployed** until merged and deployed. Do not claim that untested features are live.

## 2. Sales and inventory changes
Demonstrate checkout confirmation, authorized void with reversal, stock receiving, inventory reporting and audit entries. Delivery receiving includes receipt reference, name of recipient/verifier and an acknowledgment timestamp. This is a recorded acknowledgement, **not** an uploaded signature or photograph.

## 3. Access and database backup
Owner can create additional role names based on cashier or stock access templates. These roles inherit the selected template's access, and cannot obtain owner rights through this form.
SQL backup export uses PostgreSQL pg_dump. In-app restoration of SQL is not yet supported; legacy JSON restore is distinct.

## 4. Presentation checklist
- Add a table of contents to the main thesis/document.
- Set pictures and captions to keep together; avoid splitting figures across pages.
- Include a schema-based ERD and confirm it matches migrations.
- Face the audience and use the projected system only to demonstrate flows.
- Test revised functionality before describing it as completed and deployed.
