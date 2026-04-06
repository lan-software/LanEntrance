LanEntrance System/Subsystem Specification (SSS)
===

# 1. Scope

This section identifies the specification, the system context, and the purpose of this document.

## 1.1 Identification

* Title: LanEntrance System/Subsystem Specification
* Short Name: LanEntrance SSS
* System Name: LanEntrance
* Ecosystem: LanSoftware
* Parent Platform: LanCore
* Specification Type: Subsystem Specification
* Version: Draft v0.1
* Status: Working Draft

LanEntrance is a dedicated entrance management subsystem for LAN events. It consists of a mobile-first Single Page Application (SPA) and a dedicated backend service that orchestrates entrance workflows against LanCore.

## 1.2 System overview

LanEntrance is the operational entrance subsystem of the LanSoftware ecosystem. Its purpose is to enable secure, fast, and auditable attendee admission at LAN events.

LanEntrance is intended for use by entrance staff and organizers. It integrates with LanCore for identity, authorization, event data, ticket data, check-in state, and audit records. LanEntrance owns the entrance-specific user experience and its own backend service layer, but it does not own the authoritative ticket or attendance state.

Project context:

* Sponsor: LAN event organizers
* Acquirer/User: Organizers and entrance staff
* Developer: LanSoftware project
* Support agencies: LanSoftware operations and maintainers

Operating sites:

* Event entrances and check-in points
* Mobile devices carried by staff
* Desktop/laptop stations at organizer desks

Relevant documents:

* LanEntrance Operational Concept Document (OCD)
* LanCore architecture and integration documents
* LanSoftware domain architecture documentation

## 1.3 Document overview

This document defines the acceptance-relevant requirements for LanEntrance. It specifies capabilities, interfaces, quality factors, security expectations, operational constraints, and qualification provisions.

Security and privacy considerations:

* QR codes shall not contain sensitive attendee information.
* Authoritative validation and audit shall reside in LanCore.
* Only authenticated and authorized personnel shall be able to perform operational check-in actions.
* The specification includes requirements related to privacy, auditability, authorization, and data minimization.

---

# 2. Referenced documents

| ID      | Title                                      |        Revision / Version | Date                  | Source              |
| ------- | ------------------------------------------ | ------------------------: | --------------------- | ------------------- |
| REF-001 | LanEntrance Operational Concept Document   |                Draft v0.2 | Current project draft | LanSoftware project |
| REF-002 | LanCore Architecture Specification         |     Current working draft | Current project draft | LanSoftware project |
| REF-003 | LanCore SSO Integration Documentation      |     Current working draft | Current project draft | LanSoftware project |
| REF-004 | LanSoftware Domain Architecture Guidelines |     Current working draft | Current project draft | LanSoftware project |
| REF-005 | OpenID Connect Core                        |                       1.0 | External standard     | OpenID Foundation   |
| REF-006 | OAuth 2.0                                  |                  RFC 6749 | External standard     | IETF                |
| REF-007 | JSON Web Token (JWT)                       |                  RFC 7519 | External standard     | IETF                |
| REF-008 | HTTP Semantics                             |                  RFC 9110 | External standard     | IETF                |
| REF-009 | TLS                                        | Current supported version | External standard     | IETF                |

---

# 3. Requirements

Each requirement in this specification is assigned a unique identifier of the form `LENT-<section>-<number>`. Qualification methods are identified as:

* **DEM** = Demonstration
* **TST** = Test
* **ANL** = Analysis
* **INSP** = Inspection

## 3.1 Required states and modes

LanEntrance shall support the following states and modes:

| State / Mode       | Description                                                                                     |
| ------------------ | ----------------------------------------------------------------------------------------------- |
| `IDLE`             | Application is loaded but no active scan or lookup is in progress.                              |
| `READY`            | Authenticated user is authorized and scanner or manual lookup is ready.                         |
| `ACTIVE_SCAN`      | Camera-based scan is in progress.                                                               |
| `ACTIVE_LOOKUP`    | Manual lookup or ticket search is in progress.                                                  |
| `DECISION_DISPLAY` | Validation result is being shown to the operator.                                               |
| `DEGRADED`         | LanEntrance is running with reduced capability due to connectivity or dependent service issues. |
| `MAINTENANCE`      | Administrative or maintenance mode for non-event operations.                                    |

