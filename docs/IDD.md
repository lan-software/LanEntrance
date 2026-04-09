LanEntrance Interface Design Description (MIL-STD-498 Adapted)
===

# 1. Scope

## 1.1 Identification

* Title: LanEntrance Interface Design Description
* Short Name: LanEntrance IDD
* System Name: LanEntrance
* Ecosystem: LanSoftware
* Version: Draft v0.1
* Status: Working Draft

This document describes the detailed interface designs for all LanEntrance communication boundaries.

## 1.2 System overview

See SSDD Section 3.1 for architecture overview. This document specifies the concrete API contracts, request/response schemas, error formats, and protocol details for each interface identified in the IRS.

## 1.3 Document overview

Sections are organized by interface boundary. Each section defines endpoints, request/response schemas, authentication, error handling, and rate limiting.

---

# 2. Referenced documents

| ID      | Title                | Version    |
| ------- | -------------------- | ---------- |
| REF-001 | LanEntrance IRS      | Draft v0.1 |
| REF-002 | LanEntrance SDD      | Draft v0.1 |
| REF-003 | LanEntrance SRS      | Draft v0.1 |

---

# 3. LENT-IF-001: Operator Browser Interface — Entrance API

## 3.1 General conventions

| Property         | Value                                              |
| ---------------- | -------------------------------------------------- |
| Base path        | `/api/entrance`                                    |
| Content-Type     | `application/json`                                 |
| Authentication   | Laravel session cookie + CSRF token                |
| Authorization    | Role-based via `EnsureEntranceRole` middleware      |
| Rate limiting    | Per-user, configurable via `throttle:entrance`     |
| Error format     | Standardized JSON (see Section 3.6)                |

All timestamps in responses use ISO 8601 format (UTC).

---

## 3.2 POST /api/entrance/validate

**IRS Trace**: LENT-IR-001-004
**Purpose**: Validate a scanned or entered ticket token.

### Request

```http
POST /api/entrance/validate HTTP/1.1
Content-Type: application/json
X-CSRF-TOKEN: {csrf_token}
Cookie: {session_cookie}
```

```json
{
  "token": "abc123-opaque-ticket-token"
}
```

| Field   | Type   | Required | Constraints        | Description                  |
| ------- | ------ | -------- | ------------------- | ---------------------------- |
| `token` | string | Yes      | max:512             | Opaque ticket token from QR  |

### Response — Success (200)

```json
{
  "decision": "valid",
  "message": "Ticket is valid. Proceed with check-in.",
  "validation_id": "val_8f3a2b1c",
  "attendee": {
    "name": "Max Mustermann",
    "group": "Team Alpha"
  },
  "seating": {
    "seat": "A-42",
    "area": "Hall A",
    "directions": "Enter Hall A, turn left past the info desk, Row 4, Seat 42 on the right"
  },
  "addons": [
    { "name": "Pizza Package", "info": "Collect at Booth 3" },
    { "name": "Chair Rental", "info": "Pre-placed at your seat" },
    { "name": "Tournament Entry", "info": null }
  ],
  "verification": null,
  "override_allowed": false,
  "audit_id": "aud_9e4d3c2b",
  "group_policy": null,
  "degraded": false
}
```

| Field              | Type    | Nullable | Description                                     |
| ------------------ | ------- | -------- | ------------------------------------------------ |
| `decision`         | string  | No       | One of: `valid`, `invalid`, `already_checked_in`, `denied_by_policy`, `override_possible`, `verification_required`, `payment_required` |
| `message`          | string  | No       | Human-readable guidance for the operator         |
| `validation_id`    | string  | No       | ID to reference in subsequent checkin/override    |
| `attendee`         | object  | Yes      | Authorized attendee details                      |
| `attendee.name`    | string  | No       | Attendee display name                            |
| `attendee.group`   | string  | Yes      | Group/team name (if applicable)                  |
| `seating`          | object  | Yes      | Seating assignment and directions (see 3.2.1)    |
| `addons`           | array   | Yes      | List of purchased addons (see 3.2.2)             |
| `verification`     | object  | Yes      | Verification requirements (see 3.2.3)            |
| `payment`          | object  | Yes      | Outstanding payment details (see 3.2.5)          |
| `override_allowed` | boolean | No       | Whether staff override is available              |
| `audit_id`         | string  | Yes      | LanCore audit correlation ID                     |
| `group_policy`     | object  | Yes      | Group policy details (see Section 3.2.4)         |
| `degraded`         | boolean | No       | Whether the response is from degraded state      |

