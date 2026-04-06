LanEntrance Software Design Description (MIL-STD-498 Adapted)
===

# 1. Scope

## 1.1 Identification

* Title: LanEntrance Software Design Description
* Short Name: LanEntrance SDD
* System Name: LanEntrance
* Ecosystem: LanSoftware
* Version: Draft v0.1
* Status: Working Draft

This document describes the detailed software design for both CSCIs of LanEntrance.

## 1.2 System overview

LanEntrance consists of two CSCIs: LanEntrance-Frontend (LENT-FE) and LanEntrance-Backend (LENT-BE). See SSDD for system-level architecture.

## 1.3 Document overview

This document details the design of each Computer Software Component (CSC) within each CSCI, including responsibilities, interfaces, algorithms, and data structures.

---

# 2. Referenced documents

| ID      | Title                | Version    |
| ------- | -------------------- | ---------- |
| REF-001 | LanEntrance SRS      | Draft v0.1 |
| REF-002 | LanEntrance IRS      | Draft v0.1 |
| REF-003 | LanEntrance SSDD     | Draft v0.1 |
| REF-004 | LanEntrance IDD      | Draft v0.1 |

---

# 3. CSCI-wide design decisions

## 3.1 LENT-FE: Frontend CSCI

### 3.1.1 Framework and rendering

* **Vue 3** with Composition API (`<script setup>`)
* **Inertia.js 3** for page-level server-driven navigation
* **Vite** for development server and production bundling
* **TypeScript** for type safety in composables and API interactions

### 3.1.2 Styling approach

* **Tailwind CSS 4** utility classes for all styling
* **Reka UI** headless components for accessible primitives (dialogs, dropdowns)
* **Mobile-first** breakpoints: default = mobile portrait, `md:` = tablet/desktop
* **Large touch targets**: minimum 48x48px for entrance operation controls
* **High-contrast colors**: Decision states use WCAG AA compliant color pairs

### 3.1.3 Component conventions

* Pages in `resources/js/pages/` — one per Inertia route
* Reusable components in `resources/js/components/`
* Composables in `resources/js/composables/` — encapsulate stateful logic
* UI primitives in `resources/js/components/ui/` — project-agnostic

## 3.2 LENT-BE: Backend CSCI

### 3.2.1 Framework conventions

* **Laravel 13** with standard directory structure
* **Controllers**: Thin, delegate to services/actions
* **Services**: Encapsulate external API communication (`LanCoreClient`)
* **Actions**: Single-responsibility operations (`SyncUserRolesFromLanCore`)
* **DTOs**: Typed data transfer objects for API request/response mapping
* **Form Requests**: Input validation at the controller boundary

### 3.2.2 Error handling strategy

* External API errors (LanCore) → caught in service layer → mapped to structured error responses
* Validation errors → Laravel's built-in 422 handling
* Authorization errors → middleware-level 403 responses
* Unhandled exceptions → Laravel's exception handler → 500 with generic message

---

# 4. LENT-FE CSC designs

## 4.1 CSC: Scanner Page (`pages/entrance/Scanner.vue`)

### 4.1.1 Purpose

Primary entrance operation page. Hosts the QR scanner, displays validation results, and provides override actions.

**SRS Trace**: LENT-SW-STATE-001, LENT-SW-SCAN-001, LENT-SW-CHECKIN-003, LENT-SW-UI-001

### 4.1.2 Design

```vue
<!-- Conceptual structure -->
<template>
  <AppLayout>
    <DegradedBanner v-if="state.degraded" />
    
    <!-- READY / ACTIVE_SCAN state: QrScanner wraps vue-qrcode-reader's QrcodeStream -->
    <QrScanner 
      v-if="state.current === 'READY' || state.current === 'ACTIVE_SCAN'"
      ref="scannerRef"
      @decoded="onQrDecoded"
      @error="onScanError"
    />
    
    <!-- DECISION_DISPLAY state: full-screen overlay -->
    <DecisionDisplay
      v-if="state.current === 'DECISION_DISPLAY'"
      :result="state.lastResult"
      @dismiss="resetAndResume"
      @override="showOverride"
      @verify-checkin="onVerifyCheckin"
      @confirm-payment="onConfirmPayment"
    />
    
    <!-- Override modal -->
    <OverrideModal
      v-if="overrideVisible"
      :context="state.lastResult"
      @submit="submitOverride"
      @cancel="overrideVisible = false"
    />
    
    <!-- Manual lookup link (always available as fallback) -->
    <RouterLink to="/entrance/lookup">Manual Lookup</RouterLink>
  </AppLayout>
</template>

<script setup>
import { useEntranceState } from '@/composables/useEntranceState'
import { useCheckin } from '@/composables/useCheckin'

const scannerRef = ref()
const { state, resetToReady } = useEntranceState()
const { validate, checkin, verifyCheckin, confirmPayment, override } = useCheckin()

async function onQrDecoded(token: string) {
  // QrScanner auto-pauses (freeze-frame) on detect via vue-qrcode-reader's paused prop
  const result = await validate(token)
  state.transition('DECISION_DISPLAY', result)
}

async function onVerifyCheckin() {
  // Operator confirmed manual verification checks — proceed with check-in
  const result = await verifyCheckin(
    state.lastResult.token,
    state.lastResult.validation_id,
  )
  // Replace orange overlay with green success (includes seating + addons)
  state.transition('DECISION_DISPLAY', result)
}

async function onConfirmPayment(method: string) {
  // Operator confirmed payment collected — proceed with check-in
  const result = await confirmPayment(
    state.lastResult.token,
    state.lastResult.validation_id,
    method,
    state.lastResult.payment.amount,
  )
  // Replace orange overlay with green success (includes seating + addons + receipt_sent)
  state.transition('DECISION_DISPLAY', result)
}

function resetAndResume() {
  resetToReady()
  scannerRef.value?.resume() // Unpauses QrcodeStream to restart scanning
}
</script>
```

