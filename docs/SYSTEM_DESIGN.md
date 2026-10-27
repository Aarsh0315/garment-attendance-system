# System Design Document
## Garment Factory Attendance Management System

| Document Information | Details |
|---|---|
| Project Name | Garment Factory Attendance Management System |
| Document Type | System Design |
| Version | 1.0 |
| Status | Initial Draft — Pending Technical Review |
| Related Documents | PROJECT_BRIEF.md, PRD.md, SRS.md |
| Backend | Laravel / PHP |
| Database | MySQL |
| Frontend | Laravel Blade with a responsive CSS framework |
| Target Workforce | Approximately 50 employees |
| Architecture | Modular Laravel Monolith |
| Prepared By | Project Developer |
| Last Updated | 27 October 2026 |

---

## 1. Purpose

This document defines the proposed technical architecture, application structure, database entities, relationships, security boundaries, and implementation conventions for the Garment Factory Attendance Management System.

The design is intended to provide a maintainable, secure, and testable foundation for a web application used by HR Admins, Supervisors, and Employees.

The design must remain consistent with the approved PRD and SRS. Unconfirmed business policies must not be implemented as fixed assumptions.

## 2. Architecture Overview

### 2.1 Proposed Architecture

The application will use a modular monolithic architecture.

The application will be deployed as one Laravel application backed by a MySQL database. Business functionality will be organized into clear modules instead of separate microservices.

This approach is appropriate for the initial workforce of approximately 50 employees and avoids unnecessary infrastructure complexity.

### 2.2 High-Level Architecture

Browser
- HR Admin dashboard
- Supervisor dashboard
- Employee dashboard

↓

Laravel application
- Routes
- Authentication and authorization
- Controllers
- Form Requests and validation
- Policies and middleware
- Business services
- Eloquent models
- Reporting and attendance calculations

↓

MySQL database
- User accounts and roles
- Employees and departments
- Shifts and assignments
- Attendance events and summaries
- Leave, corrections, and overtime
- Audit history

### 2.3 Request Lifecycle

1. A user opens a page or submits a form in the browser.
2. Laravel receives the HTTP request.
3. Authentication middleware verifies the user's session.
4. Authorization policies verify access to the requested operation and record.
5. Validation checks the submitted data.
6. The controller coordinates the requested action.
7. Business services apply attendance or approval rules where appropriate.
8. Eloquent models and database transactions persist the changes.
9. Laravel returns the response or redirects the user with a suitable message.

Sensitive business rules must be enforced on the server, not only in the frontend.

## 3. Technology Stack

| Component | Proposed Choice |
|---|---|
| Programming language | PHP |
| Backend framework | Laravel |
| Database | MySQL |
| Frontend rendering | Laravel Blade |
| CSS framework | Bootstrap or Tailwind CSS |
| Client-side scripting | JavaScript where needed |
| ORM | Laravel Eloquent |
| Schema management | Laravel migrations |
| Authentication | A maintained Laravel-compatible solution |
| Authorization | Policies, gates, middleware, and role checks |
| Testing | PHPUnit or Pest, depending on the selected setup |
| Version control | Git |
| Development environment | Windows with compatible PHP and Composer |
| Production transport | HTTPS |

The exact Laravel version, authentication package, CSS framework, and test framework must be selected after inspecting the installed environment.

Do not introduce additional frameworks or packages without a clear requirement.

## 4. Application Module Design

### 4.1 Authentication and Authorization

Responsibilities:
- Login and logout.
- Account status checks.
- Role and permission enforcement.
- Route protection.
- Record-level access restrictions.

Primary entities:
- Users.
- Roles and permissions, if implemented as separate tables.

### 4.2 Employee Management

Responsibilities:
- Employee profiles.
- Employee codes.
- Department assignments.
- Supervisor assignments.
- Employment status.
- Employee search and filtering.

Primary entities:
- Employees.
- Departments.
- Users.

### 4.3 Shift Management

Responsibilities:
- Shift definitions.
- Start and end times.
- Break configuration.
- Shift assignments and effective dates.
- Weekly offs and holiday rules.

Primary entities:
- Shifts.
- Employee shift assignments.
- Holidays.

### 4.4 Attendance Management