**Presence rules for optional response fields:**

* `seating` — returned by LanCore whenever the attendee has an assigned seat, regardless of decision type. The frontend displays it prominently on the green success overlay but may also show it on orange overlays for context.
* `addons` — returned by LanCore whenever the ticket includes purchased extras, regardless of decision type. Displayed prominently on the green success overlay.
* `verification` — returned only when `decision` is `verification_required`. Absent for all other decision types.
* `payment` — returned only when `decision` is `payment_required`. Contains amount, currency, item breakdown, and accepted methods. Absent for all other decision types.
* `group_policy` — returned only when the decision involves a group policy restriction (`denied_by_policy` or `override_possible`).

### 3.2.1 Seating object

Provided when an attendee has an assigned seat. Displayed on the green success overlay so staff can direct the attendee.

```json
{
  "seating": {
    "seat": "A-42",
    "area": "Hall A",
    "directions": "Enter Hall A, turn left past the info desk, Row 4, Seat 42 on the right"
  }
}
```

| Field        | Type   | Nullable | Description                                       |
| ------------ | ------ | -------- | ------------------------------------------------- |
| `seat`       | string | No       | Seat identifier / label                           |
| `area`       | string | Yes      | Area, hall, or zone designation                   |
| `directions` | string | Yes      | Human-readable navigational text from entrance    |

### 3.2.2 Addons array

Provided when the ticket includes purchased extras. Displayed on the green success overlay so staff can inform the attendee of their entitlements.

```json
{
  "addons": [
    { "name": "Pizza Package", "info": "Collect at Booth 3" },
    { "name": "Chair Rental", "info": "Pre-placed at your seat" },
    { "name": "Tournament Entry", "info": null }
  ]
}
```

| Field  | Type   | Nullable | Description                                       |
| ------ | ------ | -------- | ------------------------------------------------- |
| `name` | string | No       | Addon display name                                |
| `info` | string | Yes      | Pickup / redemption instructions                  |

### 3.2.3 Verification object

Provided when the decision is `verification_required`. Lists conditions the operator must manually verify before confirming check-in.

```json
{
  "verification": {
    "message": "This ticket requires manual verification before entry.",
    "checks": [
      { "label": "Student ID", "instruction": "Must show a valid university student ID with photo" },
      { "label": "Age Verification", "instruction": "Attendee must be 18 or older" }
    ]
  }
}
```

| Field                   | Type   | Nullable | Description                                      |
| ----------------------- | ------ | -------- | ------------------------------------------------ |
| `message`               | string | No       | Summary message for the operator                 |
| `checks`                | array  | No       | List of verification actions                     |
| `checks[].label`        | string | No       | What to verify (e.g., "Student ID")              |
| `checks[].instruction`  | string | Yes      | How to verify (e.g., "Must show photo ID")       |

### 3.2.4 Group policy object

```json
{
  "group_policy": {
    "rule": "all_members_present",
    "message": "All group members must check in together.",
    "members_checked_in": 2,
    "members_total": 5
  }
}
```

| Field                | Type   | Description                          |
| -------------------- | ------ | ------------------------------------ |
| `rule`               | string | Policy rule identifier               |
| `message`            | string | Human-readable policy description    |
| `members_checked_in` | int    | Number of members already checked in |
| `members_total`      | int    | Total group member count             |

### 3.2.5 Payment object

Provided when the decision is `payment_required`. Contains the outstanding amount, item breakdown, and accepted on-site payment methods.

```json
{
  "payment": {
    "amount": "42.00",
    "currency": "EUR",
    "items": [
      { "name": "Weekend Ticket", "price": "35.00" },
      { "name": "Tournament Entry", "price": "7.00" }
    ],
    "methods": ["cash", "card"]
  }
}
```