### 4.1.3 Behavior

1. On mount: transition to READY, activate scanner
2. On QR decode: call `POST /api/entrance/validate` via `useCheckin`
3. On response: transition to DECISION_DISPLAY, render result
4. On dismiss: transition back to READY, reactivate scanner
5. On override: show modal, collect reason, call `POST /api/entrance/override`

---

## 4.2 CSC: QrScanner Component (`components/entrance/QrScanner.vue`)

### 4.2.1 Purpose

Wraps `vue-qrcode-reader`'s `QrcodeStream` component with LanEntrance-specific UX: scan overlay, permission state handling, pause-on-detect behavior, and error mapping.

**SRS Trace**: LENT-SW-SCAN-001, LENT-SW-SCAN-002
**Dependency**: `vue-qrcode-reader` v5.7+ (`QrcodeStream`, `QrcodeCapture`)

### 4.2.2 Design

```vue
<template>
  <div class="relative w-full h-full">
    <!-- Camera permission denied / unavailable fallback -->
    <div v-if="cameraError" class="flex flex-col items-center justify-center h-full gap-4 p-6">
      <AlertCircle class="w-12 h-12 text-amber-500" />
      <p class="text-center text-lg">{{ cameraError.message }}</p>
      <p class="text-sm text-muted-foreground">Use Manual Lookup instead</p>
    </div>

    <!-- Live camera scanner -->
    <QrcodeStream
      v-else
      :paused="paused"
      :torch="torchActive"
      :constraints="{ facingMode: 'environment' }"
      :formats="['qr_code']"
      :track="trackDetectedCodes"
      @detect="onDetect"
      @camera-on="onCameraOn"
      @camera-off="onCameraOff"
      @error="onError"
    >
      <!-- Scan overlay slot -->
      <div class="absolute inset-0 flex items-center justify-center">
        <div class="w-64 h-64 border-2 border-white/60 rounded-2xl" />
      </div>

      <!-- Torch toggle (only if supported) -->
      <button
        v-if="torchSupported"
        class="absolute top-4 right-4"
        @click="torchActive = !torchActive"
      >
        <Flashlight :class="torchActive ? 'text-yellow-400' : 'text-white'" />
      </button>
    </QrcodeStream>
  </div>
</template>

<script setup lang="ts">
import { QrcodeStream } from 'vue-qrcode-reader'
import type { DetectedBarcode } from 'vue-qrcode-reader'

const emit = defineEmits<{
  decoded: [token: string]
  error: [message: string]
}>()

const paused = ref(false)
const torchActive = ref(false)
const torchSupported = ref(false)
const cameraError = ref<{ name: string; message: string } | null>(null)

function onDetect(detectedCodes: DetectedBarcode[]) {
  if (detectedCodes.length === 0) return
  paused.value = true // Freeze frame on detection
  emit('decoded', detectedCodes[0].rawValue)
}

function onCameraOn(capabilities: MediaTrackCapabilities) {
  torchSupported.value = !!capabilities.torch
}

function onCameraOff() {
  torchSupported.value = false
}

function onError(error: Error) {
  const messages: Record<string, string> = {
    NotAllowedError: 'Camera permission was denied. Please allow camera access in your browser settings.',
    NotFoundError: 'No camera found on this device.',
    NotSupportedError: 'Secure connection (HTTPS) required for camera access.',
    NotReadableError: 'Camera is in use by another application.',
    OverconstrainedError: 'No suitable camera found.',
    StreamApiNotSupportedError: 'Your browser does not support camera streaming.',
    InsecureContextError: 'Camera requires a secure (HTTPS) connection.',
  }
  cameraError.value = {
    name: error.name,
    message: messages[error.name] ?? `Camera error: ${error.message}`,
  }
  emit('error', cameraError.value.message)
}

function trackDetectedCodes(codes: DetectedBarcode[], ctx: CanvasRenderingContext2D) {
  for (const code of codes) {
    const [first, ...rest] = code.cornerPoints
    ctx.strokeStyle = '#22c55e'
    ctx.lineWidth = 3
    ctx.beginPath()
    ctx.moveTo(first.x, first.y)
    for (const point of rest) ctx.lineTo(point.x, point.y)
    ctx.closePath()
    ctx.stroke()
  }
}

/** Called by parent to resume scanning after processing a result */
function resume() {
  paused.value = false
}

defineExpose({ resume })
</script>
```

**Props**: None (configuration is internal; parent controls via events + `resume()`)
**Emits**: `decoded(token: string)`, `error(message: string)`
**Exposed methods**: `resume()` — unpauses scanner after result is dismissed

**Behavior**:
1. On mount: `QrcodeStream` requests `getUserMedia({ facingMode: 'environment' })`
2. On camera granted: `camera-on` fires, torch capability detected
3. Continuous scanning: ZXing-Wasm decodes frames; `track` draws green outline on detected codes
4. On QR detect: `paused` set to true (freeze-frame), `decoded` event emitted with `rawValue`
5. On parent dismiss: parent calls `resume()`, scanning restarts
6. On error: mapped to user-friendly message, `cameraError` state set, fallback UI shown

### 4.2.3 Accessibility

* Camera viewport managed by `QrcodeStream` (renders `<video>` with appropriate attributes)
* Error state uses `aria-live` region for screen reader announcement
* Torch toggle has accessible label
* Overlay content in default slot is rendered above the video feed

---

## 4.3 CSC: DecisionDisplay Component (`components/entrance/DecisionDisplay.vue`)

### 4.3.1 Purpose

Full-screen overlay that covers the entire scanner viewport to display the decision result. Optimized for instant recognition at arm's length in noisy, fast-paced entrance environments.

**SRS Trace**: LENT-SW-UI-001, LENT-SW-UI-002, LENT-SW-UI-003, LENT-SW-UI-004, LENT-SW-UI-005, LENT-SW-CHECKIN-003, LENT-SW-CHECKIN-007, LENT-SW-CHECKIN-008, LENT-SW-CHECKIN-009

