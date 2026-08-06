<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Award, LineChart, Trophy, Video } from 'lucide-vue-next';

const features = [
    {
        icon: Video,
        title: 'Live Zoom Sessions',
        description: 'Every session runs on Zoom, one click away — no separate app to install for teachers or students.',
    },
    {
        icon: Award,
        title: 'Certified Teachers',
        description: "Teachers pass the same course quiz as students before they're allowed to teach it — a real certification gate.",
    },
    {
        icon: Trophy,
        title: 'Gamified Learning',
        description: 'Points, badges, and leaderboards keep students motivated between sessions.',
    },
    {
        icon: LineChart,
        title: 'Progress Insights',
        description: 'Attendance, performance scores, and flags — a real scorecard for every teacher and student.',
    },
];
</script>

<template>
    <section id="why-us" class="bg-lightGrey10 pb-110px pt-50px dark:bg-lightGrey10-dark">
        <div class="container">
            <div class="gap-x-30px pt-30px grid grid-cols-1 lg:grid-cols-2">
                <div class="mb-30px md:pb-30px pb-0 lg:mb-0 xl:pb-0" data-aos="fade-up">
                    <span class="bg-whitegrey3 py-5px text-primaryColor mb-5 inline-block rounded-full px-6 text-sm font-semibold">Why Edurock</span>
                    <h3
                        class="pb-25px text-blackColor dark:text-blackColor-dark md:text-size-35 md:leading-45px 2xl:text-size-38 2xl:leading-50px 3xl:text-size-42 3xl:leading-2xl text-3xl font-bold leading-10"
                    >
                        Everything a Tutoring Center Needs
                    </h3>
                    <p class="mb-10px text-contentColor dark:text-contentColor-dark 2xl:mb-50px text-sm md:text-base">
                        From scheduling and Zoom delivery to payroll and HR requests — one platform for teachers, students, and admins.
                    </p>
                    <div>
                        <Link
                            :href="route('register')"
                            class="border-secondaryColor bg-secondaryColor px-25px py-10px text-size-15 text-whiteColor hover:bg-whiteColor hover:text-secondaryColor dark:hover:bg-whiteColor-dark dark:hover:text-whiteColor inline-block rounded border"
                        >
                            Get Started <i class="icofont-long-arrow-right"></i>
                        </Link>
                    </div>
                </div>

                <div class="gap-30px grid grid-cols-1 md:grid-cols-2">
                    <div v-for="feature in features" :key="feature.title" data-aos="fade-up">
                        <div
                            class="bg-whiteColor p-35px shadow-dropdown-secodary hover:-translate-y-5px hover:bg-primaryColor hover:text-whiteColor dark:bg-whiteColor-dark dark:hover:bg-primaryColor group rounded-xl transition-all duration-300"
                        >
                            <component :is="feature.icon" class="mb-15px text-primaryColor group-hover:text-whiteColor size-9" />
                            <h4 class="mb-15px font-hind dark:text-whiteColor text-2xl font-semibold lg:text-lg 2xl:text-2xl">
                                {{ feature.title }}
                            </h4>
                            <p class="text-contentColor group-hover:text-whiteColor text-sm transition-all duration-300 lg:text-xs 2xl:text-sm">
                                {{ feature.description }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
