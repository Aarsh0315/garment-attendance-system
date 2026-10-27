# Product Requirements Document (PRD)
## Garment Factory Attendance Management System

| Document Information | Details |
|---|---|
| Project Name | Garment Factory Attendance Management System |
| Document Type | Product Requirements Document |
| Version | 1.0 |
| Status | Initial Draft — Pending Stakeholder Review |
| Related Document | PROJECT_BRIEF.md |
| Target Users | HR Admin, Supervisor, Employee |
| Target Workforce | Approximately 50 employees |
| Application Type | Responsive Web Application |
| Backend | Laravel / PHP |
| Database | MySQL |
| Prepared By | Project Developer |
| Last Updated | 27 October 2026 |

---

## 1. Product Overview

The Garment Factory Attendance Management System is a web-based workforce attendance application for a garment factory with approximately 50 employees.

The product will allow HR administrators, supervisors, and employees to perform attendance-related tasks through a centralized website.

The system will provide employee management, department management, shift configuration, attendance recording, leave requests, attendance correction requests, overtime approvals, dashboards, and attendance reports.

The product's primary goal is to make attendance management more organized, reliable, traceable, and efficient.

The initial release will focus on one factory location and a browser-based application.

## 2. Product Goals

The product should:

1. Centralize employee and attendance information.
2. Reduce manual attendance recording and reporting effort.
3. Provide clear visibility into daily attendance.
4. Enable employees to access their own attendance history.
5. Allow supervisors to manage their assigned teams.
6. Allow HR to administer factory-wide attendance.
7. Support structured leave and correction approvals.
8. Maintain accurate attendance summaries.
9. Provide useful daily and monthly reports.
10. Protect employee information through appropriate access controls.

## 3. Target Users and Personas

### 3.1 HR Admin

**Primary need:** Manage employees and monitor attendance across the factory.

Typical activities:
- Create and update employee records.
- Configure departments and shifts.
- Review daily attendance.
- Approve leave and attendance corrections.
- Review overtime records.
- Generate monthly reports.

### 3.2 Supervisor

**Primary need:** Monitor attendance for an assigned group of employees.

Typical activities:
- View assigned employees.
- Record attendance where authorized.
- Identify absent employees and missing punches.
- Review attendance exceptions.
- Process requests according to assigned permissions.

### 3.3 Employee

**Primary need:** Record attendance and check personal attendance information.

Typical activities:
- Log in to the website.
- Check in and check out.
- View personal attendance history.
- Apply for leave.
- Request attendance corrections.
- Check the status of requests.

## 4. Product Scope

### 4.1 Included in the MVP

The Minimum Viable Product (MVP) will include:

1. Authentication and role-based access.
2. Employee management.
3. Department management.
4. Shift management.
5. Employee shift assignments.
6. Website-based attendance recording.
7. Daily attendance summaries.
8. Attendance exception handling.
9. Leave requests and approvals.
10. Attendance correction requests and approvals.
11. Overtime records and approval.
12. Role-specific dashboards.
13. Daily and monthly attendance reports.
14. CSV or spreadsheet-compatible exports.
15. Audit history for important changes.

### 4.2 Excluded from the MVP

Unless separately approved, the first release will not include:

- Biometric device integration.
- Facial recognition.
- Full payroll processing or salary disbursement.
- Native Android or iOS applications.
- Recruitment management.
- Production planning and garment manufacturing tracking.
- Inventory and accounting modules.
- Multi-factory administration.
- Automated messaging integrations.
- AI-based attendance predictions.

## 5. Functional Requirements

### 5.1 Authentication and Access Management

**Feature ID:** FEAT-001

The system must provide secure authentication and role-based access.

Requirements:

- Users can log in using their configured credentials.
- Users can log out securely.
- Protected pages require authentication.
- Each account has an assigned role.
- HR Admin has authorized factory-wide access.
- Supervisors can access only their assigned employees and permitted operations.
- Employees can access only their own attendance and requests.
- Unauthorized operations are rejected on the server.
- Inactive accounts cannot continue accessing the application.

**Acceptance criteria:**

- An unauthenticated visitor cannot open protected pages.
- An employee cannot access another employee's attendance by changing a URL or record ID.
- A supervisor cannot access an unrelated team's restricted records.
- Each role sees only the functions it is authorized to use.

