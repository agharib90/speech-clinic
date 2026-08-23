# Speech Clinic — Client Requirements & Delivery Tracking

Last consolidated: 2026-08-22

This document is the working business-requirements baseline for the Speech Clinic project.
Existing capabilities must be extended where possible; duplicate modules/tables should not
be created when an equivalent capability already exists.

---

## 1. Patient Operational Workspace

- One patient should have one operational workspace for day-to-day work.
- Patient-specific operations should happen from the patient workspace whenever safely possible.
- Avoid repeatedly selecting the same patient.
- Global modules remain available for clinic-wide management.
- Patient name/identifier, invoice number, plan title, guardian name, etc. should open details directly.
- Remove redundant "عرض" buttons when the entity title already performs the same navigation.
- Keep real action buttons such as Add, Edit, Delete, Payment, Allocation, Booking, PDF, Activate, etc.
- Specialized pages should provide clear and safe Back navigation to the Patient Workspace when entered from it.

Status:
Implemented substantially; continue improving operational consolidation where useful.

---

## 2. Specialties, Services and Specialists

- A specialist can have multiple specialties/services.
- Management assigns allowed services manually.
- Specialist entitlement/rate can differ by specialist and service.
- Historical specialist rates must remain preserved.
- Customer service price and specialist entitlement are separate concepts.

Status:
Implemented foundation.

---

## 3. Official Service Customer Pricing

- Every normal billable service has an official current customer price in Service management.
- Staff should not manually remember/type the normal service price when building a patient plan.
- Changing the current service price applies to future plan assignments only.
- Historical patient plans must not be rewritten when the service price changes.
- A service without a valid approved customer price cannot be activated/assigned normally.

Status:
Implemented and manually accepted.

---

## 4. Patient Service Plans

A patient plan may contain, in the required order:

- evaluations
- tests
- therapy services
- future Day Care services

Rules:

- Service price is taken from the current official Service price when the plan item is created.
- The plan item stores a historical price snapshot.
- Patient-specific discount reduces the customer price without altering the global Service price.
- Discount requires authorized permission.
- Payment allocation follows plan-item order.
- Only full service units become authorized.
- Remaining partial money remains monetary credit.
- Existing historical pricing must not change after future Service price changes.
- Financial/operational history locks protected plan structure.

Status:
Implemented foundation and pricing snapshot manually accepted.

---

## 5. Patient Invoicing from Plan

Inside Patient Workspace:

Default invoice mode:
- "خدمة من خطة الحالة"

The employee selects a service from the patient's ACTIVE plan.

The system derives:
- description
- historical agreed unit price

from PatientServicePlanItem.

The invoice must use:
PatientServicePlanItem.final_unit_price

and NOT the Service's current price.

A controlled secondary option remains:
- "بند إضافي حر"

Draft/completed/other-patient plan items cannot be used for a new normal Workspace plan invoice.

Status:
Implemented and manually accepted.

---

## 6. Appointment Financial Confirmation

- A confirmed booking requires a down payment.
- Default confirmation deposit = 50% of final patient service price after discount.
- Deposit percentage is configurable in Settings.
- Appointment stores percentage and amount snapshots.
- Changing the setting affects new bookings only.
- Partial deposit confirms the appointment slot only.
- Partial deposit does NOT authorize completion of the service.
- Full service funding is required for authorized executable units.
- The same partial deposit cannot confirm multiple appointment slots.
- Legacy booking remains a controlled exception requiring permission and mandatory reason.
- Exact no-show financial penalty is NOT yet finalized and must not be invented.

Status:
Implemented foundation.

---

## 7. Specialist Calendar and Booking Availability

### Existing rule to preserve

A booked appointment reserves the specialist's full appointment time.

Example:

Appointment:
09:00 → 09:30

No overlapping appointment may be booked during that occupied interval.

Server-side overlap/conflict protection must always remain authoritative.

### NEW CLIENT REQUIREMENT — Availability Windows

Reception staff should NOT be offered times that cannot actually be booked.

The booking experience should use:

Specialist Work Periods
MINUS
Existing Occupied Appointments
PLUS
Selected Service Duration

to calculate real availability.

#### Specialist working periods

Working periods are defined from the employee/specialist card.

The specialist may have MULTIPLE working periods on the same day.

Example:

08:00 → 15:00
18:00 → 22:00

15:00 → 18:00 is not available.

#### Available time windows

After selecting the required date, service and specialist, the system should
show the real FREE WINDOWS within that specialist's work periods.

Example concept:

Working period:
08:00 → 15:00

