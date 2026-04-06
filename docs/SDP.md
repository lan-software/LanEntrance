LanEntrance Software Development Plan (MIL-STD-498 Adapted)
===

# 1. Scope

## 1.1 Identification

* Title: LanEntrance Software Development Plan
* Short Name: LanEntrance SDP
* System Name: LanEntrance
* Ecosystem: LanSoftware
* Version: Draft v0.1
* Status: Working Draft

This document describes the software development plan for LanEntrance, a mobile-first entrance management subsystem within the LanSoftware ecosystem.

## 1.2 System overview

LanEntrance is a dedicated entrance and check-in system for LAN events. It consists of a mobile-first Single Page Application (SPA) and a backend service that orchestrates entrance workflows against LanCore. See OCD Section 1.2 and SSS Section 1.2 for full system overview.

## 1.3 Document overview

This document defines the development approach, organization, tools, standards, schedule, and risk management strategy for LanEntrance development. It governs how the software will be built, tested, and delivered.

## 1.4 Relationship to other plans

This SDP is the governing plan for LanEntrance development activities. It references:

* OCD (REF-001) for operational concept
* SSS (REF-002) for system requirements
* SRS for software requirements
* STP for test planning

---

# 2. Referenced documents

| ID      | Title                                      | Version           |
| ------- | ------------------------------------------ | ----------------- |
| REF-001 | LanEntrance Operational Concept Document   | Draft v0.2        |
| REF-002 | LanEntrance System/Subsystem Specification | Draft v0.1        |
| REF-003 | MIL-STD-498 (adapted)                      | Reference edition |

---

# 3. Overview of required work

## 3.1 System requirements allocated to software

All system requirements defined in the SSS (LENT-3.x-xxx) are allocated to software. LanEntrance has no hardware-specific development tasks. The software is divided into two CSCIs:

* **LanEntrance-Frontend**: Vue 3 SPA delivered via Inertia.js
* **LanEntrance-Backend**: Laravel 13 backend service

## 3.2 Work breakdown

| Phase   | Description                                   | Status      |
| ------- | --------------------------------------------- | ----------- |
| Phase 0 | Project scaffolding, MIL-STD-498 documents    | In Progress |
| Phase 1 | Authentication, SSO, role sync, user settings | Complete    |
| Phase 2 | Entrance core (scan, validate, check-in)      | Planned     |
| Phase 3 | Degraded mode, analytics, polish              | Planned     |

### Phase 1 deliverables (complete)

* LanCore SSO integration (`LanCoreAuthController`, `LanCoreClient`)
* User synchronization (`UserSyncService`)
* Role synchronization via webhook (`SyncUserRolesFromLanCore`, `LanCoreRolesWebhookController`)
* Fortify-based authentication (login, register, 2FA, password reset)
* Settings pages (profile, security, appearance)
* Dashboard stub
* CI/CD pipelines (tests, lint, frontend tests, Docker publish)

### Phase 2 deliverables (planned)

* QR scanner component (browser camera API via `vue-qrcode-reader`)
* Validation endpoint (`POST /api/entrance/validate`)
* Check-in confirmation endpoint (`POST /api/entrance/checkin`)
* Verification confirmation endpoint (`POST /api/entrance/verify-checkin`)
* On-site payment confirmation endpoint (`POST /api/entrance/confirm-payment`)
* Override flow endpoint (`POST /api/entrance/override`)
* Manual lookup endpoint (`GET /api/entrance/lookup`)
* Full-screen decision display overlay (green/red/orange with seating, addons, verification, payment)
* On-site payment collection flow (amount display, method selection, confirmation, receipt trigger)
* Operator state machine (IDLE → READY → ACTIVE_SCAN → DECISION_DISPLAY)
* LanCoreClient extensions for validation, check-in, verification, payment, and override API calls

### Phase 3 deliverables (planned)

* Degraded mode detection and UI
* Request queueing for retriable operations
* Entrance analytics dashboard
* Performance optimization and load testing

---

# 4. Plans for performing general software development activities

## 4.1 Software development process

LanEntrance follows an iterative agile development process with MIL-STD-498 artifacts maintained as living documents. Each iteration includes:

1. Requirements review (update SRS/IRS as needed)
2. Design update (update SSDD/SDD/IDD)
3. Implementation
4. Testing (update STD/STR)
5. RTM update

## 4.2 General standards

### Coding standards

