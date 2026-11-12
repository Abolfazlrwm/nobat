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
    demo: boolean;
    locale: 'fa' | 'en';
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
    rating_avg?: number | null;
    rating_count?: number;
}

export interface BusinessWithServices extends BusinessSummary {
    services: ServiceItem[];
    staff: StaffItem[];
    photos?: GalleryPhoto[];
    reviews_summary?: ReviewSummary | null;
    reviews?: ReviewItem[];
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

export interface AnalyticsPoint {
    date: string;
    count: number;
    revenue: number;
}

export interface AnalyticsHeatCell {
    weekday: number;
    hour: number;
    count: number;
}

export interface AnalyticsHourPeak {
    hour: number;
    count: number;
}

export interface AnalyticsStats {
    revenue: number;
    bookings: number;
    completed: number;
    cancelled: number;
    completion_rate: number;
    cancellation_rate: number;
    avg_ticket: number | null;
    unique_customers: number;
    avg_rating: number | null;
    revenue_delta: number | null;
    bookings_delta: number | null;
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
    discount_amount?: number | null;
    discount_code?: string | null;
    payment?: PaymentSummary | null;
    has_review?: boolean;
    review?: { rating: number; comment?: string | null } | null;
}

export interface DiscountCodeItem {
    id: number;
    code: string;
    type: string;
    type_label: string;
    value: number;
    value_label: string;
    min_amount?: number | null;
    max_uses?: number | null;
    used_count: number;
    per_user_limit?: number | null;
    starts_at?: string | null;
    expires_at?: string | null;
    is_active: boolean;
}

export interface WaitlistItem {
    id: number;
    desired_date: string;
    customer_name: string;
    customer_phone: string;
    status: string;
    status_label: string;
    notified_at?: string | null;
    note?: string | null;
    service?: { name: string } | null;
    staff?: { name: string } | null;
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

/** One photo of the business gallery. */
export interface GalleryPhoto {
    id: number;
    url: string;
    caption?: string | null;
    is_cover: boolean;
}

/** One aggregated customer row of the customer book (per phone). */
export interface CustomerSummary {
    phone: string;
    name: string;
    appointments_count: number;
    completed_count: number;
    cancelled_count: number;
    no_show_count: number;
    no_show_rate: number;
    total_spent: number;
    last_visit_at?: string | null;
}

/** One appointment row of a customer profile history. */
export interface CustomerHistoryItem {
    id: number;
    code: string;
    service_name?: string;
    staff_name?: string;
    starts_at: string;
    status: string;
    status_label: string;
    price: number;
}

export type BreadcrumbItemType = BreadcrumbItem;

export interface Paginated<T> {
    data: T[];
    current_page: number;
    last_page: number;
    total: number;
    per_page: number;
    links: { url: string | null; label: string; active: boolean }[];
}
