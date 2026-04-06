LanEntrance Requirements Traceability Matrix (MIL-STD-498 Adapted)
===

# 1. Scope

## 1.1 Identification

* Title: LanEntrance Requirements Traceability Matrix
* Short Name: LanEntrance RTM
* Version: Draft v0.1
* Status: Working Draft

This document provides bidirectional traceability from SSS requirements through SRS software requirements to design components and test cases.

## 1.2 Document overview

The RTM maps every SSS requirement to its SRS decomposition, design component(s), test case(s), and current implementation status.

### Status definitions

| Status      | Definition                                                                 |
| ----------- | -------------------------------------------------------------------------- |
| Implemented | Code exists and automated tests pass in CI                                 |
| Partial     | Some code exists (e.g., transport layer) but feature-specific logic pending |
| Planned     | Designed in SRS/SDD, awaiting implementation                               |
| Deferred    | Explicitly moved to a later phase                                          |
| Verified    | Confirmed by inspection without code (e.g., schema absence)               |

---

# 2. Referenced documents

| ID      | Title            | Version    |
| ------- | ---------------- | ---------- |
| REF-001 | LanEntrance SSS  | Draft v0.1 |
| REF-002 | LanEntrance SRS  | Draft v0.1 |
| REF-003 | LanEntrance SDD  | Draft v0.1 |
| REF-004 | LanEntrance STD  | Draft v0.1 |

---

# 3. Traceability matrix

## 3.1 States and modes (SSS 3.1)

| SSS Req ID     | SRS Req ID          | Design Component                    | Test Case ID     | Qual | Status      |
| -------------- | ------------------- | ----------------------------------- | ---------------- | ---- | ----------- |
| LENT-3.1-001   | LENT-SW-STATE-001   | useEntranceState.ts, Scanner.vue    | TC-STATE-001     | TST, INSP | Planned |
| LENT-3.1-002   | LENT-SW-STATE-002   | EnsureEntranceRole, auth middleware | TC-STATE-002     | TST  | Planned     |
| LENT-3.1-003   | LENT-SW-STATE-003   | LanCoreValidationService, useEntranceState | TC-STATE-003 | TST, ANL | Planned |
| LENT-3.1-004   | LENT-SW-STATE-004   | DegradedBanner.vue                  | TC-STATE-004     | DEM, TST | Planned |

## 3.2 Authentication and session (SSS 3.2.1)

| SSS Req ID       | SRS Req ID         | Design Component                         | Test Case ID     | Qual | Status      |
| ---------------- | ------------------ | ---------------------------------------- | ---------------- | ---- | ----------- |
| LENT-3.2.1-001   | LENT-SW-AUTH-001   | LanCoreAuthController, LanCoreClient     | TC-AUTH-001      | TST  | Implemented |
| LENT-3.2.1-002   | LENT-SW-AUTH-002   | LanCoreValidationService (metadata)      | TC-AUTH-002      | TST, INSP | Planned |
| LENT-3.2.1-003   | LENT-SW-AUTH-003   | Laravel session, Inertia error handling  | TC-AUTH-003      | TST  | Partial     |
| LENT-3.2.1-004   | LENT-SW-AUTH-004   | Tailwind responsive, Vite build          | TC-AUTH-004      | DEM, TST | Partial |

## 3.3 Authorization (SSS 3.2.2)

| SSS Req ID       | SRS Req ID          | Design Component                     | Test Case ID     | Qual | Status      |
| ---------------- | ------------------- | ------------------------------------ | ---------------- | ---- | ----------- |
| LENT-3.2.2-001   | LENT-SW-AUTHZ-001  | EnsureEntranceRole middleware        | TC-AUTHZ-001     | TST  | Planned     |
| LENT-3.2.2-002   | LENT-SW-AUTHZ-002  | EnsureEntranceRole, DecisionDisplay  | TC-AUTHZ-002     | DEM, TST | Planned |
| LENT-3.2.2-003   | LENT-SW-AUTHZ-003  | EnsureEntranceRole, UserRole enum    | TC-AUTHZ-003     | INSP, TST | Planned |

## 3.4 QR scanning (SSS 3.2.3)

