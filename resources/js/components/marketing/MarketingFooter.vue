<script setup lang="ts">
import wordmarkDark from '@/assets/branding/gen-academy-primary-dark.svg';
import { Link } from '@inertiajs/vue3';

const socialLinks = [
    { icon: 'facebook', href: 'https://www.facebook.com/GenAcademyEU' },
    { icon: 'twitter', href: '#' },
    { icon: 'linkedin', href: '#' },
    { icon: 'instagram', href: '#' },
];
</script>

<template>
    <footer class="bg-darkblack">
        <div class="pt-65px container pb-5 lg:pb-10">
            <section>
                <div class="gap-30px pb-50px pt-60px md:pb-30px md:pt-30px lg:pt-110px grid grid-cols-12 md:gap-y-5 lg:gap-y-0 lg:pb-20">
                    <div class="mr-30px col-span-12 col-start-1 md:col-span-6 lg:col-span-5" data-aos="fade-up">
                        <img :src="wordmarkDark" alt="Gen Academy" class="mb-3 h-8 w-auto max-w-none" />
                        <p class="mb-30px leading-1.8 text-darkgray 2xl:leading-1.8 text-base lg:text-sm 2xl:text-base">
                            A live-tutoring platform for teachers, students, and admins — Zoom sessions, certification, payroll, and HR requests in
                            one place.
                        </p>
                    </div>

                    <div class="col-span-12 col-start-1 md:col-span-6 md:col-start-7 lg:col-span-3 lg:col-start-6" data-aos="fade-up">
                        <h4 class="text-size-22 text-whiteColor mb-3 font-bold">Quick Links</h4>
                        <ul class="flex flex-col gap-y-3">
                            <li>
                                <a
                                    href="#why-us"
                                    class="after:h-2px after:bg-primaryColor text-darkgray hover:text-primaryColor relative after:absolute after:bottom-0 after:left-0 after:w-0 after:transition-all after:duration-300 hover:after:w-full"
                                    >Why Us</a
                                >
                            </li>
                            <li>
                                <a
                                    href="#testimonials"
                                    class="after:h-2px after:bg-primaryColor text-darkgray hover:text-primaryColor relative after:absolute after:bottom-0 after:left-0 after:w-0 after:transition-all after:duration-300 hover:after:w-full"
                                    >Testimonials</a
                                >
                            </li>
                        </ul>
                    </div>

                    <div class="2xl:pl-50px col-span-12 col-start-1 pl-0 md:col-span-6 lg:col-span-4 lg:col-start-9" data-aos="fade-up">
                        <h4 class="text-size-22 text-whiteColor mb-3 font-bold">Account</h4>
                        <ul class="flex flex-col gap-y-3">
                            <li>
                                <Link
                                    :href="route('login')"
                                    class="after:h-2px after:bg-primaryColor text-darkgray hover:text-primaryColor relative after:absolute after:bottom-0 after:left-0 after:w-0 after:transition-all after:duration-300 hover:after:w-full"
                                    >Login</Link
                                >
                            </li>
                            <li>
                                <Link
                                    :href="route('register')"
                                    class="after:h-2px after:bg-primaryColor text-darkgray hover:text-primaryColor relative after:absolute after:bottom-0 after:left-0 after:w-0 after:transition-all after:duration-300 hover:after:w-full"
                                    >Get Started</Link
                                >
                            </li>
                        </ul>
                    </div>
                </div>
            </section>

            <div>
                <div class="lg:gap-30px grid grid-cols-1 items-center gap-5 pt-10 lg:grid-cols-12">
                    <div class="lg:col-span-3 lg:col-start-1">
                        <Link href="/"><img :src="wordmarkDark" alt="Gen Academy" class="h-6 w-auto max-w-none" /></Link>
                    </div>

                    <div class="lg:col-span-6 lg:col-start-4">
                        <p class="text-whiteColor">
                            Copyright &copy; <span class="text-primaryColor">{{ new Date().getFullYear() }}</span> GenAcademy. All Rights Reserved.
                        </p>
                    </div>

                    <div class="lg:col-span-3 lg:col-start-10">
                        <ul class="flex gap-3 lg:justify-end lg:gap-2 2xl:gap-3">
                            <li v-for="social in socialLinks" :key="social.icon">
                                <a
                                    :href="social.href"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="h-37px w-40.19px bg-whiteColor leading-37px text-whiteColor hover:bg-primaryColor lg:h-35px lg:w-35px lg:leading-35px 2xl:h-37px 2xl:w-40.19px 2xl:leading-37px block bg-opacity-10 text-center"
                                    ><i :class="`icofont-${social.icon}`"></i
                                ></a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </footer>
</template>
