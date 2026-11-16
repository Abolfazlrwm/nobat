<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useLocale } from '@/composables/useLocale';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps<{
    status?: string;
    phone?: string | null;
}>();

const { t } = useLocale();

const step = ref<'phone' | 'code'>(props.phone ? 'code' : 'phone');

const phoneForm = useForm({
    phone: props.phone ?? '',
});

const codeForm = useForm({
    phone: props.phone ?? '',
    code: '',
});

const requestCode = () => {
    phoneForm.post(route('otp.request'), {
        onSuccess: () => {
            codeForm.phone = phoneForm.phone;
            step.value = 'code';
        },
    });
};

const verifyCode = () => {
    codeForm.post(route('otp.verify'));
};

const backToPhone = () => {
    step.value = 'phone';
    codeForm.reset();
};
</script>

<template>
    <AuthBase :title="t('auth.otpLoginTitle')" :description="t('auth.otpLoginDesc')">
        <Head :title="t('auth.otpLoginPageTitle')" />

        <div v-if="status" class="mb-4 text-center text-sm font-medium text-green-600">
            {{ status }}
        </div>

        <form v-if="step === 'phone'" @submit.prevent="requestCode" class="flex flex-col gap-6">
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="phone">{{ t('auth.otpPhoneLabel') }}</Label>
                    <Input
                        id="phone"
                        type="tel"
                        inputmode="numeric"
                        dir="ltr"
                        required
                        autofocus
                        tabindex="1"
                        autocomplete="tel"
                        v-model="phoneForm.phone"
                        :placeholder="t('auth.otpPhonePlaceholder')"
                    />
                    <InputError :message="phoneForm.errors.phone" />
                </div>

                <Button type="submit" class="mt-4 w-full" tabindex="2" :disabled="phoneForm.processing">
                    <LoaderCircle v-if="phoneForm.processing" class="h-4 w-4 animate-spin" />
                    {{ t('auth.otpSendCode') }}
                </Button>
            </div>

            <div class="text-center text-sm text-muted-foreground">
                {{ t('auth.otpHavePassword') }}
                <TextLink :href="route('login')" :tabindex="3">{{ t('auth.otpUsePassword') }}</TextLink>
            </div>
        </form>

        <form v-else @submit.prevent="verifyCode" class="flex flex-col gap-6">
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="code">{{ t('auth.otpCodeLabel') }}</Label>
                    <Input
                        id="code"
                        type="text"
                        inputmode="numeric"
                        dir="ltr"
                        required
                        maxlength="6"
                        autofocus
                        tabindex="1"
                        autocomplete="one-time-code"
                        v-model="codeForm.code"
                        :placeholder="t('auth.otpCodePlaceholder')"
                    />
                    <InputError :message="codeForm.errors.code" />
                </div>

                <p class="text-center text-xs text-muted-foreground" dir="ltr">
                    {{ codeForm.phone }}
                    <InputError :message="phoneForm.errors.phone" />
                </p>

                <Button type="submit" class="mt-4 w-full" tabindex="2" :disabled="codeForm.processing">
                    <LoaderCircle v-if="codeForm.processing" class="h-4 w-4 animate-spin" />
                    {{ t('auth.otpVerify') }}
                </Button>

                <div class="flex items-center justify-between text-sm" tabindex="3">
                    <button
                        type="button"
                        class="text-muted-foreground underline underline-offset-4"
                        @click="backToPhone"
                    >
                        {{ t('auth.otpChangePhone') }}
                    </button>
                    <button
                        type="button"
                        class="text-muted-foreground underline underline-offset-4 disabled:opacity-50"
                        :disabled="phoneForm.processing"
                        @click="requestCode"
                    >
                        {{ t('auth.otpResend') }}
                    </button>
                </div>
            </div>
        </form>
    </AuthBase>
</template>
