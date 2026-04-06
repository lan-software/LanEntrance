LanEntrance Software Test Plan (MIL-STD-498 Adapted)
===

# 1. Scope

## 1.1 Identification

* Title: LanEntrance Software Test Plan
* Short Name: LanEntrance STP
* System Name: LanEntrance
* Ecosystem: LanSoftware
* Version: Draft v0.1
* Status: Working Draft

This document defines the test strategy, test levels, test environments, and qualification approach for LanEntrance.

## 1.2 System overview

See SSS Section 1.2. LanEntrance consists of two CSCIs (Frontend and Backend) requiring testing at unit, integration, and system levels.

## 1.3 Document overview

This document covers test organization, test levels, test environment, tools, and the mapping from SSS qualification methods to concrete testing activities. Individual test case descriptions are in the STD. Test execution results are recorded in the STR.

---

# 2. Referenced documents

| ID      | Title                | Version    |
| ------- | -------------------- | ---------- |
| REF-001 | LanEntrance SSS      | Draft v0.1 |
| REF-002 | LanEntrance SRS      | Draft v0.1 |
| REF-003 | LanEntrance SDD      | Draft v0.1 |
| REF-004 | LanEntrance STD      | Draft v0.1 |
| REF-005 | LanEntrance RTM      | Draft v0.1 |

---

# 3. Software test environment

## 3.1 Software items

| Item              | Version     | Purpose                              |
| ----------------- | ----------- | ------------------------------------ |
| PHP               | 8.5         | Backend runtime                      |
| Node.js           | 22+         | Frontend build and test runtime      |
| Pest              | 4.4         | PHP test framework                   |
| Vitest            | 4.1         | Vue component test framework         |
| Playwright        | 1.52        | Browser E2E test framework           |
| Vue Test Utils    | 2.4         | Vue component test utilities         |
| Laravel Sail      | —           | Docker development environment       |
| SQLite            | —           | Unit/feature test database           |
| MySQL             | 8           | Integration test database            |

## 3.2 Hardware and network

| Environment     | Hardware                      | Network                            |
| --------------- | ----------------------------- | ---------------------------------- |
| CI (GitHub)     | GitHub-hosted runners (Linux) | Standard internet                  |
| Local dev       | Developer workstation         | Docker `lanparty` network          |
| Device testing  | Physical smartphones/laptops  | Event-simulated WiFi (when avail.) |

## 3.3 Test databases

| Level               | Database | Rationale                                        |
| -------------------- | -------- | ------------------------------------------------ |
| Unit tests           | SQLite   | Fast, in-memory, no server dependency            |
| Feature tests        | SQLite   | Sufficient for HTTP/controller testing           |
| Integration tests    | MySQL    | Matches production; tests DB-specific behavior   |
| E2E tests            | MySQL    | Full application stack                           |

---

# 4. Test levels and approach

## 4.1 Unit tests

**Scope**: Individual classes, methods, and functions in isolation.
**CSCI**: LENT-BE (Pest), LENT-FE (Vitest)

### Backend unit tests (Pest)

| Target                      | Test Directory              | Isolation Strategy     |
| --------------------------- | --------------------------- | ---------------------- |
| DTOs (ValidationResponse)   | `tests/Unit/DTOs/`         | Direct instantiation   |
| Actions (SyncUserRoles)     | `tests/Unit/Actions/`      | Mocked dependencies    |
| Enums (UserRole)            | `tests/Unit/Enums/`        | Direct assertion       |

### Frontend unit tests (Vitest)

| Target                     | Test Location                            | Isolation Strategy     |
| -------------------------- | ---------------------------------------- | ---------------------- |
| useEntranceState           | `resources/js/composables/*.test.ts`    | Direct instantiation   |
| useCheckin                 | `resources/js/composables/*.test.ts`    | Mocked fetch/axios     |

Note: Camera management and QR decoding are handled internally by `vue-qrcode-reader`'s `QrcodeStream` component. The `QrScanner.vue` wrapper is tested at the component level (Section 4.3), not as a composable.

## 4.2 Feature / integration tests