| SSS Req ID       | SRS Req ID         | Design Component                    | Test Case ID     | Qual | Status      |
| ---------------- | ------------------- | ----------------------------------- | ---------------- | ---- | ----------- |
| LENT-3.2.3-001   | LENT-SW-SCAN-001   | QrScanner.vue (vue-qrcode-reader)   | TC-SCAN-001      | DEM, TST | Planned |
| LENT-3.2.3-002   | LENT-SW-SCAN-002   | QrScanner.vue                       | TC-SCAN-002      | TST  | Planned     |
| LENT-3.2.3-003   | LENT-SW-SCAN-003   | ValidateTokenRequest, EntranceCtrl  | TC-SCAN-003      | TST  | Planned     |
| LENT-3.2.3-004   | LENT-SW-SCAN-004   | DecisionDisplay.vue, EntranceCtrl   | TC-SCAN-004      | DEM, TST | Planned |
| LENT-3.2.3-005   | LENT-SW-SCAN-005   | Lookup.vue, LookupController        | TC-SCAN-005      | DEM, TST | Planned |

## 3.5 Validation and check-in (SSS 3.2.4)

| SSS Req ID       | SRS Req ID            | Design Component                        | Test Case ID       | Qual | Status      |
| ---------------- | --------------------- | --------------------------------------- | ------------------ | ---- | ----------- |
| LENT-3.2.4-001   | LENT-SW-CHECKIN-001   | EntranceController, LanCoreValService   | TC-CHECKIN-001     | INSP, TST | Planned |
| LENT-3.2.4-002   | LENT-SW-CHECKIN-002   | LanCoreValidationService                | TC-CHECKIN-002     | INSP, TST | Planned |
| LENT-3.2.4-003   | LENT-SW-CHECKIN-003   | DecisionDisplay.vue, ValidationResponse | TC-CHECKIN-003     | TST  | Planned     |
| LENT-3.2.4-004   | LENT-SW-CHECKIN-004   | LanCoreClient (timeout), useCheckin     | TC-CHECKIN-004     | TST, ANL | Planned |
| LENT-3.2.4-005   | LENT-SW-CHECKIN-005   | useCheckin.ts, DecisionDisplay.vue       | TC-CHECKIN-005     | TST  | Planned     |
| LENT-3.2.4-006   | LENT-SW-CHECKIN-006   | LanCoreValidationService                | TC-CHECKIN-006     | TST  | Planned     |
| LENT-3.2.4-007   | LENT-SW-CHECKIN-007   | DecisionDisplay.vue, EntranceCtrl       | TC-CHECKIN-007     | DEM, TST | Planned |
| LENT-3.2.4-008   | LENT-SW-CHECKIN-008   | DecisionDisplay.vue, LanCoreValService  | TC-CHECKIN-008     | DEM, TST | Planned |
| LENT-3.2.4-009   | LENT-SW-CHECKIN-009   | DecisionDisplay.vue, LanCoreValService  | TC-CHECKIN-009     | DEM, TST | Planned |

## 3.6 Group ticket handling (SSS 3.2.5)

| SSS Req ID       | SRS Req ID          | Design Component                        | Test Case ID     | Qual | Status      |
| ---------------- | ------------------- | --------------------------------------- | ---------------- | ---- | ----------- |
| LENT-3.2.5-001   | LENT-SW-GROUP-001   | DecisionDisplay.vue, LanCoreValService  | TC-GROUP-001     | DEM, TST | Planned |
| LENT-3.2.5-002   | LENT-SW-GROUP-001   | DecisionDisplay.vue                     | TC-GROUP-002     | DEM, TST | Planned |
| LENT-3.2.5-003   | LENT-SW-GROUP-002   | OverrideModal.vue, OverrideController   | TC-GROUP-003     | TST  | Planned     |
| LENT-3.2.5-004   | LENT-SW-GROUP-003   | OverrideModal.vue, OverrideRequest      | TC-GROUP-004     | TST  | Planned     |

## 3.65 On-site payment (SSS 3.2.9)

| SSS Req ID       | SRS Req ID         | Design Component                         | Test Case ID    | Qual | Status      |
| ---------------- | ------------------- | ---------------------------------------- | --------------- | ---- | ----------- |
| LENT-3.2.9-001   | LENT-SW-PAY-001    | DecisionDisplay.vue (payment section)    | TC-PAY-001      | DEM, TST | Planned |
| LENT-3.2.9-002   | LENT-SW-PAY-002    | DecisionDisplay.vue (method selection)   | TC-PAY-002      | DEM, TST | Planned |
| LENT-3.2.9-003   | LENT-SW-PAY-003    | PaymentController, LanCoreValService     | TC-PAY-003, 004 | TST  | Planned     |
| LENT-3.2.9-004   | LENT-SW-PAY-004    | DecisionDisplay.vue (receipt notice)     | TC-PAY-005      | DEM, TST | Planned |
| LENT-3.2.9-005   | LENT-SW-PAY-005    | Database schema (no payment tables)      | —               | INSP | Planned     |
| LENT-3.2.9-006   | LENT-SW-PAY-006    | DecisionDisplay.vue, EnsureEntranceRole  | TC-PAY-006      | TST  | Planned     |

