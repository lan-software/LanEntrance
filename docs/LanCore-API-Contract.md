LanCore API Contract for LanEntrance
===

> **Status**: Draft — pending confirmation from the LanCore team
> **Consumer**: LanEntrance (entrance check-in subsystem)
> **Date**: 2026-04-06
> **Version**: 0.1

This document defines the API endpoints that **LanCore must provide** for LanEntrance to function. LanEntrance is a mobile-first entrance check-in app that validates tickets, collects on-site payments, and manages attendee admission at LAN events. LanCore is the authoritative source of truth for all ticket, payment, check-in, and audit state.

---

# 1. QR code token format (LanCore-owned)

LanEntrance treats QR code payloads as **opaque tokens**. It does not parse, decode, or validate the internal structure of the QR content. The raw string scanned from the QR code is forwarded verbatim to LanCore in the `token` field.

**LanCore is responsible for defining the QR code format**, including:

- Token structure and encoding (e.g., UUID, signed JWT, base64, custom format)
- Token generation when tickets are issued
- Token validation and lookup when received via the `/api/entrance/validate` endpoint
- Whether the token embeds data or is a lookup key into LanCore's database

**LanEntrance constraints on the token**:

| Constraint       | Value                                                              |
| ---------------- | ------------------------------------------------------------------ |
| Max length       | 512 characters (enforced by LanEntrance request validation)        |
| Content          | Any UTF-8 string — LanEntrance does not inspect or parse it        |
| Sensitivity      | Must **not** contain PII (per SSS LENT-3.8-005)                   |
| URL requirement  | Must **not** require the token to be a URL (per SSS LENT-3.12-002)|
| Quiet zone       | QR codes should have adequate white margin for reliable scanning   |

**Recommendation**: Use a short, opaque, non-guessable identifier (e.g., a UUID or HMAC-signed short token) that LanCore can resolve server-side. This keeps QR codes compact (faster scanning) and avoids exposing ticket details if a QR code is photographed.

---

# 2. Transport & authentication

| Property       | Value                                                        |
| -------------- | ------------------------------------------------------------ |
| Protocol       | HTTPS (REST, JSON)                                           |
| Authentication | Bearer token in `Authorization` header                       |
| Content-Type   | `application/json` (requests and responses)                  |
| Timeout        | LanEntrance times out after **5 seconds** (configurable)     |
| Retries        | Up to **2 retries** with 100ms delay on failure              |
| Base URL       | Configurable via `LANCORE_INTERNAL_URL` (service-to-service) |

```
Authorization: Bearer {LANCORE_TOKEN}
```

All timestamps use **ISO 8601 UTC** format (e.g., `2026-04-06T14:30:00Z`).
All monetary amounts are **decimal strings** (e.g., `"42.00"`), never floats.

---

# 3. Audit metadata

Every request from LanEntrance includes the following metadata fields alongside the endpoint-specific payload. LanCore should use these for audit trail correlation.

| Field              | Type   | Description                                    |
| ------------------ | ------ | ---------------------------------------------- |
| `operator_id`      | int    | LanCore user ID of the authenticated staff member |
| `operator_session` | string | Server-side session identifier                 |
| `timestamp`        | string | ISO 8601 UTC timestamp of the request          |
| `client_info`      | string | User-Agent string from the operator's browser (may be null) |

---

# 4. Endpoints

## 3.1 POST /api/entrance/validate

Validate a scanned or manually entered ticket token. Returns a decision with attendee context.

### Request

```json
{
  "token": "abc123-opaque-ticket-token",
  "operator_id": 42,
  "operator_session": "sess_abc123",
  "timestamp": "2026-04-06T14:30:00Z",
  "client_info": "Mozilla/5.0..."
}
```

| Field   | Type   | Required | Constraints | Description                     |
| ------- | ------ | -------- | ----------- | ------------------------------- |
| `token` | string | Yes      | max 512     | Opaque ticket token from QR code or manual entry |