### 5.2 Employee Management

**Feature ID:** FEAT-002

HR Admin can manage employee records.

Requirements:

- Create employee profiles.
- Assign unique employee codes.
- Store approved employee information.
- Assign departments, designations, supervisors, and shifts.
- View employee lists and profiles.
- Search and filter employees.
- Update employee information.
- Deactivate employees without deleting historical attendance.

**Acceptance criteria:**

- Employee codes must be unique.
- Required fields must be validated.
- Invalid employee data must not be saved.
- Deactivated employees must not be able to log in.
- Historical attendance remains available to authorized users after deactivation.

### 5.3 Department Management

**Feature ID:** FEAT-003

HR Admin can organize employees into factory departments.

Requirements:

- Create and update departments.
- View department details.
- Assign employees to departments.
- Assign supervisors to the appropriate teams.
- Prevent inappropriate deletion of departments with historical or active dependencies.

Potential departments include cutting, stitching, finishing, quality control, packing, and warehouse. The actual list will be confirmed by the factory.

**Acceptance criteria:**

- Employees can be associated with the appropriate department.
- Department filters return the correct employees.
- Supervisors cannot manage teams outside their authorized assignments.

### 5.4 Shift Management

**Feature ID:** FEAT-004

HR Admin can configure work shifts and employee assignments.

Requirements:

- Create and update shift definitions.
- Configure start and end times.
- Configure break durations.
- Assign shifts to employees.
- Define effective dates for shift assignments.
- Support weekly offs and holidays.
- Support overnight shifts where required.

**Acceptance criteria:**

- Shift start and end times are validated.
- Overnight shifts are handled correctly.
- Historical attendance uses the applicable shift assignment for that work date.
- Shift changes do not unintentionally rewrite historical attendance.

Exact shift rules and treatment of grace periods require stakeholder confirmation.

### 5.5 Attendance Recording

**Feature ID:** FEAT-005

Authorized users can record employee attendance through the website.

Requirements:

- Employees can check in and check out.
- Authorized supervisors can record attendance where permitted.
- Each punch records the relevant employee and timestamp.
- The system prevents duplicate or invalid punch sequences.
- The system generates daily attendance summaries.
- The system identifies missing punches and attendance exceptions.
- The system calculates recorded working duration according to approved shift and break rules.
- Attendance records can be reviewed by authorized users.
- Corrections follow the approved correction workflow.

**Acceptance criteria:**

- A valid check-in creates an attendance event.
- A check-out cannot be accepted as a valid completed shift when there is no valid open check-in, unless an approved exception workflow applies.
- Repeated clicks do not create unintended duplicate events.
- The daily summary reflects valid punch events.
- Invalid or incomplete attendance is flagged rather than silently treated as correct.
- Attendance calculations follow the configured rules.

### 5.6 Attendance Status and Exceptions

**Feature ID:** FEAT-006

The system must support daily attendance statuses and exceptions.

Potential statuses include:

- Present.
- Absent.
- Late.
- Half-day.
- On approved leave.
- Weekly off.
- Holiday.
- Incomplete attendance.
- Pending correction.

Final status definitions and combinations will be documented in the SRS.

Requirements:

- Attendance status is calculated using the approved rules.
- Authorized users can review exceptions.
- Corrections are auditable.
- The system distinguishes raw attendance events from the resulting daily status.

**Acceptance criteria:**

- An employee with incomplete punches is flagged.
- An approved leave request is reflected according to the factory's attendance policy.
- Status calculations do not overwrite the underlying punch history.
- Authorized corrections update the daily summary consistently.

### 5.7 Leave Management

**Feature ID:** FEAT-007

Employees can submit leave requests, and authorized users can process them.

Requirements:

- Display available leave types.
- Submit leave requests with dates and required information.
- View request status.
- Approve or reject requests according to permissions.
- Maintain a history of leave decisions.
- Calculate leave balances only when the factory's entitlement rules are configured.

**Acceptance criteria:**

- Invalid date ranges are rejected.
- Unauthorized users cannot approve their own requests unless an explicitly approved policy allows it.
- Only authorized users can approve or reject leave.
- The employee can see the decision and current status.
- Approved leave is reflected in attendance reporting according to the configured policy.

