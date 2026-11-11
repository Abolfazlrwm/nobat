<script setup lang="ts">
import JalaliDatePicker from '@/components/JalaliDatePicker.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { useLocale } from '@/composables/useLocale';
import { toDateString } from '@/lib/persian';
import { cn } from '@/lib/utils';
import { BusinessWithServices, ServiceItem, SlotItem } from '@/types';
import { router } from '@inertiajs/vue3';
import { Calendar, Check, CheckCircle2, Clock, Hourglass, Loader2, Tag, UserRound, X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const props = defineProps<{
    business: BusinessWithServices;
}>();

const { t, num, price } = useLocale();

const stepCount = 4;
const step = ref(0);

const selectedService = ref<ServiceItem | null>(null);
const selectedStaffId = ref<number | null>(null);
const selectedDate = ref<string | null>(null);
const selectedSlot = ref<SlotItem | null>(null);

const form = ref({
    customer_name: '',
    customer_phone: '',
    notes: '',
});

const slots = ref<SlotItem[]>([]);
const loadingSlots = ref(false);
const submitting = ref(false);
const formErrors = ref<Record<string, string>>({});

// Discount code state (checked live against the business before submitting).
const discountInput = ref('');
const discountChecking = ref(false);
const discountApplied = ref<{ code: string; discount_amount: number; final_amount: number } | null>(null);
const discountError = ref('');

// Waitlist state for fully booked days.
const showWaitlistForm = ref(false);
const waitlistSubmitting = ref(false);
const waitlistDone = ref(false);
const waitlistError = ref('');
const waitlistForm = ref({ customer_name: '', customer_phone: '' });

const payableAmount = computed(() =>
    discountApplied.value
        ? discountApplied.value.final_amount
        : (selectedService.value?.price ?? 0),
);

const canContinue = computed(() => {
    if (step.value === 0) return selectedService.value !== null;
    if (step.value === 1) return true; // "any staff" is always allowed
    if (step.value === 2) return selectedSlot.value !== null;

    return form.value.customer_name.length > 1 && /^09\d{9}$/.test(form.value.customer_phone);
});

const minDate = toDateString(new Date());

watch(step, (value) => {
    if (value === 2) {
        loadSlots();
    }
});

watch(selectedDate, () => {
    selectedSlot.value = null;
    if (step.value === 2) {
        loadSlots();
    }
});

// Leaving the slot step (or changing the service) resets the waitlist panel.
watch([step, () => selectedService.value?.id], () => {
    showWaitlistForm.value = false;
    waitlistDone.value = false;
    waitlistError.value = '';
});

async function loadSlots(): Promise<void> {
    if (!selectedService.value || !selectedDate.value) return;

    loadingSlots.value = true;
    slots.value = [];

    const params = new URLSearchParams({
        service_id: String(selectedService.value.id),
        date: selectedDate.value,
    });

    if (selectedStaffId.value !== null) {
        params.set('staff_id', String(selectedStaffId.value));
    }

    try {
        const response = await fetch(`/b/${props.business.slug}/slots?${params}`, {
            headers: { Accept: 'application/json' },
        });

        if (response.ok) {
            const data = await response.json();
            slots.value = data.slots ?? [];
        }
    } finally {
        loadingSlots.value = false;
    }
}

async function checkDiscount(): Promise<void> {
    if (!selectedService.value || discountInput.value.trim().length < 2) return;

    discountChecking.value = true;
    discountError.value = '';
    discountApplied.value = null;

    const params = new URLSearchParams({
        code: discountInput.value.trim(),
        service_id: String(selectedService.value.id),
    });

    if (form.value.customer_phone) {
        params.set('phone', form.value.customer_phone);
    }

    try {
        const response = await fetch(`/b/${props.business.slug}/discount?${params}`, {
            headers: { Accept: 'application/json' },
        });

        if (response.ok) {
            const data = await response.json();

            if (data.valid) {
                discountApplied.value = data;
            } else {
                discountError.value = t('wizard.discountInvalid');
            }
        } else {
            discountError.value = t('wizard.discountInvalid');
        }
    } finally {
        discountChecking.value = false;
    }
}

function clearDiscount(): void {
    discountInput.value = '';
    discountApplied.value = null;
    discountError.value = '';
}

async function joinWaitlist(): Promise<void> {
    if (!selectedService.value || !selectedDate.value) return;
    if (waitlistForm.value.customer_name.length < 3 || !/^09\d{9}$/.test(waitlistForm.value.customer_phone)) {
        waitlistError.value = t('wizard.waitlistError');
        return;
    }

    waitlistSubmitting.value = true;
    waitlistError.value = '';

    try {
        const response = await fetch(route('waitlist.store', props.business.slug), {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-XSRF-TOKEN': decodeURIComponent(
                    (document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/) ?? [])[1] ?? '',
                ),
            },
            body: JSON.stringify({
                service_id: selectedService.value.id,
                desired_date: selectedDate.value,
                ...waitlistForm.value,
            }),
        });

        const data = await response.json().catch(() => null);

        if (response.ok) {
            waitlistDone.value = true;
            showWaitlistForm.value = false;
        } else if (response.status === 409) {
            waitlistError.value = t('wizard.waitlistDuplicate');
        } else if (response.status === 422 && data?.errors) {
            waitlistError.value = Object.values(data.errors)[0] as string;
        } else {
            waitlistError.value = t('wizard.waitlistError');
        }
    } finally {
        waitlistSubmitting.value = false;
    }
}