Requirements:

* **LENT-3.1-001**: The system shall expose behavior consistent with the states `IDLE`, `READY`, `ACTIVE_SCAN`, `ACTIVE_LOOKUP`, `DECISION_DISPLAY`, `DEGRADED`, and `MAINTENANCE`. **[INSP, TST]**
* **LENT-3.1-002**: The system shall transition to `READY` only after successful authentication and authorization of the operator. **[TST]**
* **LENT-3.1-003**: The system shall transition to `DEGRADED` when required upstream services are unreachable or when connectivity prevents completion of an operational request within configured thresholds. **[TST, ANL]**
* **LENT-3.1-004**: In `DEGRADED` mode, the system shall clearly indicate reduced capabilities to the operator and shall not falsely represent unresolved check-in operations as completed. **[DEM, TST]**

## 3.2 System capability requirements

### 3.2.1 Authentication and session capability

* **LENT-3.2.1-001**: The system shall require operator authentication through LanCore-integrated SSO before granting access to entrance functions. **[TST]**
* **LENT-3.2.1-002**: The system shall associate each operational action with an authenticated operator identity. **[TST, INSP]**
* **LENT-3.2.1-003**: The system shall terminate or invalidate local operational access when the authenticated session expires or is revoked. **[TST]**
* **LENT-3.2.1-004**: The system shall support use from modern mobile browsers and desktop browsers without requiring installation of a native application. **[DEM, TST]**

### 3.2.2 Authorization capability

* **LENT-3.2.2-001**: The system shall permit entrance actions only to operators authorized through LanCore-provided roles or permissions. **[TST]**
* **LENT-3.2.2-002**: The system shall deny unauthorized check-in, override, or sensitive lookup operations and present a clear denial message to the operator. **[DEM, TST]**
* **LENT-3.2.2-003**: The system shall support distinct permissions for scan, validate, check-in, override, and attendee detail access. **[INSP, TST]**

### 3.2.3 QR scan capability

* **LENT-3.2.3-001**: The system shall support camera-based QR code scanning in supported browsers. **[DEM, TST]**
* **LENT-3.2.3-002**: The system shall accept QR payloads containing opaque data and shall not require QR payloads to contain a fully qualified URL. **[TST]**
* **LENT-3.2.3-003**: The system shall parse and validate QR payload structure before forwarding operational requests to LanCore. **[TST]**
* **LENT-3.2.3-004**: The system shall reject malformed QR payloads and present an operator-readable error state without creating a false successful check-in. **[DEM, TST]**
* **LENT-3.2.3-005**: The system shall support a manual lookup fallback when scanning is not possible or fails. **[DEM, TST]**

### 3.2.4 Validation and check-in capability

* **LENT-3.2.4-001**: The system shall submit scan and lookup validation requests through the LanEntrance backend service. **[INSP, TST]**
* **LENT-3.2.4-002**: The system shall use LanCore as the authoritative decision source for ticket validity, check-in state, and policy enforcement. **[INSP, TST]**
* **LENT-3.2.4-003**: The system shall support, at minimum, the decision outcomes `valid`, `invalid`, `already_checked_in`, `denied_by_policy`, `override_possible`, `verification_required`, and `payment_required`. **[TST]**
* **LENT-3.2.4-004**: The system shall display the decision outcome to the operator within 2 seconds for 95 percent of requests under nominal operating conditions. **[TST, ANL]**
* **LENT-3.2.4-005**: The system shall prevent the UI from representing a check-in as completed until a successful authoritative response has been received from LanCore. **[TST]**
* **LENT-3.2.4-006**: The system shall support re-entry workflows when enabled by LanCore policy. **[TST]**
* **LENT-3.2.4-007**: When LanCore returns a `verification_required` decision, the system shall display the required verification actions (e.g., check student ID, verify team membership, confirm age) to the operator before allowing check-in to proceed. **[DEM, TST]**
* **LENT-3.2.4-008**: Upon successful check-in, the system shall display the attendee's seating information including seat identifier, area or hall designation, and directional guidance when provided by LanCore. **[DEM, TST]**
* **LENT-3.2.4-009**: Upon successful check-in, the system shall display a list of addons purchased with the ticket (e.g., merchandise, food packages, equipment rentals) when provided by LanCore, so the operator can inform the attendee of their entitlements. **[DEM, TST]**

