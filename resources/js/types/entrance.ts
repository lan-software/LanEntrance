export type Decision =
    | 'valid'
    | 'invalid'
    | 'already_checked_in'
    | 'denied_by_policy'
    | 'override_possible'
    | 'verification_required'
    | 'payment_required';

export interface Seating {
    seat: string;
    area?: string | null;
    directions?: string | null;
}

export interface Addon {
    name: string;
    info?: string | null;
}

export interface VerificationCheck {
    label: string;
    instruction?: string | null;
}

export interface Verification {
    message: string;
    checks: VerificationCheck[];
}

export interface PaymentItem {
    name: string;
    price: string;
}

export interface Payment {
    amount: string;
    currency: string;
    items: PaymentItem[];
    methods: string[];
}

export interface GroupPolicy {
    rule: string;
    message: string;
    members_checked_in: number;
    members_total: number;
}

export interface DecisionResult {
    decision: Decision;
    message: string;
    validation_id: string;
    degraded: boolean;
    override_allowed: boolean;
    audit_id?: string | null;
    attendee?: { name: string; group?: string | null } | null;
    seating?: Seating | null;
    addons?: Addon[] | null;
    verification?: Verification | null;
    payment?: Payment | null;
    group_policy?: GroupPolicy | null;
    checkin_id?: string | null;
    payment_id?: string | null;
    override_id?: string | null;
    receipt_sent?: boolean | null;
}

export interface AttendeeResult {
    token: string;
    name: string;
    status: 'not_checked_in' | 'checked_in';
    seat?: string | null;
    group?: string | null;
}

export type EntranceStateName =
    | 'IDLE'
    | 'READY'
    | 'ACTIVE_SCAN'
    | 'ACTIVE_LOOKUP'
    | 'DECISION_DISPLAY';

export interface EntranceState {
    current: EntranceStateName;
    degraded: boolean;
    lastToken: string | null;
    lastResult: DecisionResult | null;
    loading: boolean;
}
