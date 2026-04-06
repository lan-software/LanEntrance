LanEntrance Software Test Description (MIL-STD-498 Adapted)
===

# 1. Scope

## 1.1 Identification

* Title: LanEntrance Software Test Description
* Short Name: LanEntrance STD
* System Name: LanEntrance
* Ecosystem: LanSoftware
* Version: Draft v0.1
* Status: Working Draft

This document describes the individual test cases for LanEntrance, organized by the test case IDs referenced in the RTM.

## 1.2 Document overview

Each test case specifies: purpose, SRS/SSS traceability, preconditions, test steps, expected results, and the test tool/framework used.

---

# 2. Referenced documents

| ID      | Title                | Version    |
| ------- | -------------------- | ---------- |
| REF-001 | LanEntrance SRS      | Draft v0.1 |
| REF-002 | LanEntrance STP      | Draft v0.1 |
| REF-003 | LanEntrance RTM      | Draft v0.1 |
| REF-004 | LanEntrance IDD      | Draft v0.1 |

---

# 3. Test case descriptions

## 3.1 State and mode tests

### TC-STATE-001: Application state machine transitions
**SRS Trace**: LENT-SW-STATE-001
**Tool**: Vitest
**File**: `resources/js/composables/useEntranceState.test.ts`

| Step | Action                                  | Expected Result                            |
| ---- | --------------------------------------- | ------------------------------------------ |
| 1    | Initialize composable                   | State is `IDLE`, degraded is false         |
| 2    | Call transition to READY                | State changes to `READY`                   |
| 3    | Call transition to ACTIVE_SCAN          | State changes to `ACTIVE_SCAN`             |
| 4    | Call transition to DECISION_DISPLAY with result | State is `DECISION_DISPLAY`, lastResult set |
| 5    | Call resetToReady()                     | State returns to `READY`, lastResult cleared |

### TC-STATE-002: Ready state requires authentication
**SRS Trace**: LENT-SW-STATE-002
**Tool**: Pest Feature
**File**: `tests/Feature/Entrance/EntranceAuthorizationTest.php`

| Step | Action                                  | Expected Result                            |
| ---- | --------------------------------------- | ------------------------------------------ |
| 1    | GET /entrance as unauthenticated user   | Redirect to login (302)                    |
| 2    | GET /entrance as authenticated user     | Page loads (200)                           |
| 3    | POST /api/entrance/validate as guest    | 401 Unauthenticated                        |
| 4    | POST /api/entrance/validate as user     | 200 or validation response                 |

### TC-STATE-003: Degraded state on LanCore failure
**SRS Trace**: LENT-SW-STATE-003
**Tool**: Pest Feature
**File**: `tests/Feature/Entrance/DegradedModeTest.php`

| Step | Action                                  | Expected Result                            |
| ---- | --------------------------------------- | ------------------------------------------ |
| 1    | Mock LanCore to timeout                 | —                                          |
| 2    | POST /api/entrance/validate             | Response contains `degraded: true`         |
| 3    | Mock LanCore to return 500              | —                                          |
| 4    | POST /api/entrance/validate             | Response contains `degraded: true`         |
| 5    | Mock LanCore connection refused         | —                                          |
| 6    | POST /api/entrance/validate             | Response contains `degraded: true`         |

### TC-STATE-004: Degraded banner display
**SRS Trace**: LENT-SW-STATE-004
**Tool**: Vitest
**File**: `resources/js/components/entrance/DegradedBanner.test.ts`

| Step | Action                                  | Expected Result                            |
| ---- | --------------------------------------- | ------------------------------------------ |
| 1    | Render DegradedBanner                   | Banner visible with warning text           |
| 2    | Check accessibility                     | Has `role="alert"` attribute               |

---

## 3.2 Authentication tests

### TC-AUTH-001: LanCore SSO authentication flow
**SRS Trace**: LENT-SW-AUTH-001
**Tool**: Pest Feature
**File**: `tests/Feature/Auth/LanCoreSsoTest.php`
**Status**: Implemented