### 4.3.2 Data types

```typescript
interface Seating {
  seat: string            // "A-42"
  area?: string           // "Hall A"
  directions?: string     // "Enter Hall A, turn left, Row 4, Seat 42"
}

interface Addon {
  name: string            // "Pizza Package"
  info?: string           // "Collect at Booth 3"
}

interface VerificationCheck {
  label: string           // "Student ID"
  instruction?: string    // "Must show valid university ID with photo"
}

interface Verification {
  message: string
  checks: VerificationCheck[]
}

interface GroupPolicy {
  rule: string
  message: string
  members_checked_in: number
  members_total: number
}

type Decision =
  | 'valid'
  | 'invalid'
  | 'already_checked_in'
  | 'denied_by_policy'
  | 'override_possible'
  | 'verification_required'
  | 'payment_required'

interface PaymentItem {
  name: string            // "Weekend Ticket"
  price: string           // "35.00"
}

interface Payment {
  amount: string          // "42.00"
  currency: string        // "EUR"
  items: PaymentItem[]
  methods: string[]       // ["cash", "card"]
}

interface DecisionResult {
  decision: Decision
  message: string
  validation_id: string
  attendee?: { name: string; group?: string }
  seating?: Seating
  addons?: Addon[]
  verification?: Verification
  payment?: Payment
  override_allowed: boolean
  receipt_sent?: boolean
  audit_id?: string
  group_policy?: GroupPolicy
  degraded: boolean
}
```

### 4.3.3 Three-tier color mapping

| Decision               | Tier   | Background         | Icon            | Primary Text              |
| ---------------------- | ------ | ------------------- | --------------- | ------------------------- |
| `valid`                | Green  | `bg-green-600`     | `CircleCheck`   | "Checked In"              |
| `invalid`              | Red    | `bg-red-600`       | `CircleX`       | "Entry Denied"            |
| `denied_by_policy`     | Red    | `bg-red-600`       | `ShieldX`       | "Entry Denied"            |
| `already_checked_in`   | Orange | `bg-orange-600`    | `AlertTriangle` | "Already Checked In"      |
| `override_possible`    | Orange | `bg-orange-600`    | `AlertTriangle` | "Override Available"      |
| `verification_required`| Orange | `bg-orange-600`    | `ClipboardCheck`| "Verification Required"   |
| `payment_required`     | Orange | `bg-orange-600`    | `Banknote`      | "Payment Required"        |

### 4.3.4 Design

