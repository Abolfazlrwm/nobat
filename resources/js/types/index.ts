import type { LucideIcon } from 'lucide-vue-next';

export interface Auth {
    user: SharedUser;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon;
    isActive?: boolean;
}

export interface FlashData {
    success?: string;
    error?: string;
    info?: string;
}

export interface SharedData {
    name: string;
    auth: Auth;
    flash: FlashData;
    ziggy: {
        location: string;
        url: string;
        port: null | number;
        defaults: Record<string, unknown>;
        routes: Record<string, string>;
    };
    [key: string]: unknown;
}

export interface SharedUser {
    id: number;
    name: string;
    email: string;
    roles: string[];
    is_super_admin: boolean;
}

/** Authenticated user, enriched with optional profile fields. */
export interface User extends SharedUser {
    avatar?: string;
    email_verified_at?: string | null;
    created_at?: string;
    updated_at?: string;
}

/* ------------------------------------------------------------------ */
/* Domain shapes passed from Laravel to Inertia pages                   */
/* ------------------------------------------------------------------ */

export interface BusinessSummary {
    id: number;
    name: string;
    slug: string;
    description?: string | null;
    phone: string;
    address?: string | null;
    services_count?: number;
    staff_count?: number;
}

export interface BusinessWithServices extends BusinessSummary {
    services: ServiceItem[];
    staff: StaffItem[];
}

export interface ServiceItem {
    id: number;
    name: string;
    duration_minutes: number;
    price: number;
    is_active?: boolean;
    sort_order?: number;
}

export interface StaffItem {
    id: number;
    name: string;
    specialty?: string | null;
    is_active?: boolean;
    has_account?: boolean;
    email?: string | null;
}

export interface SlotItem {
    starts_at: string;
    label: string;
    ends_label: string;
    staff_ids: number[];
}

export interface AppointmentItem {
    id: number;
    code: string;
    customer_name: string;
    customer_phone: string;
    starts_at: string;
    status: string;
    status_label: string;
    service_name?: string;
    staff_name?: string;
    business_name?: string;
}

export interface StatCardData {
    today_count: number;
    pending_count: number;
    week_count: number;
    month_revenue: number;
}

export interface ChartPoint {
    date: string;
    count: number;
}

export interface WindowItem {
    id: number;
    weekday: number;
    weekday_name: string;
    starts_at: string;
    ends_at: string;
}

export interface WeekdayOption {
    value: number;
    label: string;
}

export interface AdminBusiness {
    id: number;
    name: string;
    slug: string;
    phone: string;
    is_active: boolean;
    owner_name?: string;
    owner_email?: string;
    appointments_count: number;
    services_count: number;
    staff_count: number;
}

export interface PaymentSummary {
    status: string;
    status_label: string;
    amount: number;
    ref_id?: string | null;
    invoice_number?: string | null;
}

export interface BookingConfirmation {
    code: string;
    status: string;
    status_label: string;
    customer_name: string;
    starts_at: string;
    payment_expires_at?: string | null;
    business: { name: string; slug: string; phone: string; address?: string | null };
    service: { name: string; duration_minutes: number; price: number };
    staff: { name: string; specialty?: string | null };
    payment?: PaymentSummary | null;
    has_review?: boolean;
    review?: { rating: number; comment?: string | null } | null;
}

export interface ReviewItem {
    id: number;
    customer_name: string;
    rating: number;
    comment?: string | null;
    is_approved: boolean;
    created_at?: string | null;
}

export interface ReviewSummary {
    average: number | null;
    count: number;
}

export interface BusinessReview extends ReviewItem {}

export type BreadcrumbItemType = BreadcrumbItem;

export interface Paginated<T> {
    data: T[];
    current_page: number;
    last_page: number;
    total: number;
    per_page: number;
    links: { url: string | null; label: string; active: boolean }[];
}
