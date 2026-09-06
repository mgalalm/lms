<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

const form = useForm({
    contact: '',
});

const submit = () => {
    form.post(route('parents.preferences.request.store'));
};
</script>

<template>
    <AuthLayout title="Manage notification preferences" description="Enter the email or phone number on file to get a fresh link.">
        <Head title="Request preferences link" />

        <form @submit.prevent="submit" class="flex flex-col gap-6">
            <div class="grid gap-2">
                <Label for="contact">Email or phone number</Label>
                <Input id="contact" type="text" required autofocus v-model="form.contact" placeholder="parent@example.com" />
                <InputError :message="form.errors.contact" />
            </div>

            <Button type="submit" class="w-full" :disabled="form.processing">
                <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                Send me a link
            </Button>
        </form>
    </AuthLayout>
</template>