Responsibilities:
- Check-in and check-out.
- Attendance event validation.
- Daily attendance summaries.
- Missing-punch detection.
- Recorded working-duration calculations.
- Attendance exceptions.

Primary entities:
- Attendance punches.
- Daily attendance records.
- Shift assignments.

### 4.5 Leave Management

Responsibilities:
- Leave types.
- Leave requests.
- Approval and rejection.
- Leave history and balances where applicable.

Primary entities:
- Leave types.
- Leave requests.

### 4.6 Attendance Corrections

Responsibilities:
- Correction requests.
- Review and approval.
- Preservation of original event history.
- Recalculation of affected daily summaries.
- Audit history.

Primary entities:
- Attendance adjustment requests.
- Attendance punches.
- Daily attendance records.
- Audit logs.

### 4.7 Overtime Management

Responsibilities:
- Potential overtime identification.
- Overtime submissions.
- Approval or rejection.
- Approved overtime reporting.

Primary entities:
- Overtime records.
- Attendance records.
- Shift assignments.

### 4.8 Dashboards and Reports

Responsibilities:
- Daily attendance statistics.
- Employee and department filters.
- Monthly reports.
- Leave and overtime summaries.
- Authorized CSV exports.

Reporting logic should be reusable and should enforce the same authorization rules as the underlying records.

## 5. Proposed Project Directory Structure

The following is the target structure for the Laravel application. Laravel will generate its standard directories during project initialization.

garment-attendance-system/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/
│   │   │   ├── Supervisor/
│   │   │   └── Employee/
│   │   ├── Middleware/
│   │   └── Requests/
│   ├── Models/
│   ├── Policies/
│   ├── Services/
│   └── Support/
├── bootstrap/
├── config/
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
├── docs/
│   ├── PROJECT_BRIEF.md
│   ├── PRD.md
│   ├── SRS.md
│   ├── SYSTEM_DESIGN.md
│   └── IMPLEMENTATION_PLAN.md
├── public/
├── resources/
│   ├── css/
│   ├── js/
│   └── views/
│       ├── layouts/
│       ├── components/
│       ├── admin/
│       ├── supervisor/
│       ├── employee/
│       ├── attendance/
│       ├── leave/
│       └── reports/
├── routes/
├── storage/
├── tests/
│   ├── Feature/
│   └── Unit/
├── AGENTS.md
├── .env.example
├── .gitignore
├── composer.json
├── package.json
└── README.md

This is a target structure, not a list of directories that should all be created manually.

Use Laravel's default structure wherever possible. Add custom folders only when the corresponding feature needs them. Do not create empty architecture folders just to match this diagram.

## 6. Database Design

The following entities are proposed. Their exact columns, data types, foreign keys, indexes, and deletion rules will be finalized during implementation planning and migration design.

### 6.1 Users

Purpose: Store authentication accounts.

Proposed fields:
- id
- name
- email or configured login identifier
- password
- role reference or role assignment
- is_active
- timestamps

Password values must be securely hashed.

Login identifiers must be unique according to the selected authentication design.

### 6.2 Roles and Permissions

Purpose: Define access responsibilities.

Possible roles:
- HR Admin.
- Supervisor.
- Employee.

The implementation may use a simple role field or normalized role and permission tables, depending on the final authorization requirements.

Record-level restrictions must be enforced regardless of the role storage approach.

### 6.3 Employees

Purpose: Store employment and organizational information.

Proposed fields:
- id
- user_id, where an employee has a login account
- employee_code
- department_id
- supervisor reference, where applicable
- designation
- joining_date
- employment_status
- timestamps

Contact and other personal information should be collected only when needed.

### 6.4 Departments

Purpose: Organize employees.

Proposed fields:
- id
- name
- description, if needed
- status
- timestamps

Department names or codes must follow the selected uniqueness rules.

### 6.5 Shifts

Purpose: Define work schedules.

Proposed fields:
- id
- name
- start_time
- end_time
- break_duration
- configuration fields for applicable policies
- status
- timestamps

Overnight shifts must be represented and calculated correctly.

### 6.6 Employee Shift Assignments

Purpose: Preserve the shifts applicable to employees over time.

Proposed fields:
- id
- employee_id
- shift_id
- effective_from
- effective_to, where applicable
- timestamps