| Domain   | Standard / Tool             | Configuration           |
| -------- | --------------------------- | ----------------------- |
| PHP      | PSR-12 via Laravel Pint     | `pint.json`             |
| JS/Vue   | ESLint + Prettier           | `eslint.config.js`      |
| CSS      | Tailwind CSS 4 conventions  | `tailwind.config.js`    |
| Commits  | Conventional Commits        | Enforced via CI         |
| Docs     | MIL-STD-498 adapted for MD  | This document           |

### Documentation standards

All MIL-STD-498 documents are written in Markdown and version-controlled alongside the codebase. Documents follow the DID structure adapted for readability in developer tooling.

## 4.3 Software development methods

* **Architecture**: Backend-for-Frontend (BFF) pattern with Inertia.js bridging
* **Frontend**: Component-based SPA with Vue 3 Composition API
* **Backend**: Laravel service layer with dedicated service classes for external integrations
* **State management**: Server-driven via Inertia props; local UI state via Vue composables
* **API design**: RESTful JSON endpoints for entrance operations

## 4.4 Reusable software products

| Product            | Usage                              | Source            |
| ------------------ | ---------------------------------- | ----------------- |
| Laravel Framework  | Backend application framework      | Packagist         |
| Vue 3              | Frontend SPA framework             | npm               |
| Inertia.js         | SPA-backend bridge                 | npm / Packagist   |
| Laravel Fortify    | Authentication scaffolding         | Packagist         |
| Reka UI            | Accessible UI component primitives | npm               |
| Tailwind CSS 4     | Utility-first CSS framework        | npm               |
| vue-qrcode-reader  | QR code scanning (camera stream)   | npm               |
| Lucide Icons       | Icon library                       | npm               |
| Laravel Sail       | Docker development environment     | Packagist         |

---

# 5. Plans for performing detailed software development activities

## 5.1 Project planning and oversight

Development is tracked through GitHub Issues and Pull Requests. MIL-STD-498 documents provide the formal requirements and design baseline.

## 5.2 Establishing a software development environment

### Development environment

* **Containerized**: Docker Compose via Laravel Sail (`compose.yaml`)
* **PHP**: 8.5 (Sail image `sail-8.5/app`)
* **Node.js**: 22+ (for frontend build tooling)
* **Database**: MySQL 8 (Docker service) / SQLite (testing)
* **Ports**: APP_PORT=84, VITE_PORT=5177
* **Network**: `lanparty` external Docker network (shared with LanCore). Must be created before first run:
  ```bash
  docker network create --driver bridge lanparty
  ```
  This network is shared between LanEntrance and LanCore containers, enabling `LANCORE_INTERNAL_URL` communication.

### IDE and tooling

* Editor: Developer preference (VS Code, PhpStorm, etc.)
* Code generation: Laravel Wayfinder for typed route generation
* Debugging: Xdebug (configured in Sail container)

## 5.3 Software requirements analysis

Requirements are derived from the SSS and documented in the SRS. Each SSS requirement (LENT-3.x-xxx) maps to one or more software requirements (LENT-SW-xxx). The RTM maintains bidirectional traceability.

## 5.4 Software design

Design is documented across three levels:

1. **SSDD**: System-level architecture decomposition
2. **SDD**: Detailed software design per CSCI/CSC
3. **IDD**: Interface contracts and data formats

## 5.5 Software implementation and unit testing

* PHP unit tests: Pest framework (`tests/Unit/`, `tests/Feature/`)
* Vue component tests: Vitest (`resources/js/**/*.test.ts`)
* E2E tests: Playwright (`tests/e2e/`)
* Coverage: Uploaded to Codecov via CI

## 5.6 Unit integration and testing

Integration testing validates LanEntrance Backend ↔ LanCore API interactions using HTTP mocking in Pest feature tests. Frontend integration is validated via Playwright E2E tests.

## 5.7 Software qualification testing

Qualification methods per SSS Section 4:

| Method | Implementation                                   |
| ------ | ------------------------------------------------ |
| DEM    | Playwright E2E tests + manual demonstration      |
| TST    | Pest feature tests + Vitest component tests      |
| ANL    | Code review + architecture inspection            |
| INSP   | PR review + document review                      |

## 5.8 Software configuration management

