LanEntrance Software Requirements Specification (MIL-STD-498 Adapted)
===

# 1. Scope

## 1.1 Identification

* Title: LanEntrance Software Requirements Specification
* Short Name: LanEntrance SRS
* System Name: LanEntrance
* Ecosystem: LanSoftware
* Version: Draft v0.1
* Status: Working Draft

This document specifies the software requirements for LanEntrance, derived from the System/Subsystem Specification (SSS).

## 1.2 System overview

LanEntrance consists of two Computer Software Configuration Items (CSCIs):

* **LanEntrance-Frontend (LENT-FE)**: Vue 3 SPA delivered via Inertia.js, providing scanner, lookup, decision display, and settings interfaces
* **LanEntrance-Backend (LENT-BE)**: Laravel 13 backend service providing API orchestration, LanCore integration, authentication, and session management

See SSS Section 1.2 for full system overview.

## 1.3 Document overview

This document translates SSS-level requirements (LENT-3.x-xxx) into software-level requirements (LENT-SW-xxx) allocated to specific CSCIs. Each requirement is traceable to its SSS origin.

**LanCore API dependency**: Requirements LENT-SW-CHECKIN-003 through LENT-SW-CHECKIN-009 assume specific response structures from LanCore (decision types, seating, addons, verification objects). These structures are documented in the IDD (Sections 3.2 and 5.2) as expected contracts pending confirmation from the LanCore team. See SDP Section 6 for the LanCore API contract finalization milestone.

---

# 2. Referenced documents

| ID      | Title                                          | Version           |
| ------- | ---------------------------------------------- | ----------------- |
| REF-001 | LanEntrance Operational Concept Document (OCD) | Draft v0.2        |
| REF-002 | LanEntrance System/Subsystem Specification     | Draft v0.1        |
| REF-003 | LanEntrance Software Development Plan (SDP)    | Draft v0.1        |
| REF-004 | LanEntrance Interface Requirements Spec (IRS)  | Draft v0.1        |

---

# 3. Requirements

Requirements use the identifier format `LENT-SW-<category>-<number>`. Each requirement identifies:

* **CSCI allocation**: LENT-FE, LENT-BE, or Both
* **SSS trace**: Originating SSS requirement(s)
* **Qualification**: DEM, TST, ANL, INSP

## 3.1 Required states and modes

### LENT-SW-STATE-001
**Title**: Application state machine implementation
**CSCI**: Both
**SSS Trace**: LENT-3.1-001

The frontend shall implement a UI state machine with the following states:

| State              | Description                                              | Entry Condition                      |
| ------------------ | -------------------------------------------------------- | ------------------------------------ |
| `IDLE`             | App loaded, no active operation                          | Initial load, after DECISION_DISPLAY |
| `READY`            | Authenticated, scanner/lookup ready                      | Successful auth + authorization      |
| `ACTIVE_SCAN`      | Camera feed active, scanning for QR                      | User activates scanner               |
| `ACTIVE_LOOKUP`    | Manual lookup form submitted, awaiting response          | User submits lookup query            |
| `DECISION_DISPLAY` | Validation result displayed to operator                  | Response received from backend       |
| `DEGRADED`         | Orthogonal state: backend/LanCore unreachable            | Request timeout or error             |
| `MAINTENANCE`      | Admin mode for non-event operations                      | Configured via backend               |

**Qualification**: TST, INSP

### LENT-SW-STATE-002
**Title**: Ready state requires authentication and authorization
**CSCI**: Both
**SSS Trace**: LENT-3.1-002

The backend shall only serve entrance-operational pages and API responses to authenticated users with an authorized role. The frontend shall not render operational UI elements until the backend confirms authorization.

**Qualification**: TST

### LENT-SW-STATE-003
**Title**: Degraded state detection
**CSCI**: Both
**SSS Trace**: LENT-3.1-003

The backend shall return a specific error response (HTTP 503 with `degraded: true`) when LanCore API calls fail or exceed the configured timeout (`lancore.timeout`). The frontend shall transition to DEGRADED state upon receiving this response.

**Qualification**: TST, ANL

### LENT-SW-STATE-004
**Title**: Degraded state operator notification
**CSCI**: LENT-FE
**SSS Trace**: LENT-3.1-004

The frontend shall display a prominent, persistent banner in DEGRADED state indicating reduced capabilities. Pending operations shall be visually distinguished from completed operations.

**Qualification**: DEM, TST

---

## 3.2 Software capability requirements

### 3.2.1 Authentication and session management