```vue
<template>
  <!-- Full-screen overlay: fixed position, covers entire viewport -->
  <div
    :class="[
      'fixed inset-0 z-50 flex flex-col',
      tierClass,
    ]"
  >
    <!-- ===== TOP: Status header ===== -->
    <div class="flex flex-col items-center justify-center flex-shrink-0 pt-12 pb-6 px-6">
      <component
        :is="tierIcon"
        class="w-20 h-20 text-white mb-4"
        :stroke-width="1.5"
      />
      <h1 class="text-3xl font-bold text-white text-center">
        {{ primaryText }}
      </h1>
      <p class="text-lg text-white/90 text-center mt-2 max-w-sm">
        {{ result.message }}
      </p>
      <!-- Attendee name -->
      <p v-if="result.attendee?.name" class="text-xl text-white font-semibold mt-3">
        {{ result.attendee.name }}
      </p>
    </div>

    <!-- ===== MIDDLE: Scrollable detail area ===== -->
    <div class="flex-1 overflow-y-auto px-6 pb-4 space-y-4">

      <!-- GREEN: Seating information -->
      <div
        v-if="result.decision === 'valid' && result.seating"
        class="bg-white/20 backdrop-blur rounded-2xl p-5"
      >
        <h2 class="text-sm font-semibold text-white/70 uppercase tracking-wider mb-2">
          Seating
        </h2>
        <p class="text-2xl font-bold text-white">
          {{ result.seating.seat }}
        </p>
        <p v-if="result.seating.area" class="text-lg text-white/90">
          {{ result.seating.area }}
        </p>
        <p v-if="result.seating.directions" class="text-base text-white/80 mt-2 leading-relaxed">
          {{ result.seating.directions }}
        </p>
      </div>

      <!-- GREEN: Addon list -->
      <div
        v-if="result.decision === 'valid' && result.addons?.length"
        class="bg-white/20 backdrop-blur rounded-2xl p-5"
      >
        <h2 class="text-sm font-semibold text-white/70 uppercase tracking-wider mb-3">
          Ticket Addons
        </h2>
        <ul class="space-y-3">
          <li
            v-for="addon in result.addons"
            :key="addon.name"
            class="flex items-start gap-3"
          >
            <Package class="w-5 h-5 text-white/80 mt-0.5 flex-shrink-0" />
            <div>
              <p class="text-base font-medium text-white">{{ addon.name }}</p>
              <p v-if="addon.info" class="text-sm text-white/70">{{ addon.info }}</p>
            </div>
          </li>
        </ul>
      </div>

      <!-- ORANGE (verification_required): Verification checklist -->
      <div
        v-if="result.decision === 'verification_required' && result.verification"
        class="bg-white/20 backdrop-blur rounded-2xl p-5"
      >
        <h2 class="text-sm font-semibold text-white/70 uppercase tracking-wider mb-3">
          Please Verify
        </h2>
        <ul class="space-y-4">
          <li
            v-for="check in result.verification.checks"
            :key="check.label"
            class="flex items-start gap-3"
          >
            <ClipboardList class="w-6 h-6 text-white mt-0.5 flex-shrink-0" />
            <div>
              <p class="text-lg font-semibold text-white">{{ check.label }}</p>
              <p v-if="check.instruction" class="text-sm text-white/80">
                {{ check.instruction }}
              </p>
            </div>
          </li>
        </ul>
      </div>

      <!-- ORANGE (payment_required): Payment collection -->
      <div
        v-if="result.decision === 'payment_required' && result.payment"
        class="space-y-4"
      >
        <!-- Amount due -->
        <div class="bg-white/20 backdrop-blur rounded-2xl p-5 text-center">
          <h2 class="text-sm font-semibold text-white/70 uppercase tracking-wider mb-2">
            Amount Due
          </h2>
          <p class="text-4xl font-bold text-white">
            {{ result.payment.amount }} {{ result.payment.currency }}
          </p>
        </div>

        <!-- Item breakdown -->
        <div class="bg-white/20 backdrop-blur rounded-2xl p-5">
          <h2 class="text-sm font-semibold text-white/70 uppercase tracking-wider mb-3">
            Items
          </h2>
          <ul class="space-y-2">
            <li
              v-for="item in result.payment.items"
              :key="item.name"
              class="flex justify-between text-white"
            >
              <span>{{ item.name }}</span>
              <span class="font-semibold">{{ item.price }} {{ result.payment.currency }}</span>
            </li>
          </ul>
        </div>

        <!-- Payment method selection -->
        <div class="bg-white/20 backdrop-blur rounded-2xl p-5">
          <h2 class="text-sm font-semibold text-white/70 uppercase tracking-wider mb-3">
            Payment Method
          </h2>
          <div class="flex gap-3">
            <button
              v-for="method in result.payment.methods"
              :key="method"
              :class="[
                'flex-1 py-3 rounded-xl text-lg font-semibold transition-all',
                selectedMethod === method
                  ? 'bg-white text-orange-600 shadow-lg'
                  : 'bg-white/10 text-white border border-white/30'
              ]"
              @click="selectedMethod = method"
            >
              <Banknote v-if="method === 'cash'" class="w-5 h-5 inline mr-2" />
              <CreditCard v-if="method === 'card'" class="w-5 h-5 inline mr-2" />
              {{ method === 'cash' ? 'Cash' : 'Card' }}
            </button>
          </div>
        </div>
      </div>

      <!-- GREEN: Receipt sent notice -->
      <div
        v-if="result.decision === 'valid' && result.receipt_sent"
        class="bg-white/20 backdrop-blur rounded-2xl p-4 text-center"
      >
        <p class="text-base text-white/90">
          <Mail class="w-5 h-5 inline mr-1" />
          Receipt sent to attendee's email
        </p>
      </div>

      <!-- ORANGE (override_possible): Override context -->
      <div
        v-if="result.decision === 'override_possible' && result.group_policy"
        class="bg-white/20 backdrop-blur rounded-2xl p-5"
      >
        <p class="text-base text-white">{{ result.group_policy.message }}</p>
        <p class="text-sm text-white/70 mt-1">
          {{ result.group_policy.members_checked_in }} of
          {{ result.group_policy.members_total }} members checked in
        </p>
      </div>

      <!-- RED (denied): Denial reason -->
      <div
        v-if="isRedTier && result.group_policy"
        class="bg-white/20 backdrop-blur rounded-2xl p-5"
      >
        <p class="text-base text-white">{{ result.group_policy.message }}</p>
      </div>
    </div>

    <!-- ===== BOTTOM: Audit reference + Action buttons ===== -->
    <div class="flex-shrink-0 p-6 space-y-3">
      <!-- Audit reference (small, subtle) -->
      <p v-if="result.audit_id" class="text-xs text-white/40 text-center">
        Ref: {{ result.audit_id }}
      </p>
      <!-- Verification confirm button -->
      <button
        v-if="result.decision === 'verification_required'"
        class="w-full py-4 rounded-2xl bg-green-600 text-white text-lg font-bold
               shadow-lg active:scale-[0.98] transition-transform"
        @click="$emit('verifyCheckin')"
      >
        Confirm &amp; Check In
      </button>

      <!-- Payment confirm button -->
      <button
        v-if="result.decision === 'payment_required'"
        :disabled="!selectedMethod"
        :class="[
          'w-full py-4 rounded-2xl text-lg font-bold shadow-lg transition-all',
          selectedMethod
            ? 'bg-green-600 text-white active:scale-[0.98]'
            : 'bg-white/20 text-white/50 cursor-not-allowed'
        ]"
        @click="$emit('confirmPayment', selectedMethod)"
      >
        Confirm Payment &amp; Check In
      </button>

      <!-- Override button (for override_possible + payment bypass for Moderator+) -->
      <button
        v-if="(result.decision === 'override_possible' || result.decision === 'payment_required') && result.override_allowed"
        class="w-full py-4 rounded-2xl bg-white text-orange-600 text-lg font-bold
               shadow-lg active:scale-[0.98] transition-transform"
        @click="$emit('override')"
      >
        Override
      </button>

      <!-- Next Scan / Dismiss (hidden for payment_required unless Moderator+) -->
      <button
        v-if="result.decision !== 'payment_required' || result.override_allowed"
        class="w-full py-4 rounded-2xl text-lg font-bold
               active:scale-[0.98] transition-transform"
        :class="['verification_required', 'payment_required'].includes(result.decision)
          ? 'bg-white/20 text-white'
          : 'bg-white text-gray-900 shadow-lg'"
        @click="$emit('dismiss')"
      >
        Next Scan
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import {
  CircleCheck, CircleX, ShieldX, AlertTriangle,
  ClipboardCheck, ClipboardList, Package, Banknote, CreditCard, Mail,
} from 'lucide-vue-next'

const selectedMethod = ref<string | null>(null)

const props = defineProps<{ result: DecisionResult }>()

defineEmits<{
  dismiss: []
  override: []
  verifyCheckin: []
  confirmPayment: [method: string]
}>()

const tierConfig = computed(() => {
  const map: Record<Decision, { class: string; icon: any; text: string }> = {
    valid:                 { class: 'bg-green-600',  icon: CircleCheck,    text: 'Checked In' },
    invalid:               { class: 'bg-red-600',    icon: CircleX,        text: 'Entry Denied' },
    denied_by_policy:      { class: 'bg-red-600',    icon: ShieldX,        text: 'Entry Denied' },
    already_checked_in:    { class: 'bg-orange-600',  icon: AlertTriangle,  text: 'Already Checked In' },
    override_possible:     { class: 'bg-orange-600',  icon: AlertTriangle,  text: 'Override Available' },
    verification_required: { class: 'bg-orange-600',  icon: ClipboardCheck, text: 'Verification Required' },
    payment_required:      { class: 'bg-orange-600',  icon: Banknote,       text: 'Payment Required' },
  }
  return map[props.result.decision]
})

const tierClass = computed(() => tierConfig.value.class)
const tierIcon = computed(() => tierConfig.value.icon)
const primaryText = computed(() => tierConfig.value.text)
const isRedTier = computed(() =>
  ['invalid', 'denied_by_policy'].includes(props.result.decision)
)
</script>
```