## 3.7 Operator guidance (SSS 3.2.6)

| SSS Req ID       | SRS Req ID       | Design Component          | Test Case ID   | Qual | Status      |
| ---------------- | ---------------- | ------------------------- | -------------- | ---- | ----------- |
| LENT-3.2.6-001   | LENT-SW-UI-001   | DecisionDisplay.vue (full-screen overlay) | TC-UI-001 | DEM, TST | Planned |
| LENT-3.2.6-002   | LENT-SW-UI-002   | DecisionDisplay.vue (seating section)     | TC-UI-002 | DEM, TST | Planned |
| LENT-3.2.6-003   | LENT-SW-UI-003   | DecisionDisplay.vue (denial context)      | TC-UI-003 | DEM, TST | Planned |
| LENT-3.2.6-004   | LENT-SW-UI-004   | DecisionDisplay.vue (addon list)          | TC-UI-004 | DEM, TST | Planned |
| LENT-3.2.6-005   | LENT-SW-UI-005   | DecisionDisplay.vue (verification list)   | TC-UI-005 | DEM, TST | Planned |

## 3.8 Audit and traceability (SSS 3.2.7)

| SSS Req ID       | SRS Req ID          | Design Component               | Test Case ID     | Qual | Status      |
| ---------------- | ------------------- | ------------------------------ | ---------------- | ---- | ----------- |
| LENT-3.2.7-001   | LENT-SW-AUDIT-001   | LanCoreValidationService       | TC-AUDIT-001     | INSP, TST | Planned |
| LENT-3.2.7-002   | LENT-SW-AUDIT-002   | LanCoreValidationService       | TC-AUDIT-002     | INSP, TST | Planned |
| LENT-3.2.7-003   | LENT-SW-AUDIT-003   | DecisionDisplay.vue            | TC-AUDIT-003     | DEM, TST | Planned |

## 3.9 Degraded operation (SSS 3.2.8)

| SSS Req ID       | SRS Req ID              | Design Component                    | Test Case ID       | Qual | Status      |
| ---------------- | ----------------------- | ----------------------------------- | ------------------ | ---- | ----------- |
| LENT-3.2.8-001   | LENT-SW-DEGRADED-001    | LanCoreValidationService            | TC-DEGRADED-001    | DEM, TST | Planned |
| LENT-3.2.8-002   | LENT-SW-DEGRADED-002    | LanCoreValidationService            | TC-DEGRADED-002    | INSP, TST | Planned |
| LENT-3.2.8-003   | LENT-SW-DEGRADED-003    | (deferred — Phase 3)                | TC-DEGRADED-003    | INSP, TST | Deferred |

## 3.10 External interfaces (SSS 3.3)

| SSS Req ID       | SRS/IRS Req ID         | Design Component                  | Test Case ID     | Qual | Status      |
| ---------------- | ---------------------- | --------------------------------- | ---------------- | ---- | ----------- |
| LENT-3.3.2-001   | LENT-IR-001-001        | Scanner.vue, Inertia pages        | TC-IF-001        | DEM, TST | Planned |
| LENT-3.3.2-002   | LENT-IR-001-009        | QrScanner.vue                     | TC-IF-002        | DEM, TST | Planned |
| LENT-3.3.2-003   | LENT-IR-001-010        | Lookup.vue                        | TC-IF-003        | DEM, TST | Planned |
| LENT-3.3.2-004   | LENT-IR-001-008        | DecisionDisplay.vue               | TC-IF-004        | DEM, INSP | Planned |
| LENT-3.3.3-001   | LENT-IR-002-001        | LanCoreAuthController             | TC-SSO-001       | INSP, TST | Implemented |
| LENT-3.3.3-002   | LENT-IR-002-002        | LanCoreAuthController             | TC-SSO-002       | TST  | Implemented |
| LENT-3.3.3-003   | LENT-IR-002-003        | LanCoreAuthController             | TC-SSO-003       | TST  | Implemented |
| LENT-3.3.4-001   | LENT-IR-003-001        | LanCoreClient::http()             | TC-API-001       | INSP, TST | Partial |
| LENT-3.3.4-002   | LENT-IR-003-003        | LanCoreValidationService          | TC-API-002       | INSP, TST | Planned |
| LENT-3.3.4-003   | LENT-IR-003-004        | LanCoreValidationService          | TC-API-003       | TST  | Planned     |
| LENT-3.3.4-004   | LENT-IR-003-007        | LanCoreValidationService          | TC-API-004       | TST  | Planned     |