| Field            | Type   | Nullable | Description                                   |
| ---------------- | ------ | -------- | --------------------------------------------- |
| `amount`         | string | No       | Total amount due (decimal, e.g., "42.00")     |
| `currency`       | string | No       | ISO 4217 currency code (e.g., "EUR")          |
| `items`          | array  | No       | Breakdown of payable items                    |
| `items[].name`   | string | No       | Item display name                             |
| `items[].price`  | string | No       | Item price (decimal string)                   |
| `methods`        | array  | No       | Accepted on-site payment method identifiers   |

**Accepted method identifiers**: `cash`, `card`. Additional methods may be added by LanCore.

---

## 3.3 POST /api/entrance/checkin

**IRS Trace**: LENT-IR-001-005
**Purpose**: Confirm check-in after a `valid` validation result.

### Request

```json
{
  "token": "abc123-opaque-ticket-token",
  "validation_id": "val_8f3a2b1c"
}
```

| Field           | Type   | Required | Description                                  |
| --------------- | ------ | -------- | -------------------------------------------- |
| `token`         | string | Yes      | The validated ticket token                   |
| `validation_id` | string | Yes      | ID returned from the validate response       |

### Response — Success (200)

```json
{
  "decision": "valid",
  "message": "Check-in confirmed. Welcome!",
  "checkin_id": "chk_7b2a1d3e",
  "attendee": {
    "name": "Max Mustermann",
    "group": "Team Alpha"
  },
  "seating": {
    "seat": "A-42",
    "area": "Hall A",
    "directions": "Enter Hall A, turn left past the info desk, Row 4, Seat 42 on the right"
  },
  "addons": [
    { "name": "Pizza Package", "info": "Collect at Booth 3" },
    { "name": "Chair Rental", "info": "Pre-placed at your seat" }
  ],
  "audit_id": "aud_1a2b3c4d",
  "degraded": false
}
```

| Field       | Type   | Nullable | Description                                          |
| ----------- | ------ | -------- | ---------------------------------------------------- |
| `decision`  | string | No       | Always `valid` on successful check-in                |
| `message`   | string | No       | Confirmation message                                 |
| `checkin_id`| string | No       | Check-in record identifier                           |
| `attendee`  | object | No       | Attendee name and group                              |
| `seating`   | object | Yes      | Seat assignment with directions (see Section 3.2.1)  |
| `addons`    | array  | Yes      | Purchased extras list (see Section 3.2.2)            |
| `audit_id`  | string | Yes      | Audit correlation ID                                 |
| `degraded`  | boolean| No       | Always false on successful check-in                  |

The green full-screen overlay shall display the `seating` and `addons` sections prominently so the operator can relay this information to the attendee before dismissing.

---

## 3.4 POST /api/entrance/verify-checkin

**IRS Trace**: LENT-IR-001-005
**Purpose**: Confirm check-in after operator has completed manual verification steps (for `verification_required` decisions).

### Request

```json
{
  "token": "abc123-opaque-ticket-token",
  "validation_id": "val_8f3a2b1c"
}
```

| Field           | Type   | Required | Description                                        |
| --------------- | ------ | -------- | -------------------------------------------------- |
| `token`         | string | Yes      | The validated ticket token                         |
| `validation_id` | string | Yes      | ID from the validate response                      |

This endpoint is called when the operator taps **Confirm & Check In** on the orange verification overlay after completing all manual checks. The backend forwards the confirmation to LanCore, which records that the verification was performed by the operator.

### Response — Success (200)

Same response schema as `POST /api/entrance/checkin` (Section 3.3), including `seating` and `addons`. See Section 3.2.1 and 3.2.2 for object structures.

---

### 3.4.1 Validate response example: `verification_required`

When a ticket requires manual verification, the validate endpoint returns:

```json
{
  "decision": "verification_required",
  "message": "Student ticket — manual verification required before entry.",
  "validation_id": "val_5e6f7a8b",
  "attendee": {
    "name": "Lisa Schmidt",
    "group": null
  },
  "seating": {
    "seat": "B-17",
    "area": "Hall B",
    "directions": "Enter Hall B straight ahead, Row 1, Seat 17 on the left"
  },
  "addons": [
    { "name": "Tournament Entry", "info": null }
  ],
  "verification": {
    "message": "This ticket requires manual verification before entry.",
    "checks": [
      { "label": "Student ID", "instruction": "Must show a valid university student ID with photo" }
    ]
  },
  "override_allowed": false,
  "audit_id": "aud_3c4d5e6f",
  "group_policy": null,
  "degraded": false
}
```