### 4.3.5 Layout structure (mobile portrait)

```
┌──────────────────────────────────┐
│         [COLORED BACKGROUND]      │  ← Full viewport
│                                   │
│            ● Icon (80px)          │  ← Tier icon
│         "Checked In"              │  ← Primary text (28px+)
│    "Welcome to the event!"        │  ← Message from LanCore
│        "Max Mustermann"           │  ← Attendee name
│                                   │
│  ┌─────────────────────────────┐  │
│  │ SEATING               ▾    │  │  ← Card (green only)
│  │ Seat A-42                   │  │
│  │ Hall A                      │  │
│  │ Enter Hall A, turn left...  │  │
│  └─────────────────────────────┘  │
│                                   │
│  ┌─────────────────────────────┐  │
│  │ TICKET ADDONS               │  │  ← Card (green only)
│  │ 📦 Pizza Package            │  │
│  │    Collect at Booth 3       │  │
│  │ 📦 Chair Rental             │  │
│  │    Pre-placed at your seat  │  │
│  │ 📦 Tournament Entry         │  │
│  └���────────────────────────────┘  │
│                                   │
│  ┌─────────────────────────────┐  │
│  │      [ Next Scan ]          │  │  ← Action button area
│  └���────────────────────────────┘  │
└──────────────────────────────────┘
```

Orange `verification_required` layout:

```
┌──────────────────────────────────┐
│       [ORANGE BACKGROUND]         │
│                                   │
│         📋 Icon (80px)           │
│    "Verification Required"        │
│  "Student ticket — please verify" │
│        "Lisa Schmidt"             │
│                                   │
│  ┌─────────────────────────────┐  │
│  │ PLEASE VERIFY               │  │  ← Checklist card
│  │ 📋 Student ID               ���  │
│  │    Show valid university ID  │  │
│  │ 📋 Age Verification          │  │
│  │    Attendee must be 18+      │  │
│  └──��──────────────────────────┘  │
│                                   │
│  ┌─────────────────────────────┐  │
│  │ [  Confirm & Check In  ]    │  │  ← Green button on orange
│  │ [      Next Scan       ]    │  │  ← Subtle dismiss
│  └���────────────────────────────┘  │
���─────────��────────────────────────┘
```

### 4.3.6 Behavior

1. **Overlay mounts**: Covers scanner viewport entirely (`fixed inset-0 z-50`)
2. **No auto-dismiss**: Overlay stays until explicit button tap — prevents accidental dismissal
3. **Scrollable middle**: If seating + addon list exceeds viewport, the middle section scrolls
4. **Green (`valid`)**: Shows seating directions + addon list. Single "Next Scan" button
5. **Red (`invalid`, `denied_by_policy`)**: Shows denial reason. Single "Next Scan" button
6. **Orange (`already_checked_in`)**: Shows prior check-in context. Single "Next Scan" button
7. **Orange (`override_possible`)**: Shows policy context. "Override" + "Next Scan" buttons
8. **Orange (`verification_required`)**: Shows checklist. "Confirm & Check In" (green) + "Next Scan" (subtle) buttons
9. **On "Confirm & Check In"**: Emits `verifyCheckin` → parent calls `POST /api/entrance/verify-checkin` → result replaces overlay with green success (including seating + addons)
10. **On "Override"**: Emits `override` → parent opens OverrideModal
11. **On "Next Scan"**: Emits `dismiss` → parent resumes scanner

---

## 4.4 CSC: OverrideModal Component (`components/entrance/OverrideModal.vue`)

### 4.4.1 Purpose

Modal dialog for staff override with required reason.

**SRS Trace**: LENT-SW-GROUP-002, LENT-SW-GROUP-003

### 4.4.2 Design

**Props**: `context: DecisionResult`
**Emits**: `submit(reason: string)`, `cancel`

**Fields**:
* Reason textarea (required, min 10 characters)
* Attendee context display (read-only)
* Submit button (disabled until reason valid)
* Cancel button

Uses Reka UI `Dialog` primitive for accessible modal behavior.

---

## 4.5 CSC: Lookup Page (`pages/entrance/Lookup.vue`)

### 4.5.1 Purpose

Manual attendee search when camera scanning is unavailable.

**SRS Trace**: LENT-SW-SCAN-005

### 4.5.2 Design

**Layout**:
1. Search input with debounced query (300ms)
2. Results list with attendee name, ticket status indicator
3. Tap result → call validate → transition to DecisionDisplay

**API**: `GET /api/entrance/lookup?q={query}`

---

## 4.6 CSC: useEntranceState Composable (`composables/useEntranceState.ts`)

### 4.6.1 Purpose

Manages the entrance workflow state machine.

**SRS Trace**: LENT-SW-STATE-001, LENT-SW-STATE-003, LENT-SW-STATE-004

