<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <section class="bg-register bg-cover bg-center bg-no-repeat">
        <div class="overlay bg-blueDark relative z-0 bg-opacity-90 py-20">
            <div>
                <img
                    class="z--1 animate-move-hor absolute left-0 top-0 block lg:left-[8%] 2xl:top-10"
                    src="/theme/edurock/images/register/register__1.png"
                    alt=""
                />
                <img
                    class="z--1 animate-spin-slow absolute left-3/4 top-1/2 block md:left-2/3 lg:left-1/2 2xl:left-[8%]"
                    src="/theme/edurock/images/register/register__2.png"
                    alt=""
                />
                <img
                    class="z--1 animate-move-var absolute right-20 top-20 block md:right-20 md:top-14 lg:right-[90%] lg:top-3/4"
                    src="/theme/edurock/images/register/register__3.png"
                    alt=""
                />
            </div>
            <div class="container">
                <div class="mx-auto max-w-3xl text-center" data-aos="fade-up">
                    <span class="bg-whitegrey3 py-5px text-primaryColor mb-5 inline-block rounded-full px-6 text-sm font-semibold">Get Started</span>
                    <h3 class="pb-25px text-whiteColor 2xl:text-size-42 2xl:leading-2xl text-3xl font-bold leading-[45px] md:text-[35px]">
                        Ready to bring your tutoring center
                        <span
                            class="after:bg-secondaryColor relative z-0 after:absolute after:bottom-2 after:left-0 after:z-[-1] after:h-[7px] after:w-full md:after:bottom-4"
                            >online</span
                        >?
                    </h3>
                    <p class="mb-45px text-size-15 font-medium text-white md:text-lg">
                        Create an account to schedule sessions, track certification, and manage your teachers and students in one place.
                    </p>
                    <Link
                        :href="route('register')"
                        class="border-primaryColor bg-primaryColor px-25px py-10px text-size-15 text-whiteColor hover:bg-whiteColor hover:text-primaryColor dark:hover:bg-whiteColor-dark dark:hover:text-whiteColor inline-block rounded border font-semibold"
                    >
                        Get Started <i class="icofont-long-arrow-right"></i>
                    </Link>
                </div>
            </div>
        </div>
    </section>
</template>
