# Software Requirements Specification (SRS)
## Garment Factory Attendance Management System

| Document Information | Details |
|---|---|
| Project Name | Garment Factory Attendance Management System |
| Document Type | Software Requirements Specification |
| Version | 1.0 |
| Status | Initial Draft — Pending Stakeholder Review |
| Related Documents | PROJECT_BRIEF.md, PRD.md |
| Target Workforce | Approximately 50 employees |
| Application Type | Responsive Web Application |
| Backend | Laravel / PHP |
| Database | MySQL |
| Prepared By | Project Developer |
| Last Updated | 27 October 2026 |

---

## 1. Introduction

### 1.1 Purpose

This document defines the functional and non-functional requirements of the Garment Factory Attendance Management System.

It provides implementation guidance for developers and AI coding agents and establishes a reference for testing and stakeholder acceptance.

### 1.2 Scope

The system will manage employee information, departments, shifts, attendance events, daily attendance summaries, leave requests, attendance corrections, overtime approvals, dashboards, and reports.

The initial release will serve one factory location with approximately 50 employees.

### 1.3 Intended Audience

- Factory owner and management.
- HR administrators.
- Supervisors.
- Employees.
- Developers.
- QA testers.
- AI coding agents.

### 1.4 Definitions

| Term | Definition |
|---|---|
| Employee | A person whose employment and attendance are recorded in the system. |
| HR Admin | An authorized user who administers factory-wide HR and attendance information. |
| Supervisor | An authorized user who manages attendance for assigned employees. |
| Punch | A timestamped attendance event, such as check-in or check-out. |
| Daily Attendance Summary | A calculated summary of an employee's attendance for a work date. |
| Shift | A configured work schedule with start and end times and applicable breaks. |
| Work Date | The date to which an attendance summary belongs under the configured shift rules. |
| Attendance Exception | An attendance condition requiring review, such as a missing punch. |
| Correction Request | A request to change or correct an attendance record. |
| Overtime | Work time classified as overtime under approved factory rules. |
| Audit Log | A record of an important action performed in the application. |

## 2. System Overview

### 2.1 Product Perspective

The application will be a Laravel-based web application using MySQL for persistent storage.

The system will use a browser-based interface and a server-side backend for business rules, authentication, authorization, validation, and database operations.

### 2.2 User Classes

#### HR Admin
Can manage employees, departments, shifts, attendance policies, approvals, and authorized reports.

#### Supervisor
Can access assigned teams and perform attendance-related actions allowed by their permissions.

#### Employee
Can access personal attendance information, record attendance if enabled, submit leave requests, and request attendance corrections.

### 2.3 Operating Environment

The system is expected to run in:

- A compatible PHP and Laravel environment.
- A MySQL database environment.
- A supported desktop or mobile browser.
- A secure production hosting environment.

The exact software versions and hosting configuration will be selected during technical design.

## 3. Functional Requirements

### 3.1 Authentication

**Requirement IDs: FR-AUTH-001 to FR-AUTH-006**

- FR-AUTH-001: The system shall allow registered, active users to log in.
- FR-AUTH-002: The system shall allow authenticated users to log out.
- FR-AUTH-003: Protected routes shall require authentication.
- FR-AUTH-004: Passwords shall be stored using secure password hashing.
- FR-AUTH-005: Inactive accounts shall be denied access.
- FR-AUTH-006: Authentication failures shall return an appropriate message without exposing sensitive system details.

### 3.2 Authorization

**Requirement IDs: FR-AUTHZ-001 to FR-AUTHZ-005**

- FR-AUTHZ-001: The system shall enforce permissions on the server.
- FR-AUTHZ-002: HR Admin shall access factory-wide records within authorized administrative permissions.
- FR-AUTHZ-003: Supervisors shall access only employees and operations assigned to them.
- FR-AUTHZ-004: Employees shall access only their own personal records and permitted actions.
- FR-AUTHZ-005: Unauthorized requests shall be rejected even if the user manually changes a URL, record ID, or request payload.

## 4. Employee Management Requirements

**Requirement IDs: FR-EMP-001 to FR-EMP-008**