### 4.6.2 Design

```typescript
interface EntranceState {
  current: 'IDLE' | 'READY' | 'ACTIVE_SCAN' | 'ACTIVE_LOOKUP' | 'DECISION_DISPLAY'
  degraded: boolean
  lastResult: DecisionResult | null
  loading: boolean
}

function useEntranceState() {
  const state = reactive<EntranceState>({
    current: 'IDLE',
    degraded: false,
    lastResult: null,
    loading: false,
  })
  
  function transition(to: EntranceState['current'], result?: DecisionResult) { ... }
  function setDegraded(degraded: boolean) { ... }
  function resetToReady() { ... }
  
  return { state, transition, setDegraded, resetToReady }
}
```

---

## 4.7 CSC: useCheckin Composable (`composables/useCheckin.ts`)

### 4.7.1 Purpose

Encapsulates API calls for validation, check-in, and override operations.

**SRS Trace**: LENT-SW-CHECKIN-001, LENT-SW-GROUP-002

### 4.7.2 Design

```typescript
function useCheckin() {
  async function validate(token: string): Promise<DecisionResult> {
    // POST /api/entrance/validate { token }
    // Handle degraded responses
  }
  
  async function checkin(token: string, validationId: string): Promise<DecisionResult> {
    // POST /api/entrance/checkin { token, validation_id }
    // Response includes seating + addons for green overlay
  }
  
  async function verifyCheckin(token: string, validationId: string): Promise<DecisionResult> {
    // POST /api/entrance/verify-checkin { token, validation_id }
    // Called after operator confirms manual verification checks
    // Response includes seating + addons for green overlay
  }
  
  async function confirmPayment(token: string, validationId: string, method: string, amount: string): Promise<DecisionResult> {
    // POST /api/entrance/confirm-payment { token, validation_id, payment_method, amount }
    // LanCore records payment, generates PDF receipt, sends email
    // Response includes seating + addons + receipt_sent for green overlay
  }
  
  async function override(token: string, validationId: string, reason: string): Promise<DecisionResult> {
    // POST /api/entrance/override { token, validation_id, reason }
  }
  
  async function lookup(query: string): Promise<AttendeeResult[]> {
    // GET /api/entrance/lookup?q=query
  }
  
  return { validate, checkin, verifyCheckin, confirmPayment, override, lookup }
}
```

---

## 4.8 CSC: useScanner Composable — REMOVED

Camera lifecycle and QR decoding are now fully managed by `vue-qrcode-reader`'s `QrcodeStream` component internally. The `useScanner` composable is no longer needed as a separate abstraction.

**Camera management**: Handled by `QrcodeStream` via `constraints` prop and `camera-on`/`camera-off`/`error` events.
**Pause/resume**: Handled via the `paused` prop on `QrcodeStream`.
**Decoding**: Handled by the `barcode-detector` polyfill (ZXing-Wasm) integrated into `QrcodeStream`.

The `QrScanner.vue` component (Section 4.2) wraps `QrcodeStream` directly, exposing a `resume()` method for parent control.

---

## 4.9 CSC: DegradedBanner Component (`components/entrance/DegradedBanner.vue`)

### 4.9.1 Purpose

Persistent banner indicating degraded connectivity state.

**SRS Trace**: LENT-SW-STATE-004

### 4.9.2 Design

* Fixed-position amber banner at top of viewport
* Text: "Reduced connectivity — results may be delayed or unavailable"
* Includes animated icon to indicate ongoing connectivity issue
* `role="alert"` and `aria-live="assertive"` for accessibility

---

# 5. LENT-BE CSC designs

## 5.1 CSC: EntranceController (`Http/Controllers/Entrance/EntranceController.php`)

### 5.1.1 Purpose

Handles validation and check-in API requests.

**SRS Trace**: LENT-SW-CHECKIN-001, LENT-SW-INT-001

### 5.1.2 Design

```php
class EntranceController extends Controller
{
    public function __construct(
        private LanCoreValidationService $validation,
    ) {}
    
    /**
     * POST /api/entrance/validate
     * Validates a scanned or entered token against LanCore.
     */
    public function validate(ValidateTokenRequest $request): JsonResponse
    {
        $result = $this->validation->validate(
            token: $request->validated('token'),
            operator: $request->user(),
        );
        
        return response()->json($result);
    }
    
    /**
     * POST /api/entrance/checkin
     * Confirms check-in after a valid validation.
     * Response includes seating directions and addon list.
     */
    public function checkin(CheckinRequest $request): JsonResponse
    {
        $result = $this->validation->checkin(
            token: $request->validated('token'),
            validationId: $request->validated('validation_id'),
            operator: $request->user(),
        );
        
        return response()->json($result);
    }
    
    /**
     * POST /api/entrance/verify-checkin
     * Confirms check-in after operator completes manual verification steps.
     * Called for verification_required decisions.
     * Response includes seating directions and addon list.
     */
    public function verifyCheckin(CheckinRequest $request): JsonResponse
    {
        $result = $this->validation->verifyCheckin(
            token: $request->validated('token'),
            validationId: $request->validated('validation_id'),
            operator: $request->user(),
        );
        
        return response()->json($result);
    }
}
```

### 5.1.3 Middleware

* `auth` — Requires authenticated session
* `verified` — Requires verified email
* `throttle:entrance` — Rate limiting for entrance operations
* `EnsureEntranceRole` — Checks minimum role for operation

---

## 5.2 CSC: OverrideController (`Http/Controllers/Entrance/OverrideController.php`)

### 5.2.1 Purpose

Handles staff override requests with elevated authorization.

**SRS Trace**: LENT-SW-GROUP-002, LENT-SW-GROUP-003, LENT-SW-AUTHZ-001

### 5.2.2 Design

