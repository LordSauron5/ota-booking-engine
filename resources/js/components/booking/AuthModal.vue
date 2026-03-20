<script setup lang="ts">
import { Loader2, LogIn, UserPlus } from 'lucide-vue-next';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Tabs,
    TabsContent,
    TabsList,
    TabsTrigger,
} from '@/components/ui/tabs';
import { buildHeaders } from '@/composables/useHttp';
import { useBookingStore } from '@/stores/bookingStore';

const props = defineProps<{
    open: boolean;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    'success': [];
}>();

const store = useBookingStore();

const activeTab    = ref<'login' | 'register'>('login');
const isSubmitting = ref(false);
const errorMessage = ref<string | null>(null);

const loginForm   = ref({ email: '', password: '' });
const loginErrors = ref<Record<string, string>>({});

const registerForm = ref({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});
const registerErrors = ref<Record<string, string>>({});

async function submitLogin() {
    isSubmitting.value = true;
    errorMessage.value = null;
    loginErrors.value  = {};

    try {
        await ensureCsrfCookie();

        const response = await fetch('/login', {
            method: 'POST',
            headers: buildHeaders(),
            body: JSON.stringify(loginForm.value),
        });

        if (response.status === 204 || response.ok) {
            await onAuthSuccess();

            return;
        }

        if (response.status === 422) {
            const data = await response.json().catch(() => null);

            // If the response is a plain array of messages
            if (Array.isArray(data)) {
                errorMessage.value = data.join(' ');

                return;
            }

            // If Laravel-style { errors: { field: [msgs] } } or a plain object map
            const errs = data?.errors ?? data;

            if (Array.isArray(errs)) {
                errorMessage.value = errs.join(' ');

                return;
            }

            if (errs && typeof errs === 'object') {
                const mapped: Record<string, string> = {};

                for (const key in errs) {
                    const val = errs[key];
                    mapped[key] = Array.isArray(val) ? val.join(' ') : String(val ?? '');
                }

                loginErrors.value = mapped;

                errorMessage.value = data?.message ?? Object.values(mapped)[0] ?? null;

                return;
            }

            errorMessage.value = data?.message ?? 'Validation failed.';

            return;
        }

        errorMessage.value = 'Login failed. Please try again.';

    } catch {
        errorMessage.value = 'A network error occurred. Please try again.';
    } finally {
        isSubmitting.value = false;
    }
}

async function submitRegister() {
    isSubmitting.value   = true;
    errorMessage.value   = null;
    registerErrors.value = {};

    try {
        await ensureCsrfCookie();

        const response = await fetch('/register', {
            method: 'POST',
            headers: buildHeaders(),
            body: JSON.stringify(registerForm.value),
        });

        if (response.status === 201 || response.ok) {
            await onAuthSuccess();

            return;
        }

        if (response.status === 422) {
            const data = await response.json().catch(() => null);

            if (Array.isArray(data)) {
                errorMessage.value = data.join(' ');

                return;
            }

            const errs = data?.errors ?? data;

            if (Array.isArray(errs)) {
                errorMessage.value = errs.join(' ');

                return;
            }

            if (errs && typeof errs === 'object') {
                const mapped: Record<string, string> = {};

                for (const key in errs) {
                    const val = errs[key];
                    mapped[key] = Array.isArray(val) ? val.join(' ') : String(val ?? '');
                }

                registerErrors.value = mapped;

                errorMessage.value = data?.message ?? Object.values(mapped)[0] ?? null;

                return;
            }

            errorMessage.value = data?.message ?? 'Validation failed.';

            return;
        }

        errorMessage.value = 'Registration failed. Please try again.';

    } catch {
        errorMessage.value = 'A network error occurred. Please try again.';
    } finally {
        isSubmitting.value = false;
    }
}

async function onAuthSuccess() {
    if (store.bookingId) {
        const claimResponse = await fetch(`/bookings/${store.bookingId}/claim`, {
            method: 'POST',
            headers: buildHeaders(),
        });

        if (!claimResponse.ok && claimResponse.status !== 200) {
            const data = await claimResponse.json();

            errorMessage.value = data.message ?? 'Could not link booking to your account.';

            isSubmitting.value = false;

            return;
        }
    }

    emit('update:open', false);
    emit('success');

    // Reload to refresh auth state — Pinia survives via sessionStorage
    window.location.reload();
}

async function ensureCsrfCookie(): Promise<void> {
    await fetch('/sanctum/csrf-cookie', {
        method: 'GET',
        credentials: 'same-origin',
    });
}

</script>