#### LENT-SW-AUTH-001
**Title**: LanCore SSO authentication
**CSCI**: LENT-BE
**SSS Trace**: LENT-3.2.1-001

The backend shall authenticate operators via LanCore SSO using the existing `LanCoreAuthController` flow:

1. Redirect to `LanCoreClient::ssoAuthorizeUrl()`
2. Handle callback with authorization code
3. Exchange code via `LanCoreClient::exchangeCode()`
4. Synchronize user via `UserSyncService::resolveFromLanCore()`
5. Synchronize roles via `SyncUserRolesFromLanCore::handle()`
6. Establish authenticated Laravel session

**Status**: Implemented
**Qualification**: TST

#### LENT-SW-AUTH-002
**Title**: Operator identity association
**CSCI**: LENT-BE
**SSS Trace**: LENT-3.2.1-002

The backend shall include the authenticated user's `id` and `lancore_user_id` in all operational requests forwarded to LanCore.

**Qualification**: TST, INSP

#### LENT-SW-AUTH-003
**Title**: Session expiry handling
**CSCI**: Both
**SSS Trace**: LENT-3.2.1-003

The backend shall invalidate the Laravel session upon expiry or explicit logout. The frontend shall detect 401/419 responses and redirect to the SSO login flow.

**Qualification**: TST

#### LENT-SW-AUTH-004
**Title**: Browser compatibility
**CSCI**: LENT-FE
**SSS Trace**: LENT-3.2.1-004

The frontend shall function in modern mobile browsers (Safari iOS 16+, Chrome Android 100+) and desktop browsers (Chrome, Firefox, Safari, Edge — current and previous major version) without native app installation.

**Qualification**: DEM, TST

### 3.2.2 Authorization

#### LENT-SW-AUTHZ-001
**Title**: Role-based entrance authorization
**CSCI**: LENT-BE
**SSS Trace**: LENT-3.2.2-001

The backend shall enforce role-based authorization on entrance endpoints using the `UserRole` enum. Minimum required roles per operation:

| Operation          | Minimum Role   |
| ------------------ | -------------- |
| Scan / Validate    | User           |
| Check-in confirm   | User           |
| Manual lookup      | User           |
| Override           | Moderator      |
| Attendee details   | Moderator      |
| Entrance analytics | Admin          |
| System settings    | Admin          |

**Qualification**: TST

#### LENT-SW-AUTHZ-002
**Title**: Authorization denial feedback
**CSCI**: Both
**SSS Trace**: LENT-3.2.2-002

The backend shall return HTTP 403 with a machine-readable error code for unauthorized operations. The frontend shall display a clear denial message and shall not render controls for operations the user is not authorized to perform.

**Qualification**: DEM, TST

#### LENT-SW-AUTHZ-003
**Title**: Granular permission model
**CSCI**: LENT-BE
**SSS Trace**: LENT-3.2.2-003

The backend shall support distinct authorization checks for: scan, validate, check-in, override, and attendee detail access. Initial implementation may map these to role tiers; future iterations may introduce fine-grained permissions from LanCore.

**Qualification**: INSP, TST

### 3.2.3 QR scanning

#### LENT-SW-SCAN-001
**Title**: Browser camera QR scanning
**CSCI**: LENT-FE
**SSS Trace**: LENT-3.2.3-001

The frontend shall implement QR code scanning using `vue-qrcode-reader` (v5.7+, MIT license). The `QrcodeStream` component provides continuous camera-based scanning via the browser `getUserMedia` API with ZXing-Wasm decoding engine. The scanner shall:

* Request camera permission on activation (rear camera via `facingMode: 'environment'`)
* Display the live camera feed in the scanning viewport via `QrcodeStream`
* Continuously decode frames for QR codes using the `barcode-detector` polyfill
* Auto-detect and parse valid QR payloads via the `detect` event
* Pause scanning on detection (freeze-frame via `paused` prop) to prevent duplicate reads
* Provide `QrcodeCapture` as a file-upload fallback for devices without camera API support

**Qualification**: DEM, TST

#### LENT-SW-SCAN-002
**Title**: Opaque QR payload support
**CSCI**: LENT-FE
**SSS Trace**: LENT-3.2.3-002

The frontend shall accept QR payloads as opaque token strings. The scanner shall not require QR payloads to be URLs or any specific format beyond the token structure defined by LanCore.

**Qualification**: TST

#### LENT-SW-SCAN-003
**Title**: QR payload validation
**CSCI**: LENT-BE
**SSS Trace**: LENT-3.2.3-003

The backend shall validate the structural format of QR payloads before forwarding to LanCore. Invalid structures shall be rejected with HTTP 422 and an operator-readable error message.