The orange overlay displays the `verification.checks` list. After the operator verifies and taps **Confirm & Check In**, the frontend calls `POST /api/entrance/verify-checkin`.

---

## 3.45 POST /api/entrance/confirm-payment

**IRS Trace**: LENT-IR-001-013
**Purpose**: Confirm on-site payment collection and complete check-in for `payment_required` tickets.

### Request

```json
{
  "token": "abc123-opaque-ticket-token",
  "validation_id": "val_8f3a2b1c",
  "payment_method": "cash",
  "amount": "42.00"
}
```

| Field            | Type   | Required | Constraints        | Description                              |
| ---------------- | ------ | -------- | ------------------- | ---------------------------------------- |
| `token`          | string | Yes      | max:512             | The ticket token                         |
| `validation_id`  | string | Yes      | —                   | ID from the validate response            |
| `payment_method` | string | Yes      | in:cash,card        | Payment method used by attendee          |
| `amount`         | string | Yes      | decimal format      | Confirmed total amount (must match)      |

### Response — Success (200)

```json
{
  "decision": "valid",
  "message": "Payment confirmed. Check-in complete.",
  "checkin_id": "chk_pay_9a8b7c6d",
  "payment_id": "pay_5e4d3c2b",
  "receipt_sent": true,
  "attendee": {
    "name": "Max Mustermann",
    "group": "Team Alpha"
  },
  "seating": {
    "seat": "A-42",
    "area": "Hall A",
    "directions": "Enter Hall A, turn left past the info desk, Row 4, Seat 42 on the right"
  },
  "addons": [
    { "name": "Tournament Entry", "info": null }
  ],
  "audit_id": "aud_7f8a9b0c",
  "degraded": false
}
```

| Field          | Type    | Description                                          |
| -------------- | ------- | ---------------------------------------------------- |
| `payment_id`   | string  | LanCore payment record identifier                    |
| `receipt_sent` | boolean | Whether LanCore triggered PDF receipt email delivery  |

All other fields follow the standard check-in response schema (Section 3.3). The green success overlay shall additionally display "Receipt sent to attendee's email" when `receipt_sent` is true.

### Response — Amount mismatch (422)

```json
{
  "error": "amount_mismatch",
  "message": "Confirmed amount does not match the outstanding balance.",
  "degraded": false,
  "details": {
    "expected": "42.00",
    "received": "35.00"
  }
}
```

---

## 3.5 POST /api/entrance/override

**IRS Trace**: LENT-IR-001-006
**Purpose**: Submit a staff override for a denied or policy-restricted ticket.
**Authorization**: Requires `Moderator` role or higher.

### Request

```json
{
  "token": "abc123-opaque-ticket-token",
  "validation_id": "val_8f3a2b1c",
  "reason": "Group leader confirmed all members are present, system count is stale."
}
```

| Field           | Type   | Required | Constraints        | Description                    |
| --------------- | ------ | -------- | ------------------- | ------------------------------ |
| `token`         | string | Yes      | max:512             | The ticket token               |
| `validation_id` | string | Yes      | —                   | ID from the validate response  |
| `reason`        | string | Yes      | min:10, max:500     | Operator-provided reason       |

### Response — Success (200)

```json
{
  "decision": "valid",
  "message": "Override accepted. Check-in confirmed.",
  "checkin_id": "chk_override_4e5f6a",
  "override_id": "ovr_3d2c1b0a",
  "attendee": {
    "name": "Max Mustermann",
    "seat": "A-42"
  },
  "audit_id": "aud_5e6f7a8b",
  "degraded": false
}
```

### Response — Forbidden (403)

Returned when the operator lacks Moderator role:

```json
{
  "error": "insufficient_role",
  "message": "Override requires Moderator role or higher."
}
```

---

## 3.6 GET /api/entrance/lookup

**IRS Trace**: LENT-IR-001-007
**Purpose**: Search for attendees by name or ticket identifier.

