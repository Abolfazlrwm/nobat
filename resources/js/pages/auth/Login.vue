<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useLocale } from '@/composables/useLocale';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle, Zap } from 'lucide-vue-next';
import { computed } from 'vue';

defineProps<{
    status?: string;
    canResetPassword: boolean;
    demo?: boolean;
}>();

const { t } = useLocale();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const demoAccounts = computed(() => [
    { label: t('auth.demoSuperAdmin'), email: 'admin@nobat.ir' },
    { label: t('auth.demoClinicManager'), email: 'manager@nobat.ir' },
    { label: t('auth.demoClinicStaff'), email: 'staff@nobat.ir' },
    { label: t('auth.demoSalonManager'), email: 'salon@nobat.ir' },
]);

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};

const signInAsDemo = (email: string) => {
    form.email = email;
    form.password = 'password';
    form.remember = true;
    form.post(route('login'));
};
</script>

<template>
    <AuthBase :title="t('auth.loginTitle')" :description="t('auth.loginDesc')">
        <Head :title="t('auth.loginPageTitle')" />

        <div v-if="status" class="mb-4 text-center text-sm font-medium text-green-600">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="flex flex-col gap-6">
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="email">{{ t('auth.email') }}</Label>
                    <Input
                        id="email"
                        type="email"
                        required
                        autofocus
                        tabindex="1"
                        autocomplete="email"
                        v-model="form.email"
                        placeholder="email@example.com"
                    />
                    <InputError :message="form.errors.email" />
                </div>

                <div class="grid gap-2">
                    <div class="flex items-center justify-between">
                        <Label for="password">{{ t('auth.password') }}</Label>
                        <TextLink v-if="canResetPassword" :href="route('password.request')" class="text-sm" tabindex="5">
                            {{ t('auth.forgot') }}
                        </TextLink>
                    </div>
                    <Input
                        id="password"
                        type="password"
                        required
                        tabindex="2"
                        autocomplete="current-password"
                        v-model="form.password"
                        placeholder="Password"
                    />
                    <InputError :message="form.errors.password" />
                </div>

                <div class="flex items-center justify-between" tabindex="3">
                    <Label for="remember" class="flex items-center gap-3">
                        <Checkbox id="remember" v-model:checked="form.remember" tabindex="4" />
                        <span>{{ t('auth.remember') }}</span>
                    </Label>
                </div>

                <Button type="submit" class="mt-4 w-full" tabindex="4" :disabled="form.processing">
                    <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                    {{ t('auth.signIn') }}
                </Button>
            </div>

            <div class="text-center text-sm text-muted-foreground">
                {{ t('auth.noAccount') }}
                <TextLink :href="route('register')" :tabindex="5">{{ t('auth.registerLink') }}</TextLink>
            </div>
        </form>

        <div v-if="demo" class="mt-2 rounded-xl border border-dashed border-border bg-muted/40 p-4">
            <p class="mb-3 flex items-center justify-center gap-1.5 text-sm font-medium">
                <Zap class="h-4 w-4 text-amber-500" />
                {{ t('auth.demoPanel') }}
            </p>
            <div class="grid gap-2">
                <Button
                    v-for="account in demoAccounts"
                    :key="account.email"
                    type="button"
                    variant="outline"
                    class="w-full justify-between"
                    :disabled="form.processing"
                    @click="signInAsDemo(account.email)"
                >
                    <span>{{ account.label }}</span>
                    <span dir="ltr" class="text-xs font-normal text-muted-foreground">{{ account.email }}</span>
                </Button>
            </div>
            <p class="mt-3 text-center text-xs text-muted-foreground">
                {{ t('auth.demoPasswordA') }}
                <code dir="ltr" class="rounded bg-muted px-1 py-0.5 font-mono">password</code>
            </p>
        </div>
    </AuthBase>
</template>
