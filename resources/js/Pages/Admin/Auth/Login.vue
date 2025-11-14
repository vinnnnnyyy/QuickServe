<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/Shared/Base/InputError.vue';
import InputLabel from '@/Components/Shared/Base/InputLabel.vue';
import PrimaryButton from '@/Components/Shared/Base/PrimaryButton.vue';
import TextInput from '@/Components/Shared/Base/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('admin.login.store'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Admin Login" />
    
    <div class="min-h-screen flex items-center justify-center bg-background-light dark:bg-background-dark">
        <div class="max-w-md w-full space-y-8 p-8">
            <!-- Logo/Brand -->
            <div class="text-center">
                <h1 class="text-3xl font-bold gradient-text mb-2">Café Delight</h1>
                <h2 class="text-xl text-gray-600 dark:text-gray-400">Admin Panel</h2>
            </div>

            <!-- Status Message -->
            <div v-if="status" class="mb-4 text-sm font-medium text-green-600 bg-green-50 dark:bg-green-900/20 p-3 rounded-lg">
                {{ status }}
            </div>

            <!-- Login Form -->
            <div class="glass rounded-2xl p-8 shadow-xl">
                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <InputLabel for="email" value="Email Address" class="text-gray-700 dark:text-gray-300" />
                        <TextInput
                            id="email"
                            type="email"
                            class="mt-2 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-orange-500 focus:ring-orange-500"
                            v-model="form.email"
                            required
                            autofocus
                            autocomplete="username"
                            placeholder="admin@cafedelight.com"
                        />
                        <InputError class="mt-2" :message="form.errors.email" />
                    </div>

                    <div>
                        <InputLabel for="password" value="Password" class="text-gray-700 dark:text-gray-300" />
                        <TextInput
                            id="password"
                            type="password"
                            class="mt-2 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-orange-500 focus:ring-orange-500"
                            v-model="form.password"
                            required
                            autocomplete="current-password"
                            placeholder="••••••••"
                        />
                        <InputError class="mt-2" :message="form.errors.password" />
                    </div>

                    <div class="flex items-center justify-between">
                        <label class="flex items-center">
                            <Checkbox name="remember" v-model:checked="form.remember" />
                            <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Remember me</span>
                        </label>

                        <Link
                            v-if="canResetPassword"
                            :href="route('password.request')"
                            class="text-sm text-orange-600 hover:text-orange-500 dark:text-orange-400 dark:hover:text-orange-300 transition-colors"
                        >
                            Forgot password?
                        </Link>
                    </div>

                    <button
                        type="submit"
                        class="w-full flex items-center justify-center py-3 text-base font-medium rounded-lg focus-ring bg-orange-500 hover:bg-orange-600 focus:bg-orange-600 active:bg-orange-700 text-white transition duration-150 ease-in-out focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
                        :disabled="form.processing"
                    >
                        <svg v-if="form.processing" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        {{ form.processing ? 'Signing in...' : 'Sign in to Admin Panel' }}
                    </button>
                </form>
            </div>

            <!-- Footer -->
            <div class="text-center text-sm text-gray-500 dark:text-gray-400">
                <p>© 2024 Café Delight. All rights reserved.</p>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Admin login specific styles */
.gradient-text {
    background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.glass {
    background: linear-gradient(135deg, rgba(255,255,255,0.9) 0%, rgba(255,255,255,0.8) 100%);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.dark .glass {
    background: rgba(31, 41, 55, 0.9);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.focus-ring:focus-visible {
    outline: none;
    box-shadow: 0 0 0 2px #fff, 0 0 0 4px #f97316;
}

/* iOS text size fix */
@media screen and (-webkit-min-device-pixel-ratio: 0) {
    input, select, textarea {
        font-size: 16px;
    }
}
</style>