| Step | Action                                  | Expected Result                            |
| ---- | --------------------------------------- | ------------------------------------------ |
| 1    | GET /auth/redirect with LanCore enabled | Redirect to LanCore SSO URL               |
| 2    | GET /auth/callback with valid code      | User created, session established, redirect to dashboard |
| 3    | GET /auth/callback with invalid code    | Redirect to / with error                   |

### TC-AUTH-002: Operator identity in requests
**SRS Trace**: LENT-SW-AUTH-002
**Tool**: Pest Feature
**File**: `tests/Feature/Entrance/AuditMetadataTest.php`

| Step | Action                                  | Expected Result                            |
| ---- | --------------------------------------- | ------------------------------------------ |
| 1    | Authenticate as LanCore user (id: 42)  | —                                          |
| 2    | POST /api/entrance/validate             | LanCore request includes operator_id: 42   |
| 3    | Verify request contains timestamp       | ISO 8601 UTC timestamp present             |
| 4    | Verify request contains session_id      | Session ID present                         |

### TC-AUTH-003: Session expiry handling
**SRS Trace**: LENT-SW-AUTH-003
**Tool**: Pest Feature
**File**: `tests/Feature/Auth/SessionExpiryTest.php`

| Step | Action                                  | Expected Result                            |
| ---- | --------------------------------------- | ------------------------------------------ |
| 1    | Authenticate user                       | Session valid                              |
| 2    | Invalidate session                      | —                                          |
| 3    | POST /api/entrance/validate             | 401 response                               |

---

## 3.3 Authorization tests

### TC-AUTHZ-001: Role-based entrance authorization
**SRS Trace**: LENT-SW-AUTHZ-001
**Tool**: Pest Feature
**File**: `tests/Feature/Entrance/EntranceAuthorizationTest.php`

| Step | Action                                  | Expected Result                            |
| ---- | --------------------------------------- | ------------------------------------------ |
| 1    | POST /api/entrance/validate as User     | 200 (allowed)                              |
| 2    | POST /api/entrance/override as User     | 403 (denied — requires Moderator)          |
| 3    | POST /api/entrance/override as Moderator| 200 (allowed)                              |
| 4    | POST /api/entrance/override as Admin    | 200 (allowed)                              |

### TC-AUTHZ-002: Authorization denial UI feedback
**SRS Trace**: LENT-SW-AUTHZ-002
**Tool**: Playwright
**File**: `tests/e2e/authorization.spec.ts`

| Step | Action                                  | Expected Result                            |
| ---- | --------------------------------------- | ------------------------------------------ |
| 1    | Login as User role                      | —                                          |
| 2    | Navigate to entrance                    | Override button not visible                |
| 3    | Login as Moderator role                 | —                                          |
| 4    | Trigger override_possible decision      | Override button visible                    |

---

## 3.4 QR scanning tests

### TC-SCAN-001: QrScanner component camera handling
**SRS Trace**: LENT-SW-SCAN-001
**Tool**: Vitest
**File**: `resources/js/components/entrance/QrScanner.test.ts`
**Dependency**: `vue-qrcode-reader` (`QrcodeStream` component)

| Step | Action                                  | Expected Result                            |
| ---- | --------------------------------------- | ------------------------------------------ |
| 1    | Mount QrScanner, simulate `camera-on` event | Component renders, torch capability detected |
| 2    | Simulate `detect` event with QR payload | `decoded` event emitted with `rawValue`, scanner pauses (freeze-frame) |
| 3    | Call `resume()` exposed method          | Scanner unpauses, ready for next scan      |
| 4    | Simulate `error` event (NotAllowedError)| Error state shown with "Camera permission denied" message |
| 5    | Simulate `error` event (NotFoundError)  | Error state shown with "No camera found" message |
| 6    | Simulate `error` event (InsecureContextError) | Error state shown with HTTPS required message |

### TC-SCAN-003: Token payload validation
**SRS Trace**: LENT-SW-SCAN-003
**Tool**: Pest Feature
**File**: `tests/Feature/Entrance/TokenValidationTest.php`

