---
name: Cross-app shared strings
description: Strings flagged across LanBrackets (app1), LanEntrance (app2), LanShout (app3) as centralization candidates
type: project
---

## Status
Tracked across apps 1-3 of 5. LanCore and LanHelp still to be processed.

## Strings confirmed in 3+ Lan* apps

| String | LanBrackets | LanEntrance | LanShout | Notes |
|--------|------------|-------------|----------|-------|
| "Sign in" | flagged | flagged | landing.signIn | auth header CTA |
| "Log out" | flagged | flagged | common.logout | user menu |
| "Settings" | flagged | flagged | common.settings | user menu, nav |
| "Profile" | flagged | flagged | navigation.profile | settings nav |
| "Dashboard" | flagged | flagged | navigation.dashboard / common.dashboard | breadcrumb, nav |
| "Save" / "Saved." | flagged | flagged | common.save, settings.*.saved | form submit |
| "Cancel" | flagged | flagged | common.cancel | dialog footer |
| "Confirm" | flagged | flagged | auth.confirmPassword.button | password confirm |
| "Password" | flagged | flagged | auth.*.password | form label |
| "Email address" | flagged | flagged | auth.*.email | form label |
| "Forgot password?" | flagged | flagged | auth.login.forgotPassword | login form link |
| "Remember me" | flagged | flagged | auth.login.rememberMe | login checkbox |
| "Something went wrong." | flagged | flagged | common.somethingWentWrong | error component |
| "Dismiss" | flagged | flagged | common.dismiss | announcement banner |
| "Platform" | flagged | flagged | common.platform | sidebar group label |
| "Light" / "Dark" / "System" | flagged | flagged | settings.appearance.light/dark/system | appearance tabs |
| "Navigation menu" | flagged | flagged | navigation.menu | mobile sheet title |
| "Delete account" | flagged | flagged | deleteUser.title | settings page |
| "Warning" (delete dialog) | flagged | flagged | deleteUser.warning | delete user dialog |

## Strings unique to LanShout
- `landing.*` (heroTitle, heroDescription, joinConversation, loginViaLanCore, poweredBy, feature cards)
- `chat.*`, `chatSettings.*`, `activeUsers.*`, `muted.*` — chat-specific
- `admin.*` (LanShout has a full admin panel; LanBrackets/LanEntrance have different admin structures)

## Centralization recommendation
When LanCore is processed (app 4 or 5), consider extracting a `@lan-software/i18n-core` package or a shared `resources/js/locales/shared/en.json` containing at minimum:
- All `common.*` primitives
- All `auth.*` strings (login, register, forgotPassword, resetPassword, verifyEmail, confirmPassword)
- All `navigation.*` strings
- `deleteUser.*`
- `settingsLayout.*`
- `settings.appearance.*`

This would cover ~60% of the translation surface that is repeated verbatim across the suite.

**Why:** 3 data points now confirm near-identical strings across LanBrackets, LanEntrance, and LanShout. Centralizing in LanCore would eliminate per-app translation drift as more locales are added.