The system must use the correct assignment for the attendance work date.

### 6.7 Attendance Punches

Purpose: Store individual attendance events.

Proposed fields:
- id
- employee_id
- punched_at
- punch_type
- source
- recorded_by, where applicable
- timestamps

The design must prevent invalid event sequences and unintended duplicates.

Raw punch history should not be replaced by a daily summary.

### 6.8 Daily Attendance Records

Purpose: Store calculated attendance information for each employee and work date.

Proposed fields:
- id
- employee_id
- work_date
- status or approved status flags
- first_check_in, where applicable
- last_check_out, where applicable
- recorded_work_duration
- exception indicators
- calculation or approval metadata where required
- timestamps

A suitable database constraint should prevent unintended duplicate summaries for the same employee and work date.

The summary must remain traceable to its underlying attendance events.

### 6.9 Leave Types and Leave Requests

Purpose: Manage leave definitions and employee requests.

Leave type fields may include:
- id
- name
- paid_or_unpaid classification, where applicable
- status

Leave request fields may include:
- id
- employee_id
- leave_type_id
- start_date
- end_date
- reason, where appropriate
- status
- approved_by, where applicable
- decision_timestamp
- timestamps

Leave entitlements and balance calculations must follow confirmed policy rules.

### 6.10 Attendance Adjustment Requests

Purpose: Track proposed changes to attendance.

Proposed fields:
- id
- employee_id
- attendance_record_id, where applicable
- affected_work_date
- requested_change
- reason
- status
- reviewed_by
- decision_timestamp
- timestamps

Approved changes must preserve the relevant original values and audit history.

### 6.11 Overtime Records

Purpose: Record potential and approved overtime.

Proposed fields:
- id
- employee_id
- attendance_record_id, where applicable
- work_date
- duration
- status
- submitted_by
- approved_by
- decision_timestamp
- timestamps

The overtime calculation and approval policy must be confirmed before implementation.

### 6.12 Holidays

Purpose: Store applicable factory holidays.

Proposed fields:
- id
- holiday_date
- name
- applicable scope, if required
- timestamps

The system must avoid treating every calendar day as a working day without considering the configured schedule.

### 6.13 Audit Logs

Purpose: Preserve a history of important actions.

Proposed fields:
- id
- actor_user_id
- action
- entity_type
- entity_id
- relevant change metadata
- created_at

Audit metadata must avoid unnecessary sensitive information. Ordinary users must not be able to modify audit history.

## 7. High-Level Entity Relationships

- A user account may be associated with an employee profile.
- A department may contain multiple employees.
- A supervisor may be assigned to multiple employees.
- An employee may have multiple shift assignments over time.
- A shift may be assigned to multiple employees.
- An employee may have many attendance punches.
- An employee may have one daily attendance summary per work date.
- An employee may submit multiple leave requests.
- An employee may submit multiple attendance correction requests.
- An employee may have multiple overtime records.
- An authorized user may approve many requests.
- An actor may generate many audit log entries.

All relationships and deletion rules must be enforced using appropriate foreign keys and application-level authorization.

## 8. Attendance Processing Design

### 8.1 Check-In

1. Authenticate the user.
2. Resolve the employee identity from the authenticated account.
3. Check whether the account is active and authorized to record attendance.
4. Determine the applicable shift and work-date rules.
5. Validate whether a new check-in is permitted.
6. Record a server-generated timestamp.
7. Persist the event safely.
8. Update or recalculate the daily summary as appropriate.
9. Return a clear confirmation.

### 8.2 Check-Out

1. Authenticate and authorize the user.
2. Identify the relevant open attendance session.
3. Reject or flag invalid sequences.
4. Record the server-generated check-out timestamp.
5. Recalculate the applicable attendance summary.
6. Apply the configured break and shift rules.
7. Identify incomplete attendance or potential overtime.
8. Return a clear result.

### 8.3 Attendance Corrections

1. An employee or authorized user submits a correction request.
2. The system records the proposed change and reason.
3. An authorized approver reviews the request.
4. If approved, the change is applied through a controlled operation.
5. The system preserves the original event history.
6. The daily summary is recalculated when appropriate.
7. The action is recorded in the audit history.

### 8.4 Reliability