**Qualification**: TST

#### LENT-SW-SCAN-004
**Title**: Malformed QR rejection
**CSCI**: Both
**SSS Trace**: LENT-3.2.3-004

The frontend shall display an error state for malformed QR payloads. The backend shall never create a check-in record for a structurally invalid payload.

**Qualification**: DEM, TST

#### LENT-SW-SCAN-005
**Title**: Manual lookup fallback
**CSCI**: Both
**SSS Trace**: LENT-3.2.3-005

The frontend shall provide a manual lookup interface accessible when camera scanning is unavailable or fails. The backend shall expose a search endpoint (`GET /api/entrance/lookup`) accepting text queries.

**Qualification**: DEM, TST

### 3.2.4 Validation and check-in

#### LENT-SW-CHECKIN-001
**Title**: Backend-mediated validation
**CSCI**: LENT-BE
**SSS Trace**: LENT-3.2.4-001

All scan and lookup validation requests shall be submitted through the LanEntrance backend. The backend shall forward requests to LanCore via `LanCoreClient` and return the authoritative response to the frontend.

**Qualification**: INSP, TST

#### LENT-SW-CHECKIN-002
**Title**: LanCore as authoritative source
**CSCI**: LENT-BE
**SSS Trace**: LENT-3.2.4-002

The backend shall treat LanCore responses as authoritative for ticket validity, check-in state, and policy decisions. The backend shall not maintain its own check-in state database.

**Qualification**: INSP, TST

#### LENT-SW-CHECKIN-003
**Title**: Decision outcome types
**CSCI**: Both
**SSS Trace**: LENT-3.2.4-003

The system shall support the following decision outcomes from LanCore, each rendered as a **full-screen overlay** covering the scanner viewport:

| Outcome                | Description                                    | Color  | UI Treatment                          |
| ---------------------- | ---------------------------------------------- | ------ | ------------------------------------- |
| `valid`                | Ticket valid, check-in permitted               | Green  | Success icon, attendee info, seating directions, addon list |
| `invalid`              | Ticket invalid or not found                    | Red    | Deny icon, reason text                |
| `already_checked_in`   | Attendee already admitted                      | Orange | Warning icon, prior check-in details  |
| `denied_by_policy`     | Group or policy restriction prevents check-in  | Red    | Deny icon, policy guidance            |
| `override_possible`    | Denied but staff override is available         | Orange | Warning icon, override action button  |
| `verification_required`| Check-in allowed after operator verifies conditions | Orange | Checklist of verification actions, confirm button |
| `payment_required`     | Ticket purchased with "Pay on Site" — payment must be collected | Orange | Payment amount, item breakdown, method selection, confirm button |

**Qualification**: TST

#### LENT-SW-CHECKIN-004
**Title**: Response time target
**CSCI**: Both
**SSS Trace**: LENT-3.2.4-004

The backend shall return validation responses to the frontend within 2 seconds for 95% of requests under nominal conditions. The frontend shall display a loading indicator during pending requests.

**Qualification**: TST, ANL

#### LENT-SW-CHECKIN-005
**Title**: No premature success display
**CSCI**: LENT-FE
**SSS Trace**: LENT-3.2.4-005

The frontend shall not display a check-in as completed until the backend confirms a successful authoritative response from LanCore. Pending states shall be visually distinct from success states.

**Qualification**: TST

#### LENT-SW-CHECKIN-006
**Title**: Re-entry support
**CSCI**: Both
**SSS Trace**: LENT-3.2.4-006

When LanCore policy permits re-entry, the backend shall forward re-entry validation requests. The frontend shall display re-entry context to the operator when the response indicates a re-entry scenario.

**Qualification**: TST

#### LENT-SW-CHECKIN-007
**Title**: Verification-required decision handling
**CSCI**: Both
**SSS Trace**: LENT-3.2.4-007

When LanCore returns a `verification_required` decision, the backend shall pass through the verification requirements to the frontend. The frontend shall display an orange full-screen overlay listing each verification action the operator must perform (e.g., "Check student ID", "Verify team membership card", "Confirm attendee is 18+"). The overlay shall include a **Confirm & Check In** button that the operator taps after completing the manual verification. The system shall not auto-complete the check-in — it requires explicit operator confirmation.

**Qualification**: DEM, TST

#### LENT-SW-CHECKIN-008
**Title**: Post-check-in seating information display
**CSCI**: Both
**SSS Trace**: LENT-3.2.4-008

Upon successful check-in (decision `valid` on check-in confirmation), the green full-screen overlay shall display the attendee's seating information:

* **Seat identifier**: The assigned seat label (e.g., "A-42")
* **Area/Hall**: The area or hall designation (e.g., "Hall A")
* **Directions**: Human-readable navigational text describing how to reach the seat from the entrance (e.g., "Enter Hall A, turn left, Row 4, Seat 42")

The seating section shall be visually prominent so the operator can quickly relay directions to the attendee. If no seating data is provided by LanCore, the section shall not be rendered.

**Qualification**: DEM, TST

#### LENT-SW-CHECKIN-009
**Title**: Post-check-in addon list display
**CSCI**: Both
**SSS Trace**: LENT-3.2.4-009

Upon successful check-in, the green full-screen overlay shall display a list of addons purchased with the ticket. Each addon shall show:

* **Addon name**: Human-readable label (e.g., "Pizza Package", "Chair Rental", "Tournament Entry")
* **Pickup/redemption info**: Optional instruction text (e.g., "Collect at Booth 3", "Pre-placed at seat")

The addon list allows the operator to inform the attendee about their purchased extras. If no addons are present, the section shall not be rendered.

**Qualification**: DEM, TST

### 3.2.45 On-site payment

#### LENT-SW-PAY-001
**Title**: Payment-required decision display
**CSCI**: Both
**SSS Trace**: LENT-3.2.9-001

When LanCore returns a `payment_required` decision, the frontend shall display an orange full-screen overlay containing:

* **Attendee name**
* **Total amount due**: Prominently displayed (e.g., "42,00 EUR")
* **Item breakdown**: List of payable items with individual prices (e.g., "Weekend Ticket — 35,00 EUR", "Tournament Entry — 7,00 EUR")
* **Accepted payment methods**: Icons/labels for each method accepted at this entrance (e.g., "Cash", "Card")

The backend shall pass through all payment details from the LanCore response.

**Qualification**: DEM, TST

#### LENT-SW-PAY-002
**Title**: Payment method selection and confirmation
**CSCI**: LENT-FE
**SSS Trace**: LENT-3.2.9-002

The orange payment overlay shall require the operator to:

1. Select the payment method the attendee used (from the accepted methods list)
2. Tap **Confirm Payment & Check In** to proceed

The confirm button shall be disabled until a payment method is selected. The system shall not auto-confirm payment.

**Qualification**: DEM, TST

#### LENT-SW-PAY-003
**Title**: Payment confirmation submission
**CSCI**: LENT-BE
**SSS Trace**: LENT-3.2.9-003

The backend shall submit payment confirmation to LanCore via `POST /api/entrance/confirm-payment` containing:

* `token`: The ticket token
* `validation_id`: From the prior validate response
* `payment_method`: The method selected by the operator (e.g., `cash`, `card`)
* `amount`: The total amount confirmed
* `operator_id`: Authenticated operator's `lancore_user_id`
* `timestamp`: Server-side UTC timestamp

The backend shall not represent the check-in as completed until LanCore confirms the payment record has been created. On success, LanCore returns a standard check-in response (including seating and addons).

**Qualification**: TST

#### LENT-SW-PAY-004
**Title**: Receipt notification
**CSCI**: LENT-FE
**SSS Trace**: LENT-3.2.9-004

Upon successful payment confirmation, the green success overlay shall include a notice: "Receipt sent to attendee's email". LanCore is responsible for PDF generation and email delivery — LanEntrance only displays the confirmation that this has been triggered.

**Qualification**: DEM, TST

#### LENT-SW-PAY-005
**Title**: No local payment state
**CSCI**: LENT-BE
**SSS Trace**: LENT-3.2.9-005

The backend shall not store payment records, amounts, or transaction state in the LanEntrance database. All payment data resides in LanCore.

**Qualification**: INSP

#### LENT-SW-PAY-006
**Title**: Payment bypass requires override
**CSCI**: Both
**SSS Trace**: LENT-3.2.9-006

The payment overlay shall not offer a "Skip" or "Next Scan" action to User-role operators. Only Moderator+ operators shall see an **Override** button to bypass payment, which follows the standard override flow (reason required, forwarded to LanCore).

**Qualification**: TST

### 3.2.5 Group ticket handling

#### LENT-SW-GROUP-001
**Title**: Group policy evaluation display
**CSCI**: Both
**SSS Trace**: LENT-3.2.5-001, LENT-3.2.5-002

The frontend shall display group policy guidance when LanCore returns a group-related restriction. The backend shall pass through group policy details from LanCore responses.

**Qualification**: DEM, TST

