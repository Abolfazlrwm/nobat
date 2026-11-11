<script setup lang="ts">
import JalaliDatePicker from '@/components/JalaliDatePicker.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { faDigits, faPrice, toDateString } from '@/lib/persian';
import { cn } from '@/lib/utils';
import { BusinessWithServices, ServiceItem, SlotItem } from '@/types';
import { router } from '@inertiajs/vue3';
import { Calendar, Check, Clock, Loader2, UserRound } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const props = defineProps<{
    business: BusinessWithServices;
}>();

const steps = ['خدمت', 'پرسنل', 'زمان', 'اطلاعات'] as const;
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

function next(): void {
    if (!canContinue.value) return;
    if (step.value === 3) {
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
            ...form.value,
        },
        {
            onError: (errors) => {
                formErrors.value = errors;
                submitting.value = false;

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
                <li v-for="(title, index) in steps" :key="title" class="flex flex-1 flex-col items-center gap-1">
                    <div class="flex w-full items-center gap-2">
                        <span
                            :class="[
                                'flex size-7 shrink-0 items-center justify-center rounded-full border text-xs font-medium',
                                index < step && 'border-primary bg-primary text-primary-foreground',
                                index === step && 'border-primary text-primary',
                                index > step && 'text-muted-foreground',
                            ]"
                        >
                            <Check v-if="index < step" class="size-4" />
                            <template v-else>{{ faDigits(index + 1) }}</template>
                        </span>
                        <span v-if="index < steps.length - 1" class="h-0.5 flex-1 rounded bg-border" />
                    </div>
                    <span :class="[index === step ? 'font-medium text-foreground' : 'text-muted-foreground']">
                        {{ title }}
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
                        'rounded-xl border p-4 text-right transition-colors hover:bg-accent',
                        selectedService?.id === service.id && 'border-primary bg-primary/5',
                    ]"
                    @click="selectedService = service"
                >
                    <div class="font-medium">{{ service.name }}</div>
                    <div class="mt-2 flex items-center gap-3 text-sm text-muted-foreground">
                        <span class="inline-flex items-center gap-1">
                            <Clock class="size-4" />
                            {{ faDigits(service.duration_minutes) }} دقیقه
                        </span>
                        <span>{{ faPrice(service.price) }}</span>
                    </div>
                </button>
            </div>

            <!-- Step 2: staff -->
            <div v-if="step === 1" class="grid gap-3 sm:grid-cols-2">
                <button
                    type="button"
                    :class="[
                        'flex items-center gap-3 rounded-xl border p-4 text-right transition-colors hover:bg-accent',
                        selectedStaffId === null && 'border-primary bg-primary/5',
                    ]"
                    @click="selectedStaffId = null"
                >
                    <span class="flex size-10 items-center justify-center rounded-full bg-muted">
                        <UserRound class="size-5 text-muted-foreground" />
                    </span>
                    <span>
                        <span class="block font-medium">فرقی نمی‌کند</span>
                        <span class="block text-sm text-muted-foreground">اولین همکار آزاد به شما اختصاص می‌یابد</span>
                    </span>
                </button>

                <button
                    v-for="member in business.staff"
                    :key="member.id"
                    type="button"
                    :class="[
                        'flex items-center gap-3 rounded-xl border p-4 text-right transition-colors hover:bg-accent',
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
                    <p v-if="!selectedDate" class="mt-2 text-center text-xs text-muted-foreground">ابتدا یک روز را انتخاب کنید</p>
                </div>

                <div class="flex-1">
                    <div
                        v-if="!selectedDate"
                        class="flex h-full min-h-48 items-center justify-center rounded-xl border border-dashed text-sm text-muted-foreground"
                    >
                        <span class="inline-flex items-center gap-2"><Calendar class="size-4" /> برای مشاهده بازه‌ها تاریخ را انتخاب کنید</span>
                    </div>

                    <div v-else-if="loadingSlots" class="flex h-full min-h-48 items-center justify-center text-muted-foreground">
                        <Loader2 class="size-6 animate-spin" />
                    </div>

                    <template v-else>
                        <div
                            v-if="slots.length === 0"
                            class="flex h-full min-h-48 items-center justify-center rounded-xl border border-dashed text-sm text-muted-foreground"
                        >
                            در این روز بازه‌ی خالی وجود ندارد؛ روز دیگری را امتحان کنید.
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
                                {{ faDigits(slot.label) }}
                            </button>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Step 4: customer info -->
            <div v-if="step === 3" class="mx-auto max-w-md space-y-4">
                <div class="rounded-xl bg-muted/60 p-4 text-sm">
                    <div class="flex justify-between py-1">
                        <span class="text-muted-foreground">خدمت</span>
                        <span>{{ selectedService?.name }}</span>
                    </div>
                    <div class="flex justify-between py-1">
                        <span class="text-muted-foreground">پرسنل</span>
                        <span>{{ business.staff.find((s) => s.id === selectedStaffId)?.name ?? 'فرقی نمی‌کند' }}</span>
                    </div>
                    <div class="flex justify-between py-1">
                        <span class="text-muted-foreground">ساعت</span>
                        <span class="font-medium" dir="ltr">{{ selectedSlot?.starts_at }}</span>
                    </div>
                </div>

                <div class="space-y-2">
                    <Label for="customer_name">نام و نام خانوادگی</Label>
                    <Input id="customer_name" v-model="form.customer_name" placeholder="مثلاً علی رضایی" />
                    <p v-if="formErrors.customer_name" class="text-sm text-destructive">{{ formErrors.customer_name }}</p>
                </div>

                <div class="space-y-2">
                    <Label for="customer_phone">شماره موبایل</Label>
                    <Input id="customer_phone" v-model="form.customer_phone" inputmode="numeric" placeholder="09xxxxxxxxx" dir="ltr" />
                    <p v-if="formErrors.customer_phone" class="text-sm text-destructive">{{ formErrors.customer_phone }}</p>
                </div>

                <div class="space-y-2">
                    <Label for="notes">توضیحات (اختیاری)</Label>
                    <Textarea id="notes" v-model="form.notes" rows="3" />
                    <p v-if="formErrors.notes" class="text-sm text-destructive">{{ formErrors.notes }}</p>
                </div>

                <p v-if="formErrors.starts_at" class="text-sm text-destructive">{{ formErrors.starts_at }}</p>
            </div>

            <!-- Navigation -->
            <div class="mt-6 flex items-center justify-between">
                <Button variant="outline" :disabled="step === 0 || submitting" @click="back">قبلی</Button>

                <Badge v-if="selectedService" variant="secondary"> {{ selectedService.name }} — {{ faPrice(selectedService.price) }} </Badge>

                <Button :disabled="!canContinue || submitting" @click="next">
                    <Loader2 v-if="submitting" class="ml-2 size-4 animate-spin" />
                    {{ step === 3 ? 'ثبت نهایی نوبت' : 'بعدی' }}
                </Button>
            </div>
        </CardContent>
    </Card>
</template>