| Step | Action                                  | Expected Result                            |
| ---- | --------------------------------------- | ------------------------------------------ |
| 1    | POST validate with empty token          | 422, token required                        |
| 2    | POST validate with token > 512 chars    | 422, token max exceeded                    |
| 3    | POST validate with valid token format   | Request forwarded to LanCore               |

### TC-SCAN-005: Manual lookup fallback
**SRS Trace**: LENT-SW-SCAN-005
**Tool**: Pest Feature + Playwright
**File**: `tests/Feature/Entrance/LookupTest.php`, `tests/e2e/lookup.spec.ts`

| Step | Action                                  | Expected Result                            |
| ---- | --------------------------------------- | ------------------------------------------ |
| 1    | GET /api/entrance/lookup?q=test         | Returns matching results from LanCore      |
| 2    | GET /api/entrance/lookup?q=x (1 char)   | 422, min:2 validation error                |
| 3    | (E2E) Type search query                 | Results displayed after debounce           |
| 4    | (E2E) Click result                      | Validate request sent for selected token   |

---

## 3.5 Validation and check-in tests

### TC-CHECKIN-001: Backend-mediated validation
**SRS Trace**: LENT-SW-CHECKIN-001
**Tool**: Pest Feature
**File**: `tests/Feature/Entrance/ValidationTest.php`

| Step | Action                                  | Expected Result                            |
| ---- | --------------------------------------- | ------------------------------------------ |
| 1    | Mock LanCore to return `valid`          | —                                          |
| 2    | POST /api/entrance/validate             | Response decision = "valid"                |
| 3    | Verify LanCore received the request     | Http::assertSent with correct payload      |

### TC-CHECKIN-003: Decision outcome full-screen overlay
**SRS Trace**: LENT-SW-CHECKIN-003, LENT-SW-UI-001
**Tool**: Vitest
**File**: `resources/js/components/entrance/DecisionDisplay.test.ts`

| Step | Action                                  | Expected Result                            |
| ---- | --------------------------------------- | ------------------------------------------ |
| 1    | Render with decision = `valid`          | Green full-screen overlay, CircleCheck icon, "Checked In" |
| 2    | Render with decision = `invalid`        | Red full-screen overlay, CircleX icon, "Entry Denied" |
| 3    | Render with `already_checked_in`        | Orange full-screen overlay, AlertTriangle icon |
| 4    | Render with `denied_by_policy`          | Red full-screen overlay, ShieldX icon      |
| 5    | Render with `override_possible`         | Orange full-screen overlay, override button visible |
| 6    | Render with `verification_required`     | Orange full-screen overlay, ClipboardCheck icon, checklist visible, "Confirm & Check In" button |
| 7    | Verify overlay is `fixed inset-0 z-50`  | Overlay covers full viewport               |

### TC-CHECKIN-004: Response time target
**SRS Trace**: LENT-SW-CHECKIN-004
**Tool**: Pest Feature (ANL)
**File**: `tests/Feature/Entrance/PerformanceTest.php`

| Step | Action                                  | Expected Result                            |
| ---- | --------------------------------------- | ------------------------------------------ |
| 1    | Mock LanCore with 100ms delay           | —                                          |
| 2    | POST /api/entrance/validate             | Response within 2000ms                     |
| 3    | Measure response time                   | < 2000ms for 95% of requests              |

### TC-CHECKIN-005: No premature success display
**SRS Trace**: LENT-SW-CHECKIN-005
**Tool**: Vitest
**File**: `resources/js/composables/useCheckin.test.ts`

| Step | Action                                  | Expected Result                            |
| ---- | --------------------------------------- | ------------------------------------------ |
| 1    | Call validate(), don't resolve promise  | State is loading, not success              |
| 2    | Resolve with valid response             | Only now state shows success               |

---

## 3.6 Group and override tests

### TC-CHECKIN-007: Verification-required flow
**SRS Trace**: LENT-SW-CHECKIN-007, LENT-SW-UI-005
**Tool**: Pest Feature + Vitest
**File**: `tests/Feature/Entrance/VerificationTest.php`, `resources/js/components/entrance/DecisionDisplay.test.ts`

