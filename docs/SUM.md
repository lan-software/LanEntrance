LanEntrance Software User Manual (MIL-STD-498 Adapted)
===

# 1. Scope

## 1.1 Identification

* Title: LanEntrance Software User Manual
* Short Name: LanEntrance SUM
* System Name: LanEntrance
* Ecosystem: LanSoftware
* Version: Draft v0.1
* Status: Working Draft

This document provides operational guidance for entrance staff using LanEntrance at LAN events.

## 1.2 System overview

LanEntrance is a mobile-first web application used by entrance staff to validate tickets and check in attendees at LAN events. It runs in a web browser on smartphones or laptops — no app installation is required.

## 1.3 Document overview

This manual covers: logging in, scanning tickets, understanding results, handling special situations (overrides, degraded mode), manual lookup, and troubleshooting.

---

# 2. Getting started

## 2.1 Requirements

* A smartphone or laptop with a modern web browser (Chrome, Safari, Firefox, or Edge)
* Camera access (recommended for QR scanning; not required for manual lookup)
* Network connectivity to the event's LanEntrance server
* A LanCore staff account with entrance permissions

## 2.2 Accessing LanEntrance

1. Open your browser
2. Navigate to the LanEntrance URL provided by the event organizer
3. You will be redirected to log in via LanCore SSO

## 2.3 Logging in

1. On the LanCore login page, enter your staff credentials
2. After successful authentication, you will be redirected to the LanEntrance dashboard
3. If you have two-factor authentication (2FA) enabled, enter your 2FA code when prompted

Once logged in, your session remains active for the configured session duration. You do not need to log in again for each scan.

---

# 3. Entrance scanner

## 3.1 Opening the scanner

1. From the dashboard, tap **Scanner** in the navigation
2. Your browser will request camera permission — tap **Allow**
3. The camera viewfinder will appear, ready to scan

## 3.2 Scanning a ticket

1. Point your camera at the attendee's QR code
2. Hold steady until the QR code is recognized (a brief vibration or sound may confirm detection)
3. The system automatically sends the ticket for validation
4. A result screen appears within moments

## 3.3 Understanding scan results

After scanning, a **full-screen result** appears covering the entire screen. The color tells you the outcome at a glance:

### Green — Checked In (Entry Allowed)

The ticket is valid and the attendee has been admitted.

The green screen shows:
* **Attendee name** at the top
* **Seating information** — the seat number, area/hall, and directions to reach the seat (e.g., "Seat A-42 — Hall A — Enter Hall A, turn left, Row 4, Seat 42 on the right")
* **Ticket addons** — a list of extras purchased with the ticket (e.g., "Pizza Package — Collect at Booth 3", "Chair Rental — Pre-placed at your seat", "Tournament Entry")

**What to do**: Welcome the attendee. Read them their seat location and directions. If they have addons, let them know what they're entitled to and where to pick things up. Tap **Next Scan** when done.

### Red — Entry Denied

The ticket is not recognized, has been revoked, or is restricted by policy with no override available.

**What to do**: Inform the attendee that their ticket could not be validated. The screen shows the reason. Direct them to the organizer help desk. Tap **Next Scan** to proceed.

### Orange — Already Checked In

This ticket has already been used for entry.

**What to do**: The attendee may be attempting re-entry or there may be a duplicate ticket. The screen shows when the ticket was previously used. Check with the attendee and, if needed, contact a supervisor. Tap **Next Scan** to proceed.

### Orange — Override Available

The ticket is restricted by a policy (e.g., group check-in rules), but a staff override is possible.

**What to do**: If you are authorized (Moderator or higher), you can tap **Override** and provide a reason. If you are not authorized, direct the attendee to a supervisor. Tap **Next Scan** to skip.

### Orange — Verification Required

The ticket is valid but requires you to manually verify something before the attendee can enter. Common examples:
* **Student ticket** — check that the attendee has a valid student ID
* **Team ticket** — verify team membership card or roster
* **Age-restricted** — confirm the attendee meets the age requirement

The screen shows a checklist of what to verify. Each item includes an instruction (e.g., "Must show a valid university student ID with photo").

**What to do**:
1. Go through each verification item on the checklist
2. Ask the attendee to show the required ID or document
3. If everything checks out, tap **Confirm & Check In**
4. The screen turns green with seating and addon information
5. If verification fails, tap **Next Scan** to dismiss without checking in

### Orange — Payment Required

The attendee purchased their ticket with "Pay on Site" and has not yet paid. You must collect payment before they can enter.

The screen shows:
* The **total amount due** in large text (e.g., "42,00 EUR")
* A **breakdown of items** (e.g., "Weekend Ticket — 35,00 EUR", "Tournament Entry — 7,00 EUR")
* **Payment method buttons** (Cash / Card)

**What to do**:
1. Tell the attendee the total amount due
2. Collect payment from the attendee (cash or card)
3. Tap the payment method button matching how they paid (**Cash** or **Card**)
4. Tap **Confirm Payment & Check In**
5. The screen turns green with seating and addon information
6. The attendee will automatically receive a receipt by email

**Important**: You cannot skip the payment screen or tap "Next Scan" — payment must be collected. If there is an exceptional situation (e.g., organizer has pre-authorized free entry), only a Moderator or higher can tap **Override** to bypass payment.

---

## 3.4 Seating and addon information

After a successful check-in (green screen), you'll see two information sections:

### Seating

If the attendee has an assigned seat, you'll see:
* The **seat number** in large text (e.g., "A-42")
* The **area or hall** (e.g., "Hall A")
* **Directions** explaining how to get there from the entrance

Read this information to the attendee, or turn your screen to show them.

### Addons