### 5.8 Attendance Correction Requests

**Feature ID:** FEAT-008

Employees can report missing or incorrect attendance events.

Requirements:

- Submit a correction request.
- Provide the affected date, requested correction, and reason.
- Allow authorized users to review the request.
- Approve or reject the request.
- Record the decision and relevant timestamps.
- Maintain an audit trail of the original and corrected values.

**Acceptance criteria:**

- Employees cannot directly edit approved attendance records.
- Unauthorized users cannot approve corrections.
- Approved changes are reflected in the daily summary.
- Rejected requests preserve the existing attendance data.
- The correction history identifies who made or approved the change.

### 5.9 Overtime Management

**Feature ID:** FEAT-009

The system will support overtime recording and approval.

Requirements:

- Identify potential overtime using configured shift rules.
- Allow authorized users to submit or review overtime entries.
- Track pending, approved, and rejected records.
- Store the relevant employee, date, duration, and approval information.
- Include approved overtime in authorized reports.

**Acceptance criteria:**

- Recorded extra time is not automatically treated as approved overtime.
- Unauthorized users cannot approve overtime.
- Overtime status is visible to the appropriate users.
- Reports distinguish pending and approved overtime.

### 5.10 Dashboards

**Feature ID:** FEAT-010

The system will display a dashboard appropriate to each role.

HR Admin dashboard:
- Active employee count.
- Present and absent counts.
- Late arrivals.
- Approved leave count.
- Pending approvals.
- Department-wise attendance.
- Report shortcuts.

Supervisor dashboard:
- Assigned employee count.
- Team attendance status.
- Missing punches.
- Attendance exceptions.
- Relevant pending requests.

Employee dashboard:
- Today's attendance status.
- Check-in and check-out actions.
- Recent attendance history.
- Leave request status.
- Correction request status.

**Acceptance criteria:**

- Dashboard figures use the applicable date and configured attendance rules.
- Supervisors see only authorized team information.
- Employees see only their personal information.
- Empty states and errors are displayed clearly.

### 5.11 Reports and Exports

**Feature ID:** FEAT-011

HR Admin and other authorized users can generate attendance reports.

Requirements:

- Daily attendance report.
- Monthly attendance report.
- Employee attendance history.
- Department-wise summary.
- Absence and late-arrival reports.
- Recorded working-hours report.
- Leave report.
- Approved overtime report.
- CSV or compatible spreadsheet export.

**Acceptance criteria:**

- Report filters produce the expected results.
- Exported data matches the authorized report.
- Users cannot export data outside their permissions.
- Calculations are consistent with attendance summaries.

### 5.12 Audit History

**Feature ID:** FEAT-012

The application will record important changes to sensitive records.

Requirements:

- Record the acting user.
- Record the action and affected record.
- Record the date and time of the change.
- Capture relevant old and new values for attendance corrections where appropriate.
- Restrict access to audit records.
- Prevent ordinary users from modifying the audit history.

**Acceptance criteria:**

- Important attendance corrections are traceable.
- Unauthorized users cannot access restricted audit records.
- Audit records are not silently changed during normal attendance operations.

## 6. User Journeys

### 6.1 Employee Attendance Journey

1. Employee opens the website.
2. Employee logs in.
3. Employee opens the attendance dashboard.
4. Employee selects Check In.
5. The system records the punch and confirms the result.
6. At the end of the shift, the employee selects Check Out.
7. The system records the punch and updates the daily summary.
8. Employee can review personal attendance history.

### 6.2 Supervisor Attendance Journey

1. Supervisor logs in.
2. Supervisor opens the assigned team dashboard.
3. Supervisor reviews employee attendance.
4. Supervisor identifies missing punches or exceptions.
5. Supervisor records attendance or submits a correction when authorized.
6. The system records the action and updates the relevant summary.

### 6.3 HR Monthly Reporting Journey

1. HR Admin logs in.
2. HR opens the attendance reports page.
3. HR selects the month and any applicable department filters.
4. The system generates the monthly attendance summary.
5. HR reviews exceptions and approved overtime.
6. HR exports the report for authorized business use.

## 7. User Interface Requirements

The interface should:

