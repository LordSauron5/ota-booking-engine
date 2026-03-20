<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { LogIn, LogOut, User } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import AuthModal from '@/components/booking/AuthModal.vue';
import { Button } from '@/components/ui/button';
import { useHttp } from '@/composables/useHttp';

const props = defineProps<{
    propertyName: string;
}>();

const page            = usePage();
const showAuthModal   = ref(false);
const isLoggingOut    = ref(false);

const { getCsrfToken } = useHttp();

const isAuthenticated = computed(() => !!page.props.auth?.user);
const userEmail       = computed(() => (page.props.auth as any)?.user?.email ?? '');

async function logout() {
    isLoggingOut.value = true;

    try {
        await fetch('/logout', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        // Reload so Inertia refreshes auth state
        // Pinia store survives in sessionStorage
        window.location.reload();

    } catch {
        isLoggingOut.value = false;
    }
}

</script>

<template>
    <header class="border-b border-border bg-background/80 backdrop-blur-sm sticky top-0 z-40">
        <div class="max-w-3xl mx-auto px-4 h-14 flex items-center justify-between">

            <!-- Property name -->
            <span class="font-semibold text-foreground text-sm">
                {{ propertyName }}
            </span>

            <!-- Auth controls -->
            <div class="flex items-center gap-2">
                <template v-if="isAuthenticated">
                    <!-- User email -->
                    <div class="hidden sm:flex items-center gap-1.5 text-sm text-muted-foreground">
                        <User class="w-3.5 h-3.5" aria-hidden="true" />
                        <span>{{ userEmail }}</span>
                    </div>

                    <!-- Logout -->
                    <Button
                        variant="ghost"
                        size="sm"
                        :disabled="isLoggingOut"
                        aria-label="Sign out"
                        @click="logout"
                    >
                        <LogOut class="w-3.5 h-3.5 mr-1.5" aria-hidden="true" />
                        {{ isLoggingOut ? 'Signing out...' : 'Sign out' }}
                    </Button>
                </template>

                <template v-else>
                    <Button
                        variant="ghost"
                        size="sm"
                        aria-label="Sign in or create account"
                        @click="showAuthModal = true"
                    >
                        <LogIn class="w-3.5 h-3.5 mr-1.5" aria-hidden="true" />
                        Sign in
                    </Button>
                </template>
            </div>
        </div>
    </header>

    <!-- Auth modal — available from any step -->
    <AuthModal
        v-model:open="showAuthModal"
    />
</template>