### 3.2.5 Group ticket handling capability

* **LENT-3.2.5-001**: The system shall support entrance decisions influenced by group ticket policy as provided by LanCore. **[TST]**
* **LENT-3.2.5-002**: The system shall display operator guidance when a group policy prevents immediate check-in. **[DEM, TST]**
* **LENT-3.2.5-003**: The system shall support explicit override workflows for authorized staff when LanCore indicates that an override is permitted. **[TST]**
* **LENT-3.2.5-004**: The system shall require an override reason when performing an operator override. **[TST]**

### 3.2.9 On-site payment capability

Tickets purchased with a "Pay on Site" payment provider require payment collection at the entrance before admission. LanEntrance facilitates the payment interaction between operator and attendee; LanCore owns the authoritative payment record, PDF receipt generation, and receipt delivery.

* **LENT-3.2.9-001**: When LanCore returns a `payment_required` decision, the system shall display the outstanding amount, a breakdown of payable items, and the accepted on-site payment methods to the operator. **[DEM, TST]**
* **LENT-3.2.9-002**: The system shall require the operator to select the payment method used (e.g., cash, card) and explicitly confirm that payment has been collected before proceeding with check-in. **[DEM, TST]**
* **LENT-3.2.9-003**: The system shall submit the payment confirmation (amount, method, operator identity) to LanCore through the LanEntrance backend. The system shall not represent the check-in as completed until LanCore confirms the payment has been recorded. **[TST]**
* **LENT-3.2.9-004**: Upon successful payment confirmation, LanCore shall generate a PDF receipt and send it to the attendee via email. LanEntrance shall inform the operator that a receipt has been sent. **[DEM, TST]**
* **LENT-3.2.9-005**: The system shall not store authoritative payment records. All payment state shall reside in LanCore. **[INSP]**
* **LENT-3.2.9-006**: The system shall prevent the operator from bypassing the payment step for `payment_required` tickets without explicit override authorization from a Moderator or higher. **[TST]**

### 3.2.6 Operator guidance capability

* **LENT-3.2.6-001**: The system shall present the decision outcome as a full-screen overlay with color-coded visual state: green for admitted, red for denied, and orange for situations requiring operator verification or attention before proceeding. **[DEM, TST]**
* **LENT-3.2.6-002**: The system shall display attendee seating information including seat identifier, area designation, and navigational directions when such information is returned by LanCore after a successful check-in. **[DEM, TST]**
* **LENT-3.2.6-003**: The system shall provide a recent-result context sufficient for the operator to understand why a ticket was denied, including cases such as prior check-in. **[DEM, TST]**
* **LENT-3.2.6-004**: The system shall display a list of ticket addons (purchased extras) to the operator upon successful check-in so the attendee can be informed of their entitlements. **[DEM, TST]**
* **LENT-3.2.6-005**: Each full-screen decision overlay shall include a recognizable icon, a primary status message, and supplementary detail text when provided by LanCore. **[DEM, TST]**

### 3.2.7 Audit and traceability capability

* **LENT-3.2.7-001**: The system shall include operator identity, timestamp context, and device or session context in operational requests sent to LanCore when available. **[INSP, TST]**
* **LENT-3.2.7-002**: The system shall not suppress or overwrite authoritative audit outcomes returned by LanCore. **[INSP, TST]**
* **LENT-3.2.7-003**: The system shall support operator visibility into the most recent audit-relevant outcome returned for the current validation flow. **[DEM, TST]**

### 3.2.8 Degraded operation capability

* **LENT-3.2.8-001**: The system shall detect failed or timed-out dependent service calls and inform the operator that the operational result is unresolved. **[DEM, TST]**
* **LENT-3.2.8-002**: The system shall not create locally authoritative check-in state during degraded operation. **[INSP, TST]**
* **LENT-3.2.8-003**: The system may queue retriable entrance requests only when such queueing is explicitly implemented and clearly presented as pending rather than completed. **[INSP, TST]**

## 3.3 System external interface requirements

### 3.3.1 Interface identification and diagrams

Required external interfaces are identified below.