#### LENT-SW-GROUP-002
**Title**: Override workflow
**CSCI**: Both
**SSS Trace**: LENT-3.2.5-003

When LanCore indicates `override_possible`, the frontend shall present an override action to authorized operators (Moderator+). The backend shall forward override requests with operator identity and reason to LanCore.

**Qualification**: TST

#### LENT-SW-GROUP-003
**Title**: Override reason requirement
**CSCI**: Both
**SSS Trace**: LENT-3.2.5-004

The frontend shall require a non-empty reason text before submitting an override. The backend shall validate the presence of a reason and include it in the LanCore override request.

**Qualification**: TST

### 3.2.6 Operator guidance

#### LENT-SW-UI-001
**Title**: Full-screen decision overlay with three-tier color system
**CSCI**: LENT-FE
**SSS Trace**: LENT-3.2.6-001, LENT-3.2.6-005

The frontend shall display every decision outcome as a **full-screen overlay** that covers the scanner viewport entirely. The overlay uses a three-tier color system:

| Tier   | Color                  | Icon              | Used For                                                    |
| ------ | ---------------------- | ----------------- | ----------------------------------------------------------- |
| Green  | `#16a34a` / green-600  | CircleCheck       | `valid` — attendee admitted                                 |
| Red    | `#dc2626` / red-600    | CircleX           | `invalid`, `denied_by_policy` — entry denied                |
| Orange | `#ea580c` / orange-600 | AlertTriangle     | `already_checked_in`, `override_possible`, `verification_required` — operator attention needed |

Each overlay shall contain:
1. **Icon**: Large (min 64px), centered, white on colored background
2. **Primary status text**: Bold, min 28px, 1-line summary (e.g., "Checked In", "Entry Denied", "Verification Required")
3. **Message text**: Secondary detail from LanCore `message` field (e.g., "Student ticket — please verify student ID")
4. **Supplementary information section**: Context-dependent (seating, addons, verification checklist, denial reason — see LENT-SW-UI-002 through LENT-SW-UI-005)
5. **Action area**: Bottom of screen with primary action button(s)

The overlay shall be dismissible only via explicit button tap (no swipe-away, no auto-dismiss) to prevent accidental dismissal in fast-paced scanning.

**Qualification**: DEM, TST

#### LENT-SW-UI-002
**Title**: Post-check-in seating information display
**CSCI**: LENT-FE
**SSS Trace**: LENT-3.2.6-002

On the green success overlay after check-in, a **Seating** section shall display:

* **Seat label**: Large, bold text (e.g., "Seat A-42")
* **Area/Hall**: Where the seat is located (e.g., "Hall A")
* **Directions**: Step-by-step text describing how to reach the seat from the entrance (e.g., "Enter Hall A, turn left past the info desk, Row 4, Seat 42 is on the right")

The seating section shall be visually distinct (card or highlighted block) so the operator can read it aloud or show the screen to the attendee. If LanCore provides no seating data, this section shall not render.

**Qualification**: DEM, TST

#### LENT-SW-UI-003
**Title**: Denial context display
**CSCI**: LENT-FE
**SSS Trace**: LENT-3.2.6-003

On red and orange overlays, the frontend shall display the human-readable reason for denial or warning, including:

* Prior check-in timestamp (for `already_checked_in`)
* Group policy details and member count (for `denied_by_policy`)
* Specific verification requirements (for `verification_required`)

**Qualification**: DEM, TST

#### LENT-SW-UI-004
**Title**: Post-check-in addon list display
**CSCI**: LENT-FE
**SSS Trace**: LENT-3.2.6-004

On the green success overlay after check-in, an **Addons** section shall display a list of extras purchased with the ticket. Each addon entry shows:

* **Name**: The addon label (e.g., "Pizza Package", "Chair Rental", "Tournament Entry Fee")
* **Pickup info**: Optional instruction (e.g., "Collect at Booth 3", "Pre-placed at your seat")

The list shall be scrollable if it exceeds the visible area. If LanCore returns no addons, this section shall not render.

**Qualification**: DEM, TST

#### LENT-SW-UI-005
**Title**: Verification checklist display
**CSCI**: LENT-FE
**SSS Trace**: LENT-3.2.6-005, LENT-3.2.4-007

On the orange `verification_required` overlay, the frontend shall display a checklist of verification actions the operator must perform before admitting the attendee. Each item shows:

* **Verification label**: What to check (e.g., "Student ID", "Team membership card", "Age verification (18+)")
* **Instruction**: Optional guidance text (e.g., "Must show a valid university student ID with photo")