<template>
    <Dialog
        :open="open"
        @update:open="emit('update:open', $event)"
    >
        <DialogContent
            class="sm:max-w-md"
            aria-describedby="auth-modal-desc"
        >
            <DialogHeader>
                <DialogTitle>Sign in to continue</DialogTitle>
                <DialogDescription id="auth-modal-desc">
                    Your booking details are saved. Signing in won't change anything.
                </DialogDescription>
            </DialogHeader>

            <Tabs v-model="activeTab" class="mt-2">
                <TabsList class="w-full">
                    <TabsTrigger value="login" class="flex-1">
                        <LogIn class="w-3.5 h-3.5 mr-1.5" aria-hidden="true" />
                        Sign in
                    </TabsTrigger>
                    <TabsTrigger value="register" class="flex-1">
                        <UserPlus class="w-3.5 h-3.5 mr-1.5" aria-hidden="true" />
                        Create account
                    </TabsTrigger>
                </TabsList>

                <!-- Login tab -->
                <TabsContent value="login" class="mt-4">
                    <form class="flex flex-col gap-4" @submit.prevent="submitLogin">
                        <div class="flex flex-col gap-1.5">
                            <Label for="login-email">Email</Label>
                            <Input
                                id="login-email"
                                v-model="loginForm.email"
                                type="email"
                                autocomplete="email"
                                required
                                :aria-describedby="loginErrors.email ? 'login-email-err' : undefined"
                            />
                            <p
                                v-if="loginErrors.email"
                                id="login-email-err"
                                role="alert"
                                class="text-xs text-destructive"
                            >
                                {{ loginErrors.email }}
                            </p>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <Label for="login-password">Password</Label>
                            <Input
                                id="login-password"
                                v-model="loginForm.password"
                                type="password"
                                autocomplete="current-password"
                                required
                                :aria-describedby="loginErrors.password ? 'login-pass-err' : undefined"
                            />
                            <p
                                v-if="loginErrors.password"
                                id="login-pass-err"
                                role="alert"
                                class="text-xs text-destructive"
                            >
                                {{ loginErrors.password }}
                            </p>
                        </div>

                        <p
                            v-if="errorMessage"
                            role="alert"
                            class="text-sm text-destructive"
                        >
                            {{ errorMessage }}
                        </p>

                        <Button type="submit" :disabled="isSubmitting" class="w-full">
                            <Loader2
                                v-if="isSubmitting"
                                class="w-4 h-4 mr-2 animate-spin"
                                aria-hidden="true"
                            />
                            {{ isSubmitting ? 'Signing in...' : 'Sign in' }}
                        </Button>
                    </form>
                </TabsContent>

                <!-- Register tab -->
                <TabsContent value="register" class="mt-4">
                    <form class="flex flex-col gap-4" @submit.prevent="submitRegister">
                        <div class="flex flex-col gap-1.5">
                            <Label for="reg-name">Name</Label>
                            <Input
                                id="reg-name"
                                v-model="registerForm.name"
                                type="text"
                                autocomplete="name"
                                required
                                :aria-describedby="registerErrors.name ? 'reg-name-err' : undefined"
                            />
                            <p
                                v-if="registerErrors.name"
                                id="reg-name-err"
                                role="alert"
                                class="text-xs text-destructive"
                            >
                                {{ registerErrors.name }}
                            </p>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <Label for="reg-email">Email</Label>
                            <Input
                                id="reg-email"
                                v-model="registerForm.email"
                                type="email"
                                autocomplete="email"
                                required
                                :aria-describedby="registerErrors.email ? 'reg-email-err' : undefined"
                            />
                            <p
                                v-if="registerErrors.email"
                                id="reg-email-err"
                                role="alert"
                                class="text-xs text-destructive"
                            >
                                {{ registerErrors.email }}
                            </p>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <Label for="reg-password">Password</Label>
                            <Input
                                id="reg-password"
                                v-model="registerForm.password"
                                type="password"
                                autocomplete="new-password"
                                required
                                :aria-describedby="registerErrors.password ? 'reg-pass-err' : undefined"
                            />
                            <p
                                v-if="registerErrors.password"
                                id="reg-pass-err"
                                role="alert"
                                class="text-xs text-destructive"
                            >
                                {{ registerErrors.password }}
                            </p>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <Label for="reg-confirm">Confirm password</Label>
                            <Input
                                id="reg-confirm"
                                v-model="registerForm.password_confirmation"
                                type="password"
                                autocomplete="new-password"
                                required
                            />
                        </div>

                        <p
                            v-if="errorMessage"
                            role="alert"
                            class="text-sm text-destructive"
                        >
                            {{ errorMessage }}
                        </p>

                        <Button type="submit" :disabled="isSubmitting" class="w-full">
                            <Loader2
                                v-if="isSubmitting"
                                class="w-4 h-4 mr-2 animate-spin"
                                aria-hidden="true"
                            />
                            {{ isSubmitting ? 'Creating account...' : 'Create account' }}
                        </Button>
                    </form>
                </TabsContent>
            </Tabs>
        </DialogContent>
    </Dialog>
</template>