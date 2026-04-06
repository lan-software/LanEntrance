LanEntrance System/Subsystem Design Description (MIL-STD-498 Adapted)
===

# 1. Scope

## 1.1 Identification

* Title: LanEntrance System/Subsystem Design Description
* Short Name: LanEntrance SSDD
* System Name: LanEntrance
* Ecosystem: LanSoftware
* Version: Draft v0.1
* Status: Working Draft

This document describes the system-level architecture and design of LanEntrance.

## 1.2 System overview

LanEntrance is decomposed into two CSCIs (Computer Software Configuration Items) operating as a Backend-for-Frontend (BFF) architecture within the LanSoftware ecosystem. See SSS Section 1.2 and SRS Section 1.2 for context.

## 1.3 Document overview

This document covers the high-level architecture decomposition, CSCI identification, inter-CSCI communication, state machine design, and data flow. Detailed per-component design is in the SDD.

---

# 2. Referenced documents

| ID      | Title                                      | Version    |
| ------- | ------------------------------------------ | ---------- |
| REF-001 | LanEntrance SSS                            | Draft v0.1 |
| REF-002 | LanEntrance SRS                            | Draft v0.1 |
| REF-003 | LanEntrance IRS                            | Draft v0.1 |
| REF-004 | LanEntrance SDD                            | Draft v0.1 |

---

# 3. System-wide design decisions

## 3.1 Architecture pattern

LanEntrance uses a **Backend-for-Frontend (BFF)** pattern:

```
┌─────────────────────────────────────────────────────┐
│                  Operator Device                     │
│  ┌───────────────────────────────────────────────┐  │
│  │         LanEntrance-Frontend (LENT-FE)        │  │
│  │            Vue 3 SPA via Inertia.js           │  │
│  └──────────────────┬────────────────────────────┘  │
└─────────────────────┼───────────────────────────────┘
                      │ HTTPS (Inertia + REST JSON)
                      │ LENT-IF-001
┌─────────────────────┼───────────────────────────────┐
│  ┌──────────────────┴────────────────────────────┐  │
│  │         LanEntrance-Backend (LENT-BE)         │  │
│  │              Laravel 13 Service               │  │
│  │                                               │  │
│  │  ┌─────────────┐  ┌────────────────────────┐  │  │
│  │  │ Controllers │  │ Services               │  │  │
│  │  │             │  │  ├─ LanCoreClient       │  │  │
│  │  │ Auth        │  │  └─ UserSyncService     │  │  │
│  │  │ Entrance    │  │                         │  │  │
│  │  │ Settings    │  │ Actions                 │  │  │
│  │  │ Webhook     │  │  └─ SyncUserRoles       │  │  │
│  │  └─────────────┘  └────────────────────────┘  │  │
│  └──────────────────┬────────────────────────────┘  │
│            LanEntrance Server Container              │
└─────────────────────┼───────────────────────────────┘
                      │ HTTPS (REST JSON)
                      │ LENT-IF-003
┌─────────────────────┼───────────────────────────────┐
│  ┌──────────────────┴────────────────────────────┐  │
│  │              LanCore API                      │  │
│  │  Identity · Tickets · Check-in · Audit        │  │
│  └───────────────────────────────────────────────┘  │
│                    LanCore Platform                   │
└─────────────────────────────────────────────────────┘
```

**Rationale** (SSS 3.12-003, SRS LENT-SW-DESIGN-003):
* Isolates the browser from LanCore API complexity
* Enables server-side audit metadata injection
* Provides a security boundary for token handling
* Allows backend-side rate limiting and request validation

## 3.2 Technology decisions