Existing occupied appointment:
09:30 → 10:00

Free windows:
08:00 → 09:30
10:00 → 15:00

The receptionist should be able to see the WHOLE free interval, not only
coarse fixed half-hour blocks.

#### Service duration

Possible appointment starts depend on the selected service duration.

Example:

Free window:
08:00 → 09:30

For a 30-minute service, valid start times may include:
08:00
08:15
08:30
08:45
09:00

09:15 must NOT be offered because the service would end at 09:45 and exceed
the available window ending at 09:30.

For a 15-minute service, additional starts may fit.

The calculation therefore must use the FULL appointment interval, not only
the appointment start time.

#### Busy appointment types

Any real appointment/activity occupying the specialist's calendar must block
that time regardless of whether it is a therapy session, evaluation, test,
or another supported appointment type.

Cancelled appointments should not normally occupy availability.

#### UX principle

Reception should primarily see:
"What CAN I book?"

rather than selecting an invalid time and only receiving a conflict error later.

However, server-side conflict validation must remain as a second safety layer
for concurrency/race conditions.

Status:
Implemented; pending manual acceptance.
Specialist personal work periods support multiple periods per weekday, and the shared
availability engine subtracts non-cancelled appointments and applies the selected Service
duration with a centralized 15-minute start increment. Past starts are excluded for today,
and personal work periods are also enforced for controlled legacy bookings. Clinic-wide working hours remain a
separate single-period setting and were not redesigned or imposed on specialist periods in
this phase.

---

## 8. Attendance / Reception

- Existing Reception/attendance capability must be inspected and extended rather than rebuilt.
- One child QR/check-in identifies the child.
- QR scan alone must not count as service completion.
- Reception explicitly confirms attendance for the intended appointment/service.
- Multiple same-day services remain independent.
- Attendance itself does not consume a therapy unit.
- Attendance itself does not create specialist earning.
- Appointment unresolved after a grace period may generate a reminder.
- Do NOT automatically classify no-show unless the agreed business rule is implemented.
- Corrections require authorization/audit rather than silent history rewriting.

Status:
Complete

---

## 9. Service Completion

- Actual completion is distinct from attendance.
- Completion is the future point that may:
  - consume the authorized service unit
  - recognize specialist entitlement snapshot
  - update operational progress
- Do not trigger those effects merely from reception attendance.

Status:
Further implementation/review required.

---

## 10. Specialist / Employee Compensation

Specialist:
- entitlement based on actual services performed
- per-service rates may differ
- historical rate snapshots required

Employee may receive:
- salary
- service fees
- both

Payroll:
- bonuses/deductions can be manually entered for the same month
- salary changes apply from the next month, not retroactively
- attendance does not automatically deduct salary
- management decides deductions
- corrections should be auditable

Status:
Existing payroll and rate foundations present; complete workflow remains subject to review.

---

## 11. Payments and Financial Accounts

Supported/required payment methods include:
- Cash
- InstaPay
- Wallet

Visa may be added later.

Requirements:
- separate financial ledgers/accounts
- cash safe tracking
- internal transfers are not income/expense
- receipts/vouchers
- expenses
- other income
- references/images where applicable

Status:
Partial/current finance capabilities exist; complete commercial finance cycle requires comparison review.

---

## 12. Daily / Monthly Financial Controls

- Daily safe close.
- Discrepancy visibility.
- Daily report.
- Monthly closing locks original financial records.
- Corrections after closing should use reversal/corrective entries rather than destructive editing.

Status:
Requires implementation comparison/review.

---

## 13. Dashboard and Notifications

- Dashboard should eventually be role-based.
- Notification center required later.
- Information/actions should reflect the user's permissions and operational role.

Status:
Partial dashboard functionality exists; later phase.

---

## 14. Fingerprint Attendance

- ZK fingerprint integration is a later requirement.
- Do not build duplicate attendance logic solely for fingerprint support.

Status:
Later phase.

---

## 15. Permissions and Delegation

- Operational authority should use permissions.
- Do not hardcode workflow authority solely by job title.
- Patient-level access restrictions must remain enforced.
- Signed Patient Workspace context does not replace patient-specific authorization.

Status:
Current permission/security foundation implemented and under continued regression protection.

---

## Development Principle

Before implementing any requested capability:

1. Inspect what already exists.
2. Reuse/extend existing capability where possible.
3. Do not create duplicate tables/modules/services for the same business concept.
4. Preserve historical finance, pricing and audit integrity.
5. Add focused regression tests.
6. Manual acceptance before final production release.

