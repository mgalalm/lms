<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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

                <Button type="submit" class="mt-2 w-full" tabindex="8" :disabled="form.processing">
                    <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                    Create account
                </Button>
            </div>

            <div class="text-center text-sm text-muted-foreground">
                Already have an account?
                <TextLink :href="route('login')" class="underline underline-offset-4" tabindex="9">Log in</TextLink>
            </div>
        </form>
    </AuthBase>
</template>