```php
class OverrideController extends Controller
{
    public function __construct(
        private LanCoreValidationService $validation,
    ) {}
    
    /**
     * POST /api/entrance/override
     * Submits a staff override with required reason.
     */
    public function __invoke(OverrideRequest $request): JsonResponse
    {
        // EnsureEntranceRole middleware checks Moderator+
        
        $result = $this->validation->override(
            token: $request->validated('token'),
            validationId: $request->validated('validation_id'),
            reason: $request->validated('reason'),
            operator: $request->user(),
        );
        
        return response()->json($result);
    }
}
```

---

## 5.25 CSC: PaymentController (`Http/Controllers/Entrance/PaymentController.php`)

### 5.25.1 Purpose

Handles on-site payment confirmation for `payment_required` tickets.

**SRS Trace**: LENT-SW-PAY-003, LENT-SW-PAY-005

### 5.25.2 Design

```php
class PaymentController extends Controller
{
    public function __construct(
        private LanCoreValidationService $validation,
    ) {}
    
    /**
     * POST /api/entrance/confirm-payment
     * Confirms on-site payment collected and completes check-in.
     * LanCore records payment, generates PDF receipt, sends email.
     */
    public function __invoke(ConfirmPaymentRequest $request): JsonResponse
    {
        $result = $this->validation->confirmPayment(
            token: $request->validated('token'),
            validationId: $request->validated('validation_id'),
            paymentMethod: $request->validated('payment_method'),
            amount: $request->validated('amount'),
            operator: $request->user(),
        );
        
        return response()->json($result);
    }
}
```

---

## 5.3 CSC: LookupController (`Http/Controllers/Entrance/LookupController.php`)

### 5.3.1 Purpose

Handles manual attendee search requests.

**SRS Trace**: LENT-SW-SCAN-005

### 5.3.2 Design

```php
class LookupController extends Controller
{
    public function __construct(
        private LanCoreValidationService $validation,
    ) {}
    
    /**
     * GET /api/entrance/lookup?q={query}
     * Searches for attendees via LanCore.
     */
    public function __invoke(LookupRequest $request): JsonResponse
    {
        $results = $this->validation->search(
            query: $request->validated('q'),
            operator: $request->user(),
        );
        
        return response()->json(['results' => $results]);
    }
}
```

---

## 5.4 CSC: LanCoreValidationService (`Services/LanCoreValidationService.php`)

### 5.4.1 Purpose

Orchestrates validation, check-in, override, and search operations against LanCore API. Injects audit metadata.

**SRS Trace**: LENT-SW-CHECKIN-001, LENT-SW-AUDIT-001, LENT-SW-DEGRADED-001

### 5.4.2 Design

```php
class LanCoreValidationService
{
    public function __construct(
        private LanCoreClient $client,
    ) {}
    
    public function validate(string $token, User $operator): array
    {
        try {
            $response = $this->client->validateTicket(
                token: $token,
                metadata: $this->buildMetadata($operator),
            );
            
            return $this->mapValidationResponse($response);
        } catch (ConnectionException $e) {
            return $this->degradedResponse('LanCore unavailable');
        } catch (RequestException $e) {
            return $this->mapErrorResponse($e->response);
        }
    }
    
    public function checkin(string $token, string $validationId, User $operator): array { ... }
    public function verifyCheckin(string $token, string $validationId, User $operator): array { ... }
    public function confirmPayment(string $token, string $validationId, string $paymentMethod, string $amount, User $operator): array { ... }
    public function override(string $token, string $validationId, string $reason, User $operator): array { ... }
    public function search(string $query, User $operator): array { ... }
    
    private function buildMetadata(User $operator): array
    {
        return [
            'operator_id' => $operator->lancore_user_id,
            'operator_session' => session()->getId(),
            'timestamp' => now()->toISOString(),
            'client_info' => request()->userAgent(),
        ];
    }
    
    private function degradedResponse(string $message): array
    {
        return [
            'decision' => 'error',
            'message' => $message,
            'degraded' => true,
        ];
    }
}
```

### 5.4.3 Error mapping

| LanCore HTTP Status | Mapped Decision          | Frontend Treatment |
| ------------------- | ------------------------ | ------------------- |
| 200                 | As returned by LanCore   | Decision display   |
| 404                 | `invalid`                | Red error state    |
| 422                 | Forward validation errors| Error display      |
| 429                 | Rate limit error         | Rate limit message |
| 500                 | `error` + `degraded`     | Degraded banner    |
| Timeout/Connection  | `error` + `degraded`     | Degraded banner    |

---

## 5.5 CSC: LanCoreClient Extensions (`Services/LanCoreClient.php`)

### 5.5.1 Purpose

Extends existing LanCoreClient with validation, check-in, override, and search API methods.

**SRS Trace**: LENT-SW-CHECKIN-001, LENT-SW-CHECKIN-002

### 5.5.2 New methods (Phase 2)

```php
// Added to existing LanCoreClient class

public function validateTicket(string $token, array $metadata): array
{
    $this->ensureEnabled();
    
    return $this->http()->post('/api/entrance/validate', [
        'token' => $token,
        ...$metadata,
    ])->throw()->json();
}

public function confirmCheckin(string $token, string $validationId, array $metadata): array
{
    $this->ensureEnabled();
    
    return $this->http()->post('/api/entrance/checkin', [
        'token' => $token,
        'validation_id' => $validationId,
        ...$metadata,
    ])->throw()->json();
}

public function confirmVerifyCheckin(string $token, string $validationId, array $metadata): array
{
    $this->ensureEnabled();
    
    return $this->http()->post('/api/entrance/verify-checkin', [
        'token' => $token,
        'validation_id' => $validationId,
        ...$metadata,
    ])->throw()->json();
}

public function confirmPayment(string $token, string $validationId, string $paymentMethod, string $amount, array $metadata): array
{
    $this->ensureEnabled();
    
    return $this->http()->post('/api/entrance/confirm-payment', [
        'token' => $token,
        'validation_id' => $validationId,
        'payment_method' => $paymentMethod,
        'amount' => $amount,
        ...$metadata,
    ])->throw()->json();
}

public function submitOverride(string $token, string $validationId, string $reason, array $metadata): array
{
    $this->ensureEnabled();
    
    return $this->http()->post('/api/entrance/override', [
        'token' => $token,
        'validation_id' => $validationId,
        'reason' => $reason,
        ...$metadata,
    ])->throw()->json();
}

public function searchAttendees(string $query, array $metadata): array
{
    $this->ensureEnabled();
    
    return $this->http()->get('/api/entrance/search', [
        'q' => $query,
        ...$metadata,
    ])->throw()->json();
}
```