### Request

```http
GET /api/entrance/lookup?q=mustermann HTTP/1.1
Cookie: {session_cookie}
```

| Parameter | Type   | Required | Constraints    | Description            |
| --------- | ------ | -------- | -------------- | ---------------------- |
| `q`       | string | Yes      | min:2, max:100 | Search query string    |

### Response — Success (200)

```json
{
  "results": [
    {
      "token": "abc123-opaque-ticket-token",
      "name": "Max Mustermann",
      "status": "not_checked_in",
      "seat": "A-42",
      "group": "Team Alpha"
    },
    {
      "token": "def456-opaque-ticket-token",
      "name": "Maria Mustermann",
      "status": "checked_in",
      "seat": "A-43",
      "group": "Team Alpha"
    }
  ]
}
```

| Field    | Type   | Description                                          |
| -------- | ------ | ---------------------------------------------------- |
| `token`  | string | Ticket token (for subsequent validate/checkin calls) |
| `name`   | string | Attendee display name                                |
| `status` | string | One of: `not_checked_in`, `checked_in`               |
| `seat`   | string | Assigned seat (if available)                         |
| `group`  | string | Group/team name (if applicable)                      |

---

## 3.7 Error response format

All entrance API endpoints return errors in a consistent format:

```json
{
  "error": "<error_code>",
  "message": "<human_readable_message>",
  "degraded": false,
  "details": {}
}
```

### HTTP status codes

| Status | Usage                                                    |
| ------ | -------------------------------------------------------- |
| 200    | Successful operation (decision may still be "invalid")   |
| 401    | Unauthenticated (session expired)                        |
| 403    | Unauthorized (insufficient role)                         |
| 419    | CSRF token mismatch                                      |
| 422    | Validation error (malformed request)                     |
| 429    | Rate limit exceeded                                      |
| 503    | Degraded mode (LanCore unavailable)                      |

### Standard error codes

| Error Code             | HTTP | Description                               |
| ---------------------- | ---- | ----------------------------------------- |
| `unauthenticated`     | 401  | Session expired or invalid                |
| `insufficient_role`   | 403  | User lacks required role                  |
| `csrf_mismatch`       | 419  | CSRF token expired or invalid             |
| `validation_error`    | 422  | Request payload validation failed         |
| `malformed_token`     | 422  | Token format is invalid                   |
| `rate_limited`        | 429  | Too many requests                         |
| `service_unavailable` | 503  | LanCore API unreachable (degraded mode)   |

### 422 Validation error details

```json
{
  "error": "validation_error",
  "message": "The given data was invalid.",
  "degraded": false,
  "details": {
    "token": ["The token field is required."],
    "reason": ["The reason must be at least 10 characters."]
  }
}
```

### 503 Degraded response

```json
{
  "error": "service_unavailable",
  "message": "LanCore is currently unreachable. Please try again.",
  "degraded": true,
  "details": {}
}
```

---

## 3.8 Rate limiting headers

Rate-limited responses include standard headers:

```http
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 0
Retry-After: 30
```

---

# 4. LENT-IF-002: SSO Authentication Interface

## 4.1 Current implementation

The SSO interface is fully implemented. This section documents the current design.

### 4.1.1 Authorization redirect

**Endpoint**: `GET /auth/redirect`
**Controller**: `LanCoreAuthController::redirect()`

Constructs the SSO URL via `LanCoreClient::ssoAuthorizeUrl()`:

```
{LANCORE_BASE_URL}/apps/{LANCORE_APP_SLUG}/authorize?callback_url={LANCORE_CALLBACK_URL}
```

Redirects the browser to this URL via Inertia location redirect.

### 4.1.2 Callback handling

**Endpoint**: `GET /auth/callback`
**Controller**: `LanCoreAuthController::callback()`

**Query parameters**: `code` (required)

**Flow**:
1. Extract `code` from query string
2. Call `LanCoreClient::exchangeCode(code)` → POST to `{LANCORE_INTERNAL_URL}/api/integration/sso/exchange`
3. Receive: `{ id: int, username: string, email: ?string, roles: string[] }`
4. `UserSyncService::resolveFromLanCore(userData)` → find/create User model
5. `SyncUserRolesFromLanCore::handle(user, roles)` → map and assign highest role
6. `Auth::login(user)` → create session
7. Redirect to `/dashboard`