| Decision                | Choice              | Rationale                                        |
| ----------------------- | ------------------- | ------------------------------------------------ |
| SPA framework           | Vue 3               | SSS 3.10.3-001; ecosystem standard               |
| Backend framework       | Laravel 13          | SSS 3.10.3-002; ecosystem standard               |
| SPA-backend bridge      | Inertia.js 3        | Server-driven SPA without separate API layer     |
| Entrance API transport  | REST JSON            | Low latency for scan operations; simple contracts |
| CSS framework           | Tailwind CSS 4      | Utility-first; mobile-responsive by default      |
| UI primitives           | Reka UI              | Accessible headless components                   |
| Container runtime       | Docker (Sail)        | Consistent dev/prod environments                 |
| Testing                 | Pest + Vitest + PW   | Full stack coverage                              |

## 3.3 Hybrid Inertia + API approach

LanEntrance uses Inertia.js for page navigation (dashboard, settings, entrance page) but raw REST API calls for scan operations. This hybrid approach is deliberate:

* **Inertia pages**: Used for full-page navigation where server-side rendering of props is natural (dashboard, settings, entrance page shell)
* **REST API**: Used for scan/validate/checkin operations where sub-second latency is critical and full-page transitions are undesirable

---

# 4. CSCI-wide design decisions

## 4.1 LENT-FE: Frontend CSCI

### 4.1.1 Component architecture

```
resources/js/
├── pages/
│   ├── Welcome.vue              # Landing / SSO redirect
│   ├── Dashboard.vue            # Operator dashboard
│   ├── entrance/
│   │   ├── Scanner.vue          # QR scan + validation (Phase 2)
│   │   └── Lookup.vue           # Manual attendee lookup (Phase 2)
│   ├── auth/                    # Auth pages (implemented)
│   └── settings/                # Settings pages (implemented)
├── components/
│   ├── entrance/
│   │   ├── QrScanner.vue        # Camera QR scanner (Phase 2)
│   │   ├── DecisionDisplay.vue  # Validation result display (Phase 2)
│   │   ├── OverrideModal.vue    # Staff override dialog (Phase 2)
│   │   └── LookupForm.vue       # Manual search form (Phase 2)
│   ├── ui/                      # Reka UI / shadcn components (implemented)
│   └── ...                      # Existing shared components
├── composables/
│   ├── useCheckin.ts            # Validation API state machine (Phase 2)
│   └── useEntranceState.ts      # UI state machine (Phase 2)
│   # Note: Camera + QR decode managed by vue-qrcode-reader's QrcodeStream internally
└── layouts/
    └── ...                      # Existing layout components
```

### 4.1.2 State management strategy

* **Page-level state**: Inertia props (server-driven)
* **Scan workflow state**: Vue composable (`useEntranceState`) managing the state machine
* **Camera state**: Managed internally by `vue-qrcode-reader`'s `QrcodeStream` component (no separate composable)
* **No global store**: State is scoped to entrance page components; no Pinia/Vuex needed

### 4.1.3 QR scanning approach

The scanner uses **`vue-qrcode-reader`** (v5.7, MIT license), a Vue 3 native library providing the `QrcodeStream` component for continuous camera-based QR decoding.

**Selected library**: `vue-qrcode-reader` v5.7+
**Decoding engine**: ZXing-cpp compiled to WebAssembly via `barcode-detector` polyfill
**npm**: `npm install vue-qrcode-reader`

**Key capabilities**:
* `QrcodeStream` — continuous real-time camera scanning (primary component)
* `QrcodeCapture` — file-upload fallback for devices without camera API
* `QrcodeDropZone` — drag-and-drop image decode (desktop convenience)

**Rationale for selection**:
* Vue 3 native with TypeScript — no framework adapter needed
* Actively maintained (2,300+ stars, commits in 2025, semantic-release)
* Uses `barcode-detector` Web API polyfill with ZXing-Wasm engine — mature, fast decoding
* Built-in camera permission handling with typed error events
* `paused` prop enables scan-then-confirm UX without releasing the camera
* Default `facingMode: "environment"` targets rear camera (ideal for badge scanning)
* Slot-based overlay system for custom scan UI (crosshair, result feedback)
* MIT license — compatible with LanSoftware ecosystem