Attendance operations should use appropriate database transactions, validation, and duplicate-request protection.

The implementation must not silently discard events when summary generation fails. Failure handling and reconciliation should be tested.

## 9. Security Design

### 9.1 Authentication

- Use a maintained Laravel-compatible authentication solution.
- Store passwords using secure hashing.
- Protect authenticated sessions.
- Reject inactive accounts.

### 9.2 Authorization

- Enforce access through server-side policies, gates, middleware, or equivalent mechanisms.
- Validate record ownership and supervisor assignments.
- Do not rely only on hiding buttons or pages in the frontend.
- Protect report exports and download endpoints.

### 9.3 Input and Request Security

- Validate all inputs on the server.
- Use CSRF protection for applicable web forms.
- Use parameterized database access through Laravel's database layer.
- Escape untrusted output appropriately.
- Apply request throttling where appropriate, especially to authentication endpoints.

### 9.4 Secrets and Configuration

- Store environment-specific secrets outside source-controlled code.
- Exclude .env from Git.
- Provide a safe .env.example.
- Do not expose production credentials in logs or error pages.

### 9.5 Audit and Privacy

- Restrict access to audit records.
- Record important attendance changes.
- Collect only required employee data.
- Establish retention and deletion rules before production use.

## 10. Validation and Error Handling

The application should:

- Validate required fields and supported values.
- Reject invalid attendance sequences.
- Reject unauthorized operations.
- Prevent duplicate employee codes and daily summaries.
- Use transactions for operations requiring consistency.
- Return appropriate validation, authorization, and not-found responses.
- Avoid exposing database internals or secrets in user-facing errors.
- Log unexpected failures securely.
- Preserve sufficient information for authorized troubleshooting.

## 11. Testing Strategy

### Unit Tests

Test:
- Working-duration calculations.
- Break deductions.
- Overnight shift calculations.
- Late-arrival rules.
- Attendance status calculations.
- Overtime calculations under approved policies.

### Feature Tests

Test:
- Authentication.
- Role-based access.
- Employee creation and updates.
- Check-in and check-out.
- Duplicate punch protection.
- Leave submissions and approvals.
- Attendance correction workflows.
- Overtime approvals.
- Report filtering and export authorization.

### Integration and Data Integrity Tests

Test:
- Database constraints.
- Foreign-key relationships.
- Transactions.
- Summary recalculation.
- Audit logging.
- Failure handling.

### User Acceptance Testing

Factory representatives should test realistic workflows with sample data before the system is used operationally.

## 12. Deployment and Operations

The production environment should include:

- A supported PHP and Laravel configuration.
- A supported MySQL version.
- HTTPS.
- Secure environment variables.
- Database backups.
- Tested recovery procedures.
- Appropriate application logging.
- Controlled deployment and database migration procedures.
- A documented rollback or recovery approach.

Production deployment must be separate from local development and test environments.

## 13. Technical Decisions and Constraints

1. Use a Laravel monolith for the initial release.
2. Use MySQL for persistent application data.
3. Use Blade for server-rendered pages unless a specific requirement justifies another approach.
4. Use Laravel migrations to manage schema changes.
5. Keep raw attendance events separate from calculated summaries.
6. Enforce permissions on the server.
7. Preserve historical attendance and shift assignments.
8. Avoid unnecessary packages and infrastructure.
9. Do not implement unconfirmed business policies as hardcoded rules.
10. Do not introduce biometric or external integrations into the MVP without approval.

## 14. Open Technical Questions

- Which Laravel version is compatible with the installed environment?
- Which authentication starter kit or solution should be used?
- Should roles be represented by a role column or separate role and permission tables?
- Which CSS framework will be used?
- Which test framework is already installed or preferred?
- What timezone and overnight work-date rules should be configured?
- What hosting and backup solution will be used?

These decisions should be finalized before the corresponding implementation tasks.

## 15. Related Documents

- PROJECT_BRIEF.md
- PRD.md
- SRS.md
- IMPLEMENTATION_PLAN.md
- AGENTS.md

## 16. Document Status

**Status:** Initial Draft — Pending Technical Review

The architecture and entity definitions are proposed designs. They must be reconciled with the approved requirements and actual environment before database migrations and application features are implemented.

---

*End of System Design Document.*