| Step | Action                                  | Expected Result                            |
| ---- | --------------------------------------- | ------------------------------------------ |
| 1    | Mock LanCore to return `verification_required` | —                                   |
| 2    | POST /api/entrance/validate             | Response decision = "verification_required", verification.checks array present |
| 3    | (Vitest) Render with verification data  | Orange overlay, checklist items displayed with labels and instructions |
| 4    | (Vitest) Verify "Confirm & Check In" button | Green button visible on orange background |
| 5    | (Vitest) Verify "Next Scan" is subtle   | Dismiss button has low-contrast styling    |
| 6    | POST /api/entrance/verify-checkin       | Response decision = "valid" with seating + addons |

### TC-CHECKIN-008: Post-check-in seating display
**SRS Trace**: LENT-SW-CHECKIN-008, LENT-SW-UI-002
**Tool**: Vitest
**File**: `resources/js/components/entrance/DecisionDisplay.test.ts`

| Step | Action                                  | Expected Result                            |
| ---- | --------------------------------------- | ------------------------------------------ |
| 1    | Render valid with seating object        | Seating card visible with seat label, area, directions |
| 2    | Render valid without seating            | No seating card rendered                   |
| 3    | Verify seat label is large text         | Seat identifier in bold, >= 24px           |
| 4    | Verify directions text                  | Directions paragraph rendered              |

### TC-CHECKIN-009: Post-check-in addon list display
**SRS Trace**: LENT-SW-CHECKIN-009, LENT-SW-UI-004
**Tool**: Vitest
**File**: `resources/js/components/entrance/DecisionDisplay.test.ts`

| Step | Action                                  | Expected Result                            |
| ---- | --------------------------------------- | ------------------------------------------ |
| 1    | Render valid with addons array (3 items)| Addon list visible with 3 entries          |
| 2    | Verify addon name displayed             | Each addon shows name text                 |
| 3    | Verify addon info displayed             | Pickup instructions shown when present     |
| 4    | Verify addon without info               | Only name shown, no info text              |
| 5    | Render valid without addons             | No addon section rendered                  |
| 6    | Render valid with empty addons array    | No addon section rendered                  |

### TC-PAY-001: Payment-required decision display
**SRS Trace**: LENT-SW-PAY-001
**Tool**: Vitest
**File**: `resources/js/components/entrance/DecisionDisplay.test.ts`

| Step | Action                                  | Expected Result                            |
| ---- | --------------------------------------- | ------------------------------------------ |
| 1    | Render with decision = `payment_required` | Orange overlay, Banknote icon, "Payment Required" |
| 2    | Verify amount displayed                 | "42.00 EUR" in large text                  |
| 3    | Verify item breakdown                   | Each item name + price listed              |
| 4    | Verify payment method buttons           | Cash and Card buttons visible              |
| 5    | Verify confirm button disabled          | Disabled until method selected             |

### TC-PAY-002: Payment method selection and confirmation
**SRS Trace**: LENT-SW-PAY-002
**Tool**: Vitest
**File**: `resources/js/components/entrance/DecisionDisplay.test.ts`

| Step | Action                                  | Expected Result                            |
| ---- | --------------------------------------- | ------------------------------------------ |
| 1    | Click "Cash" method button              | Cash button highlighted, Card not          |
| 2    | Verify confirm button enabled           | "Confirm Payment & Check In" active        |
| 3    | Click "Card" method button              | Card highlighted, Cash not                 |
| 4    | Click confirm button                    | `confirmPayment` event emitted with method |

### TC-PAY-003: Payment confirmation backend flow
**SRS Trace**: LENT-SW-PAY-003
**Tool**: Pest Feature
**File**: `tests/Feature/Entrance/PaymentTest.php`

| Step | Action                                  | Expected Result                            |
| ---- | --------------------------------------- | ------------------------------------------ |
| 1    | Mock LanCore payment confirmation       | —                                          |
| 2    | POST /api/entrance/confirm-payment      | 200, decision = "valid", receipt_sent = true |
| 3    | Verify LanCore received payment_method  | Http::assertSent with payment_method + amount |
| 4    | Verify response includes seating+addons | seating and addons fields present          |