Below the checklist, a **Confirm & Check In** button allows the operator to proceed after completing all checks. The button shall be visually distinct (green on orange background) to indicate it advances the check-in.

**Qualification**: DEM, TST

### 3.2.7 Audit and traceability

#### LENT-SW-AUDIT-001
**Title**: Audit metadata injection
**CSCI**: LENT-BE
**SSS Trace**: LENT-3.2.7-001

The backend shall include the following metadata in all operational requests to LanCore:

* `operator_id`: Authenticated user's `lancore_user_id`
* `operator_session`: Current session identifier
* `timestamp`: Server-side UTC timestamp
* `client_info`: User-Agent string (when available)

**Qualification**: INSP, TST

#### LENT-SW-AUDIT-002
**Title**: Audit response integrity
**CSCI**: LENT-BE
**SSS Trace**: LENT-3.2.7-002

The backend shall pass through audit-related fields from LanCore responses without modification. The backend shall not suppress, alter, or override audit outcomes.

**Qualification**: INSP, TST

#### LENT-SW-AUDIT-003
**Title**: Audit visibility
**CSCI**: LENT-FE
**SSS Trace**: LENT-3.2.7-003

The frontend shall display the most recent audit-relevant outcome (e.g., check-in confirmation ID, timestamp) on the decision display screen for the current validation flow.

**Qualification**: DEM, TST

### 3.2.8 Degraded operation

#### LENT-SW-DEGRADED-001
**Title**: Service failure detection
**CSCI**: LENT-BE
**SSS Trace**: LENT-3.2.8-001

The backend shall detect LanCore API failures (connection refused, timeout exceeding `lancore.timeout`, HTTP 5xx responses) and return a structured error response with `degraded: true` to the frontend.

**Qualification**: DEM, TST

#### LENT-SW-DEGRADED-002
**Title**: No local authoritative state
**CSCI**: LENT-BE
**SSS Trace**: LENT-3.2.8-002

The backend shall not create, store, or return locally authoritative check-in records during degraded operation. All check-in state remains in LanCore.

**Qualification**: INSP, TST

#### LENT-SW-DEGRADED-003
**Title**: Retriable request queueing
**CSCI**: Both
**SSS Trace**: LENT-3.2.8-003

If request queueing is implemented, the backend shall clearly mark queued requests as `pending` (not `completed`). The frontend shall display queued requests with a distinct visual treatment indicating unresolved status.

**Qualification**: INSP, TST

---

## 3.3 External interface requirements

Software-level interface requirements are specified in the Interface Requirements Specification (IRS). Key interfaces:

* **LENT-IF-001**: Operator Browser Interface (SSS 3.3.2)
* **LENT-IF-002**: SSO Authentication Interface (SSS 3.3.3)
* **LENT-IF-003**: Platform Validation Interface (SSS 3.3.4)

See IRS for detailed software-level interface requirements.

---

## 3.4 Internal interface requirements

#### LENT-SW-INT-001
**Title**: Internal API boundary
**CSCI**: Both
**SSS Trace**: LENT-3.4-001

The frontend and backend shall communicate through a documented REST API under the `/api/entrance/` namespace. Inertia.js page rendering shall use standard Inertia protocols for navigation and props.

**Qualification**: INSP

#### LENT-SW-INT-002
**Title**: No direct client-to-LanCore calls
**CSCI**: Both
**SSS Trace**: LENT-3.4-002

The frontend shall not make direct HTTP requests to LanCore API endpoints. All operational requests shall route through the LanEntrance backend.

**Qualification**: INSP, TST

---

## 3.5 Internal data requirements

#### LENT-SW-DATA-001
**Title**: No authoritative ticket storage
**CSCI**: LENT-BE
**SSS Trace**: LENT-3.5-001

The backend database shall not contain tables for ticket validity or check-in status. The `users` table stores operator accounts only.

**Qualification**: INSP

#### LENT-SW-DATA-002
**Title**: Transient operational data
**CSCI**: LENT-BE
**SSS Trace**: LENT-3.5-002

The backend may cache LanCore responses in the session or short-lived cache for display purposes. Cached data shall not be treated as authoritative for subsequent operations.

**Qualification**: INSP

#### LENT-SW-DATA-003
**Title**: Minimal data retention
**CSCI**: LENT-BE
**SSS Trace**: LENT-3.5-003

Transient operational data (session state, cache entries) shall have a maximum TTL of the active session duration. No operational data shall persist beyond session termination.

**Qualification**: INSP, TST

---

## 3.6 Adaptation requirements