If the attendee purchased extras with their ticket, you'll see a list:
* Each addon has a **name** (e.g., "Pizza Package")
* Some addons have **pickup instructions** (e.g., "Collect at Booth 3")

Let the attendee know what addons they have and where to collect them.

### Receipt

If the attendee paid on site, you'll see a notice: **"Receipt sent to attendee's email"**. The receipt is generated and sent automatically — you don't need to do anything. If the attendee asks about their receipt, let them know to check their email.

---

# 4. Staff override

## 4.1 When to override

Overrides are available when the system shows an orange **Override Available** result. Common scenarios:
* Group check-in policy requires all members, but a member is arriving separately
* An organizer has authorized an exception

## 4.2 Performing an override

1. On the orange result screen, tap **Override**
2. A modal appears asking for a reason
3. Enter a clear explanation (minimum 10 characters) for why the override is being performed
4. Tap **Confirm Override**
5. The system will process the override and show the result

**Note**: All overrides are logged with your identity and reason for audit purposes.

## 4.3 Who can override

Only staff with **Moderator** role or higher can perform overrides. If you don't see the Override button, you don't have the required permissions — contact your supervisor.

---

# 5. Manual lookup

## 5.1 When to use manual lookup

Use manual lookup when:
* The camera is not working or permission was denied
* The QR code is damaged or unreadable
* You need to search for an attendee by name

## 5.2 Using manual lookup

1. Tap **Manual Lookup** on the scanner page (or navigate to the Lookup page)
2. Type the attendee's name or ticket identifier (at least 2 characters)
3. Matching results appear as you type
4. Tap on the correct attendee
5. The system validates their ticket and shows the result (same as a QR scan)

---

# 6. Degraded mode

## 6.1 What is degraded mode

Degraded mode occurs when LanEntrance cannot communicate with LanCore (the central platform). This can happen due to:
* Network connectivity issues at the event venue
* LanCore server maintenance or outage

## 6.2 How to recognize degraded mode

An **amber banner** appears at the top of the screen:

> "Reduced connectivity — results may be delayed or unavailable"

## 6.3 What to do in degraded mode

* **Do not assume check-ins are successful** — if the system cannot reach LanCore, results are unresolved
* **Wait and retry** — tap to retry the scan when the banner clears
* **Contact your supervisor** if degraded mode persists
* **Do not let attendees through** based on a degraded result — the system intentionally prevents false confirmations

Degraded mode resolves automatically when connectivity is restored.

---

# 7. Settings

## 7.1 Profile settings

Access via the navigation menu → **Settings** → **Profile**:
* Update your display name
* Update your email address

## 7.2 Security settings

Access via **Settings** → **Security**:
* Change your password
* Enable or disable two-factor authentication (2FA)
* View 2FA recovery codes

## 7.3 Appearance

Access via **Settings** → **Appearance**:
* Switch between light mode, dark mode, or system default

---

# 8. Troubleshooting

## 8.1 Camera not working

| Symptom                    | Solution                                                |
| -------------------------- | ------------------------------------------------------- |
| "Camera permission denied" | Go to browser settings → Site permissions → Allow camera |
| Camera shows black screen  | Close other apps using the camera; restart browser       |
| No camera option appears   | Your device may not have a camera — use Manual Lookup    |
| Camera is slow/laggy       | Close other browser tabs; ensure sufficient lighting     |

## 8.2 Login issues

| Symptom                        | Solution                                          |
| ------------------------------ | ------------------------------------------------- |
| "Cannot connect to LanCore"    | Check network connection; try again in a moment   |
| Login redirects back to login  | Clear browser cookies; try in incognito/private    |
| "Unauthorized" after login     | Your account may lack entrance permissions — contact organizer |

## 8.3 Scan issues

| Symptom                        | Solution                                          |
| ------------------------------ | ------------------------------------------------- |
| QR code not detected           | Ensure good lighting; hold camera 15-30cm from code |
| Scan takes long to respond     | Network may be slow; check the degraded mode banner |
| "Service unavailable"          | LanCore may be down — see Degraded Mode section    |

## 8.4 General tips

* **Keep your phone charged** — scanning uses the camera continuously
* **Use a recent browser version** — older browsers may have camera issues
* **Bookmark the LanEntrance URL** for quick access
* **Enable screen auto-rotate lock** — portrait mode is recommended for scanning
* **Brightness**: Set screen brightness high enough to see results in bright venues

---

# 9. Quick reference

## 9.1 Scan workflow summary

```
Open Scanner → Point at QR → Wait for result → Act on result → Next Scan
```

## 9.2 Result color guide

| Color  | Meaning                 | Action                                      |
| ------ | ----------------------- | ------------------------------------------- |
| Green  | Checked in              | Read seating + addons, welcome attendee     |
| Red    | Entry denied            | Direct to help desk                         |
| Orange | Needs attention         | Already in / override / verify, then decide |

## 9.3 Key contacts

| Situation                    | Contact              |
| ---------------------------- | -------------------- |
| System not working           | Event IT support     |
| Override needed (no permission)| Entrance supervisor |
| Attendee dispute             | Organizer help desk  |

---

# 10. Notes

## 10.1 Glossary

* **QR Code**: A square barcode on the attendee's ticket
* **SSO**: Single Sign-On — logging in with your LanCore account
* **Override**: A manual approval by authorized staff to bypass a restriction
* **Degraded mode**: Reduced functionality when the central system is unreachable
* **2FA**: Two-factor authentication — an extra security step during login

## 10.2 Privacy

LanEntrance displays minimal attendee information. Only the data needed for the check-in decision (name, seat, group) is shown. All actions are logged for event audit purposes.