### TC-PAY-004: Payment confirmation validation
**SRS Trace**: LENT-SW-PAY-003
**Tool**: Pest Feature
**File**: `tests/Feature/Entrance/PaymentTest.php`

| Step | Action                                  | Expected Result                            |
| ---- | --------------------------------------- | ------------------------------------------ |
| 1    | POST confirm-payment without method     | 422, payment_method required               |
| 2    | POST confirm-payment with invalid method| 422, payment_method must be cash or card   |
| 3    | POST confirm-payment without amount     | 422, amount required                       |
| 4    | POST confirm-payment with valid data    | Request accepted                           |

### TC-PAY-005: Receipt sent notice
**SRS Trace**: LENT-SW-PAY-004
**Tool**: Vitest
**File**: `resources/js/components/entrance/DecisionDisplay.test.ts`

| Step | Action                                  | Expected Result                            |
| ---- | --------------------------------------- | ------------------------------------------ |
| 1    | Render valid with receipt_sent = true   | "Receipt sent to attendee's email" visible |
| 2    | Render valid with receipt_sent = false  | No receipt notice                          |

### TC-PAY-006: Payment bypass requires Moderator
**SRS Trace**: LENT-SW-PAY-006
**Tool**: Pest Feature + Vitest
**File**: `tests/Feature/Entrance/PaymentTest.php`, `resources/js/components/entrance/DecisionDisplay.test.ts`

| Step | Action                                  | Expected Result                            |
| ---- | --------------------------------------- | ------------------------------------------ |
| 1    | (Vitest) Render payment_required with override_allowed=false | No Override button, no Next Scan |
| 2    | (Vitest) Render payment_required with override_allowed=true  | Override + Next Scan visible      |
| 3    | (Pest) POST override as User for payment ticket | 403 Forbidden                    |
| 4    | (Pest) POST override as Moderator for payment   | 200 Accepted                     |

### TC-GROUP-001: Group policy display
**SRS Trace**: LENT-SW-GROUP-001
**Tool**: Vitest
**File**: `resources/js/components/entrance/DecisionDisplay.test.ts`

| Step | Action                                  | Expected Result                            |
| ---- | --------------------------------------- | ------------------------------------------ |
| 1    | Render with group_policy object         | Policy message displayed                   |
| 2    | Verify member count shown               | "2 of 5 members checked in" visible        |

### TC-GROUP-003: Override submission
**SRS Trace**: LENT-SW-GROUP-002
**Tool**: Pest Feature
**File**: `tests/Feature/Entrance/OverrideTest.php`

| Step | Action                                  | Expected Result                            |
| ---- | --------------------------------------- | ------------------------------------------ |
| 1    | Authenticate as Moderator               | —                                          |
| 2    | Mock LanCore override success           | —                                          |
| 3    | POST /api/entrance/override with reason | 200, decision = valid                      |
| 4    | Verify LanCore received reason          | Http::assertSent with reason field         |

### TC-GROUP-004: Override requires reason
**SRS Trace**: LENT-SW-GROUP-003
**Tool**: Pest Feature
**File**: `tests/Feature/Entrance/OverrideTest.php`

| Step | Action                                  | Expected Result                            |
| ---- | --------------------------------------- | ------------------------------------------ |
| 1    | POST override with empty reason         | 422, reason required                       |
| 2    | POST override with 5-char reason        | 422, reason min:10                         |
| 3    | POST override with valid 20-char reason | Request accepted                           |

---

## 3.7 Audit tests

### TC-AUDIT-001: Audit metadata injection
**SRS Trace**: LENT-SW-AUDIT-001
**Tool**: Pest Feature
**File**: `tests/Feature/Entrance/AuditMetadataTest.php`

| Step | Action                                  | Expected Result                            |
| ---- | --------------------------------------- | ------------------------------------------ |
| 1    | Authenticate as LanCore user            | —                                          |
| 2    | POST /api/entrance/validate             | —                                          |
| 3    | Inspect mocked LanCore request          | Contains operator_id, operator_session, timestamp, client_info |