function next(): void {
    if (!canContinue.value) return;
    if (step.value === stepCount - 1) {
        submit();
        return;
    }

    step.value++;
}

function back(): void {
    if (step.value > 0) step.value--;
}

function submit(): void {
    if (!selectedService.value || !selectedSlot.value) return;

    submitting.value = true;
    formErrors.value = {};

    router.post(
        route('bookings.store', props.business.slug),
        {
            service_id: selectedService.value.id,
            staff_id: selectedStaffId.value,
            starts_at: selectedSlot.value.starts_at,
            discount_code: discountApplied.value?.code ?? null,
            ...form.value,
        },
        {
            onError: (errors) => {
                formErrors.value = errors;
                submitting.value = false;

                if (errors.discount_code) {
                    discountApplied.value = null;
                    discountError.value = errors.discount_code;
                }

                if (errors.starts_at) {
                    step.value = 2;
                    loadSlots();
                }
            },
        },
    );
}
</script>

<template>
    <Card>
        <CardContent class="pt-6">
            <!-- Step indicator -->
            <ol class="mb-6 flex items-start justify-between gap-2 text-sm">
                <li v-for="index in stepCount" :key="index" class="flex flex-1 flex-col items-center gap-1">
                    <div class="flex w-full items-center gap-2">
                        <span
                            :class="[
                                'flex size-7 shrink-0 items-center justify-center rounded-full border text-xs font-medium',
                                index - 1 < step && 'border-primary bg-primary text-primary-foreground',
                                index - 1 === step && 'border-primary text-primary',
                                index - 1 > step && 'text-muted-foreground',
                            ]"
                        >
                            <Check v-if="index - 1 < step" class="size-4" />
                            <template v-else>{{ num(index) }}</template>
                        </span>
                        <span v-if="index < stepCount" class="h-0.5 flex-1 rounded bg-border" />
                    </div>
                    <span :class="[index - 1 === step ? 'font-medium text-foreground' : 'text-muted-foreground']">
                        {{ t(`wizard.steps.${index - 1}`) }}
                    </span>
                </li>
            </ol>

            <!-- Step 1: service -->
            <div v-if="step === 0" class="grid gap-3 sm:grid-cols-2">
                <button
                    v-for="service in business.services"
                    :key="service.id"
                    type="button"
                    :class="[
                        'rounded-xl border p-4 text-start transition-colors hover:bg-accent',
                        selectedService?.id === service.id && 'border-primary bg-primary/5',
                    ]"
                    @click="selectedService = service"
                >
                    <div class="font-medium">{{ service.name }}</div>
                    <div class="mt-2 flex items-center gap-3 text-sm text-muted-foreground">
                        <span class="inline-flex items-center gap-1">
                            <Clock class="size-4" />
                            {{ t('wizard.minutes', { n: num(service.duration_minutes) }) }}
                        </span>
                        <span>{{ price(service.price) }}</span>
                    </div>
                </button>
            </div>

            <!-- Step 2: staff -->
            <div v-if="step === 1" class="grid gap-3 sm:grid-cols-2">
                <button
                    type="button"
                    :class="[
                        'flex items-center gap-3 rounded-xl border p-4 text-start transition-colors hover:bg-accent',
                        selectedStaffId === null && 'border-primary bg-primary/5',
                    ]"
                    @click="selectedStaffId = null"
                >
                    <span class="flex size-10 items-center justify-center rounded-full bg-muted">
                        <UserRound class="size-5 text-muted-foreground" />
                    </span>
                    <span>
                        <span class="block font-medium">{{ t('wizard.anyStaff') }}</span>
                        <span class="block text-sm text-muted-foreground">{{ t('wizard.anyStaffHint') }}</span>
                    </span>
                </button>

                <button
                    v-for="member in business.staff"
                    :key="member.id"
                    type="button"
                    :class="[
                        'flex items-center gap-3 rounded-xl border p-4 text-start transition-colors hover:bg-accent',
                        selectedStaffId === member.id && 'border-primary bg-primary/5',
                    ]"
                    @click="selectedStaffId = member.id"
                >
                    <span class="flex size-10 items-center justify-center rounded-full bg-primary/10 text-sm font-semibold text-primary">
                        {{ member.name.charAt(0) }}
                    </span>
                    <span>
                        <span class="block font-medium">{{ member.name }}</span>
                        <span v-if="member.specialty" class="block text-sm text-muted-foreground">{{ member.specialty }}</span>
                    </span>
                </button>
            </div>

            <!-- Step 3: date + slots -->
            <div v-if="step === 2" class="flex flex-col gap-6 lg:flex-row">
                <div class="mx-auto">
                    <JalaliDatePicker v-model="selectedDate" :min-date="minDate" />
                    <p v-if="!selectedDate" class="mt-2 text-center text-xs text-muted-foreground">{{ t('wizard.pickDayFirst') }}</p>
                </div>

                <div class="flex-1">
                    <div
                        v-if="!selectedDate"
                        class="flex h-full min-h-48 items-center justify-center rounded-xl border border-dashed text-sm text-muted-foreground"
                    >
                        <span class="inline-flex items-center gap-2"><Calendar class="size-4" /> {{ t('wizard.pickDateHint') }}</span>
                    </div>

                    <div v-else-if="loadingSlots" class="flex h-full min-h-48 items-center justify-center text-muted-foreground">
                        <Loader2 class="size-6 animate-spin" />
                    </div>

                    <template v-else>
                        <div
                            v-if="slots.length === 0"
                            class="flex h-full min-h-48 flex-col items-center justify-center gap-3 rounded-xl border border-dashed p-4 text-center"
                        >
                            <template v-if="!waitlistDone">
                                <Hourglass class="size-6 text-muted-foreground" />
                                <p class="text-sm font-medium">{{ t('wizard.waitlistTitle') }}</p>
                                <p class="max-w-sm text-sm text-muted-foreground">{{ t('wizard.waitlistHint') }}</p>

                                <Button v-if="!showWaitlistForm" variant="outline" size="sm" class="gap-2" @click="showWaitlistForm = true">
                                    <Hourglass class="size-4" />
                                    {{ t('wizard.waitlistJoin') }}
                                </Button>

                                <form v-else class="w-full max-w-xs space-y-2 text-start" @submit.prevent="joinWaitlist">
                                    <Input v-model="waitlistForm.customer_name" :placeholder="t('wizard.fullName')" />
                                    <Input
                                        v-model="waitlistForm.customer_phone"
                                        inputmode="numeric"
                                        placeholder="09xxxxxxxxx"
                                        dir="ltr"
                                    />
                                    <p v-if="waitlistError" class="text-sm text-destructive">{{ waitlistError }}</p>
                                    <Button type="submit" size="sm" class="w-full gap-2" :disabled="waitlistSubmitting">
                                        <Loader2 v-if="waitlistSubmitting" class="size-4 animate-spin" />
                                        {{ waitlistSubmitting ? t('wizard.waitlistSubmitting') : t('wizard.waitlistJoin') }}
                                    </Button>
                                </form>
                            </template>

                            <template v-else>
                                <CheckCircle2 class="size-6 text-emerald-500" />
                                <p class="text-sm font-medium">{{ t('wizard.waitlistSuccess') }}</p>
                            </template>
                        </div>

                        <div v-else class="grid grid-cols-3 gap-2 sm:grid-cols-4">
                            <button
                                v-for="slot in slots"
                                :key="slot.starts_at"
                                type="button"
                                :class="[
                                    cn('rounded-lg border py-2 text-sm transition-colors hover:bg-accent'),
                                    selectedSlot?.starts_at === slot.starts_at &&
                                        'border-primary bg-primary text-primary-foreground hover:bg-primary',
                                ]"
                                @click="selectedSlot = slot"
                            >
                                {{ num(slot.label) }}
                            </button>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Step 4: customer info -->
            <div v-if="step === 3" class="mx-auto max-w-md space-y-4">
                <div class="rounded-xl bg-muted/60 p-4 text-sm">
                    <div class="flex justify-between py-1">
                        <span class="text-muted-foreground">{{ t('booking.service') }}</span>
                        <span>{{ selectedService?.name }}</span>
                    </div>
                    <div class="flex justify-between py-1">
                        <span class="text-muted-foreground">{{ t('booking.staff') }}</span>
                        <span>{{ business.staff.find((s) => s.id === selectedStaffId)?.name ?? t('wizard.anyStaff') }}</span>
                    </div>
                    <div class="flex justify-between py-1">
                        <span class="text-muted-foreground">{{ t('booking.time') }}</span>
                        <span class="font-medium" dir="ltr">{{ selectedSlot?.starts_at }}</span>
                    </div>
                    <div class="flex justify-between py-1">
                        <span class="text-muted-foreground">{{ t('booking.amount') }}</span>
                        <span :class="discountApplied && 'line-through opacity-70'">{{ price(selectedService?.price ?? 0) }}</span>
                    </div>
                    <div v-if="discountApplied" class="flex justify-between py-1 text-emerald-600 dark:text-emerald-400">
                        <span>{{ t('wizard.discountLine', { code: discountApplied.code }) }}</span>
                        <span>− {{ price(discountApplied.discount_amount) }}</span>
                    </div>
                    <div v-if="discountApplied" class="flex justify-between border-t pt-2 font-medium">
                        <span>{{ t('wizard.discountFinal') }}</span>
                        <span>{{ price(payableAmount) }}</span>
                    </div>
                </div>

                <div class="space-y-2">
                    <Label for="discount_code">{{ t('wizard.discountLabel') }}</Label>
                    <div class="flex gap-2">
                        <div class="relative flex-1">
                            <Tag class="pointer-events-none absolute top-2.5 start-3 size-4 text-muted-foreground" />
                            <Input
                                id="discount_code"
                                v-model="discountInput"
                                class="ps-9"
                                :placeholder="t('wizard.discountPlaceholder')"
                                dir="ltr"
                                :disabled="!!discountApplied"
                            />
                        </div>
                        <Button
                            v-if="!discountApplied"
                            type="button"
                            variant="outline"
                            :disabled="discountChecking || discountInput.trim().length < 2"
                            @click="checkDiscount"
                        >
                            <Loader2 v-if="discountChecking" class="size-4 animate-spin" />
                            {{ discountChecking ? t('wizard.discountChecking') : t('wizard.discountApply') }}
                        </Button>
                        <Button v-else type="button" variant="outline" size="icon" @click="clearDiscount">
                            <X class="size-4" />
                        </Button>
                    </div>
                    <p v-if="discountApplied" class="text-sm font-medium text-emerald-600 dark:text-emerald-400">
                        {{ t('wizard.discountApplied', { amount: price(discountApplied.discount_amount) }) }}
                    </p>
                    <p v-else-if="discountError" class="text-sm text-destructive">{{ discountError }}</p>
                </div>

                <div class="space-y-2">
                    <Label for="customer_name">{{ t('wizard.fullName') }}</Label>
                    <Input id="customer_name" v-model="form.customer_name" :placeholder="t('wizard.namePlaceholder')" />
                    <p v-if="formErrors.customer_name" class="text-sm text-destructive">{{ formErrors.customer_name }}</p>
                </div>

                <div class="space-y-2">
                    <Label for="customer_phone">{{ t('wizard.phone') }}</Label>
                    <Input id="customer_phone" v-model="form.customer_phone" inputmode="numeric" placeholder="09xxxxxxxxx" dir="ltr" />
                    <p v-if="formErrors.customer_phone" class="text-sm text-destructive">{{ formErrors.customer_phone }}</p>
                </div>

                <div class="space-y-2">
                    <Label for="notes">{{ t('wizard.notes') }}</Label>
                    <Textarea id="notes" v-model="form.notes" rows="3" />
                    <p v-if="formErrors.notes" class="text-sm text-destructive">{{ formErrors.notes }}</p>
                </div>

                <p v-if="formErrors.starts_at" class="text-sm text-destructive">{{ formErrors.starts_at }}</p>
            </div>

            <!-- Navigation -->
            <div class="mt-6 flex items-center justify-between">
                <Button variant="outline" :disabled="step === 0 || submitting" @click="back">{{ t('wizard.prev') }}</Button>

                <Badge v-if="selectedService" variant="secondary">
                    <bdi>{{ selectedService.name }}</bdi>
                    — {{ price(payableAmount) }}
                </Badge>

                <Button :disabled="!canContinue || submitting" @click="next">
                    <Loader2 v-if="submitting" class="me-2 size-4 animate-spin" />
                    {{ step === 3 ? t('wizard.finalSubmit') : t('wizard.next') }}
                </Button>
            </div>
        </CardContent>
    </Card>
</template>