#### LENT-SW-ADAPT-001
**Title**: Environment configuration
**CSCI**: LENT-BE
**SSS Trace**: LENT-3.6-001

The backend shall support configuration via environment variables for:

* LanCore connection: `LANCORE_BASE_URL`, `LANCORE_INTERNAL_URL`, `LANCORE_TOKEN`, `LANCORE_APP_SLUG`, `LANCORE_CALLBACK_URL`
* Operational tuning: `LANCORE_TIMEOUT`, `LANCORE_RETRIES`, `LANCORE_RETRY_DELAY`
* Feature flags: `LANCORE_ENABLED`
* Webhook security: `LANCORE_ROLES_WEBHOOK_SECRET`

**Status**: Implemented in `config/lancore.php`
**Qualification**: INSP, TST

#### LENT-SW-ADAPT-002
**Title**: Event-specific presentation
**CSCI**: LENT-FE
**SSS Trace**: LENT-3.6-002

The frontend shall support event branding customization (logo, colors) via backend-provided configuration props without requiring frontend code changes.

**Qualification**: INSP

---

## 3.7 Security requirements

#### LENT-SW-SEC-001
**Title**: Authenticated access enforcement
**CSCI**: LENT-BE
**SSS Trace**: LENT-3.8-001

All entrance operational routes shall be protected by Laravel authentication middleware (`auth`, `verified`). Unauthenticated requests shall receive HTTP 401 or redirect to SSO.

**Qualification**: TST

#### LENT-SW-SEC-002
**Title**: Authorization enforcement
**CSCI**: LENT-BE
**SSS Trace**: LENT-3.8-002

Entrance operational endpoints shall check the user's role before processing. Unauthorized requests shall receive HTTP 403.

**Qualification**: TST

#### LENT-SW-SEC-003
**Title**: Encrypted transport
**CSCI**: Both
**SSS Trace**: LENT-3.8-003

All browser-to-backend and backend-to-LanCore communication shall use HTTPS in production. The `LanCoreClient` HTTP client shall enforce TLS.

**Qualification**: INSP, TST

#### LENT-SW-SEC-004
**Title**: PII minimization
**CSCI**: LENT-FE
**SSS Trace**: LENT-3.8-004

The frontend shall only display attendee information fields explicitly included in the LanCore validation response. No additional attendee data shall be fetched or cached beyond the current validation flow.

**Qualification**: INSP, DEM

#### LENT-SW-SEC-005
**Title**: Opaque QR tokens
**CSCI**: Both
**SSS Trace**: LENT-3.8-005

QR payloads shall be treated as opaque tokens. Neither the frontend nor backend shall attempt to decode sensitive information from QR payload content.

**Qualification**: TST

#### LENT-SW-SEC-006
**Title**: Audit correlation
**CSCI**: LENT-BE
**SSS Trace**: LENT-3.8-006

Every operational request to LanCore shall include the operator's `lancore_user_id` for audit trail correlation.

**Qualification**: TST

#### LENT-SW-SEC-007
**Title**: Rate limiting
**CSCI**: LENT-BE
**SSS Trace**: LENT-3.8-007

Entrance operational endpoints shall be rate-limited to prevent abuse. Rate limits shall be configurable and shall return HTTP 429 when exceeded.

**Qualification**: INSP, TST

---

## 3.8 Computer resource requirements

#### LENT-SW-RES-001
**Title**: Client hardware requirements
**CSCI**: LENT-FE
**SSS Trace**: LENT-3.10.1-001

The frontend shall function on devices with a camera (for scanning) or without (manual lookup only). No specialized hardware beyond a standard smartphone or computer is required.

**Qualification**: DEM

#### LENT-SW-RES-002
**Title**: Container deployment
**CSCI**: LENT-BE
**SSS Trace**: LENT-3.10.1-002

The backend shall be deployable as a Docker container using the existing `compose.yaml` and Sail configuration.

**Qualification**: INSP

#### LENT-SW-RES-003
**Title**: Mid-range device performance
**CSCI**: LENT-FE
**SSS Trace**: LENT-3.10.2-001

The frontend shall maintain interactive responsiveness (< 100ms input latency) on mid-range smartphones (2+ GB RAM, quad-core processor).

**Qualification**: DEM, ANL

#### LENT-SW-RES-004
**Title**: Burst load handling
**CSCI**: LENT-BE
**SSS Trace**: LENT-3.10.2-002

The backend shall handle concurrent entrance requests from multiple operators without request queuing delays exceeding the 2-second response target.

**Qualification**: TST, ANL

---

## 3.9 Quality requirements

