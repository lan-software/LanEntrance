LanEntrance Operational Concept Document (MIL-STD-498 Adapted)
===

# 1. Scope

## 1.1 Identification

* System Name: LanEntrance
* Ecosystem: LanSoftware
* Parent System: LanCore
* Type: Mobile-first web application (SPA + Backend Service)
* Version: Draft v0.2

LanEntrance is a dedicated entrance and check-in system consisting of a mobile-first SPA and a dedicated backend service, used for attendee validation and admission at LAN events.

---

## 1.2 System Overview

LanEntrance provides a fast, secure, and operationally robust interface for event staff to validate tickets, check in attendees, and handle entrance workflows.

It is part of the LanSoftware ecosystem and integrates with LanCore, which provides identity, authorization, ticketing, and audit functionality.

LanEntrance introduces its own backend service acting as an orchestration and security layer between scanning clients and LanCore.

Stakeholders:

* Sponsor: LAN event organizers
* Users: Entrance staff
* Developer: LanSoftware project
* Platform Backend: LanCore
* Entrance Service Backend: LanEntrance Backend

Operating environments:

* Mobile browsers (iOS/Android)
* Desktop browsers
* On-site LAN environments (unstable or high-load networks)

---

## 1.3 Document Overview

This document defines the operational concept of LanEntrance, including architecture, responsibilities, workflows, and integration with LanCore.

Security considerations:

* QR codes contain no sensitive data
* All validation is performed server-side
* LanEntrance Backend acts as a controlled gateway
* Full audit logging is enforced by LanCore

---

# 2. Referenced Documents

* LanCore Architecture Specification
* LanCore SSO Integration Documentation
* LanSoftware Domain Architecture Guidelines

---

# 3. Current System or Situation

## 3.1 Background, Objectives, and Scope

Current LAN event check-in systems are often manual, slow, and lack auditability.

Objectives:

* Fast and reliable check-in
* Fraud prevention
* Operational visibility

Scope:

* Entrance and admission workflows

---

## 3.2 Operational Policies and Constraints

* Must support mobile devices
* Must tolerate unstable networks
* Must enforce strict authorization
* Must operate under high throughput

---

## 3.3 Description of Current Situation

### a) Environment

* High-density entrance queues
* Time-critical interactions

### b) Components

* Printed tickets or basic QR codes
* Manual or disconnected validation systems

### c) Interfaces

* Weak or non-existent system integration

### d) Capabilities

* Limited validation
* No structured audit trail

### e) Data Flow

* Manual or fragmented

### f) Performance

* Poor scalability

### g) Quality Attributes

* Low reliability and traceability

### h) Security

* Vulnerable to duplication and misuse

---

## 3.4 Users

* Entrance staff
* Event organizers
* Attendees (indirect)

---

## 3.5 Support Concept

* Manual operations
* Limited technical support systems

---

# 4. Justification for and Nature of Changes

## 4.1 Justification

* Need for secure validation
* Need for real-time audit logging
* Need for scalable entrance operations

## 4.2 Needed Changes

* Token-based QR validation
* Centralized business logic in LanCore
* Dedicated entrance system with backend service

## 4.3 Priorities

* Essential: validation, authorization, audit
* Desirable: UX, seat guidance
* Optional: analytics

## 4.4 Not Included

* Native mobile apps (initial phase)

## 4.5 Assumptions and Constraints

* Staff use smartphones
* Network conditions may degrade

---

# 5. Concept for New System

## 5.1 Background, Objectives, Scope

LanEntrance introduces a layered entrance system with clear separation of concerns between UI, entrance backend, and platform backend.

---

## 5.2 Operational Policies and Constraints

* Only authorized staff may perform check-ins
* LanCore is the single source of truth
* LanEntrance Backend must not store authoritative state
* QR codes contain opaque tokens only

---

## 5.3 Description of New System

### a) Environment

* Mobile-first usage
* High throughput conditions

### b) Components

* LanEntrance SPA (scanner client)
* LanEntrance Backend (service layer)
* LanCore API (platform)
* QR token system

### c) Interfaces

* SPA <-> LanEntrance Backend (HTTPS API)
* LanEntrance Backend <-> LanCore API
* SSO via LanCore (OIDC)

### d) Capabilities

* QR scanning and decoding
* Ticket validation
* Check-in and re-entry handling
* Group policy enforcement
* Staff override handling
* Audit triggering

### e) Data Flow

```
Scan (SPA)
 -> Parse QR
 -> Send to LanEntrance Backend
 -> Forward to LanCore
 -> Validate + Audit (LanCore)
 -> Response to Backend
 -> UI Feedback
```

### f) Performance

* Sub-second validation
* High concurrency support

### g) Quality Attributes

* High usability
* High availability
* Mobile optimized
* Resilient to network issues

### h) Security

* Token-based validation
* Role-based access control
* Backend isolation layer
* Full audit logging in LanCore

---

## 5.4 Users

* Entrance staff (primary operators)
* Organizers (configuration & monitoring)

---

## 5.5 Support Concept

* Web-based deployment
* Stateless frontend
* Controlled backend service layer
* Central platform backend (LanCore)

---

# 6. Operational Scenarios

## Scenario 1: Standard Check-in

1. Staff logs in via SSO
2. QR code scanned
3. Token sent to backend
4. Validation via LanCore
5. Attendee checked in
6. Seat guidance displayed

## Scenario 2: Invalid Ticket

1. QR scanned
2. Validation fails
3. Error displayed
4. Audit logged

## Scenario 3: Group Restriction

1. Member scanned
2. Policy evaluated
3. Denied or override possible
4. Staff overrides if permitted
5. Audit recorded

---

# 7. Summary of Impacts

## 7.1 Operational Impacts

* Faster check-in
* Reduced fraud
* Improved transparency

## 7.2 Organizational Impacts

* Requires staff training
* Introduces role-based processes

## 7.3 Development Impacts

* Requires dual backend architecture
* Requires API orchestration

---

# 8. Analysis of Proposed System

## 8.1 Advantages

* Strong security model
* Clear separation of concerns
* High scalability

## 8.2 Disadvantages

* Increased architectural complexity
* Dependency on LanCore availability

## 8.3 Alternatives

* Direct client-to-LanCore communication (rejected: security & coupling)
* Native apps (rejected: complexity)

---

# 9. Notes

## Acronyms

* SSO: Single Sign-On
* SPA: Single Page Application
* BFF: Backend for Frontend

## Terms

* Token: Opaque identifier for validation
* Check-in: Admission of attendee

---

# Appendix A

(Reserved for sequence diagrams and API contracts)