**Error handling**: On failure, redirect to `/?error=1`

### 4.1.3 Status endpoint

**Endpoint**: `GET /auth/status`
**Response**: `{ "lancore_enabled": bool }`

---

## 4.2 Role webhook interface

**Endpoint**: `POST /api/webhooks/roles` (and alias `POST /api/webhook/roles`)
**Controller**: `LanCoreRolesWebhookController`

### Request

```http
POST /api/webhooks/roles HTTP/1.1
Content-Type: application/json
X-Webhook-Signature: sha256={hmac_hex}
X-Webhook-Event: user.roles_updated
```

```json
{
  "user": {
    "id": 42,
    "roles": ["admin", "moderator", "user"]
  }
}
```

### Signature verification

```
HMAC-SHA256(request_body, LANCORE_WEBHOOK_SECRET) == X-Webhook-Signature header value
```

The signature header format is: `sha256={hex_digest}`

### Response

| Status | Condition                    |
| ------ | ---------------------------- |
| 200    | Roles synced successfully    |
| 202    | User not found (accepted)    |
| 401    | Invalid signature            |
| 422    | Invalid event or payload     |

---

# 5. LENT-IF-003: Platform Validation Interface (LanCore API)

## 5.1 Transport configuration

| Property         | Value                                    | Config Key              |
| ---------------- | ---------------------------------------- | ----------------------- |
| Base URL         | `LANCORE_INTERNAL_URL` or `LANCORE_BASE_URL` | `lancore.internal_url` |
| Authentication   | Bearer token                             | `lancore.token`         |
| Timeout          | 5 seconds (default)                      | `lancore.timeout`       |
| Retries          | 2 (default)                              | `lancore.retries`       |
| Retry delay      | 100ms (default)                          | `lancore.retry_delay`   |

All requests are made via the `LanCoreClient::http()` method which configures these parameters.

## 5.2 Consumed LanCore endpoints

The following endpoints are expected to be provided by LanCore. Exact paths and payload schemas will be confirmed with the LanCore team.

### 5.2.1 POST /api/entrance/validate (expected)

**Purpose**: Validate a ticket token

**Request body (sent by LanEntrance)**:
```json
{
  "token": "abc123-opaque-ticket-token",
  "operator_id": 42,
  "operator_session": "sess_abc123",
  "timestamp": "2026-04-06T14:30:00Z",
  "client_info": "Mozilla/5.0..."
}
```

**Expected response** (see Section 3.2 for full schema):
```json
{
  "decision": "valid",
  "message": "Ticket is valid.",
  "validation_id": "val_8f3a2b1c",
  "attendee": { "name": "...", "group": "..." },
  "seating": { "seat": "A-42", "area": "Hall A", "directions": "..." },
  "addons": [ { "name": "...", "info": "..." } ],
  "verification": null,
  "override_allowed": false,
  "audit_id": "aud_9e4d3c2b",
  "group_policy": null
}
```

### 5.2.2 POST /api/entrance/checkin (expected)

**Purpose**: Confirm check-in

**Request body**:
```json
{
  "token": "abc123-opaque-ticket-token",
  "validation_id": "val_8f3a2b1c",
  "operator_id": 42,
  "timestamp": "2026-04-06T14:30:05Z"
}
```

### 5.2.25 POST /api/entrance/verify-checkin (expected)

**Purpose**: Confirm check-in after operator completes manual verification

**Request body**:
```json
{
  "token": "abc123-opaque-ticket-token",
  "validation_id": "val_5e6f7a8b",
  "operator_id": 42,
  "timestamp": "2026-04-06T14:30:07Z"
}
```

**Expected response**: Same schema as check-in confirmation (Section 5.2.2), including `seating` and `addons`.

### 5.2.26 POST /api/entrance/confirm-payment (expected)

**Purpose**: Record on-site payment and complete check-in

**Request body**:
```json
{
  "token": "abc123-opaque-ticket-token",
  "validation_id": "val_8f3a2b1c",
  "payment_method": "cash",
  "amount": "42.00",
  "operator_id": 42,
  "timestamp": "2026-04-06T14:30:15Z"
}
```