#### LENT-SW-QUAL-001
**Title**: Minimal training usability
**CSCI**: LENT-FE
**SSS Trace**: LENT-3.11-001

The entrance workflow (scan → result → next scan) shall be completable with a maximum of 2 taps after the initial scan.

**Qualification**: DEM, ANL

#### LENT-SW-QUAL-002
**Title**: Unambiguous decision states
**CSCI**: LENT-FE
**SSS Trace**: LENT-3.11-002

Each decision outcome shall have a visually distinct presentation (color, icon, text) that cannot be confused with another outcome under normal viewing conditions.

**Qualification**: DEM, TST

#### LENT-SW-QUAL-003
**Title**: Separation of concerns
**CSCI**: Both
**SSS Trace**: LENT-3.11-003

The codebase shall maintain clear boundaries between: Vue frontend components, Laravel controllers, service classes (LanCoreClient), and LanCore integration logic.

**Qualification**: INSP

#### LENT-SW-QUAL-004
**Title**: Automated test coverage
**CSCI**: Both
**SSS Trace**: LENT-3.11-004

Critical paths (auth, validation, check-in, override) shall have automated test coverage via Pest (backend) and Vitest/Playwright (frontend).

**Qualification**: INSP, TST

---

## 3.10 Design constraints

#### LENT-SW-DESIGN-001
**Title**: LanCore as authoritative source
**CSCI**: LENT-BE
**SSS Trace**: LENT-3.12-001

The backend shall delegate all ticket, attendance, and audit state decisions to LanCore. No local business logic shall override LanCore decisions.

**Qualification**: INSP

#### LENT-SW-DESIGN-002
**Title**: URL-free QR payloads
**CSCI**: Both
**SSS Trace**: LENT-3.12-002

The system shall not require QR payloads to contain application URLs. Payloads shall be treated as opaque tokens resolved by LanCore.

**Qualification**: INSP, TST

#### LENT-SW-DESIGN-003
**Title**: BFF architecture
**CSCI**: Both
**SSS Trace**: LENT-3.12-003

The LanEntrance backend shall serve as a Backend-for-Frontend, mediating all communication between the browser SPA and LanCore API.

**Qualification**: INSP

---

# 4. Qualification provisions

Qualification methods follow SSS Section 4. Software-level qualification mapping:

| Method | Software Implementation                                    |
| ------ | ----------------------------------------------------------- |
| DEM    | Playwright E2E tests, manual demonstration on target devices|
| TST    | Pest feature tests, Vitest component tests                  |
| ANL    | Code review, performance analysis, architecture review      |
| INSP   | PR review, code inspection, document review                 |

---

# 5. Requirements traceability

Bidirectional traceability between SSS requirements and SRS requirements is maintained in the Requirements Traceability Matrix (RTM). See `docs/RTM.md`.

Summary mapping:

| SSS Section          | SRS Category       |
| -------------------- | ------------------- |
| 3.1 States/modes     | LENT-SW-STATE-xxx   |
| 3.2.1 Authentication | LENT-SW-AUTH-xxx    |
| 3.2.2 Authorization  | LENT-SW-AUTHZ-xxx   |
| 3.2.3 QR scanning    | LENT-SW-SCAN-xxx    |
| 3.2.4 Validation     | LENT-SW-CHECKIN-xxx  |
| 3.2.5 Group handling | LENT-SW-GROUP-xxx   |
| 3.2.6 Operator UI    | LENT-SW-UI-xxx      |
| 3.2.7 Audit          | LENT-SW-AUDIT-xxx   |
| 3.2.8 Degraded mode  | LENT-SW-DEGRADED-xxx|
| 3.3 External IF      | See IRS             |
| 3.4 Internal IF      | LENT-SW-INT-xxx     |
| 3.5 Internal data    | LENT-SW-DATA-xxx    |
| 3.6 Adaptation       | LENT-SW-ADAPT-xxx   |
| 3.8 Security         | LENT-SW-SEC-xxx     |
| 3.10 Resources       | LENT-SW-RES-xxx     |
| 3.11 Quality         | LENT-SW-QUAL-xxx    |
| 3.12 Design          | LENT-SW-DESIGN-xxx  |

---

# 6. Notes

## 6.1 Acronyms

* BFF: Backend for Frontend
* CSCI: Computer Software Configuration Item
* PII: Personally Identifiable Information
* SPA: Single Page Application
* SSO: Single Sign-On
* TTL: Time to Live

## 6.2 Implementation status

Requirements marked **Status: Implemented** have existing code in the repository. All other requirements are pending Phase 2 implementation.
