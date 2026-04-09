LanEntrance Interface Requirements Specification (MIL-STD-498 Adapted)
===

# 1. Scope

## 1.1 Identification

* Title: LanEntrance Interface Requirements Specification
* Short Name: LanEntrance IRS
* System Name: LanEntrance
* Ecosystem: LanSoftware
* Version: Draft v0.1
* Status: Working Draft

This document specifies the interface requirements for LanEntrance, covering all communication boundaries between the system's components and external systems.

## 1.2 System overview

LanEntrance has three interface boundaries as identified in SSS Section 3.3:

1. **LENT-IF-001**: Operator Browser Interface (SPA ↔ LanEntrance Backend)
2. **LENT-IF-002**: SSO Authentication Interface (LanEntrance ↔ LanCore Identity)
3. **LENT-IF-003**: Platform Validation Interface (LanEntrance Backend ↔ LanCore API)

## 1.3 Document overview

This document defines the requirements for each interface boundary. Detailed interface designs (schemas, protocols, payloads) are specified in the Interface Design Description (IDD).

---

# 2. Referenced documents

| ID      | Title                                          | Version    |
| ------- | ---------------------------------------------- | ---------- |
| REF-001 | LanEntrance System/Subsystem Specification     | Draft v0.1 |
| REF-002 | LanEntrance Software Requirements Spec (SRS)   | Draft v0.1 |
| REF-003 | LanEntrance Interface Design Description (IDD) | Draft v0.1 |

---

# 3. Interface requirements

Requirements use the identifier format `LENT-IR-<interface>-<number>`.

## 3.1 LENT-IF-001: Operator Browser Interface

### 3.1.1 Interface identification

| Property         | Value                                                 |
| ---------------- | ----------------------------------------------------- |
| Interface ID     | LENT-IF-001                                           |
| Name             | Operator Browser Interface                            |
| Entities         | Operator Browser (SPA) ↔ LanEntrance Backend          |
| Protocol         | HTTPS (Inertia.js for pages, REST JSON for API)       |
| Direction        | Bidirectional                                         |
| SSS Trace        | SSS 3.3.2                                             |

### 3.1.2 Page rendering interface (Inertia.js)

#### LENT-IR-001-001
**Title**: Inertia page delivery
**SSS Trace**: LENT-3.3.2-001

The backend shall deliver page components to the frontend via Inertia.js protocol. Each page shall receive its required props as server-side data. Pages include:

| Page       | Route              | Description                    |
| ---------- | ------------------ | ------------------------------ |
| Welcome    | `GET /`            | Landing / SSO redirect         |
| Dashboard  | `GET /dashboard`   | Operator dashboard             |
| Scanner    | `GET /entrance`    | QR scan and validation         |
| Lookup     | `GET /entrance/lookup` | Manual attendee lookup     |
| Settings/* | `GET /settings/*`  | Profile, security, appearance  |

**Qualification**: INSP

#### LENT-IR-001-002
**Title**: Shared Inertia props
**SSS Trace**: LENT-3.3.2-001

The backend shall provide the following shared props on every Inertia response via `HandleInertiaRequests` middleware:

* `auth.user`: Authenticated user object (or null)
* `name`: Application name
* `sidebarOpen`: Sidebar state from cookie

**Status**: Implemented
**Qualification**: INSP

### 3.1.3 Entrance API interface (REST)

#### LENT-IR-001-003
**Title**: Entrance API namespace
**SSS Trace**: LENT-3.4-001

All entrance operational API endpoints shall be under the `/api/entrance/` namespace. Endpoints shall accept and return JSON.

**Qualification**: INSP

#### LENT-IR-001-004
**Title**: Validation request interface
**SSS Trace**: LENT-3.3.2-001, LENT-3.2.4-001

The frontend shall submit validation requests via `POST /api/entrance/validate` containing the scanned or entered token payload. The backend shall return a structured JSON response with the decision outcome.

**Qualification**: TST

#### LENT-IR-001-005
**Title**: Check-in confirmation interface
**SSS Trace**: LENT-3.2.4-001

The frontend shall confirm check-ins via `POST /api/entrance/checkin` after receiving a `valid` decision. The backend shall forward the confirmation to LanCore and return the authoritative result.

**Qualification**: TST

#### LENT-IR-001-006
**Title**: Override request interface
**SSS Trace**: LENT-3.2.5-003

The frontend shall submit override requests via `POST /api/entrance/override` containing the override reason and original validation context. The backend shall enforce authorization (Moderator+) before forwarding.

**Qualification**: TST

#### LENT-IR-001-007
**Title**: Lookup interface
**SSS Trace**: LENT-3.2.3-005

The frontend shall query for attendees via `GET /api/entrance/lookup?q={query}`. The backend shall forward the search to LanCore and return matching results.

**Qualification**: TST

#### LENT-IR-001-008
**Title**: Mobile-optimized UI delivery
**SSS Trace**: LENT-3.3.2-001, LENT-3.3.2-004

The frontend interface shall be optimized for smartphone portrait orientation with large touch targets and high-contrast decision indicators. The interface shall remain functional on desktop browsers.

**Qualification**: DEM, TST

#### LENT-IR-001-009
**Title**: Camera permission handling
**SSS Trace**: LENT-3.3.2-002

The frontend shall handle camera permission states (granted, denied, prompt, unavailable) and display appropriate messaging for each state. Camera denial shall not prevent access to manual lookup.

**Qualification**: DEM, TST

#### LENT-IR-001-010
**Title**: Manual controls without camera
**SSS Trace**: LENT-3.3.2-003

The frontend shall provide full manual lookup and action controls that do not depend on camera access.

**Qualification**: DEM, TST

#### LENT-IR-001-012
**Title**: Verification confirmation interface
**SSS Trace**: LENT-3.2.4-007

The frontend shall confirm operator-completed verification via `POST /api/entrance/verify-checkin` containing the token and validation ID. This endpoint is called when the operator taps "Confirm & Check In" after completing manual verification checks (e.g., student ID, age). The backend shall forward the confirmation to LanCore and return the authoritative check-in result including seating and addons.

**Qualification**: TST

#### LENT-IR-001-013
**Title**: Payment confirmation interface
**SSS Trace**: LENT-3.2.9-002, LENT-3.2.9-003

The frontend shall submit payment confirmation via `POST /api/entrance/confirm-payment` containing the token, validation ID, selected payment method, and confirmed amount. The backend shall forward the confirmation to LanCore, which records the payment, generates a PDF receipt, and triggers email delivery to the attendee. The backend shall return the authoritative check-in result including seating, addons, and a `receipt_sent` flag.

**Qualification**: TST

### 3.1.4 Error response interface

#### LENT-IR-001-011
**Title**: Standardized error responses
**SSS Trace**: LENT-3.3.4-004

All entrance API endpoints shall return errors in a consistent JSON format:

```json
{
  "error": "<error_code>",
  "message": "<human_readable_message>",
  "degraded": false
}
```

HTTP status codes shall follow RFC 9110 semantics (400 for client errors, 403 for authorization, 422 for validation, 429 for rate limit, 503 for degraded).

**Qualification**: TST

---

## 3.2 LENT-IF-002: SSO Authentication Interface

### 3.2.1 Interface identification

| Property         | Value                                                |
| ---------------- | ---------------------------------------------------- |
| Interface ID     | LENT-IF-002                                          |
| Name             | SSO Authentication Interface                         |
| Entities         | LanEntrance Backend ↔ LanCore Identity / SSO         |
| Protocol         | HTTPS (OAuth 2.0 / OIDC-like flow)                  |
| Direction        | Bidirectional (redirect-based)                       |
| SSS Trace        | SSS 3.3.3                                            |
| Status           | Implemented                                          |

### 3.2.2 SSO flow requirements

#### LENT-IR-002-001
**Title**: SSO authorization redirect
**SSS Trace**: LENT-3.3.3-001

The backend shall redirect unauthenticated operators to the LanCore SSO authorization URL constructed by `LanCoreClient::ssoAuthorizeUrl()`. The URL shall include the `app_slug` and `callback_url` parameters.

**Status**: Implemented in `LanCoreAuthController::redirect()`
**Qualification**: INSP, TST

#### LENT-IR-002-002
**Title**: SSO callback handling
**SSS Trace**: LENT-3.3.3-002

The backend shall handle the SSO callback at `/auth/callback` by:

1. Extracting the authorization `code` from query parameters
2. Exchanging the code via `LanCoreClient::exchangeCode()`
3. Receiving user data: `{id, username, email, roles}`
4. Creating/updating local user via `UserSyncService`
5. Synchronizing roles via `SyncUserRolesFromLanCore`
6. Establishing an authenticated session

**Status**: Implemented in `LanCoreAuthController::callback()`
**Qualification**: TST

#### LENT-IR-002-003
**Title**: SSO failure handling
**SSS Trace**: LENT-3.3.3-003

When SSO authentication fails (missing code, exchange failure, LanCore error), the backend shall redirect to the welcome page with an error indicator. No session shall be created.

**Status**: Implemented
**Qualification**: TST

### 3.2.3 Role synchronization interface

#### LENT-IR-002-004
**Title**: Role webhook interface
**SSS Trace**: LENT-3.2.2-001

The backend shall accept role update webhooks from LanCore at `POST /api/webhooks/roles`. The webhook payload shall contain:

* `user.id`: LanCore user ID
* `user.roles`: Array of role strings

The webhook shall be authenticated via HMAC-SHA256 signature in the `X-Webhook-Signature` header using the `LANCORE_WEBHOOK_SECRET`.

**Status**: Implemented in `LanCoreRolesWebhookController`
**Qualification**: TST

---

## 3.3 LENT-IF-003: Platform Validation Interface

### 3.3.1 Interface identification

| Property         | Value                                                |
| ---------------- | ---------------------------------------------------- |
| Interface ID     | LENT-IF-003                                          |
| Name             | Platform Validation Interface                        |
| Entities         | LanEntrance Backend ↔ LanCore API                    |
| Protocol         | HTTPS REST (JSON)                                    |
| Direction        | Bidirectional (request/response)                     |
| SSS Trace        | SSS 3.3.4                                            |
| Authentication   | Bearer token (`LANCORE_TOKEN`)                       |

### 3.3.2 Transport requirements

#### LENT-IR-003-001
**Title**: Authenticated HTTPS transport
**SSS Trace**: LENT-3.3.4-001

All backend-to-LanCore API requests shall use HTTPS with bearer token authentication via the `LANCORE_TOKEN` configuration. The `LanCoreClient::http()` method shall enforce this.

**Status**: Partially implemented (transport exists, validation endpoints pending)
**Qualification**: INSP, TST

#### LENT-IR-003-002
**Title**: Internal URL support
**SSS Trace**: LENT-3.3.4-001

The backend shall support a separate internal URL (`LANCORE_INTERNAL_URL`) for service-to-service communication within the Docker network, while using `LANCORE_BASE_URL` for browser-facing redirects.

**Status**: Implemented in `config/lancore.php`
**Qualification**: INSP

### 3.3.3 Validation request requirements

#### LENT-IR-003-003
**Title**: Ticket validation request
**SSS Trace**: LENT-3.3.4-002

The backend shall submit ticket validation requests to LanCore containing:

* `token`: The scanned or entered ticket token
* `operator_id`: The operator's `lancore_user_id`
* `session_id`: Current session identifier
* `timestamp`: Server-side UTC timestamp
* `client_info`: Operator's User-Agent (when available)

**Qualification**: INSP, TST

#### LENT-IR-003-004
**Title**: Validation response handling
**SSS Trace**: LENT-3.3.4-003

The backend shall accept LanCore validation responses containing:

* `decision`: One of `valid`, `invalid`, `already_checked_in`, `denied_by_policy`, `override_possible`, `verification_required`, `payment_required`
* `message`: Human-readable guidance text
* `validation_id`: Correlation ID for subsequent check-in/override/verify-checkin/confirm-payment requests
* `attendee`: Authorized attendee context (name, group — as permitted)
* `seating`: Seat assignment with area and navigational directions (see LENT-IR-003-010)
* `addons`: List of purchased ticket extras (see LENT-IR-003-011)
* `verification`: Verification requirements for `verification_required` decisions (see LENT-IR-003-012)
* `payment`: Outstanding payment details for `payment_required` decisions (see LENT-IR-003-013)
* `audit_id`: Correlation ID for the audit record
* `override_allowed`: Boolean indicating if staff override is available
* `group_policy`: Group restriction details (when applicable)

**Qualification**: TST

### 3.3.4 Check-in request requirements

#### LENT-IR-003-005
**Title**: Check-in confirmation request
**SSS Trace**: LENT-3.3.4-002

The backend shall submit check-in confirmation requests to LanCore containing:

* `token`: The validated ticket token
* `operator_id`: The operator's `lancore_user_id`
* `validation_id`: The ID returned from the prior validation response
* `timestamp`: Server-side UTC timestamp

**Qualification**: INSP, TST

#### LENT-IR-003-006
**Title**: Override request
**SSS Trace**: LENT-3.3.4-002

The backend shall submit override requests to LanCore containing:

* `token`: The ticket token
* `operator_id`: The operator's `lancore_user_id`
* `reason`: The operator-provided override reason
* `validation_id`: The ID from the prior validation
* `timestamp`: Server-side UTC timestamp

**Qualification**: INSP, TST

### 3.3.4b Response data structure requirements

#### LENT-IR-003-010
**Title**: Seating information structure
**SSS Trace**: LENT-3.2.4-008, LENT-3.2.6-002

LanCore validation and check-in responses shall include a `seating` object when the attendee has an assigned seat. The object shall contain:

* `seat`: Seat identifier/label (required)
* `area`: Area, hall, or zone designation (optional)
* `directions`: Human-readable navigational text from entrance to seat (optional)

The `seating` object is nullable — it is omitted when no seating assignment exists. When present, it may appear on any decision type but is primarily displayed on the green success overlay after check-in.

**Qualification**: TST

#### LENT-IR-003-011
**Title**: Addon list structure
**SSS Trace**: LENT-3.2.4-009, LENT-3.2.6-004

LanCore validation and check-in responses shall include an `addons` array when the ticket includes purchased extras. Each entry shall contain:

* `name`: Addon display name (required)
* `info`: Pickup or redemption instructions (optional)

The `addons` array is nullable — it is omitted or empty when no addons exist. When present, it may appear on any decision type but is primarily displayed on the green success overlay after check-in.

**Qualification**: TST

#### LENT-IR-003-012
**Title**: Verification requirements structure
**SSS Trace**: LENT-3.2.4-007

LanCore validation responses with decision `verification_required` shall include a `verification` object containing:

* `message`: Summary message for the operator (required)
* `checks`: Array of verification actions, each with a `label` (required) and optional `instruction`

The `verification` object is only present when the decision is `verification_required`.

**Qualification**: TST

#### LENT-IR-003-013
**Title**: Payment details structure
**SSS Trace**: LENT-3.2.9-001

LanCore validation responses with decision `payment_required` shall include a `payment` object containing:

* `amount`: Total amount due (decimal string, e.g., "42.00")
* `currency`: Currency code (e.g., "EUR")
* `items`: Array of payable items, each with `name` (required) and `price` (required, decimal string)
* `methods`: Array of accepted on-site payment method identifiers (e.g., `["cash", "card"]`)

The `payment` object is only present when the decision is `payment_required`.

**Qualification**: TST

### 3.3.5 Error handling requirements

#### LENT-IR-003-007
**Title**: LanCore error handling
**SSS Trace**: LENT-3.3.4-004

The backend shall handle the following LanCore error conditions:

| Condition              | LanCore Response   | Backend Behavior                           |
| ---------------------- | ------------------ | ------------------------------------------ |
| Invalid token          | HTTP 404           | Return `invalid` decision to frontend      |
| Unauthorized           | HTTP 401/403       | Log error, return service error to frontend |
| Validation error       | HTTP 422           | Forward validation details to frontend     |
| Rate limited           | HTTP 429           | Return rate limit error to frontend        |
| Server error           | HTTP 500           | Return degraded indicator to frontend      |
| Connection failure     | Timeout/refused    | Return degraded indicator to frontend      |

**Qualification**: TST

### 3.3.6 Resilience requirements

#### LENT-IR-003-008
**Title**: Request timeout and retry
**SSS Trace**: LENT-3.3.4-001

The `LanCoreClient` shall enforce configurable timeout (`lancore.timeout`, default 5s) and retry (`lancore.retries`, default 2) with delay (`lancore.retry_delay`, default 100ms) for LanCore API calls.

**Status**: Implemented in `LanCoreClient::http()`
**Qualification**: TST, ANL

#### LENT-IR-003-009
**Title**: Lookup search interface
**SSS Trace**: LENT-3.3.4-002

The backend shall forward manual lookup queries to LanCore's search endpoint and return matching attendee records (filtered to authorized fields) to the frontend.

**Qualification**: TST

---

# 4. Qualification provisions

Interface qualification follows SRS Section 4. Interface-specific qualification:

* SSO flow: Validated by existing `LanCoreSsoTest` test suite
* Webhook interface: Validated by existing `LanCoreRolesWebhookTest` test suite
* Entrance API: To be validated by new Pest feature tests
* LanCore validation API: To be validated by Pest feature tests with HTTP mocking
* Browser interface: To be validated by Playwright E2E tests

---

# 5. Requirements traceability

| IRS Requirement     | SSS Requirement(s)        | SRS Requirement(s)      |
| ------------------- | ------------------------- | ----------------------- |
| LENT-IR-001-001     | LENT-3.3.2-001            | LENT-SW-STATE-001       |
| LENT-IR-001-002     | LENT-3.3.2-001            | LENT-SW-AUTH-001        |
| LENT-IR-001-003     | LENT-3.4-001              | LENT-SW-INT-001         |
| LENT-IR-001-004     | LENT-3.3.2-001, 3.2.4-001| LENT-SW-CHECKIN-001     |
| LENT-IR-001-005     | LENT-3.2.4-001            | LENT-SW-CHECKIN-001     |
| LENT-IR-001-006     | LENT-3.2.5-003            | LENT-SW-GROUP-002       |
| LENT-IR-001-007     | LENT-3.2.3-005            | LENT-SW-SCAN-005        |
| LENT-IR-001-008     | LENT-3.3.2-001, 3.3.2-004| LENT-SW-AUTH-004        |
| LENT-IR-001-009     | LENT-3.3.2-002            | LENT-SW-SCAN-001        |
| LENT-IR-001-010     | LENT-3.3.2-003            | LENT-SW-SCAN-005        |
| LENT-IR-001-011     | LENT-3.3.4-004            | LENT-SW-DEGRADED-001    |
| LENT-IR-002-001     | LENT-3.3.3-001            | LENT-SW-AUTH-001        |
| LENT-IR-002-002     | LENT-3.3.3-002            | LENT-SW-AUTH-001        |
| LENT-IR-002-003     | LENT-3.3.3-003            | LENT-SW-AUTH-003        |
| LENT-IR-002-004     | LENT-3.2.2-001            | LENT-SW-AUTHZ-001      |
| LENT-IR-003-001     | LENT-3.3.4-001            | LENT-SW-SEC-003         |
| LENT-IR-003-002     | LENT-3.3.4-001            | LENT-SW-ADAPT-001       |
| LENT-IR-003-003     | LENT-3.3.4-002            | LENT-SW-AUDIT-001       |
| LENT-IR-003-004     | LENT-3.3.4-003            | LENT-SW-CHECKIN-003     |
| LENT-IR-003-005     | LENT-3.3.4-002            | LENT-SW-CHECKIN-001     |
| LENT-IR-003-006     | LENT-3.3.4-002            | LENT-SW-GROUP-002       |
| LENT-IR-003-007     | LENT-3.3.4-004            | LENT-SW-DEGRADED-001    |
| LENT-IR-003-008     | LENT-3.3.4-001            | LENT-SW-ADAPT-001       |
| LENT-IR-003-009     | LENT-3.3.4-002            | LENT-SW-SCAN-005        |
| LENT-IR-003-010     | LENT-3.2.4-008, 3.2.6-002| LENT-SW-CHECKIN-008     |
| LENT-IR-003-011     | LENT-3.2.4-009, 3.2.6-004| LENT-SW-CHECKIN-009     |
| LENT-IR-003-012     | LENT-3.2.4-007            | LENT-SW-CHECKIN-007     |
| LENT-IR-001-012     | LENT-3.2.4-007            | LENT-SW-CHECKIN-007     |
| LENT-IR-001-013     | LENT-3.2.9-002, 3.2.9-003| LENT-SW-PAY-002, PAY-003|
| LENT-IR-003-013     | LENT-3.2.9-001            | LENT-SW-PAY-001         |

---

# 6. Notes

## 6.1 Acronyms

* BFF: Backend for Frontend
* HMAC: Hash-based Message Authentication Code
* OIDC: OpenID Connect
* REST: Representational State Transfer

## 6.2 LanCore API contract dependency

The exact LanCore API endpoints for ticket validation, check-in, and search are defined by LanCore. This IRS specifies the requirements from LanEntrance's perspective. The IDD shall document the concrete endpoint contracts once LanCore API documentation is finalized.