**Alternatives evaluated and rejected**:
* `html5-qrcode` — framework-agnostic (no Vue integration), imperative API, larger bundle
* `jsQR` — unmaintained since 2020, no camera management, decode-only
* Raw `BarcodeDetector` API — insufficient browser support without polyfill, no Vue integration

**Integration constraints**:
* **HTTPS required**: Camera API (`getUserMedia`) only works over HTTPS or localhost
* **Wasm loading**: ZXing Wasm binary (~1 MB) is fetched from CDN at runtime by default; may need self-hosting for CSP compliance or offline operation
* **Torch/flash**: Not supported on iOS; check `camera-on` capabilities before exposing torch toggle
* **Duplicate scan prevention**: `detect` event fires continuously while code is visible; `paused` prop or debounce required

## 4.2 LENT-BE: Backend CSCI

### 4.2.1 Service architecture

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Auth/
│   │   │   └── LanCoreAuthController.php     # SSO flow (implemented)
│   │   ├── Api/
│   │   │   └── LanCoreRolesWebhookController.php  # Role webhook (implemented)
│   │   ├── Entrance/
│   │   │   ├── EntranceController.php         # Validate + checkin (Phase 2)
│   │   │   ├── LookupController.php           # Manual search (Phase 2)
│   │   │   ├── OverrideController.php         # Staff override (Phase 2)
│   │   │   └── PaymentController.php          # On-site payment confirmation (Phase 2)
│   │   └── Settings/                          # Settings controllers (implemented)
│   └── Middleware/
│       ├── HandleInertiaRequests.php           # Shared props (implemented)
│       ├── HandleAppearance.php                # Theme cookie (implemented)
│       └── EnsureEntranceRole.php              # Role gate (Phase 2)
├── Services/
│   ├── LanCoreClient.php                      # LanCore API client (implemented, extend Phase 2)
│   ├── UserSyncService.php                    # User resolution (implemented)
│   └── LanCoreValidationService.php           # Validation orchestration (Phase 2)
├── Actions/
│   └── SyncUserRolesFromLanCore.php           # Role sync (implemented)
├── Models/
│   └── User.php                               # User model (implemented)
├── Enums/
│   └── UserRole.php                           # Role enum (implemented)
└── DTOs/
    ├── ValidationRequest.php                  # Validate request DTO (Phase 2)
    ├── ValidationResponse.php                 # Validate response DTO (Phase 2)
    └── CheckinResult.php                      # Checkin result DTO (Phase 2)
```

### 4.2.2 LanCoreClient extension plan

The existing `LanCoreClient` service handles SSO and HTTP transport. Phase 2 will add methods:

| Method                  | Purpose                        | LanCore Endpoint (expected) |
| ----------------------- | ------------------------------ | --------------------------- |
| `validateTicket()`      | Submit token for validation    | `POST /api/entrance/validate` |
| `confirmCheckin()`      | Confirm check-in after valid   | `POST /api/entrance/checkin`  |
| `confirmVerifyCheckin()`| Confirm after manual verification | `POST /api/entrance/verify-checkin` |
| `confirmPayment()`     | Confirm on-site payment + checkin | `POST /api/entrance/confirm-payment`|
| `submitOverride()`      | Submit staff override          | `POST /api/entrance/override` |
| `searchAttendees()`     | Manual lookup search           | `GET /api/entrance/search`    |

All methods reuse the existing `http()` transport with its timeout/retry configuration.

---

# 5. System state machine

## 5.1 Operator workflow states

```
                    ┌──────────┐
                    │   IDLE   │
                    └────┬─────┘
                         │ Auth + authorize
                         ▼
                    ┌──────────┐
              ┌─────│  READY   │─────┐
              │     └──────────┘     │
    Activate  │                      │ Enter query
    scanner   │                      │
              ▼                      ▼
       ┌─────────────┐      ┌──────────────┐
       │ ACTIVE_SCAN │      │ ACTIVE_LOOKUP│
       └──────┬──────┘      └──────┬───────┘
              │ QR decoded /       │ Response
              │ Response received  │ received
              ▼                    ▼
       ┌──────────────────────────────────┐
       │        DECISION_DISPLAY          │
       │  (valid / invalid / override)    │
       └──────────────┬───────────────────┘
                      │ Dismiss / Next scan
                      ▼
                 ┌──────────┐
                 │  READY   │
                 └──────────┘
