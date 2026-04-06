LanEntrance Software Test Report (MIL-STD-498 Adapted)
===

# 1. Scope

## 1.1 Identification

* Title: LanEntrance Software Test Report
* Short Name: LanEntrance STR
* System Name: LanEntrance
* Ecosystem: LanSoftware
* Version: Draft v0.1
* Status: Stub — populated after test execution

This document records the results of software test execution for LanEntrance.

## 1.2 System overview

See SSS Section 1.2.

## 1.3 Document overview

This document will contain test execution results, pass/fail status, defects found, and coverage metrics for each test described in the STD. It is updated after each test execution cycle.

---

# 2. Referenced documents

| ID      | Title                | Version    |
| ------- | -------------------- | ---------- |
| REF-001 | LanEntrance STP      | Draft v0.1 |
| REF-002 | LanEntrance STD      | Draft v0.1 |
| REF-003 | LanEntrance RTM      | Draft v0.1 |

---

# 3. Test execution overview

## 3.1 Phase 1 test results

Phase 1 (Authentication, SSO, Settings) tests are executed via CI on every push.

| Test Suite                     | Framework  | Status  | Last Run   |
| ------------------------------ | ---------- | ------- | ---------- |
| AuthenticationTest             | Pest       | Pass    | CI         |
| LanCoreSsoTest                 | Pest       | Pass    | CI         |
| LanCoreRolesWebhookTest       | Pest       | Pass    | CI         |
| RegistrationTest               | Pest       | Pass    | CI         |
| PasswordResetTest              | Pest       | Pass    | CI         |
| PasswordConfirmationTest       | Pest       | Pass    | CI         |
| EmailVerificationTest          | Pest       | Pass    | CI         |
| TwoFactorChallengeTest         | Pest       | Pass    | CI         |
| ProfileUpdateTest              | Pest       | Pass    | CI         |
| SecurityTest                   | Pest       | Pass    | CI         |
| DashboardTest                  | Pest       | Pass    | CI         |

## 3.2 Phase 2 test results

*To be populated after Phase 2 implementation and test execution.*

| Test Case ID   | Description                        | Status  | Date | Defects |
| -------------- | ---------------------------------- | ------- | ---- | ------- |
| TC-STATE-001   | State machine transitions          | Pending | —    | —       |
| TC-STATE-002   | Auth required for READY            | Pending | —    | —       |
| TC-STATE-003   | Degraded state detection           | Pending | —    | —       |
| TC-STATE-004   | Degraded banner display            | Pending | —    | —       |
| TC-AUTH-002    | Operator identity in requests      | Pending | —    | —       |
| TC-AUTH-003    | Session expiry handling            | Pending | —    | —       |
| TC-AUTHZ-001   | Role-based authorization           | Pending | —    | —       |
| TC-AUTHZ-002   | Authorization denial UI            | Pending | —    | —       |
| TC-SCAN-001    | Camera QR scanning                 | Pending | —    | —       |
| TC-SCAN-003    | Token payload validation           | Pending | —    | —       |
| TC-SCAN-005    | Manual lookup fallback             | Pending | —    | —       |
| TC-CHECKIN-001 | Backend-mediated validation        | Pending | —    | —       |
| TC-CHECKIN-003 | Decision outcome display           | Pending | —    | —       |
| TC-CHECKIN-004 | Response time target               | Pending | —    | —       |
| TC-CHECKIN-005 | No premature success               | Pending | —    | —       |
| TC-GROUP-001   | Group policy display               | Pending | —    | —       |
| TC-GROUP-003   | Override submission                 | Pending | —    | —       |
| TC-GROUP-004   | Override requires reason           | Pending | —    | —       |
| TC-AUDIT-001   | Audit metadata injection           | Pending | —    | —       |
| TC-AUDIT-002   | Audit response integrity           | Pending | —    | —       |
| TC-DEGRADED-001| Service failure detection           | Pending | —    | —       |
| TC-DEGRADED-002| No local authoritative state       | Pending | —    | —       |
| TC-CHECKIN-007 | Verification-required flow         | Pending | —    | —       |
| TC-CHECKIN-008 | Post-check-in seating display      | Pending | —    | —       |
| TC-CHECKIN-009 | Post-check-in addon list display   | Pending | —    | —       |
| TC-PAY-001     | Payment-required decision display  | Pending | —    | —       |
| TC-PAY-002     | Payment method selection            | Pending | —    | —       |
| TC-PAY-003     | Payment confirmation backend       | Pending | —    | —       |
| TC-PAY-004     | Payment confirmation validation    | Pending | —    | —       |
| TC-PAY-005     | Receipt sent notice                | Pending | —    | —       |
| TC-PAY-006     | Payment bypass requires Moderator  | Pending | —    | —       |
| TC-SEC-001     | Authenticated access enforcement   | Pending | —    | —       |
| TC-SEC-007     | Rate limiting enforcement          | Pending | —    | —       |
| TC-E2E-001     | Full scan-to-checkin workflow      | Pending | —    | —       |
| TC-E2E-002     | Override workflow                  | Pending | —    | —       |
| TC-E2E-003     | Mobile viewport usability          | Pending | —    | —       |
| TC-E2E-004     | Verification-required workflow     | Pending | —    | —       |
| TC-E2E-005     | On-site payment workflow           | Pending | —    | —       |

---

# 4. Coverage metrics

## 4.1 Backend coverage

*To be populated after Phase 2 test execution.*

| Metric              | Value   |
| ------------------- | ------- |
| Line coverage       | —       |
| Branch coverage     | —       |
| Function coverage   | —       |

## 4.2 Frontend coverage

*To be populated after Phase 2 test execution.*

| Metric              | Value   |
| ------------------- | ------- |
| Line coverage       | —       |
| Branch coverage     | —       |
| Statement coverage  | —       |

---

# 5. Defect summary

*To be populated after test execution.*

| Defect ID | Test Case  | Severity | Description | Status | Resolution |
| --------- | ---------- | -------- | ----------- | ------ | ---------- |
| —         | —          | —        | —           | —      | —          |

---

# 6. Qualification summary

*To be populated after all Phase 2 tests pass.*

| Qualification Method | Tests Planned | Tests Passed | Tests Failed | Coverage |
| -------------------- | ------------- | ------------ | ------------ | -------- |
| DEM                  | —             | —            | —            | —        |
| TST                  | —             | —            | —            | —        |
| ANL                  | —             | —            | —            | —        |
| INSP                 | —             | —            | —            | —        |

---

# 7. Notes

This document is a living artifact. It is updated:
* Automatically via CI test result aggregation (Phase 1 tests)
* Manually after Phase 2 test execution cycles
* At phase boundary reviews