| Interface ID | Name                          | Interfacing Entities                           | Fixed Characteristics Imposed By | Notes                               |
| ------------ | ----------------------------- | ---------------------------------------------- | -------------------------------- | ----------------------------------- |
| LENT-IF-001  | Operator Browser Interface    | Operator Browser <-> LanEntrance SPA / Backend | LanEntrance                      | Mobile-first web UI                 |
| LENT-IF-002  | SSO Authentication Interface  | LanEntrance <-> LanCore Identity / SSO         | LanCore / OIDC standard          | Authentication and identity         |
| LENT-IF-003  | Platform Validation Interface | LanEntrance Backend <-> LanCore API            | LanCore API contract             | Validation, check-in, policy, audit |

Interface diagram:

```text
[Operator Browser]
      |
      v
[LanEntrance SPA]
      |
      v
[LanEntrance Backend]
      |
      v
[LanCore API / SSO]
```

### 3.3.2 LENT-IF-001 Operator Browser Interface

* **LENT-3.3.2-001**: The system shall present a browser-based interface optimized for smartphone portrait orientation and usable on desktop-class browsers. **[DEM, TST]**
* **LENT-3.3.2-002**: The system shall provide camera access prompts and failure messaging consistent with browser permission handling. **[DEM, TST]**
* **LENT-3.3.2-003**: The system shall support manual lookup and action controls usable without camera access. **[DEM, TST]**
* **LENT-3.3.2-004**: The system shall display operational decision states using large, distinguishable visual cues suitable for fast-paced entrance usage. **[DEM, INSP]**

### 3.3.3 LENT-IF-002 SSO Authentication Interface

* **LENT-3.3.3-001**: The system shall integrate with LanCore-provided SSO using standards-compliant web authentication flows. **[INSP, TST]**
* **LENT-3.3.3-002**: When LanCore SSO authentication succeeds, the system shall establish an operator session bound to the authenticated identity. **[TST]**
* **LENT-3.3.3-003**: When SSO authentication fails or is cancelled, the system shall deny operational entrance access. **[TST]**

### 3.3.4 LENT-IF-003 Platform Validation Interface

* **LENT-3.3.4-001**: The LanEntrance backend shall communicate with LanCore over authenticated and encrypted HTTPS connections. **[INSP, TST]**
* **LENT-3.3.4-002**: The system shall submit ticket validation requests containing, at minimum, the scanned or entered token payload, operator identity context, and sufficient request metadata for audit correlation. **[INSP, TST]**
* **LENT-3.3.4-003**: The system shall accept authoritative responses from LanCore containing decision outcome, human-readable guidance, and any operator-visible attendee context authorized for disclosure. **[TST]**
* **LENT-3.3.4-004**: The system shall handle LanCore error responses without falsely reporting success. **[TST]**

## 3.4 System internal interface requirements

* **LENT-3.4-001**: The system shall separate the frontend client interface from the backend orchestration interface through a documented internal API boundary. **[INSP]**
* **LENT-3.4-002**: The system shall ensure that client-originated operational actions pass through the LanEntrance backend and are not sent directly from the browser to LanCore operational endpoints. **[INSP, TST]**

## 3.5 System internal data requirements

* **LENT-3.5-001**: The system shall not maintain an authoritative copy of ticket validity or check-in status. **[INSP]**
* **LENT-3.5-002**: The system may store transient operational state required for active sessions, display context, and retry handling. **[INSP]**
* **LENT-3.5-003**: Any transient stored operational data shall be scoped to the minimum duration and content necessary for the function being performed. **[INSP, TST]**

## 3.6 Adaptation requirements

* **LENT-3.6-001**: The system shall support configuration of environment-specific endpoints, branding, and operational parameters without requiring changes to QR payload format. **[INSP, TST]**
* **LENT-3.6-002**: The system shall support event- or site-specific configuration for entrance presentation details where such details are not authoritative business state. **[INSP]**

## 3.7 Safety requirements

No system-specific physical safety requirements are imposed beyond standard safe use of operator devices.

## 3.8 Security and privacy requirements

* **LENT-3.8-001**: The system shall require authenticated operator access for operational entrance functions. **[TST]**
* **LENT-3.8-002**: The system shall enforce authorization before permitting validation, check-in, override, or attendee detail display. **[TST]**
* **LENT-3.8-003**: The system shall use encrypted transport for all communications carrying authentication, operational requests, or attendee-related data. **[INSP, TST]**
* **LENT-3.8-004**: The system shall minimize personally identifiable information displayed to the operator to the amount required for the operational decision and workflow. **[INSP, DEM]**
* **LENT-3.8-005**: The system shall not require QR payloads to expose attendee name, email, or other sensitive data. **[TST]**
* **LENT-3.8-006**: The system shall support audit correlation of entrance actions to authenticated operators. **[TST]**
* **LENT-3.8-007**: The system shall provide rate-limiting or comparable anti-abuse controls at the LanEntrance backend for operational endpoints. **[INSP, TST]**