```

**DEGRADED** is an orthogonal state that can overlay any operational state when LanCore connectivity is lost.

**MAINTENANCE** is entered via backend configuration, restricting operational functions.

## 5.2 State transitions

| From             | To               | Trigger                                  | Guard                        |
| ---------------- | ---------------- | ---------------------------------------- | ---------------------------- |
| IDLE             | READY            | Successful SSO + authorized role         | Auth session valid           |
| READY            | ACTIVE_SCAN      | User activates scanner                   | Camera permission granted    |
| READY            | ACTIVE_LOOKUP    | User submits lookup query                | Query non-empty              |
| ACTIVE_SCAN      | DECISION_DISPLAY | QR decoded + backend response            | Response received            |
| ACTIVE_SCAN      | READY            | User cancels scan                        | —                            |
| ACTIVE_LOOKUP    | DECISION_DISPLAY | Backend response received                | Response received            |
| DECISION_DISPLAY | READY            | User dismisses result                    | —                            |
| DECISION_DISPLAY | DECISION_DISPLAY | Override submitted                       | Override response received   |
| DECISION_DISPLAY | DECISION_DISPLAY | Verification confirmed                   | Verify-checkin response      |
| DECISION_DISPLAY | DECISION_DISPLAY | Payment confirmed                        | Confirm-payment response     |
| Any              | DEGRADED         | Backend returns degraded indicator       | —                            |
| DEGRADED         | Previous         | Connectivity restored                    | Successful request           |
| Any              | IDLE             | Session expired / logout                 | —                            |

---

# 6. Data flow

## 6.1 QR scan validation flow

```
Operator          Frontend (SPA)           Backend (Laravel)         LanCore API
  │                    │                         │                       │
  │  Scan QR code      │                         │                       │
  ├───────────────────>│                         │                       │
  │                    │  Decode QR payload       │                       │
  │                    │──────────┐               │                       │
  │                    │<─────────┘               │                       │
  │                    │                         │                       │
  │                    │  POST /api/entrance/    │                       │
  │                    │       validate          │                       │
  │                    ├────────────────────────>│                       │
  │                    │                         │  Inject audit metadata │
  │                    │                         │──────────┐            │
  │                    │                         │<─────────┘            │
  │                    │                         │                       │
  │                    │                         │  POST validate        │
  │                    │                         ├──────────────────────>│
  │                    │                         │                       │
  │                    │                         │  Decision response    │
  │                    │                         │<──────────────────────┤
  │                    │                         │                       │
  │                    │  JSON decision response │                       │
  │                    │<────────────────────────┤                       │
  │                    │                         │                       │
  │  Display decision  │                         │                       │
  │<───────────────────┤                         │                       │
  │                    │                         │                       │
```

## 6.2 Override flow

```
Operator          Frontend (SPA)           Backend (Laravel)         LanCore API
  │                    │                         │                       │
  │  Enter reason +    │                         │                       │
  │  confirm override  │                         │                       │
  ├───────────────────>│                         │                       │
  │                    │  POST /api/entrance/    │                       │
  │                    │       override          │                       │
  │                    ├────────────────────────>│                       │
  │                    │                         │  Check role >= Mod    │
  │                    │                         │──────────┐            │
  │                    │                         │<─────────┘            │
  │                    │                         │                       │
  │                    │                         │  POST override        │
  │                    │                         ├──────────────────────>│
  │                    │                         │                       │
  │                    │                         │  Override result      │
  │                    │                         │<──────────────────────┤
  │                    │                         │                       │
  │                    │  JSON override result   │                       │
  │                    │<────────────────────────┤                       │
  │                    │                         │                       │
  │  Display result    │                         │                       │
  │<───────────────────┤                         │                       │