* **VCS**: Git (GitHub)
* **Branching**: Feature branches from `main`, PR-based merges
* **CI**: GitHub Actions (`.github/workflows/`)
  * `tests.yml` — PHP tests via Pest
  * `frontend-tests.yml` — Vitest + Playwright
  * `lint.yml` — Pint + Prettier + ESLint
  * `docker-publish.yml` — Container image builds
* **Versioning**: Semantic versioning; release branches for production cuts

## 5.9 Software product evaluation

Code quality is enforced through:

* Automated linting (Pint, ESLint, Prettier) in CI
* Automated testing with coverage thresholds
* PR review requirements
* MIL-STD-498 document review at phase boundaries

## 5.10 Software quality assurance

* All changes require passing CI before merge
* Feature tests cover critical paths (auth, SSO, webhooks)
* RTM tracks requirement coverage through design and test
* STR documents test execution results

---

# 6. Schedules and activity network

| Milestone                         | Target    | Dependencies                              | Status      |
| --------------------------------- | --------- | ----------------------------------------- | ----------- |
| Phase 0: Doc scaffolding          | Current   | None                                      | Complete    |
| **LanCore API contract finalized**| **TBD**   | **LanCore team deliverable**              | **Blocking**|
| Phase 2: Design review            | TBD       | SRS, SDD, IDD complete; LanCore contract  | Planned     |
| Phase 2: Implementation           | TBD       | Design review approved                    | Planned     |
| Phase 2: Testing                  | TBD       | Implementation complete                   | Planned     |
| Phase 3: Planning                 | TBD       | Phase 2 qualification complete            | Planned     |

**Critical dependency**: Phase 2 design review cannot be approved until the LanCore API contract is finalized. The IDD (Sections 5.2.1–5.2.4) documents the expected LanCore endpoints and response structures, including `seating`, `addons`, and `verification` objects. These are assumptions pending confirmation from the LanCore team. Implementation may begin with mocked LanCore responses, but the design review gate requires a confirmed contract.

Detailed schedule will be established after Phase 2 design review.

---

# 7. Project organization and resources

## 7.1 Organization

LanEntrance is developed by the LanSoftware project team. Roles:

| Role               | Responsibility                                        |
| ------------------ | ----------------------------------------------------- |
| Project Lead       | Overall direction, requirements approval              |
| Developer(s)       | Implementation, testing, documentation                |
| Reviewer(s)        | Code review, document review, qualification approval  |

## 7.2 Resources

| Resource                 | Details                               |
| ------------------------ | ------------------------------------- |
| Development environment  | Docker Compose (Sail)                 |
| CI/CD                    | GitHub Actions                        |
| Container registry       | GitHub Container Registry (GHCR)      |
| Issue tracking           | GitHub Issues                         |

---

# 8. Risk management

| Risk ID | Description                                    | Impact | Likelihood | Mitigation                                                          |
| ------- | ---------------------------------------------- | ------ | ---------- | ------------------------------------------------------------------- |
| R-001   | LanCore API unavailable during development     | High   | Medium     | Mock LanCore responses for development; degraded mode design        |
| R-002   | Network instability at event venues             | High   | High       | Degraded mode (LENT-3.2.8-xxx); clear operator feedback             |
| R-003   | Camera API inconsistencies across mobile browsers| Medium | Medium     | Test on multiple devices; provide manual lookup fallback            |
| R-004   | Concurrent check-in race conditions             | High   | Low        | LanCore owns authoritative state; idempotent operations             |
| R-005   | Scope creep beyond entrance workflows           | Medium | Medium     | Strict adherence to SSS requirements; change control via SRS update |
| R-006   | Staff device diversity at events                | Medium | High       | Mobile-first responsive design; progressive enhancement             |
| R-007   | On-site payment disputes or errors              | Medium | Medium     | LanCore owns payment records; staff can override with Moderator role; receipt emailed as proof |
| R-008   | Payment confirmation sent but LanCore fails to record | High | Low   | Backend waits for LanCore confirmation before showing success; degraded mode for failures |

---

# 9. Notes

## 9.1 Acronyms

* BFF: Backend for Frontend
* CI/CD: Continuous Integration / Continuous Delivery
* CSCI: Computer Software Configuration Item
* CSC: Computer Software Component
* DID: Data Item Description
* E2E: End-to-End
* GHCR: GitHub Container Registry
* SPA: Single Page Application
* VCS: Version Control System

## 9.2 Conventions

All MIL-STD-498 documents for LanEntrance are stored in the `docs/` directory and maintained as living documents updated alongside the codebase.