- FR-EMP-001: HR Admin shall be able to create employee profiles.
- FR-EMP-002: Each employee shall have a unique employee code.
- FR-EMP-003: The system shall validate required employee fields.
- FR-EMP-004: HR Admin shall be able to update employee information.
- FR-EMP-005: Employees shall be assignable to departments and supervisors.
- FR-EMP-006: Employees shall be assignable to shifts using effective dates.
- FR-EMP-007: HR Admin shall be able to deactivate employees.
- FR-EMP-008: Deactivation shall preserve historical attendance and approval records.

Employee field definitions will be finalized in the database design.

## 5. Department Management Requirements

**Requirement IDs: FR-DEPT-001 to FR-DEPT-004**

- FR-DEPT-001: HR Admin shall be able to create and update departments.
- FR-DEPT-002: Employees shall be associated with valid departments where required.
- FR-DEPT-003: The system shall allow authorized users to filter employee records by department.
- FR-DEPT-004: The system shall prevent deletion of departments when doing so would invalidate dependent records or historical information.

## 6. Shift Management Requirements

**Requirement IDs: FR-SHIFT-001 to FR-SHIFT-007**

- FR-SHIFT-001: HR Admin shall be able to configure shift start and end times.
- FR-SHIFT-002: Shifts shall support configured break durations.
- FR-SHIFT-003: Employees shall be assigned shifts with effective dates.
- FR-SHIFT-004: The system shall support overnight shifts when configured.
- FR-SHIFT-005: The system shall validate shift configuration.
- FR-SHIFT-006: The system shall preserve historical shift assignments.
- FR-SHIFT-007: Weekly offs and holidays shall be configurable according to factory policy.

## 7. Attendance Requirements

### 7.1 Attendance Punches

**Requirement IDs: FR-ATT-001 to FR-ATT-009**

- FR-ATT-001: The system shall record valid check-in events.
- FR-ATT-002: The system shall record valid check-out events.
- FR-ATT-003: Attendance events shall store the employee, event type, timestamp, and recording source.
- FR-ATT-004: Timestamps shall be generated or validated by the trusted server, not accepted blindly from the client.
- FR-ATT-005: The system shall prevent invalid punch sequences.
- FR-ATT-006: Repeated requests shall not create unintended duplicate attendance events.
- FR-ATT-007: The system shall identify incomplete attendance.
- FR-ATT-008: Attendance events shall be preserved when daily summaries are recalculated.
- FR-ATT-009: Authorized supervisors shall be able to record attendance when the approved workflow permits it.

### 7.2 Daily Attendance Summaries

**Requirement IDs: FR-SUM-001 to FR-SUM-007**

- FR-SUM-001: The system shall generate a daily summary for an employee and work date.
- FR-SUM-002: The system shall derive recorded working duration from valid punch events and configured break rules.
- FR-SUM-003: The system shall apply the shift assignment relevant to the work date.
- FR-SUM-004: The system shall identify missing punches and other configured exceptions.
- FR-SUM-005: The system shall calculate attendance statuses according to approved policies.
- FR-SUM-006: The system shall preserve the source events used to calculate summaries.
- FR-SUM-007: Recalculation shall not silently overwrite approved corrections or historical information.

### 7.3 Attendance Status

Potential statuses include:

- Present.
- Absent.
- Late.
- Half-day.
- Approved leave.
- Weekly off.
- Holiday.
- Incomplete attendance.
- Pending correction.

The final status model, including whether multiple flags can apply to one day, must be approved before implementation.

## 8. Leave Management Requirements

**Requirement IDs: FR-LEAVE-001 to FR-LEAVE-008**

- FR-LEAVE-001: Employees shall be able to submit leave requests.
- FR-LEAVE-002: Leave requests shall contain the applicable dates and required details.
- FR-LEAVE-003: The system shall reject invalid date ranges.
- FR-LEAVE-004: Authorized approvers shall be able to approve or reject requests.
- FR-LEAVE-005: The system shall record the approver, decision, and decision timestamp.
- FR-LEAVE-006: Employees shall be able to view their request status.
- FR-LEAVE-007: Leave balances shall be calculated only using confirmed entitlement rules.
- FR-LEAVE-008: Approved leave shall be reflected in attendance reporting according to the approved policy.

## 9. Attendance Correction Requirements

**Requirement IDs: FR-CORR-001 to FR-CORR-007**

- FR-CORR-001: Employees shall be able to submit correction requests for their own attendance.
- FR-CORR-002: A correction request shall include the affected work date and reason.
- FR-CORR-003: The system shall allow authorized approvers to approve or reject requests.
- FR-CORR-004: The system shall preserve the original attendance event history.
- FR-CORR-005: Approved corrections shall update the appropriate daily summary.
- FR-CORR-006: The system shall record the actor, decision, timestamp, and relevant old and new values.
- FR-CORR-007: Employees shall not directly edit approved attendance records.