### TC-AUDIT-002: Audit response integrity
**SRS Trace**: LENT-SW-AUDIT-002
**Tool**: Pest Feature
**File**: `tests/Feature/Entrance/AuditMetadataTest.php`

| Step | Action                                  | Expected Result                            |
| ---- | --------------------------------------- | ------------------------------------------ |
| 1    | Mock LanCore to return audit_id         | —                                          |
| 2    | POST /api/entrance/validate             | Response includes audit_id unchanged       |

---

## 3.8 Degraded mode tests

### TC-DEGRADED-001: Service failure detection
**SRS Trace**: LENT-SW-DEGRADED-001
**Tool**: Pest Feature
**File**: `tests/Feature/Entrance/DegradedModeTest.php`

| Step | Action                                  | Expected Result                            |
| ---- | --------------------------------------- | ------------------------------------------ |
| 1    | Mock LanCore timeout (exceeds 5s)       | Response: degraded=true, error=service_unavailable |
| 2    | Mock LanCore 500 response               | Response: degraded=true                    |
| 3    | Mock LanCore connection refused         | Response: degraded=true                    |
| 4    | Verify no check-in record created       | No side effects on failure                 |

### TC-DEGRADED-002: No local authoritative state
**SRS Trace**: LENT-SW-DEGRADED-002
**Tool**: Pest Feature (INSP)
**File**: `tests/Feature/Entrance/DegradedModeTest.php`

| Step | Action                                  | Expected Result                            |
| ---- | --------------------------------------- | ------------------------------------------ |
| 1    | Mock LanCore failure                    | —                                          |
| 2    | POST /api/entrance/validate             | No database records created                |
| 3    | Inspect response                        | Decision is "error", not "valid"           |

---

## 3.9 Security tests

### TC-SEC-001: Authenticated access enforcement
**SRS Trace**: LENT-SW-SEC-001
**Tool**: Pest Feature
**File**: `tests/Feature/Entrance/EntranceAuthorizationTest.php`

| Step | Action                                  | Expected Result                            |
| ---- | --------------------------------------- | ------------------------------------------ |
| 1    | POST /api/entrance/validate as guest    | 401                                        |
| 2    | GET /entrance as guest                  | 302 redirect to login                      |

### TC-SEC-007: Rate limiting enforcement
**SRS Trace**: LENT-SW-SEC-007
**Tool**: Pest Feature
**File**: `tests/Feature/Entrance/RateLimitTest.php`

| Step | Action                                  | Expected Result                            |
| ---- | --------------------------------------- | ------------------------------------------ |
| 1    | Send requests up to limit               | All return 200                             |
| 2    | Send one more request                   | Returns 429                                |
| 3    | Check Retry-After header                | Header present with valid value            |

---

## 3.10 SSO interface tests

### TC-SSO-001: SSO redirect
**SRS Trace**: LENT-IR-002-001
**Tool**: Pest Feature
**File**: `tests/Feature/Auth/LanCoreSsoTest.php`
**Status**: Implemented

### TC-SSO-002: SSO callback and user creation
**SRS Trace**: LENT-IR-002-002
**Tool**: Pest Feature
**File**: `tests/Feature/Auth/LanCoreSsoTest.php`
**Status**: Implemented

### TC-SSO-003: SSO failure handling
**SRS Trace**: LENT-IR-002-003
**Tool**: Pest Feature
**File**: `tests/Feature/Auth/LanCoreSsoTest.php`
**Status**: Implemented

---

## 3.11 E2E workflow tests

### TC-E2E-001: Full scan-to-checkin workflow
**Tool**: Playwright
**File**: `tests/e2e/entrance.spec.ts`

| Step | Action                                  | Expected Result                            |
| ---- | --------------------------------------- | ------------------------------------------ |
| 1    | Login via SSO (mocked)                  | Dashboard visible                          |
| 2    | Navigate to entrance scanner            | Scanner page loads                         |
| 3    | Simulate QR decode event                | Loading indicator shown                    |
| 4    | Mock valid response                     | Green success screen with attendee name    |
| 5    | Click "Next Scan"                       | Scanner reactivates                        |

