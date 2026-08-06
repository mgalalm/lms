<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <section>
        <div class="container2-xl bg-darkdeep1 pb-205px pt-50px shadow-brand md:pb-35 relative overflow-hidden rounded-2xl md:pt-20">
            <div class="container grid grid-cols-1 items-center lg:grid-cols-2">
                <div data-aos="fade-up">
                    <h3 class="mb-5px font-inter text-size-15 tracking-5px text-secondaryColor md:mb-15px uppercase">Education Solution</h3>
                    <h1
                        class="mb-15px text-whiteColor md:leading-18 md:tracking-half lg:text-size-50 lg:leading-62px 2xl:leading-18 2xl:tracking-half text-3xl font-bold leading-10 md:text-6xl lg:tracking-normal 2xl:text-6xl"
                    >
                        Live Tutoring, <br class="hidden md:block" />
                        Trusted by 1000+ Families
                    </h1>
                    <p class="mb-45px text-size-15 font-medium text-white md:text-lg">
                        Certified teachers, live Zoom sessions, and real progress tracking — everything a modern tutoring center needs, in one place.
                    </p>

                    <div>
                        <Link
                            :href="route('register')"
                            class="mr-6px border-whiteColor bg-whiteColor text-darkdeep2 shadow-hero-action hover:bg-darkblack hover:text-whiteColor dark:bg-whiteColor-dark dark:text-whiteColor dark:hover:bg-whiteColor dark:hover:text-whiteColor-dark md:mr-30px md:px-30px md:text-size-15 inline-block rounded border px-5 py-3 text-sm font-semibold dark:border-none md:py-4"
                        >
                            Get Started
                        </Link>
                        <a
                            href="#why-us"
                            class="text-whiteColor hover:text-secondaryColor md:text-size-15 inline-block py-3 text-sm font-semibold md:py-4"
                        >
                            Find out more <i class="icofont-long-arrow-right"></i>
                        </a>
                    </div>
                </div>

                <div data-aos="fade-up">
                    <div class="relative">
                        <img class="w-full" src="/theme/edurock/images/about/about_8.png" alt="" />
                        <img class="absolute left-0 right-0 top-0 mx-auto lg:top-4" src="/theme/edurock/images/about/about_1.png" alt="" />
                    </div>
                </div>
            </div>

            <div>
                <img class="animate-spin-slow absolute bottom-[15%] left-1/2" src="/theme/edurock/images/register/register__2.png" alt="" />
                <img
                    class="animate-move-hor absolute left-[42%] top-[4%] sm:left-[65%] sm:top-[1%] md:left-[42%] md:top-[4%] lg:left-[5%] lg:top-[10%]"
                    src="/theme/edurock/images/herobanner/herobanner__6.png"
                    alt=""
                />
                <img class="absolute bottom-[15%] right-[5%]" src="/theme/edurock/images/herobanner/herobanner__7.png" alt="" />
            </div>
        </div>

        <!-- brand strip -->
        <div data-aos="fade-up">
            <div
                class="container2-md shadow-brand dark:bg-whiteColor-dark mx-auto flex w-full flex-wrap items-center justify-center rounded-md bg-white md:-translate-y-1/2"
            >
                <div v-for="n in 5" :key="n" class="lg:py-35px 2xl:py-45px flex basis-1/2 justify-center py-5 md:basis-1/4 lg:basis-1/5">
                    <img :src="`/theme/edurock/images/brand/brand_${n}.png`" alt="" />
                </div>
            </div>
        </div>
    </section>
</template>