## 3.9 System environment requirements

* **LENT-3.9-001**: The system shall operate in modern browser environments on current Android and iOS smartphones and on contemporary desktop browsers. **[DEM, TST]**
* **LENT-3.9-002**: The system shall tolerate intermittent connectivity by providing clear degraded-mode feedback and preserving correct operational semantics. **[DEM, TST]**
* **LENT-3.9-003**: The system shall support deployment in event environments with variable lighting, variable network quality, and high operator turnover. **[ANL, DEM]**

## 3.10 Computer resource requirements

### 3.10.1 Computer hardware requirements

* **LENT-3.10.1-001**: The client subsystem shall require only a camera-capable smartphone or a desktop/laptop browser for standard operation. **[DEM]**
* **LENT-3.10.1-002**: The backend subsystem shall be deployable on standard server or container infrastructure used within the LanSoftware ecosystem. **[INSP]**

### 3.10.2 Computer hardware resource utilization requirements

* **LENT-3.10.2-001**: The system shall be designed so that normal operator workflows remain usable on contemporary mid-range smartphones without requiring high-end hardware. **[DEM, ANL]**
* **LENT-3.10.2-002**: The backend shall support expected entrance request bursts for event admission without unacceptable operator-facing degradation under planned load. **[TST, ANL]**

### 3.10.3 Computer software requirements

* **LENT-3.10.3-001**: The frontend shall be implemented as a web application using Vue.js or an equivalent SPA-capable framework approved by project architecture decisions. **[INSP]**
* **LENT-3.10.3-002**: The backend shall be implemented using Laravel or an equivalent framework approved by project architecture decisions. **[INSP]**
* **LENT-3.10.3-003**: The system shall use a standards-compliant web browser runtime and HTTPS-capable network stack. **[INSP]**

### 3.10.4 Computer communications requirements

* **LENT-3.10.4-001**: The system shall communicate over IP-based networks using HTTPS for browser-to-backend and backend-to-LanCore communication. **[INSP, TST]**
* **LENT-3.10.4-002**: The system shall be capable of functioning in environments with moderate latency and intermittent packet loss by surfacing degraded state rather than silently failing. **[TST, ANL]**

## 3.11 System quality factors

* **LENT-3.11-001**: The system shall be usable by entrance staff with minimal training. **[DEM, ANL]**
* **LENT-3.11-002**: The system shall provide consistent and unambiguous operational decision states. **[DEM, TST]**
* **LENT-3.11-003**: The system shall be maintainable through clear separation of concerns between frontend, entrance backend, and LanCore integration boundaries. **[INSP]**
* **LENT-3.11-004**: The system shall be testable through automated frontend, backend, and integration tests. **[INSP, TST]**
* **LENT-3.11-005**: The system shall be adaptable to future entrance features without requiring changes to the fundamental QR payload model. **[ANL, INSP]**

## 3.12 Design and construction constraints

* **LENT-3.12-001**: The system shall follow the LanSoftware architectural principle that LanCore remains the authoritative source of identity, ticket state, and audit state. **[INSP]**
* **LENT-3.12-002**: The system shall not embed environment-specific application URLs into QR payloads as a core requirement of the solution. **[INSP, TST]**
* **LENT-3.12-003**: The system shall preserve a distinct backend-for-frontend style separation between browser client and platform backend. **[INSP]**

## 3.13 Personnel-related requirements

* **LENT-3.13-001**: The operator interface shall prioritize rapid comprehension, low interaction count, and clear error recovery for temporary event staff. **[DEM, ANL]**
* **LENT-3.13-002**: The system shall support use under conditions where operators may be moving, distracted, or processing long entrance queues. **[DEM, ANL]**

## 3.14 Training-related requirements

* **LENT-3.14-001**: The system shall be operable after brief organizer-led onboarding without requiring specialized technical training. **[DEM, ANL]**
* **LENT-3.14-002**: The system shall provide sufficient inline cues or guidance to support core entrance workflows. **[DEM]**