---

## 5.6 CSC: EnsureEntranceRole Middleware (`Http/Middleware/EnsureEntranceRole.php`)

### 5.6.1 Purpose

Checks that the authenticated user has the required role for the entrance operation.

**SRS Trace**: LENT-SW-AUTHZ-001, LENT-SW-SEC-002

### 5.6.2 Design

```php
class EnsureEntranceRole
{
    public function handle(Request $request, Closure $next, string $minimumRole = 'user'): Response
    {
        $role = UserRole::from($minimumRole);
        
        if (! $request->user()->hasRole($role) && ! $request->user()->hasAnyRole($this->rolesAbove($role))) {
            abort(403, 'Insufficient role for this operation.');
        }
        
        return $next($request);
    }
    
    private function rolesAbove(UserRole $role): array { ... }
}
```

---

## 5.7 CSC: Form Requests

### 5.7.1 ValidateTokenRequest

```php
class ValidateTokenRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'max:512'],
        ];
    }
}
```

### 5.7.2 CheckinRequest

```php
class CheckinRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'max:512'],
            'validation_id' => ['required', 'string'],
        ];
    }
}
```

### 5.7.3 OverrideRequest

```php
class OverrideRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'max:512'],
            'validation_id' => ['required', 'string'],
            'reason' => ['required', 'string', 'min:10', 'max:500'],
        ];
    }
}
```

### 5.7.4 ConfirmPaymentRequest

```php
class ConfirmPaymentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'max:512'],
            'validation_id' => ['required', 'string'],
            'payment_method' => ['required', 'string', 'in:cash,card'],
            'amount' => ['required', 'string', 'regex:/^\d+\.\d{2}$/'],
        ];
    }
}
```

### 5.7.5 LookupRequest

```php
class LookupRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'q' => ['required', 'string', 'min:2', 'max:100'],
        ];
    }
}
```

---

## 5.8 CSC: DTOs

### 5.8.1 ValidationResponse DTO

```php
class ValidationResponse
{
    public function __construct(
        public string $decision,
        public string $message,
        public string $validationId,
        public bool $degraded = false,
        public bool $overrideAllowed = false,
        public ?string $auditId = null,
        public ?array $attendee = null,
        public ?SeatingInfo $seating = null,
        public ?array $addons = null,          // Addon[]
        public ?VerificationInfo $verification = null,
        public ?PaymentInfo $payment = null,
        public ?array $groupPolicy = null,
        public ?bool $receiptSent = null,
    ) {}
    
    public static function fromLanCore(array $data): self { ... }
    public function toArray(): array { ... }
}

class SeatingInfo
{
    public function __construct(
        public string $seat,
        public ?string $area = null,
        public ?string $directions = null,
    ) {}
}

class Addon
{
    public function __construct(
        public string $name,
        public ?string $info = null,
    ) {}
}

class VerificationInfo
{
    public function __construct(
        public string $message,
        public array $checks,    // VerificationCheck[]
    ) {}
}

class VerificationCheck
{
    public function __construct(
        public string $label,
        public ?string $instruction = null,
    ) {}
}

class PaymentInfo
{
    public function __construct(
        public string $amount,
        public string $currency,
        public array $items,       // PaymentItem[]
        public array $methods,     // string[] e.g., ["cash", "card"]
    ) {}
}

class PaymentItem
{
    public function __construct(
        public string $name,
        public string $price,
    ) {}
}
```

---

# 6. Route registration (Phase 2)

```php
// routes/api.php additions
Route::middleware(['auth:sanctum', 'verified'])->prefix('entrance')->group(function () {
    Route::post('/validate', [EntranceController::class, 'validate'])
        ->middleware('throttle:entrance');
    
    Route::post('/checkin', [EntranceController::class, 'checkin'])
        ->middleware('throttle:entrance');
    
    Route::post('/verify-checkin', [EntranceController::class, 'verifyCheckin'])
        ->middleware('throttle:entrance');
    
    Route::post('/confirm-payment', PaymentController::class)
        ->middleware('throttle:entrance');
    
    Route::post('/override', OverrideController::class)
        ->middleware(['throttle:entrance', 'EnsureEntranceRole:moderator']);
    
    Route::get('/lookup', LookupController::class)
        ->middleware('throttle:entrance');
});
```

---

# 7. Notes

## 7.1 Design decisions

* **Inertia for pages, REST for operations**: Scanner page loads via Inertia for SSR props, but scan/validate/checkin use direct API calls for minimal latency
* **Composables over store**: Entrance state is page-scoped, no need for global state management
* **Service layer over controller logic**: `LanCoreValidationService` handles all orchestration, keeping controllers thin
* **DTOs for type safety**: Structured response objects prevent loose array passing

## 7.2 Phase 2 implementation order

1. Backend: DTOs, Form Requests, LanCoreClient extensions
2. Backend: LanCoreValidationService, EnsureEntranceRole middleware
3. Backend: Controllers, route registration
4. Backend: Feature tests with mocked LanCore
5. Frontend: Composables (useCheckin, useEntranceState)
6. Frontend: Components (QrScanner, DecisionDisplay, OverrideModal)
7. Frontend: Pages (Scanner, Lookup)
8. Frontend: Component tests + E2E tests