### Response (200 OK)

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
    { "name": "Chair Rental", "info": "Pre-placed at your seat" }
  ],
  "verification": null,
  "payment": null,
  "override_allowed": false,
  "audit_id": "aud_9e4d3c2b",
  "group_policy": null,
  "degraded": false
}
```

### Response fields

| Field              | Type    | Nullable | Description                                                    |
| ------------------ | ------- | -------- | -------------------------------------------------------------- |
| `decision`         | string  | No       | See [Decision types](#31a-decision-types) below                |
| `message`          | string  | No       | Human-readable guidance for the operator                       |
| `validation_id`    | string  | No       | Stable unique ID used in all subsequent requests for this flow |
| `attendee`         | object  | Yes      | `{ name: string, group: string \| null }`                      |
| `seating`          | object  | Yes      | See [Seating object](#31b-seating-object)                      |
| `addons`           | array   | Yes      | See [Addons array](#31c-addons-array)                          |
| `verification`     | object  | Yes      | See [Verification object](#31d-verification-object). Only when `decision = "verification_required"` |
| `payment`          | object  | Yes      | See [Payment object](#31e-payment-object). Only when `decision = "payment_required"` |
| `override_allowed` | boolean | No       | `true` if staff can override this decision                     |
| `audit_id`         | string  | Yes      | LanCore audit trail correlation ID                             |
| `group_policy`     | object  | Yes      | See [Group policy object](#31f-group-policy-object). Only for group-related decisions |
| `degraded`         | boolean | No       | Always `false` for successful responses                        |

### 3.1a Decision types

| Decision               | When to return                                                          | Expected `seating`/`addons` | Expected special object |
| ---------------------- | ----------------------------------------------------------------------- | --------------------------- | ----------------------- |
| `valid`                | Ticket is valid, check-in may proceed                                   | Yes (if assigned)           | —                       |
| `invalid`              | Ticket not found, revoked, or malformed                                 | No                          | —                       |
| `already_checked_in`   | Ticket was already used for entry                                       | No                          | —                       |
| `denied_by_policy`     | Policy restriction with no override available                           | No                          | `group_policy`          |
| `override_possible`    | Policy restriction but authorized staff can override                    | No                          | `group_policy`          |
| `verification_required`| Ticket requires manual operator checks before entry (e.g., student ID) | Yes (if assigned)           | `verification`          |
| `payment_required`     | Ticket was purchased with "Pay on Site" — payment must be collected     | Yes (if assigned)           | `payment`               |

### 3.1b Seating object

Returned when the attendee has an assigned seat. Omit or set to `null` when no assignment exists.

```json
{
  "seat": "A-42",
  "area": "Hall A",
  "directions": "Enter Hall A, turn left past the info desk, Row 4, Seat 42 on the right"
}
```

| Field        | Type   | Required | Description                                        |
| ------------ | ------ | -------- | -------------------------------------------------- |
| `seat`       | string | Yes      | Seat identifier / label                            |
| `area`       | string | No       | Hall, zone, or area name                           |
| `directions` | string | No       | Human-readable walking directions from the entrance |

### 3.1c Addons array

Returned when the ticket includes purchased extras. Omit or set to `null` when there are none.

```json
[
  { "name": "Pizza Package", "info": "Collect at Booth 3" },
  { "name": "Chair Rental", "info": "Pre-placed at your seat" },
  { "name": "Tournament Entry", "info": null }
]
```

| Field  | Type   | Required | Description                                  |
| ------ | ------ | -------- | -------------------------------------------- |
| `name` | string | Yes      | Addon display name                           |
| `info` | string | No       | Pickup or redemption instructions (nullable) |

### 3.1d Verification object

**Only returned when `decision = "verification_required"`.** Lists conditions the entrance operator must manually verify before admitting the attendee.

```json
{
  "message": "This ticket requires manual verification before entry.",
  "checks": [
    { "label": "Student ID", "instruction": "Must show a valid university student ID with photo" },
    { "label": "Age Verification", "instruction": "Attendee must be 18 or older" }
  ]
}
```

| Field                  | Type   | Required | Description                                       |
| ---------------------- | ------ | -------- | ------------------------------------------------- |
| `message`              | string | Yes      | Summary message for the operator                  |
| `checks`               | array  | Yes      | List of verification actions                      |
| `checks[].label`       | string | Yes      | What to verify (e.g., "Student ID")               |
| `checks[].instruction` | string | No       | How to verify (e.g., "Must show photo ID")        |

### 3.1e Payment object

**Only returned when `decision = "payment_required"`.** Contains the outstanding balance and accepted on-site payment methods.

```json
{
  "amount": "42.00",
  "currency": "EUR",
  "items": [
    { "name": "Weekend Ticket", "price": "35.00" },
    { "name": "Tournament Entry", "price": "7.00" }
  ],
  "methods": ["cash", "card"]
}
```

| Field          | Type   | Required | Description                                            |
| -------------- | ------ | -------- | ------------------------------------------------------ |
| `amount`       | string | Yes      | Total amount due (decimal string, e.g., `"42.00"`)     |
| `currency`     | string | Yes      | ISO 4217 currency code (e.g., `"EUR"`)                 |
| `items`        | array  | Yes      | Breakdown of payable items                             |
| `items[].name` | string | Yes      | Item display name                                      |
| `items[].price`| string | Yes      | Item price (decimal string)                            |
| `methods`      | array  | Yes      | Accepted on-site payment method identifiers            |

**Known method identifiers**: `"cash"`, `"card"`. LanCore may add others in the future — LanEntrance renders them generically.

### 3.1f Group policy object

Returned when the decision involves a group policy restriction (`denied_by_policy` or `override_possible`).

```json
{
  "rule": "all_members_present",
  "message": "All group members must check in together.",
  "members_checked_in": 2,
  "members_total": 5
}
```

| Field                | Type   | Required | Description                          |
| -------------------- | ------ | -------- | ------------------------------------ |
| `rule`               | string | Yes      | Policy rule identifier               |
| `message`            | string | Yes      | Human-readable policy description    |
| `members_checked_in` | int    | Yes      | Members already checked in           |
| `members_total`      | int    | Yes      | Total group member count             |

---

## 3.2 POST /api/entrance/checkin

Confirm check-in after a `valid` validation. Called when the operator taps "Next Scan" or the system auto-confirms.

### Request

```json
{
  "token": "abc123-opaque-ticket-token",
  "validation_id": "val_8f3a2b1c",
  "operator_id": 42,
  "operator_session": "sess_abc123",
  "timestamp": "2026-04-06T14:30:05Z",
  "client_info": "Mozilla/5.0..."
}
```

### Response (200 OK)

```json
{
  "decision": "valid",
  "message": "Check-in confirmed. Welcome!",
  "validation_id": "val_8f3a2b1c",
  "checkin_id": "chk_7b2a1d3e",
  "attendee": { "name": "Max Mustermann", "group": "Team Alpha" },
  "seating": { "seat": "A-42", "area": "Hall A", "directions": "..." },
  "addons": [ { "name": "Pizza Package", "info": "Collect at Booth 3" } ],
  "audit_id": "aud_1a2b3c4d",
  "degraded": false
}
```

| Extra field  | Type   | Description                  |
| ------------ | ------ | ---------------------------- |
| `checkin_id` | string | LanCore check-in record ID   |

All other fields follow the validate response schema.

---

## 3.3 POST /api/entrance/verify-checkin

Confirm check-in after the operator completed manual verification steps. Called for tickets that returned `verification_required`.

### Request

Same as [POST /api/entrance/checkin](#32-post-apientrancecheckin).

### Response (200 OK)

Same schema as checkin, including `checkin_id`, `seating`, and `addons`.

---

## 3.4 POST /api/entrance/confirm-payment

Confirm that on-site payment was collected and complete the check-in. Called for tickets that returned `payment_required`.

**LanCore must** on successful processing:
1. Record the payment in its authoritative payment ledger
2. Generate a PDF receipt
3. Email the receipt to the attendee's email address on file

### Request

```json
{
  "token": "abc123-opaque-ticket-token",
  "validation_id": "val_pay00001",
  "payment_method": "cash",
  "amount": "42.00",
  "operator_id": 42,
  "operator_session": "sess_abc123",
  "timestamp": "2026-04-06T14:30:15Z",
  "client_info": "Mozilla/5.0..."
}
```

| Extra field      | Type   | Required | Constraints      | Description                               |
| ---------------- | ------ | -------- | ---------------- | ----------------------------------------- |
| `payment_method` | string | Yes      | e.g., cash, card | Method the attendee used to pay           |
| `amount`         | string | Yes      | decimal string   | Total amount collected (must match validation response) |

### Response (200 OK)

```json
{
  "decision": "valid",
  "message": "Payment confirmed. Check-in complete.",
  "validation_id": "val_pay00001",
  "checkin_id": "chk_pay_9a8b",
  "payment_id": "pay_5e4d3c2b",
  "receipt_sent": true,
  "attendee": { "name": "Jan Weber", "group": "Team Beta" },
  "seating": { "seat": "C-03", "area": "Hall C", "directions": "..." },
  "addons": [ { "name": "Tournament Entry", "info": null } ],
  "audit_id": "aud_pay_conf",
  "degraded": false
}
```

| Extra field    | Type    | Description                                           |
| -------------- | ------- | ----------------------------------------------------- |
| `payment_id`   | string  | LanCore payment record identifier                     |
| `receipt_sent` | boolean | `true` if PDF receipt email delivery was triggered     |

### Error — Amount mismatch (422)

If the confirmed amount does not match the outstanding balance:

```json
{
  "error": "amount_mismatch",
  "message": "Confirmed amount does not match the outstanding balance.",
  "details": {
    "expected": "42.00",
    "received": "35.00"
  }
}
```

---

## 3.5 POST /api/entrance/override

Submit a staff override for a restricted ticket. Only sent by operators with Moderator role or higher (enforced by LanEntrance).

### Request

```json
{
  "token": "abc123-opaque-ticket-token",
  "validation_id": "val_ovrde001",
  "reason": "Group leader confirmed all members are present.",
  "operator_id": 42,
  "operator_session": "sess_abc123",
  "timestamp": "2026-04-06T14:30:10Z",
  "client_info": "Mozilla/5.0..."
}
```

| Extra field | Type   | Required | Constraints      | Description                        |
| ----------- | ------ | -------- | ---------------- | ---------------------------------- |
| `reason`    | string | Yes      | 10-500 chars     | Operator-provided override reason  |

### Response (200 OK)

```json
{
  "decision": "valid",
  "message": "Override accepted. Check-in confirmed.",
  "validation_id": "val_ovrde001",
  "checkin_id": "chk_ovrd_4e5f",
  "override_id": "ovr_3d2c1b0a",
  "attendee": { "name": "Sarah Braun", "group": "Team Gamma" },
  "seating": { "seat": "D-11", "area": "Hall D", "directions": "..." },
  "addons": null,
  "audit_id": "aud_5e6f7a8b",
  "degraded": false
}
```

| Extra field   | Type   | Description                     |
| ------------- | ------ | ------------------------------- |
| `override_id` | string | LanCore override record ID      |

---

## 3.6 GET /api/entrance/search

Search for attendees by name or ticket identifier.

### Request

```
GET /api/entrance/search?q=mustermann&operator_id=42&operator_session=sess_abc123&timestamp=2026-04-06T14:30:00Z
```

| Parameter | Type   | Required | Constraints | Description          |
| --------- | ------ | -------- | ----------- | -------------------- |
| `q`       | string | Yes      | 2-100 chars | Search query string  |

Audit metadata fields (`operator_id`, `operator_session`, `timestamp`, `client_info`) are sent as query parameters.

### Response (200 OK)

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

| Field             | Type   | Description                                              |
| ----------------- | ------ | -------------------------------------------------------- |
| `results`         | array  | Array of matching attendees                              |
| `results[].token` | string | Opaque ticket token (for subsequent validate calls)      |
| `results[].name`  | string | Attendee display name                                    |
| `results[].status`| string | `"not_checked_in"` or `"checked_in"`                     |
| `results[].seat`  | string | Assigned seat (nullable)                                 |
| `results[].group` | string | Group/team name (nullable)                               |

---

# 5. Error responses

All endpoints should return errors in this format:

```json
{
  "error": "<error_code>",
  "message": "<human_readable_message>",
  "details": {}
}
```

### HTTP status code usage

| Status | When                             | LanEntrance behavior                  |
| ------ | -------------------------------- | ------------------------------------- |
| 200    | Success (decision may be deny)   | Process response normally             |
| 404    | Token/attendee not found         | Map to `invalid` decision             |
| 422    | Validation error                 | Forward `details` to frontend         |
| 429    | Rate limited                     | Show rate limit message               |
| 500    | Server error                     | Show degraded mode banner             |
| 401    | Invalid/expired bearer token     | Log critical, show service error      |
| 403    | Operation not permitted          | Show access denied                    |

When LanCore is **unreachable** (timeout, connection refused), LanEntrance enters degraded mode and informs the operator that results are unavailable. No check-in is recorded.

---

# 6. Existing endpoints (already implemented)

These endpoints are already in production and do not need changes.

## 5.1 POST /api/integration/sso/exchange

Exchange an SSO authorization code for user data.

**Request**: `{ "code": "<64-char authorization code>" }`

**Response**: `{ "data": { "id": 42, "username": "max", "email": "max@example.com", "roles": ["admin", "user"] } }`

Note: Response is wrapped in a `data` object. `email` is nullable.

## 5.2 Webhook: user.roles_updated

LanCore pushes role changes to `POST /api/webhooks/roles` on LanEntrance.

**Headers**: `X-Webhook-Signature: sha256={hmac}`, `X-Webhook-Event: user.roles_updated`

**Body**: `{ "user": { "id": 42, "roles": ["admin", "moderator", "user"] } }`

Signature: `HMAC-SHA256(body, LANCORE_ROLES_WEBHOOK_SECRET)`

---

# 7. LanCore responsibilities summary

| Responsibility                | Endpoint(s) affected            | Notes                                    |
| ----------------------------- | ------------------------------- | ---------------------------------------- |
| Ticket validation logic       | validate                        | Determine decision type for each token   |
| Attendee data                 | validate, checkin, search       | Return name, group, seat as authorized   |
| Seating assignments           | validate, checkin, verify, pay  | Seat + area + directions                 |
| Addon data                    | validate, checkin, verify, pay  | Purchased extras list                    |
| Verification requirements     | validate                        | What operator must check (labels + instructions) |
| Payment calculation           | validate                        | Amount, currency, item breakdown, methods |
| Check-in recording            | checkin, verify, pay, override  | Authoritative check-in state             |
| Payment recording             | confirm-payment                 | Authoritative payment ledger             |
| **PDF receipt generation**    | confirm-payment                 | Generate receipt for on-site payments    |
| **Receipt email delivery**    | confirm-payment                 | Send receipt to attendee's email         |
| Override recording            | override                        | Record override with reason + operator   |
| Audit trail                   | All                             | Generate and store audit IDs             |
| Group policy enforcement      | validate                        | Evaluate group ticket rules              |
| SSO identity                  | sso/exchange                    | User ID, username, email, roles          |
| Role change notifications     | webhook                         | Push role updates to LanEntrance         |

---

# 8. Testing

LanEntrance has **12 JSON fixture files** in `tests/Fixtures/LanCore/` representing the expected response shapes for each scenario. These can be used as reference implementations:

| Fixture file                          | Decision type          |
| ------------------------------------- | ---------------------- |
| `validate-valid.json`                 | `valid`                |
| `validate-invalid.json`              | `invalid`              |
| `validate-already-checked-in.json`   | `already_checked_in`   |
| `validate-verification-required.json`| `verification_required`|
| `validate-payment-required.json`     | `payment_required`     |
| `validate-override-possible.json`    | `override_possible`    |
| `validate-denied-by-policy.json`     | `denied_by_policy`     |
| `checkin-success.json`               | Check-in confirmation  |
| `verify-checkin-success.json`        | Verify + check-in      |
| `confirm-payment-success.json`       | Payment + check-in     |
| `override-success.json`             | Override + check-in    |
| `lookup-results.json`               | Search results         |

These fixtures serve as the **acceptance contract** — LanCore's responses must match these shapes for LanEntrance to function correctly.
