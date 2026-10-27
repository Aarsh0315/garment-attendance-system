# AGENTS.md
## Coding Agent Instructions

You are helping develop a professional Garment Factory Attendance Management System using Laravel, PHP, and MySQL.

Follow these instructions whenever you inspect, plan, implement, test, or modify this project.

## 1. Read Project Documentation First

Before implementing a feature, read the relevant documents in `docs/`:

- `PROJECT_BRIEF.md` — project goals, scope, and constraints.
- `PRD.md` — product features and user requirements.
- `SRS.md` — functional and non-functional requirements.
- `SYSTEM_DESIGN.md` — architecture, data model, and security design.
- `IMPLEMENTATION_PLAN.md` — development phases and acceptance criteria.

If documentation is missing, contradictory, or unclear, explain the issue before making significant changes.

## 2. Project Goals

Build a secure, maintainable, browser-based attendance management system for one garment factory with approximately 50 employees.

The planned roles are:

- HR Admin
- Supervisor
- Employee

Implement only the features approved for the current development phase.

## 3. Technology and Architecture

- Use the Laravel version selected for this project.
- Follow Laravel conventions and the existing project structure.
- Use PHP and MySQL.
- Use Blade for server-rendered pages unless the approved architecture changes.
- Use the selected CSS framework consistently.
- Keep controllers focused on HTTP request handling.
- Use models, validation, services, policies, and middleware where appropriate.
- Avoid unnecessary packages, duplicate logic, and overcomplicated abstractions.
- Do not introduce a new framework or architecture without approval.

Inspect the existing environment and project before recommending installation commands or changing dependencies.

## 4. Coding Standards

- Write readable, maintainable code.
- Use descriptive names for classes, methods, variables, routes, and database fields.
- Follow PSR-12 where applicable and Laravel conventions.
- Validate user input on the server.
- Use Eloquent and parameterized database operations appropriately.
- Avoid duplicating business logic across controllers.
- Handle errors safely and show useful validation messages.
- Add comments only when they clarify non-obvious decisions.
- Keep changes focused on the requested task.

## 5. Security and Permissions

- Enforce authorization on the server, not only in the user interface.
- HR Admins may perform only the administrative actions granted to them.
- Supervisors must be restricted to their assigned teams and permitted actions.
- Employees must be restricted to their own permitted records.
- Never trust a user-supplied employee ID or role without authorization checks.
- Protect state-changing requests against CSRF and follow Laravel security practices.
- Store passwords using Laravel's supported secure password hashing.
- Do not expose passwords, tokens, secrets, or sensitive employee information in logs or error messages.
- Keep `.env` and other secret files out of version control.
- Never weaken authentication or permissions just to make a test pass.

## 6. Attendance Data Integrity

- Use trusted server-side timestamps for attendance events.
- Prevent accidental duplicate check-in and check-out submissions.
- Preserve original attendance punch events.
- Keep original events separate from calculated daily summaries.
- Do not silently overwrite historical attendance data.
- Record important corrections and administrative changes with the actor, timestamp, and reason where appropriate.
- Use database transactions when related changes must succeed or fail together.
- Handle missing punches and invalid sequences explicitly.
- Write tests for attendance calculations and edge cases.

Do not invent factory policies such as grace periods, overtime formulas, half-day rules, or leave entitlements. Ask for clarification or document the unresolved decision.

## 7. Database and Migrations

- Use Laravel migrations for schema changes.
- Use seeders only for intentional, documented initial data.
- Do not place real employee information or production secrets in test seeders.
- Review foreign keys, indexes, unique constraints, and deletion behavior.
- Avoid destructive schema changes without explicit approval.
- Never run database reset, drop, truncate, or destructive migration commands against a database unless the user explicitly approves the action and the target environment is confirmed.
- Preserve historical records when employees or other entities become inactive.

## 8. Testing Requirements

- Add or update automated tests for every important feature.
- Test both allowed and denied access for role-restricted actions.
- Test validation failures and important edge cases.
- Run relevant tests after making changes.
- Report the exact commands executed and whether they passed.
- Clearly state when tests could not be run and why.
- Never claim that code or tests work without evidence.

Do not remove or weaken existing tests merely to make the test suite pass.

## 9. Working Procedure

For each task:

1. Identify the current implementation phase.
2. Read the relevant project documents.
3. Inspect the existing code and related tests.
4. Explain the proposed changes briefly.
5. Implement only the requested scope.
6. Run appropriate tests and checks.
7. Summarize files changed, tests executed, results, and remaining issues.

If a task is large, break it into smaller steps.

Ask before making major architectural changes, introducing significant dependencies, changing business policies, or performing destructive actions.

## 10. File Management

- Inspect a file before editing it.
- Do not overwrite existing files blindly.
- Do not delete files or directories unless the change is approved.
- Do not create duplicate documentation or configuration files unnecessarily.
- Do not manually recreate Laravel-generated directories or files when Laravel commands can generate them safely.
- Keep documentation consistent with actual implementation.

## 11. User Communication

The user is learning Laravel and coding-agent workflows.

Therefore:

- Explain technical decisions in beginner-friendly language.
- Give exact commands and indicate where to run them.
- Explain what a command will do before running risky commands.
- Do not assume that the user understands unfamiliar Laravel terminology.
- Keep each development task small enough to review.
- Distinguish confirmed requirements from assumptions and recommendations.

## 12. Definition of Done

A task is complete only when:

- The requested behavior is implemented.
- Relevant requirements are satisfied.
- Server-side validation and authorization are included.
- Relevant automated tests have been run or their limitations explained.
- Existing functionality has not been unnecessarily changed.
- Documentation is updated when required.
- The final response summarizes the changes and any remaining decisions.
