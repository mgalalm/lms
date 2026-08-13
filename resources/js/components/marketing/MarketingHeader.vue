<script setup lang="ts">
import { useAppearance } from '@/composables/useAppearance';
import type { SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const page = usePage<SharedData>();
const { appearance, updateAppearance } = useAppearance();

const mobileMenuOpen = ref(false);

function toggleTheme(): void {
    updateAppearance(appearance.value === 'dark' ? 'light' : 'dark');
}

const navLinks = [
    { label: 'Home', href: '/' },
    { label: 'Why Us', href: '#why-us' },
    { label: 'Testimonials', href: '#testimonials' },
];
</script>

<template>
    <header>
        <!-- header top bar -->
        <div class="bg-blackColor2 dark:bg-lightGrey10-dark hidden lg:block">
            <div class="3xl:container2-lg 4xl:container py-5px text-size-12 text-whiteColor xl:py-9px container mx-auto xl:text-sm">
                <div class="flex items-center justify-between">
                    <p>Call Us: +1 800 123 456 789 &middot; Mail Us: hello@genacademy.test</p>
                    <div class="gap-37px flex items-center">
                        <p><i class="icofont-location-pin mr-5px text-size-15 text-primaryColor"></i><span>GenAcademy, Online Tutoring</span></p>
                        <ul class="gap-13px text-size-15 flex">
                            <li>
                                <a class="hover:text-primaryColor" href="#"><i class="icofont-facebook"></i></a>
                            </li>
                            <li>
                                <a class="hover:text-primaryColor" href="#"><i class="icofont-twitter"></i></a>
                            </li>
                            <li>
                                <a class="hover:text-primaryColor" href="#"><i class="icofont-instagram"></i></a>
                            </li>
                            <li>
                                <a class="hover:text-primaryColor" href="#"><i class="icofont-youtube-play"></i></a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- navbar -->
        <div class="sticky-header z-medium dark:bg-whiteColor-dark sticky top-0 bg-white transition-all duration-500">
            <nav>
                <div class="px-15px py-15px 3xl:container2-lg 4xl:container relative mx-auto lg:container lg:py-0">
                    <div class="gap-15px grid grid-cols-2 items-center lg:grid-cols-12">
                        <div class="lg:col-span-2 lg:col-start-1">
                            <Link href="/" class="text-blackColor dark:text-whiteColor block py-2 text-xl font-bold">
                                Gen<span class="text-primaryColor">Academy</span>
                            </Link>
                        </div>

                        <div class="hidden lg:col-span-7 lg:col-start-3 lg:block">
                            <ul class="nav-list flex justify-center">
                                <li v-for="link in navLinks" :key="link.label">
                                    <a
                                        :href="link.href"
                                        class="py-30px leading-lg hover:text-primaryColor dark:text-whiteColor lg:px-10px 2xl:px-15px block px-5 text-sm font-semibold"
                                    >
                                        {{ link.label }}
                                    </a>
                                </li>
                            </ul>
                        </div>

                        <div class="gap-15px flex items-center justify-end lg:col-span-3 lg:col-start-10">
                            <button
                                type="button"
                                class="desktop-flex text-blackColor hover:text-primaryColor dark:text-whiteColor h-9 w-9 items-center justify-center rounded-full"
                                :aria-label="appearance === 'dark' ? 'Switch to light mode' : 'Switch to dark mode'"
                                @click="toggleTheme"
                            >
                                <i :class="appearance === 'dark' ? 'icofont-sun' : 'icofont-moon'" class="text-lg"></i>
                            </button>

                            <Link
                                v-if="page.props.auth.user"
                                :href="route('dashboard')"
                                class="desktop-inline bg-primaryColor px-25px py-10px text-size-15 text-whiteColor hover:bg-darkblack rounded font-semibold"
                            >
                                Dashboard
                            </Link>
                            <template v-else>
                                <Link
                                    :href="route('login')"
                                    class="desktop-inline text-size-15 text-blackColor hover:text-primaryColor dark:text-whiteColor font-semibold"
                                >
                                    Login
                                </Link>
                                <Link
                                    :href="route('register')"
                                    class="desktop-inline bg-primaryColor px-25px py-10px text-size-15 text-whiteColor hover:bg-darkblack rounded font-semibold"
                                >
                                    Get Started
                                </Link>
                            </template>

                            <button
                                type="button"
                                class="text-blackColor dark:text-whiteColor text-2xl lg:hidden"
                                aria-label="Toggle menu"
                                @click="mobileMenuOpen = !mobileMenuOpen"
                            >
                                <i class="icofont-navigation-menu"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- mobile menu -->
                <div
                    v-if="mobileMenuOpen"
                    class="border-borderColor px-15px dark:border-borderColor-dark dark:bg-whiteColor-dark border-t bg-white py-4 lg:hidden"
                >
                    <ul class="flex flex-col gap-3">
                        <li v-for="link in navLinks" :key="link.label">
                            <a :href="link.href" class="dark:text-whiteColor block py-1 text-sm font-semibold" @click="mobileMenuOpen = false">
                                {{ link.label }}
                            </a>
                        </li>
                    </ul>
                    <div class="mt-4 flex items-center gap-3">
                        <Link
                            v-if="page.props.auth.user"
                            :href="route('dashboard')"
                            class="bg-primaryColor px-25px py-10px text-size-15 text-whiteColor rounded font-semibold"
                        >
                            Dashboard
                        </Link>
                        <template v-else>
                            <Link :href="route('login')" class="text-size-15 dark:text-whiteColor font-semibold">Login</Link>
                            <Link
                                :href="route('register')"
                                class="bg-primaryColor px-25px py-10px text-size-15 text-whiteColor rounded font-semibold"
                            >
                                Get Started
                            </Link>
                        </template>
                        <button type="button" class="dark:text-whiteColor ml-auto text-lg" @click="toggleTheme">
                            <i :class="appearance === 'dark' ? 'icofont-sun' : 'icofont-moon'"></i>
                        </button>
                    </div>
                </div>
            </nav>
        </div>
    </header>
</template>

<style scoped>
.desktop-flex,
.desktop-inline {
    display: none;
}

@media (min-width: 1024px) {
    .desktop-flex {
        display: flex;
    }

    .desktop-inline {
        display: inline-block;
    }
}
</style>