## 3.11 Internal interfaces (SSS 3.4)

| SSS Req ID     | SRS Req ID         | Design Component            | Test Case ID   | Qual | Status      |
| -------------- | ------------------- | --------------------------- | -------------- | ---- | ----------- |
| LENT-3.4-001   | LENT-SW-INT-001    | routes/api.php, Inertia     | TC-INT-001     | INSP | Planned     |
| LENT-3.4-002   | LENT-SW-INT-002    | Frontend code (no direct)   | TC-INT-002     | INSP, TST | Planned |

## 3.12 Internal data (SSS 3.5)

| SSS Req ID     | SRS Req ID         | Design Component            | Test Case ID   | Qual | Status      |
| -------------- | ------------------- | --------------------------- | -------------- | ---- | ----------- |
| LENT-3.5-001   | LENT-SW-DATA-001   | Database schema (no tables) | TC-DATA-001    | INSP | Verified    |
| LENT-3.5-002   | LENT-SW-DATA-002   | Session/cache usage         | TC-DATA-002    | INSP | Planned     |
| LENT-3.5-003   | LENT-SW-DATA-003   | Session TTL config          | TC-DATA-003    | INSP, TST | Planned |

## 3.13 Security and privacy (SSS 3.8)

| SSS Req ID     | SRS Req ID         | Design Component                 | Test Case ID   | Qual | Status      |
| -------------- | ------------------- | -------------------------------- | -------------- | ---- | ----------- |
| LENT-3.8-001   | LENT-SW-SEC-001    | auth middleware                  | TC-SEC-001     | TST  | Implemented |
| LENT-3.8-002   | LENT-SW-SEC-002    | EnsureEntranceRole               | TC-SEC-002     | TST  | Planned     |
| LENT-3.8-003   | LENT-SW-SEC-003    | LanCoreClient::http(), HTTPS    | TC-SEC-003     | INSP, TST | Partial |
| LENT-3.8-004   | LENT-SW-SEC-004    | DecisionDisplay.vue              | TC-SEC-004     | INSP, DEM | Planned |
| LENT-3.8-005   | LENT-SW-SEC-005    | QrScanner.vue, EntranceCtrl     | TC-SEC-005     | TST  | Planned     |
| LENT-3.8-006   | LENT-SW-SEC-006    | LanCoreValidationService         | TC-SEC-006     | TST  | Planned     |
| LENT-3.8-007   | LENT-SW-SEC-007    | throttle:entrance middleware     | TC-SEC-007     | INSP, TST | Planned |

## 3.14 Quality factors (SSS 3.11)

| SSS Req ID      | SRS Req ID          | Design Component           | Test Case ID    | Qual | Status      |
| --------------- | ------------------- | -------------------------- | --------------- | ---- | ----------- |
| LENT-3.11-001   | LENT-SW-QUAL-001    | Scanner.vue workflow       | TC-QUAL-001     | DEM, ANL | Planned |
| LENT-3.11-002   | LENT-SW-QUAL-002    | DecisionDisplay.vue        | TC-QUAL-002     | DEM, TST | Planned |
| LENT-3.11-003   | LENT-SW-QUAL-003    | Codebase architecture      | TC-QUAL-003     | INSP | Ongoing     |
| LENT-3.11-004   | LENT-SW-QUAL-004    | Test suites                | TC-QUAL-004     | INSP, TST | Partial |

---

# 4. Status summary

| Status      | Count | Description                                    |
| ----------- | ----- | ---------------------------------------------- |
| Implemented | 6     | Code exists and tests pass                     |
| Partial     | 5     | Partially implemented or partially tested      |
| Planned     | 57    | Designed, awaiting Phase 2 implementation       |
| Deferred    | 1     | Moved to Phase 3                               |
| Verified    | 1     | Verified by inspection                          |
| **Total**   | **70**|                                                 |

---

# 5. Notes

This RTM is a living document updated as implementation progresses. After each phase, test case IDs will be linked to actual test file paths and test execution results will be recorded in the STR.
