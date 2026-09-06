<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import Toast from '@/components/Toast.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { CheckCircle2, LoaderCircle, XCircle } from 'lucide-vue-next';

interface NotificationTypeOption {
    value: string;
    label: string;
    channel: string;
}

interface ChannelOption {
    value: string;
    label: string;
}

interface Props {
    parentContact: {
        id: number;
        name: string | null;
        email: string | null;
        phone: string | null;
        email_verified: boolean;
        phone_verified: boolean;
        student_name: string;
    };
    notificationTypes: NotificationTypeOption[];
    channelOptions: ChannelOption[];
}

const props = defineProps<Props>();

// Query string carries the signature/expires params that make every
// request below a validly-signed one — always post back to the exact
// current URL rather than a route()-built path that would drop them.
const currentUrl = () => window.location.pathname + window.location.search;

const contactForm = useForm({
    email: props.parentContact.email ?? '',
    phone: props.parentContact.phone ?? '',
});

const submitContact = () => {
    contactForm.post(currentUrl());
};

const preferencesForm = useForm({
    preferences: Object.fromEntries(props.notificationTypes.map((t) => [t.value, t.channel])) as Record<string, string>,
});

const submitPreferences = () => {
    preferencesForm.post(currentUrl());
};

const emailCodeForm = useForm({ channel: 'email', code: '' });
const phoneCodeForm = useForm({ channel: 'phone', code: '' });

function verifyUrl() {
    return currentUrl().replace(/(\?|$)/, '/verify-code$1');
}

function resendUrl() {
    return currentUrl().replace(/(\?|$)/, '/resend-code$1');
}

const resendEmail = () => useForm({ channel: 'email' }).post(resendUrl());
const resendPhone = () => useForm({ channel: 'phone' }).post(resendUrl());
</script>

<template>
    <AuthLayout title="Notification preferences" :description="`For ${parentContact.student_name}`">
        <Head title="Notification preferences" />
        <Toast />

        <div class="flex flex-col gap-8">
            <section class="flex flex-col gap-4 rounded-lg border border-input p-4">
                <h2 class="text-sm font-medium">Contact details</h2>

                <form @submit.prevent="submitContact" class="flex flex-col gap-4">
                    <div class="grid gap-2">
                        <Label for="email" class="flex items-center gap-2">
                            Email
                            <CheckCircle2 v-if="parentContact.email_verified" class="size-3.5 text-green-600" />
                            <XCircle v-else class="size-3.5 text-muted-foreground" />
                        </Label>
                        <Input id="email" type="email" v-model="contactForm.email" placeholder="parent@example.com" />
                        <InputError :message="contactForm.errors.email" />
                        <button
                            v-if="parentContact.email && !parentContact.email_verified"
                            type="button"
                            class="w-fit text-xs text-muted-foreground underline"
                            @click="resendEmail"
                        >
                            Resend verification code
                        </button>
                    </div>

                    <form v-if="parentContact.email && !parentContact.email_verified" @submit.prevent="emailCodeForm.post(verifyUrl())" class="flex items-end gap-2">
                        <div class="grid gap-2">
                            <Label for="email_code">Verification code</Label>
                            <Input id="email_code" v-model="emailCodeForm.code" maxlength="6" placeholder="123456" />
                            <InputError :message="emailCodeForm.errors.code" />
                        </div>
                        <Button type="submit" size="sm" :disabled="emailCodeForm.processing">Verify</Button>
                    </form>

                    <div class="grid gap-2">
                        <Label for="phone" class="flex items-center gap-2">
                            Phone
                            <CheckCircle2 v-if="parentContact.phone_verified" class="size-3.5 text-green-600" />
                            <XCircle v-else class="size-3.5 text-muted-foreground" />
                        </Label>
                        <Input id="phone" type="tel" v-model="contactForm.phone" placeholder="+353871234567" />
                        <InputError :message="contactForm.errors.phone" />
                        <button
                            v-if="parentContact.phone && !parentContact.phone_verified"
                            type="button"
                            class="w-fit text-xs text-muted-foreground underline"
                            @click="resendPhone"
                        >
                            Resend verification code
                        </button>
                    </div>

                    <form v-if="parentContact.phone && !parentContact.phone_verified" @submit.prevent="phoneCodeForm.post(verifyUrl())" class="flex items-end gap-2">
                        <div class="grid gap-2">
                            <Label for="phone_code">Verification code</Label>
                            <Input id="phone_code" v-model="phoneCodeForm.code" maxlength="6" placeholder="123456" />
                            <InputError :message="phoneCodeForm.errors.code" />
                        </div>
                        <Button type="submit" size="sm" :disabled="phoneCodeForm.processing">Verify</Button>
                    </form>

                    <Button type="submit" :disabled="contactForm.processing">
                        <LoaderCircle v-if="contactForm.processing" class="h-4 w-4 animate-spin" />
                        Save contact details
                    </Button>
                    <p class="text-xs text-muted-foreground">Changing an email or phone number makes it unverified again until you re-enter a code.</p>
                </form>
            </section>

            <section class="flex flex-col gap-4 rounded-lg border border-input p-4">
                <h2 class="text-sm font-medium">Notification channels</h2>

                <form @submit.prevent="submitPreferences" class="flex flex-col gap-4">
                    <div v-for="type in notificationTypes" :key="type.value" class="grid gap-1">
                        <Label :for="`pref_${type.value}`">{{ type.label }}</Label>
                        <select
                            :id="`pref_${type.value}`"
                            v-model="preferencesForm.preferences[type.value]"
                            class="h-9 rounded-md border border-input bg-background px-3 text-sm"
                        >
                            <option v-for="channel in channelOptions" :key="channel.value" :value="channel.value">
                                {{ channel.label }}
                            </option>
                        </select>
                    </div>

                    <Button type="submit" :disabled="preferencesForm.processing">
                        <LoaderCircle v-if="preferencesForm.processing" class="h-4 w-4 animate-spin" />
                        Save preferences
                    </Button>
                </form>
            </section>
        </div>
    </AuthLayout>
</template>
