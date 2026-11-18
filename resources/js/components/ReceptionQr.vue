<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { useLocale } from '@/composables/useLocale';
import { Check, Copy, Download } from 'lucide-vue-next';
import QRCode from 'qrcode';
import { computed, ref, watch } from 'vue';

/**
 * Reception QR — a print-ready QR of the business's public booking page.
 * Print it, pin it to the reception desk, and one scan lands the customer
 * on the booking page. Purely client-side (qrcode → data URI), so the CSP
 * img-src data: rule the booking receipt already relies on covers this too.
 */
const props = defineProps<{
    open: boolean;
    businessName: string;
    slug: string;
}>();

const emit = defineEmits<{ (e: 'update:open', value: boolean): void }>();

const { t } = useLocale();

const qrDataUrl = ref('');
const copied = ref(false);

const publicUrl = computed<string>(() => (props.slug ? route('businesses.show', { business: props.slug }) : ''));

watch(
    () => props.open,
    async (open) => {
        copied.value = false;

        if (!open || !publicUrl.value) {
            return;
        }

        try {
            qrDataUrl.value = await QRCode.toDataURL(publicUrl.value, {
                margin: 2,
                width: 512,
                color: { dark: '#171717', light: '#ffffff' },
            });
        } catch {
            // A missing QR must never break the dialog — same policy as the
            // booking confirmation receipt.
            qrDataUrl.value = '';
        }
    },
);

async function copyLink(): Promise<void> {
    if (!publicUrl.value || !navigator.clipboard) return;

    await navigator.clipboard.writeText(publicUrl.value);
    copied.value = true;
    window.setTimeout(() => (copied.value = false), 2000);
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>{{ t('panel.qr.title') }}</DialogTitle>
                <DialogDescription>{{ t('panel.qr.desc') }}</DialogDescription>
            </DialogHeader>

            <div class="flex flex-col items-center gap-3 py-2">
                <div v-if="qrDataUrl" class="rounded-xl border bg-white p-3">
                    <img :src="qrDataUrl" :alt="t('panel.qr.title')" class="size-56" />
                </div>

                <div class="text-center">
                    <div class="text-sm font-medium">{{ businessName }}</div>
                    <div class="text-xs text-muted-foreground" dir="ltr">{{ publicUrl }}</div>
                </div>
            </div>

            <DialogFooter class="gap-2">
                <Button variant="outline" as-child :disabled="!qrDataUrl">
                    <a :href="qrDataUrl" :download="`nobat-${slug}-qr.png`">
                        <Download class="me-1 size-4" />
                        {{ t('panel.qr.download') }}
                    </a>
                </Button>
                <Button variant="outline" :disabled="!publicUrl" @click="copyLink">
                    <Check v-if="copied" class="me-1 size-4 text-emerald-600" />
                    <Copy v-else class="me-1 size-4" />
                    {{ copied ? t('panel.qr.copied') : t('panel.qr.copy') }}
                </Button>
                <Button variant="default" @click="emit('update:open', false)">{{ t('panel.qr.close') }}</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