**Scope**: HTTP endpoint behavior, controller-service integration, middleware enforcement.
**CSCI**: LENT-BE (Pest feature tests)

| Target                        | Test Directory                   | Strategy                         |
| ----------------------------- | -------------------------------- | -------------------------------- |
| SSO auth flow                 | `tests/Feature/Auth/`           | HTTP mocking of LanCore          |
| Role webhook                  | `tests/Feature/Auth/`           | HMAC signature generation        |
| Entrance validate endpoint    | `tests/Feature/Entrance/`       | HTTP mocking of LanCore          |
| Entrance checkin endpoint     | `tests/Feature/Entrance/`       | HTTP mocking of LanCore          |
| Entrance override endpoint    | `tests/Feature/Entrance/`       | HTTP mocking + role assertion    |
| Entrance lookup endpoint      | `tests/Feature/Entrance/`       | HTTP mocking of LanCore          |
| Payment confirmation endpoint | `tests/Feature/Entrance/`       | HTTP mocking, amount validation  |
| Rate limiting                 | `tests/Feature/Entrance/`       | Repeated requests                |
| Authorization middleware      | `tests/Feature/Entrance/`       | Role-based user factories        |

**LanCore mocking strategy**: Use Laravel's `Http::fake()` to mock LanCore API responses. Test both success and failure scenarios.

## 4.3 Component tests

**Scope**: Vue component rendering and interaction behavior.
**CSCI**: LENT-FE (Vitest + Vue Test Utils)

| Target                  | Strategy                                    |
| ----------------------- | ------------------------------------------- |
| QrScanner.vue           | Mock `QrcodeStream` events (`detect`, `error`, `camera-on`), verify emits and pause/resume behavior |
| DecisionDisplay.vue     | Render with each decision type, verify UI   |
| OverrideModal.vue       | Render, fill form, verify validation/submit |
| DegradedBanner.vue      | Render, verify accessibility attributes     |
| LookupForm.vue          | Input, debounce, verify API call            |

## 4.4 End-to-end tests

**Scope**: Full user workflows through the browser.
**CSCI**: Both (Playwright)

| Scenario                   | Test File                       | Strategy                         |
| -------------------------- | ------------------------------- | -------------------------------- |
| SSO login flow             | `tests/e2e/auth.spec.ts`       | Mock SSO provider or test server |
| QR scan → validate → result| `tests/e2e/entrance.spec.ts`   | Simulated QR decode event        |
| Manual lookup workflow     | `tests/e2e/lookup.spec.ts`     | Text input → search → select    |
| Override flow              | `tests/e2e/override.spec.ts`   | Mock policy denial + override   |
| Degraded mode display      | `tests/e2e/degraded.spec.ts`   | Mock LanCore timeout             |
| Mobile viewport            | `tests/e2e/mobile.spec.ts`     | Playwright mobile device config  |

**Playwright device profiles**:
* iPhone 14 (375x812, touch, portrait)
* Pixel 7 (412x915, touch, portrait)
* Desktop Chrome (1280x800)

---

# 5. Qualification method mapping

SSS Section 4 defines four qualification methods. This section maps each to concrete testing activities.

## 5.1 Demonstration (DEM)

| Activity              | Tool        | Scope                                    |
| --------------------- | ----------- | ---------------------------------------- |
| Playwright E2E tests  | Playwright  | Automated browser workflows              |
| Manual device testing | Physical    | Camera scan on real smartphones          |
| UI review             | Visual      | Decision state visibility, touch targets |

**Applicable to**: UI behavior, operator flows, camera handling, mobile usability

## 5.2 Test (TST)

| Activity              | Tool        | Scope                                    |
| --------------------- | ----------- | ---------------------------------------- |
| Pest feature tests    | Pest        | Backend endpoints, middleware, services   |
| Pest unit tests       | Pest        | DTOs, actions, enums                     |
| Vitest component tests| Vitest      | Vue composables and components           |
| Playwright E2E tests  | Playwright  | Full workflow validation                 |

**Applicable to**: Functional requirements, security enforcement, API behavior, state transitions

## 5.3 Analysis (ANL)