- Use a consistent design across all pages.
- Provide navigation appropriate to each role.
- Work on desktop and mobile browsers.
- Use readable text and clear form labels.
- Display validation errors beside the relevant inputs where practical.
- Display confirmation messages for completed actions.
- Provide loading, empty, and error states.
- Make check-in and check-out easy to find.
- Use accessible controls and adequate contrast.
- Require confirmation for important irreversible actions.

The final color palette, component library, and page layouts will be agreed upon during system design.

## 8. Non-Functional Product Requirements

### Security
- Secure authentication.
- Server-side authorization.
- Protection against common web vulnerabilities.
- Secure session management.
- No secrets committed to source control.

### Reliability
- Consistent attendance records.
- Validated attendance event sequences.
- Appropriate database constraints.
- Backup and recovery procedures.

### Performance
- Responsive operation for approximately 50 employees.
- Efficient database queries.
- Appropriate indexing for attendance searches and reports.

### Maintainability
- Standard Laravel conventions.
- Clear separation of application responsibilities.
- Automated tests for important workflows.
- Updated documentation when requirements change.

### Compatibility
- Responsive desktop and mobile browser support.
- Consistent timezone handling for attendance records.

## 9. MVP Priorities

| Priority | Features |
|---|---|
| P0 — Essential | Authentication, roles, employee management, shifts, attendance recording, authorization |
| P1 — Important | Attendance exceptions, corrections, leave management, dashboards, reports |
| P2 — Additional MVP capability | Overtime approvals, audit-history improvements, expanded exports |
| Future | Biometric devices, full payroll, multi-factory support, native mobile applications |

The final priorities and delivery phases must be confirmed with the stakeholders.

## 10. Success Metrics

The product will be evaluated using the following measures:

- All active employees have unique employee records.
- Authorized users can complete the attendance workflow.
- Attendance calculations pass agreed test scenarios.
- Unauthorized access tests fail safely.
- HR can generate the agreed daily and monthly reports.
- Important attendance changes are auditable.
- Factory representatives can complete user acceptance testing.
- The system reduces manual reporting effort compared with the current process.

Baseline values and quantitative targets will be agreed upon after reviewing the existing factory workflow.

## 11. Dependencies and Assumptions

Dependencies:
- Confirmed employee information.
- Approved department and supervisor assignments.
- Approved shift and break policies.
- Confirmed leave and overtime approval rules.
- Working Laravel and MySQL environment.
- Selected hosting and backup approach.

Assumptions:
- Approximately 50 employees.
- One factory location for the MVP.
- Website-based attendance.
- HR Admin, Supervisor, and Employee roles.
- No mandatory biometric hardware in the first release.

## 12. Open Product Decisions

The following decisions must be resolved before finalizing the SRS:

| ID | Decision | Impact |
|---|---|---|
| PD-01 | Employee self-service, supervisor recording, or both? | Attendance permissions |
| PD-02 | Attendance restricted to factory premises or allowed remotely? | Attendance verification |
| PD-03 | What are the official shift timings and break rules? | Working-hour calculations |
| PD-04 | What grace period and late-arrival policy apply? | Attendance status |
| PD-05 | Who approves leave, corrections, and overtime? | Approval workflows |
| PD-06 | What are the leave entitlements and holiday rules? | Leave calculations |
| PD-07 | Should employees use email or employee-code login? | Account setup |
| PD-08 | What are the reporting and export requirements? | Report design |

These decisions must be confirmed rather than assumed by the implementation agent.

## 13. Definition of Done

A feature is considered complete only when:

1. Its requirements and acceptance criteria are satisfied.
2. Server-side validation and authorization are implemented.
3. Automated tests for important scenarios pass.
4. Relevant error and empty states are handled.
5. The feature is tested in the browser.
6. No known critical defects remain.
7. Relevant documentation is updated.
8. The implementation summary identifies files changed and test results.

## 14. Related Documents

- `PROJECT_BRIEF.md` — Business context and project scope.
- `SRS.md` — Detailed system requirements and behaviour.
- `SYSTEM_DESIGN.md` — Architecture and database design.
- `IMPLEMENTATION_PLAN.md` — Development phases and tasks.
- `AGENTS.md` — AI coding-agent instructions.

---

*End of Product Requirements Document.*