```

---

# 7. Deployment architecture

## 7.1 Container topology

```
┌─────────────────────────────────────────────┐
│              Docker Host                     │
│                                             │
│  ┌─────────────────────────────────────┐    │
│  │ lanentrance.test                    │    │
│  │ (sail-8.5/app)                      │    │
│  │                                     │    │
│  │  PHP 8.5 + Node 22                  │    │
│  │  Laravel 13 + Vue 3 (built assets)  │    │
│  │  Port: 84 (HTTP)                    │    │
│  │  Port: 5177 (Vite HMR, dev only)   │    │
│  └──────────────┬──────────────────────┘    │
│                 │                            │
│     ┌───────────┴───────────┐               │
│     │   lanparty network    │               │
│     │   (external Docker)   │               │
│     └───────────┬───────────┘               │
│                 │                            │
│  ┌──────────────┴──────────────────────┐    │
│  │ LanCore container                   │    │
│  │ (on same Docker network)            │    │
│  └─────────────────────────────────────┘    │
└─────────────────────────────────────────────┘
```

## 7.2 Network considerations

* Dev: `lanparty` external Docker network shared between LanEntrance and LanCore
* Prod: HTTPS termination via reverse proxy; `LANCORE_INTERNAL_URL` for container-to-container communication
* Events: Variable network quality mitigated by degraded mode design

---

# 8. Security architecture

## 8.1 Trust boundaries

```
┌──────────────────────────────────────────┐
│  UNTRUSTED: Operator Browser             │
│  - QR payloads treated as untrusted      │
│  - All input validated server-side       │
└────────────────┬─────────────────────────┘
                 │ LENT-IF-001 (HTTPS)
┌────────────────┴─────────────────────────┐
│  TRUSTED: LanEntrance Backend            │
│  - Session-authenticated operators       │
│  - Input validation + rate limiting      │
│  - Audit metadata injection              │
└────────────────┬─────────────────────────┘
                 │ LENT-IF-003 (HTTPS + Bearer Token)
┌────────────────┴─────────────────────────┐
│  AUTHORITATIVE: LanCore API              │
│  - Ticket state, check-in state, audit   │
└──────────────────────────────────────────┘
```

## 8.2 Authentication flow

1. Browser redirects to LanCore SSO (`LENT-IF-002`)
2. LanCore authenticates user, returns authorization code
3. Backend exchanges code for user data
4. Backend establishes Laravel session with CSRF protection
5. Session cookie secures all subsequent requests
6. Operational requests include session-derived operator identity

## 8.3 Rate limiting strategy

| Endpoint                     | Limit       | Scope       |
| ---------------------------- | ----------- | ----------- |
| `POST /api/entrance/validate`| Configurable| Per user    |
| `POST /api/entrance/checkin` | Configurable| Per user    |
| `POST /api/entrance/verify-checkin`| Configurable| Per user |
| `POST /api/entrance/override`| Configurable| Per user    |
| `GET /api/entrance/lookup`   | Configurable| Per user    |
| Login / 2FA                  | 5/min       | Per IP      |

---

# 9. Notes

## 9.1 Design assumptions

* LanCore API contracts for validation, check-in, override, and search will be provided by the LanCore team
* The `lanparty` Docker network will be available in all deployment environments
* Browser camera API availability is not guaranteed; manual lookup is always available

## 9.2 Resolved design decisions

* **QR scanning library**: `vue-qrcode-reader` v5.7+ selected (see SDD Section 4.2). Rationale: Vue 3 native, ZXing-Wasm engine, built-in camera management, MIT license.
* **Request queueing**: Deferred to Phase 3 (LENT-SW-DEGRADED-003 marked Deferred in RTM).

## 9.3 Open design questions

* **Event branding customization mechanism**: SRS LENT-SW-ADAPT-002 requires event-specific branding (logo, colors) without frontend code changes. Mechanism not yet decided — options are backend config via Inertia shared props, or a dedicated LanCore API endpoint providing event branding assets. Decision required before Phase 2 design review if branding is needed at launch.