| Activity              | Tool / Method  | Scope                                 |
| --------------------- | -------------- | ------------------------------------- |
| Code review           | GitHub PR      | Architecture adherence                |
| Performance analysis  | Browser DevTools, load testing | Response time targets    |
| Bundle analysis       | Vite build     | Frontend asset size                   |

**Applicable to**: Performance, resource utilization, degraded mode behavior, usability

## 5.4 Inspection (INSP)

| Activity              | Tool / Method  | Scope                                 |
| --------------------- | -------------- | ------------------------------------- |
| PR code review        | GitHub         | Code quality, separation of concerns  |
| Document review       | Manual         | MIL-STD-498 document completeness     |
| Architecture review   | Manual         | BFF pattern compliance                |
| Security review       | Manual + tools | OWASP checks, dependency audit        |

**Applicable to**: Architectural constraints, documentation, code structure, security posture

---

# 6. CI/CD test integration

## 6.1 Existing CI workflows

| Workflow              | File                            | Tests Run                        |
| --------------------- | ------------------------------- | -------------------------------- |
| PHP tests             | `.github/workflows/tests.yml`  | Pest (unit + feature)            |
| Frontend tests        | `.github/workflows/frontend-tests.yml` | Vitest + Playwright     |
| Linting               | `.github/workflows/lint.yml`   | Pint + ESLint + Prettier         |

## 6.2 CI test matrix

| Trigger           | PHP Tests | Frontend Tests | E2E Tests | Lint |
| ----------------- | --------- | -------------- | --------- | ---- |
| Push to main      | Yes       | Yes            | Yes       | Yes  |
| Push to develop   | Yes       | Yes            | Yes       | Yes  |
| Pull request      | Yes       | Yes            | Yes       | Yes  |

## 6.3 Coverage reporting

* Backend coverage: Pest `--coverage` → Codecov
* Frontend coverage: Vitest `--coverage` → Codecov
* E2E coverage: Playwright (no coverage — behavioral only)

## 6.4 Test gates

All CI checks must pass before PR merge. No exceptions for test failures.

---

# 7. Test data management

## 7.1 User factories

Existing `UserFactory` provides:
* Standard user: `User::factory()->create()`
* Unverified user: `User::factory()->unverified()->create()`
* 2FA user: `User::factory()->withTwoFactor()->create()`
* LanCore user: `User::factory()->lanCoreUser()->create()`

Phase 2 additions needed:
* Role-specific: `User::factory()->role(UserRole::Moderator)->create()`

## 7.2 LanCore mock responses

Standardized mock response fixtures for:
* Valid ticket validation
* Invalid ticket
* Already checked-in ticket
* Policy-denied ticket (with group policy)
* Override-possible ticket
* LanCore 500 error
* LanCore timeout

These will be stored as JSON fixtures in `tests/Fixtures/` or defined inline via `Http::fake()`.

---

# 8. Risk-based testing priorities

| Priority | Area                          | Risk                                     | Test Focus                |
| -------- | ----------------------------- | ---------------------------------------- | ------------------------- |
| Critical | Validation correctness        | False successful check-in                | Exhaustive decision tests |
| Critical | Authorization enforcement     | Unauthorized override                    | Role boundary tests       |
| Critical | Audit metadata                | Missing operator identity in audit       | Metadata injection tests  |
| High     | Degraded mode                 | Silent failure                           | Timeout/error scenarios   |
| High     | QR scanning                   | Malformed payload injection              | Payload validation tests  |
| Medium   | Rate limiting                 | Abuse / denial of service                | Throttle enforcement      |
| Medium   | Mobile usability              | Unusable on small screens                | Viewport E2E tests        |
| Low      | Manual lookup                 | Slow search                              | Response time tests       |

---

# 9. Notes

## 9.1 Test naming conventions

* Pest: `it('returns valid decision for valid ticket')` — descriptive sentence
* Vitest: `describe('DecisionDisplay') > it('shows green for valid decision')`
* Playwright: `test('operator can scan and check in attendee')`

## 9.2 Test independence

All tests shall be independent and not rely on execution order. Each test creates its own data fixtures and cleans up via database transactions (Pest `RefreshDatabase` trait) or test isolation (Vitest/Playwright).