## 10. Overtime Requirements

**Requirement IDs: FR-OT-001 to FR-OT-006**

- FR-OT-001: The system shall identify potential overtime according to configured shift rules.
- FR-OT-002: Authorized users shall be able to submit overtime records.
- FR-OT-003: Authorized approvers shall be able to approve or reject overtime.
- FR-OT-004: The system shall record overtime duration, date, employee, status, and approval details.
- FR-OT-005: Recorded time beyond a shift shall not automatically become approved overtime.
- FR-OT-006: Reports shall distinguish pending, approved, and rejected overtime.

Overtime eligibility and calculation rules require stakeholder confirmation.

## 11. Dashboard Requirements

**Requirement IDs: FR-DASH-001 to FR-DASH-006**

- FR-DASH-001: HR Admin shall see factory-wide attendance statistics.
- FR-DASH-002: Supervisors shall see statistics for assigned teams.
- FR-DASH-003: Employees shall see their own attendance information.
- FR-DASH-004: Dashboard counts shall be derived from authorized data.
- FR-DASH-005: Dashboards shall display appropriate empty and error states.
- FR-DASH-006: Dashboard filters shall use consistent date and attendance rules.

## 12. Reporting Requirements

**Requirement IDs: FR-REP-001 to FR-REP-007**

- FR-REP-001: Authorized users shall be able to generate daily attendance reports.
- FR-REP-002: Authorized users shall be able to generate monthly attendance reports.
- FR-REP-003: Reports shall support appropriate employee and department filters.
- FR-REP-004: Reports shall include relevant absence, late-arrival, and recorded-hours information.
- FR-REP-005: Reports shall include leave and overtime information where applicable.
- FR-REP-006: Authorized users shall be able to export reports to CSV or a compatible spreadsheet format.
- FR-REP-007: Report exports shall enforce the same access restrictions as the corresponding pages.

## 13. Audit Requirements

**Requirement IDs: FR-AUD-001 to FR-AUD-005**

- FR-AUD-001: Important attendance changes shall be auditable.
- FR-AUD-002: Audit records shall identify the acting user and affected record.
- FR-AUD-003: Audit records shall include timestamps and action details.
- FR-AUD-004: Ordinary users shall not be able to modify audit history.
- FR-AUD-005: Audit access shall be restricted to authorized roles.

## 14. Business Rules

The following rules are mandatory design principles. Policy-dependent values must be configured or confirmed before production use.

### BR-001: Employee Identity
Every employee must have a unique employee code.

### BR-002: Attendance Event History
Raw attendance events must remain separate from calculated daily attendance summaries.

### BR-003: Server Time
Attendance timestamps must use a trusted server-side time source.

### BR-004: Punch Sequence
A valid punch sequence must be enforced. A new check-in must not silently replace an existing open attendance session.

### BR-005: Work Duration
Working duration must follow configured shift and break rules. Missing punches must not be treated as verified working time.

### BR-006: Historical Accuracy
Changes to current employee or shift settings must not unintentionally alter historical attendance.

### BR-007: Approval Control
Leave, attendance corrections, and overtime must follow authorized approval workflows.

### BR-008: Authorization
All sensitive operations must enforce permissions on the server.

### BR-009: Auditability
Important changes to attendance records must preserve an appropriate history.

### BR-010: Idempotency
Repeated submissions must not create unintended duplicate records.

### BR-011: Work Date
The work date for an overnight shift must be determined using a documented, configured rule.

### BR-012: Policy Configuration
Grace periods, half-day rules, overtime thresholds, and leave entitlements must not be invented by the implementation agent.

## 15. Non-Functional Requirements

### 15.1 Security

- Use secure password hashing.
- Protect authenticated routes.
- Enforce record-level authorization.
- Validate all user inputs on the server.
- Use CSRF protection for applicable web forms.
- Protect against SQL injection, cross-site scripting, and insecure direct object references.
- Protect secrets and environment configuration.
- Use HTTPS in production.

### 15.2 Performance

- Support approximately 50 employees.
- Use efficient database queries.
- Add indexes for common attendance, employee, date, and department lookups.
- Keep normal operations responsive under expected usage.

