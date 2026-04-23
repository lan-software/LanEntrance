---
name: LanShout i18n state
description: Translation setup, known gaps, keys added, and patterns used in LanShout (app 3 of 5)
type: project
---

## Setup
- Laravel 13 + Inertia v3 + Vue 3.5, vue-i18n v9
- Frontend locale files: `resources/js/locales/en.json` (English, source of truth) + 9 other locales (de, es, fr, ko, nds, sv, sxu, tlh, uk) — only English is modified
- Backend: `lang/{locale}/validation.php` only — no `__()` PHP translation calls used yet (no backend strings reach browser except via Inertia props)
- i18n setup file: `resources/js/i18n.ts`

## Key naming convention
Hierarchical, camelCase keys, dot-notation namespaces:
- `common.*` — shared UI primitives (save, cancel, loading, settings, logout, etc.)
- `landing.*` — welcome/marketing page
- `auth.*` — authentication pages (login, register, forgotPassword, resetPassword, verifyEmail, confirmPassword)
- `chat.*` — chat room
- `chatSettings.*` — admin chat settings page
- `activeUsers.*` — active user list in chat
- `muted.*` — muted user state
- `dashboard.*` — dashboard page and chart controls
- `settings.*` — settings pages (profile, password, appearance sub-namespaces)
- `settingsLayout.*` — settings layout heading and description
- `admin.*` — admin panel (users, userManagement, chatSettingsCard, rolesPermissions, userDetails sub-namespaces)
- `deleteUser.*` — delete account dialog
- `navigation.*` — nav item labels
- `validation.*` — client-side validation messages

## State after this refactor (2026-04-23)
Already i18n'd before this pass:
- Chat.vue, muted pages (chat.*, muted.*)
- Dashboard.vue (dashboard.*)
- settings/Profile.vue, settings/Password.vue, settings/Appearance.vue (settings.*)
- auth/Login.vue, auth/Register.vue, auth/ForgotPassword.vue, auth/ResetPassword.vue (auth.*)
- admin/ChatSettings.vue (chatSettings.*, but 'Admin' breadcrumb was hardcoded)
- AppHeader.vue, AppSidebar.vue (navigation.* partially)

Refactored in this pass:
- `pages/Welcome.vue` — 13 strings (hero title, hero description, signIn, register, joinConversation, loginViaLanCore, 3 feature cards, footer, appName)
- `components/DeleteUser.vue` — 8 strings (title, description, warning, warningText, confirmTitle, confirmDescription, password label/placeholder, cancel, delete buttons)
- `components/AppearanceTabs.vue` — 3 strings (Light, Dark, System) — tabs converted to computed ref
- `components/UserMenuContent.vue` — 2 strings (Settings, Log out)
- `components/NavMain.vue` — 1 string (Platform)
- `components/AppSidebar.vue` — 2 strings (Github Repo, Documentation) — footerNavItems converted to computed
- `layouts/settings/Layout.vue` — 5 strings (Settings heading, description, Profile/Password/Appearance nav) — sidebarNavItems converted to computed
- `pages/admin/Index.vue` — 6 strings (Admin Panel, description, User Management, Chat Settings, Roles & Permissions, Coming soon)
- `pages/admin/users/Index.vue` — 12 strings (title, description, table headers, No roles, Verified/Unverified, View, No users found)
- `pages/admin/users/Show.vue` — 9 strings (User Details breadcrumb, User Information, Email Verification, Member Since, Last Updated, Roles, Permissions:, No roles assigned, Verified/Unverified)
- `components/announcements/AnnouncementBanner.vue` — 1 string (Dismiss)
- `components/AlertError.vue` — 1 string (Something went wrong.) — default prop pattern
- `components/AppLogo.vue` — 1 string (LANShout app name)
- `pages/auth/ConfirmPassword.vue` — 4 strings (title, description, password label/placeholder, button)
- `pages/auth/VerifyEmail.vue` — 4 strings (title, description, linkSent, resendButton, logout)

## New keys added to en.json
New namespaces/keys added:
- `common.dismiss`, `common.platform`, `common.readOnly`, `common.comingSoon`, `common.somethingWentWrong`
- `landing.signIn`, `landing.heroTitle`, `landing.heroDescription`, `landing.joinConversation`, `landing.loginViaLanCore`, `landing.poweredBy`
- `landing.features.liveChat.*`, `landing.features.moderation.*`, `landing.features.lanCoreSso.*`
- `navigation.githubRepo`, `navigation.documentation`
- `settingsLayout.title`, `settingsLayout.description`
- `admin.description`, `admin.users.description`, `admin.users.noRoles`, `admin.users.verified`, `admin.users.unverified`, `admin.users.joined`
- `admin.userManagement.*`, `admin.chatSettingsCard.description`, `admin.rolesPermissions.title`
- `admin.userDetails.*` (title, information, emailVerification, memberSince, lastUpdated, roles, noRoles, permissions)
- `deleteUser.*` (full namespace — new)

## Flagged / not translated
- `components/demo/DemoBanner.vue`: "Open Mailpit inbox" — dev-mode only, shown when demoBanner prop is set. Ambiguous whether to translate; left as-is.
- `formatDate()` in admin/users pages uses hardcoded `'en-US'` locale — should use dynamic locale from i18n in a future pass.

**Why:** DemoBanner is a developer/demo tool, not production UI. The hardcoded locale in formatDate is a separate concern (date formatting, not string translation).