### TC-E2E-002: Override workflow
**Tool**: Playwright
**File**: `tests/e2e/override.spec.ts`

| Step | Action                                  | Expected Result                            |
| ---- | --------------------------------------- | ------------------------------------------ |
| 1    | Login as Moderator                      | —                                          |
| 2    | Simulate scan with policy denial        | Amber override_possible screen             |
| 3    | Click "Override"                        | Override modal opens                       |
| 4    | Enter reason (< 10 chars)              | Submit button disabled                     |
| 5    | Enter valid reason                      | Submit button enabled                      |
| 6    | Click submit                            | Green success screen                       |

### TC-E2E-003: Mobile viewport usability
**Tool**: Playwright (iPhone 14 profile)
**File**: `tests/e2e/mobile.spec.ts`

| Step | Action                                  | Expected Result                            |
| ---- | --------------------------------------- | ------------------------------------------ |
| 1    | Load entrance page at 375x812           | No horizontal scroll, all controls visible |
| 2    | Verify touch targets                    | All buttons >= 48x48px                     |
| 3    | Verify decision display                 | Full viewport overlay, large text readable |
| 4    | Verify seating card on green overlay    | Seat label visible, directions readable    |
| 5    | Verify addon list scrolls if long       | Addon section scrollable on small viewport |

### TC-E2E-004: Verification-required workflow
**Tool**: Playwright
**File**: `tests/e2e/verification.spec.ts`

| Step | Action                                  | Expected Result                            |
| ---- | --------------------------------------- | ------------------------------------------ |
| 1    | Login as User                           | —                                          |
| 2    | Simulate scan with verification_required| Orange full-screen overlay appears          |
| 3    | Verify checklist items displayed        | Each verification check label + instruction visible |
| 4    | Tap "Confirm & Check In"               | Request sent to /api/entrance/verify-checkin |
| 5    | Mock valid response with seating+addons | Green overlay replaces orange               |
| 6    | Verify seating info displayed           | Seat, area, directions visible              |
| 7    | Verify addon list displayed             | Addon names and info visible                |
| 8    | Tap "Next Scan"                         | Scanner reactivates                         |

### TC-E2E-005: On-site payment workflow
**Tool**: Playwright
**File**: `tests/e2e/payment.spec.ts`

| Step | Action                                  | Expected Result                            |
| ---- | --------------------------------------- | ------------------------------------------ |
| 1    | Login as User                           | —                                          |
| 2    | Simulate scan with payment_required     | Orange full-screen overlay appears          |
| 3    | Verify amount and items displayed       | "42.00 EUR" visible, item breakdown listed  |
| 4    | Verify confirm button is disabled       | Cannot click without selecting method       |
| 5    | Tap "Cash" method                       | Cash button highlighted                    |
| 6    | Tap "Confirm Payment & Check In"        | Request sent to /api/entrance/confirm-payment |
| 7    | Mock valid response with receipt_sent   | Green overlay appears                       |
| 8    | Verify receipt notice                   | "Receipt sent to attendee's email" visible  |
| 9    | Verify seating info displayed           | Seat, area, directions visible              |
| 10   | Tap "Next Scan"                         | Scanner reactivates                         |

---

# 4. Notes

## 4.1 Test case ID convention

* `TC-{CATEGORY}-{NUMBER}` — corresponds to RTM test case IDs
* `TC-E2E-{NUMBER}` — end-to-end tests not directly tied to a single requirement
* `TC-SSO-{NUMBER}` — SSO-specific interface tests

## 4.2 Implementation priority

Test cases for critical requirements (SSS 3.18) are implemented first:
1. Authentication and authorization (TC-AUTH, TC-AUTHZ, TC-SEC)
2. Validation correctness (TC-CHECKIN)
3. Degraded mode safety (TC-DEGRADED)
4. UI and usability (TC-UI, TC-E2E)