Quantitative performance targets will be agreed upon before acceptance testing.

### 15.3 Data Integrity

- Use database constraints for critical relationships and uniqueness.
- Use transactions when related changes must succeed or fail together.
- Prevent orphaned attendance and approval records.
- Maintain historical records according to the approved retention policy.

### 15.4 Usability

- Provide a responsive interface.
- Display clear validation and error messages.
- Prevent accidental duplicate submissions.
- Provide understandable confirmation messages.
- Support keyboard navigation and accessible form labels.

### 15.5 Reliability and Recovery

- Establish a documented backup process.
- Test database recovery.
- Handle failures without silently losing attendance events.
- Record application errors without exposing secrets or employee information unnecessarily.

## 16. Data Requirements

The application is expected to manage the following data categories:

- User accounts and roles.
- Employee profiles.
- Departments and supervisor assignments.
- Shift definitions and assignments.
- Attendance punch events.
- Daily attendance summaries.
- Leave types and requests.
- Attendance correction requests.
- Overtime records and approvals.
- Holidays and weekly offs.
- Audit history.

The detailed table structure, column types, indexes, and foreign-key relationships will be defined in SYSTEM_DESIGN.md.

## 17. Error Handling Requirements

- Invalid form data shall return field-specific validation messages.
- Unauthenticated requests shall be redirected to login or rejected appropriately.
- Unauthorized operations shall be rejected.
- Missing records shall produce an appropriate not-found response.
- Duplicate or invalid punch attempts shall produce a clear message.
- Database failures shall not expose SQL statements, credentials, or sensitive details.
- Failed approval actions shall not leave records in inconsistent states.
- Errors shall be logged appropriately for authorized troubleshooting.

## 18. External Interfaces

### User Interface
A responsive browser-based interface for HR Admin, Supervisors, and Employees.

### Database Interface
Laravel's database layer and migrations will manage persistence and schema changes.

### External Integrations
No biometric, payroll, messaging, or third-party attendance integration is required for the initial release.

## 19. Traceability to Product Requirements

| PRD Feature | SRS Sections |
|---|---|
| FEAT-001 Authentication and access | Sections 3 |
| FEAT-002 Employee management | Section 4 |
| FEAT-003 Department management | Section 5 |
| FEAT-004 Shift management | Section 6 |
| FEAT-005 Attendance recording | Section 7 |
| FEAT-006 Attendance status and exceptions | Section 7 |
| FEAT-007 Leave management | Section 8 |
| FEAT-008 Attendance corrections | Section 9 |
| FEAT-009 Overtime | Section 10 |
| FEAT-010 Dashboards | Section 11 |
| FEAT-011 Reports | Section 12 |
| FEAT-012 Audit history | Section 13 |

## 20. Open Questions

The following decisions must be confirmed with factory management:

| ID | Question |
|---|---|
| SRS-Q-001 | Can employees record their own attendance, can supervisors record it, or can both? |
| SRS-Q-002 | Must attendance be restricted to the factory premises? |
| SRS-Q-003 | What are the official shifts, break durations, and weekly offs? |
| SRS-Q-004 | What grace period and late-arrival rules apply? |
| SRS-Q-005 | How are half-days and incomplete attendance handled? |
| SRS-Q-006 | Who approves leave, corrections, and overtime? |
| SRS-Q-007 | What leave entitlements and balance rules apply? |
| SRS-Q-008 | What is the overtime calculation and approval policy? |
| SRS-Q-009 | Should users log in with email addresses or employee codes? |
| SRS-Q-010 | What data-retention and reporting requirements apply? |

## 21. Acceptance and Verification

The system will be ready for stakeholder acceptance when:

1. All approved functional requirements are implemented.
2. Authentication and authorization tests pass.
3. Employee and department operations are validated.
4. Shift assignments and attendance calculations pass agreed test cases.
5. Leave and correction workflows behave correctly.
6. Overtime approvals are correctly restricted.
7. Reports match verified test data.
8. Audit history records important changes.
9. Critical security and data-integrity tests pass.
10. Factory representatives complete user acceptance testing.

## 22. Document Status

**Status:** Initial Draft — Pending Stakeholder Review

**Next document:** SYSTEM_DESIGN.md

The system design document will define the application architecture, database relationships, module boundaries, and technical decisions based on the approved requirements.

---

*End of Software Requirements Specification.*