**Expected response**: Standard check-in response with additional fields:
* `payment_id`: LanCore payment record ID
* `receipt_sent`: Boolean — LanCore generates PDF receipt and emails it to the attendee

**LanCore responsibilities** (not LanEntrance):
* Record the payment in LanCore's authoritative payment ledger
* Generate a PDF receipt document
* Send the receipt to the attendee's email address on file
* Return `receipt_sent: true` when email delivery is triggered

### 5.2.3 POST /api/entrance/override (expected)

**Purpose**: Submit staff override

**Request body**:
```json
{
  "token": "abc123-opaque-ticket-token",
  "validation_id": "val_8f3a2b1c",
  "reason": "Group leader confirmed all members present.",
  "operator_id": 42,
  "timestamp": "2026-04-06T14:30:10Z"
}
```

### 5.2.4 GET /api/entrance/search (expected)

**Purpose**: Search for attendees

**Query parameters**: `q={search_query}`, `operator_id={id}`

### 5.2.5 POST /api/integration/sso/exchange (implemented)

**Purpose**: Exchange SSO authorization code for user data
**Status**: Currently consumed by `LanCoreClient::exchangeCode()`

**Request body**:
```json
{
  "code": "authorization_code_from_callback"
}
```

**Response**:
```json
{
  "id": 42,
  "username": "max.mustermann",
  "email": "max@example.com",
  "roles": ["admin", "user"]
}
```

---

## 5.3 Error responses from LanCore

| HTTP Status | Meaning                                | LanEntrance handling              |
| ----------- | -------------------------------------- | --------------------------------- |
| 200         | Success                                | Map response to frontend format   |
| 401         | Invalid or expired bearer token        | Log critical, return service error|
| 403         | Operation not permitted                | Return 403 to frontend           |
| 404         | Token/attendee not found               | Return `invalid` decision        |
| 422         | Validation error                       | Forward details to frontend      |
| 429         | Rate limited by LanCore               | Return rate limit error           |
| 500         | LanCore server error                   | Return degraded response         |
| Timeout     | Request exceeded configured timeout    | Return degraded response         |
| Connection  | Cannot reach LanCore                   | Return degraded response         |

### 5.3.1 Expected LanCore error response body

LanCore error responses (4xx, 5xx) are expected to return a JSON body. The exact format is pending confirmation from the LanCore team. The `LanCoreValidationService` shall handle the following assumed structure:

```json
{
  "error": "<error_code>",
  "message": "<human_readable_message>",
  "details": {}
}
```

If LanCore returns a non-JSON response or an empty body on error, the backend shall fall back to:
* HTTP 4xx → map to an `invalid` decision with the HTTP status message
* HTTP 5xx / timeout / connection failure → map to a degraded response

**Note**: This section documents assumed behavior. The concrete error format must be confirmed with the LanCore team before Phase 2 design review (see SDP Section 6, "LanCore API contract finalized" milestone).

---

# 6. Inertia.js page interface

## 6.1 Shared props (all pages)

Provided by `HandleInertiaRequests` middleware:

```typescript
interface SharedProps {
  name: string          // Application name (APP_NAME)
  auth: {
    user: User | null   // Authenticated user object
  }
  sidebarOpen: boolean  // Sidebar state from cookie
}
```

## 6.2 Entrance page props (Phase 2)

```typescript
interface EntrancePageProps extends SharedProps {
  canScan: boolean        // User has scan permission
  canOverride: boolean    // User has override permission (Moderator+)
  maintenanceMode: boolean // Entrance is in maintenance
  eventName?: string      // Current event name (if configured)
}
```

---

# 7. Notes

## 7.1 LanCore API dependency

Sections 5.2.1 through 5.2.4 describe the **expected** LanCore API contracts. These will be finalized in coordination with the LanCore team. The LanEntrance backend is designed to adapt to minor contract changes via the `LanCoreValidationService` mapping layer.

## 7.2 Versioning

API contracts in this document correspond to LanEntrance v0.1. Future versions may introduce:
* API versioning via URL prefix (`/api/v2/entrance/`)
* Additional decision outcomes
* Extended attendee context fields