## 3.15 Logistics-related requirements

* **LENT-3.15-001**: The system shall be deployable and supportable within the standard LanSoftware hosting and operational model. **[INSP]**
* **LENT-3.15-002**: The system shall support observability hooks or operational health indicators sufficient for diagnosing entrance service availability issues. **[INSP, TST]**

## 3.16 Other requirements

* **LENT-3.16-001**: The system shall be documented sufficiently to support implementation, integration, testing, and operator rollout. **[INSP]**
* **LENT-3.16-002**: The system shall provide or reference documented API contracts for the LanEntrance backend and LanCore integration. **[INSP]**

## 3.17 Packaging requirements

As a web-based subsystem, physical packaging requirements do not apply. Standard software delivery, versioning, and deployment packaging practices of the LanSoftware ecosystem shall be used.

## 3.18 Precedence and criticality of requirements

Critical requirements are those related to security, authorization, authoritative state handling, and prevention of false successful check-in outcomes.

The following requirement groups are critical:

* 3.2.1 Authentication and session capability
* 3.2.2 Authorization capability
* 3.2.4 Validation and check-in capability
* 3.8 Security and privacy requirements
* 3.12 Design and construction constraints

Where a conflict exists between convenience and authoritative correctness, authoritative correctness shall take precedence.

---

# 4. Qualification provisions

The qualification methods used in this specification are:

* **Demonstration (DEM)**
* **Test (TST)**
* **Analysis (ANL)**
* **Inspection (INSP)**

Requirements in Section 3 are individually annotated with one or more qualification methods.

General qualification expectations:

* UI behavior and operator flows shall be qualified by demonstration and browser/device testing.
* Backend behavior, security, and interface handling shall be qualified by automated tests and inspection.
* Performance and degraded-mode behavior shall be qualified by test and analysis.
* Architectural separation and documentation obligations shall be qualified by inspection.

---

# 5. Requirements traceability

This specification is a subsystem-level specification for LanEntrance.

High-level traceability summary:

* Authentication and authorization requirements trace to LanCore platform identity and access requirements.
* Validation and check-in requirements trace to LanEntrance operational entrance objectives defined in the OCD.
* Audit and security requirements trace to ecosystem requirements for accountability, abuse resistance, and privacy-preserving ticket handling.
* Backend separation requirements trace to architectural design decisions establishing LanEntrance as an application with its own backend while preserving LanCore as authoritative source of truth.

Detailed bidirectional traceability shall be maintained in a separate traceability matrix or by future annotation of subsystem requirements to LanCore and LanEntrance architecture artifacts.

---

# 6. Notes

## 6.1 Acronyms and abbreviations

* **ANL**: Analysis
* **API**: Application Programming Interface
* **DEM**: Demonstration
* **HTTPS**: Hypertext Transfer Protocol Secure
* **INSP**: Inspection
* **JWT**: JSON Web Token
* **OCD**: Operational Concept Document
* **OIDC**: OpenID Connect
* **PII**: Personally Identifiable Information
* **QR**: Quick Response
* **SPA**: Single Page Application
* **SSO**: Single Sign-On
* **SSS**: System/Subsystem Specification
* **TLS**: Transport Layer Security
* **TST**: Test

## 6.2 Terms and definitions

* **Authoritative state**: The canonical system state that determines the valid operational truth. In this subsystem, authoritative check-in and audit state reside in LanCore.
* **Entrance backend**: The LanEntrance server-side service that mediates between browser clients and LanCore.
* **Opaque token**: A non-semantic ticket payload value that does not itself reveal sensitive business or personal information.
* **Operator**: An authenticated staff member using LanEntrance to perform entrance actions.
* **Override**: A controlled operational action allowing staff to continue despite a policy restriction, subject to authorization and audit.

---

# A. Appendixes

## Appendix A.1 Traceability matrix

Detailed requirement-to-architecture and requirement-to-test traceability is maintained in `docs/RTM.md`.

## Appendix A.2 API contract extracts

LanEntrance backend and LanCore interface payload schemas are documented in `docs/IDD.md`.

## Appendix A.3 State machine diagrams

Operator workflow state machine and state transition table are documented in `docs/SSDD.md` Section 5.
