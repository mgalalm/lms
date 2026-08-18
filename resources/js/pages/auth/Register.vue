<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTurnstile } from '@/composables/useTurnstile';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
import { ref, watch } from 'vue';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    parent_email: '',
    consent: false,
    referral_code: '',
    turnstile_token: '',
});

// Live feedback as the code is typed — purely a UX nicety, the server
// re-validates independently (and silently ignores an invalid code)
// when the form is actually submitted.
const referralCodeStatus = ref<'idle' | 'checking' | 'valid' | 'invalid'>('idle');
let referralCodeCheckTimeout: ReturnType<typeof setTimeout> | null = null;

function getCookie(name: string): string {
    return decodeURIComponent(document.cookie.match(`(?:^|; )${name}=([^;]*)`)?.[1] ?? '');
}

watch(
    () => form.referral_code,
    (code) => {
        if (referralCodeCheckTimeout) {
            clearTimeout(referralCodeCheckTimeout);
        }

        if (!code) {
            referralCodeStatus.value = 'idle';

            return;
        }

        referralCodeStatus.value = 'checking';

        referralCodeCheckTimeout = setTimeout(async () => {
            const response = await fetch(route('referral-codes.validate'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-XSRF-TOKEN': getCookie('XSRF-TOKEN'),
                },
                body: JSON.stringify({ code }),
            });

            const data = await response.json();
            referralCodeStatus.value = data.valid ? 'valid' : 'invalid';
        }, 500);
    },
);

const { container: turnstileContainer, token: turnstileToken } = useTurnstile(import.meta.env.VITE_TURNSTILE_SITE_KEY);

watch(turnstileToken, (token) => {
    form.turnstile_token = token;
});

// Covers the common case (an adult registering on behalf of a younger
// child, using their own email for both login and parent contact)
// without making them type the same address twice.
const parentEmailSameAsAccount = ref(true);

watch(
    [() => form.email, parentEmailSameAsAccount],
    ([email, same]) => {
        if (same) {
            form.parent_email = email;
        }
    },
    { immediate: true },
);

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <AuthBase title="Create an account" description="Enter your details below to create your account">
        <Head title="Register" />

        <form @submit.prevent="submit" class="flex flex-col gap-6">
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="name">Name</Label>
                    <Input id="name" type="text" required autofocus tabindex="1" autocomplete="name" v-model="form.name" placeholder="Full name" />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="email">Email address</Label>
                    <Input id="email" type="email" required tabindex="2" autocomplete="email" v-model="form.email" placeholder="email@example.com" />
                    <InputError :message="form.errors.email" />
                </div>

                <div class="grid gap-2">
                    <Label for="password">Password</Label>
                    <Input
                        id="password"
                        type="password"
                        required
                        tabindex="3"
                        autocomplete="new-password"
                        v-model="form.password"
                        placeholder="Password"
                    />
                    <InputError :message="form.errors.password" />
                </div>

                <div class="grid gap-2">
                    <Label for="password_confirmation">Confirm password</Label>
                    <Input
                        id="password_confirmation"
                        type="password"
                        required
                        tabindex="4"
                        autocomplete="new-password"
                        v-model="form.password_confirmation"
                        placeholder="Confirm password"
                    />
                    <InputError :message="form.errors.password_confirmation" />
                </div>

                <div class="grid gap-2 rounded-lg border border-input p-4">
                    <Label for="parent_email">Parent/Guardian Email</Label>
                    <p class="text-xs text-muted-foreground">We'll use this to reach you about your child's upcoming sessions.</p>

                    <Input
                        id="parent_email"
                        type="email"
                        required
                        tabindex="5"
                        autocomplete="email"
                        v-model="form.parent_email"
                        :disabled="parentEmailSameAsAccount"
                        placeholder="parent@example.com"
                    />
                    <InputError :message="form.errors.parent_email" />

                    <Label for="parent_email_same" class="mt-1 flex items-center gap-2 text-xs font-normal text-muted-foreground">
                        <Checkbox id="parent_email_same" v-model:checked="parentEmailSameAsAccount" tabindex="6" />
                        <span>Same as the email above</span>
                    </Label>

                    <Label for="consent" class="mt-2 flex items-start gap-3 border-t border-input pt-3 font-normal">
                        <Checkbox id="consent" v-model:checked="form.consent" tabindex="7" class="mt-0.5" />
                        <span>I am the parent/guardian and consent to my child's data being processed.</span>
                    </Label>
                    <InputError :message="form.errors.consent" />
                </div>

                <div class="grid gap-2">
                    <Label for="referral_code">Referral Code (optional)</Label>
                    <Input
                        id="referral_code"
                        type="text"
                        tabindex="8"
                        autocomplete="off"
                        v-model="form.referral_code"
                        placeholder="e.g. ABC12345"
                    />
                    <p v-if="referralCodeStatus === 'valid'" class="text-xs text-green-600 dark:text-green-400">
                        Valid code — you'll both get bonus sessions!
                    </p>
                    <p v-else-if="referralCodeStatus === 'invalid'" class="text-xs text-destructive">Invalid or expired code.</p>
                    <InputError :message="form.errors.referral_code" />
                </div>

                <div ref="turnstileContainer"></div>
                <InputError :message="form.errors.turnstile_token" />

                <Button type="submit" class="mt-2 w-full" tabindex="9" :disabled="form.processing">
                    <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                    Create account
                </Button>
            </div>

            <div class="text-center text-sm text-muted-foreground">
                Already have an account?
                <TextLink :href="route('login')" class="underline underline-offset-4" tabindex="10">Log in</TextLink>
            </div>
        </form>
    </AuthBase>
</template>
