<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { SessionPackage } from '@/types';

defineProps<{
    packages: SessionPackage[];
}>();

function formatPrice(price: number, currency: string): string {
    return price.toLocaleString(undefined, { style: 'currency', currency: currency.toUpperCase(), maximumFractionDigits: 0 });
}
</script>

<template>
    <section id="pricing" class="pb-110px pt-50px">
        <div class="container">
            <div class="mx-auto mb-50px max-w-2xl text-center" data-aos="fade-up">
                <span class="bg-whitegrey3 py-5px text-primaryColor mb-5 inline-block rounded-full px-6 text-sm font-semibold">Pricing</span>
                <h3 class="pb-25px text-blackColor dark:text-blackColor-dark md:text-size-35 md:leading-45px text-3xl font-bold leading-10">
                    Session Packages
                </h3>
                <p class="text-contentColor dark:text-contentColor-dark text-sm md:text-base">
                    Buy a bundle of live tutoring sessions and use them whenever you're ready to book.
                </p>
            </div>

            <div class="gap-30px grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3">
                <div v-for="pkg in packages" :key="pkg.id" data-aos="fade-up">
                    <div
                        class="bg-whiteColor p-35px shadow-dropdown-secodary hover:-translate-y-5px hover:bg-primaryColor hover:text-whiteColor dark:bg-whiteColor-dark dark:hover:bg-primaryColor group flex h-full flex-col rounded-xl transition-all duration-300"
                    >
                        <h4 class="mb-10px font-hind dark:text-whiteColor text-2xl font-semibold">{{ pkg.name }}</h4>
                        <p class="mb-15px text-primaryColor group-hover:text-whiteColor text-4xl font-bold">
                            {{ formatPrice(pkg.price, pkg.currency) }}
                        </p>
                        <p class="text-contentColor group-hover:text-whiteColor mb-15px text-sm transition-all duration-300">
                            {{ pkg.session_count }} sessions
                        </p>
                        <p v-if="pkg.description" class="text-contentColor group-hover:text-whiteColor mb-25px text-sm transition-all duration-300">
                            {{ pkg.description }}
                        </p>
                        <Link
                            :href="route('register')"
                            class="border-secondaryColor bg-secondaryColor px-25px py-10px text-size-15 text-whiteColor hover:bg-whiteColor hover:text-secondaryColor dark:hover:bg-whiteColor-dark dark:hover:text-whiteColor group-hover:border-whiteColor mt-auto inline-block w-fit rounded border"
                        >
                            Get Started <i class="icofont-long-arrow-right"></i>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
