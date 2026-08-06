<script setup lang="ts">
import { Navigation } from 'swiper/modules';
import { Swiper, SwiperSlide } from 'swiper/vue';

import 'swiper/css';
import 'swiper/css/navigation';

const testimonials = [
    {
        quote: 'My daughter looks forward to her sessions every week. The teacher always shows up prepared and the progress reports actually mean something.',
        name: 'Sarah M.',
        role: 'Parent',
    },
    {
        quote: "Being certified before I could teach a course made me a better teacher — I'd already sat through the exact quiz my students take.",
        name: 'Ahmed K.',
        role: 'Teacher',
    },
    {
        quote: 'Scheduling, payroll, and HR requests used to be three different tools. Now our whole team runs on one dashboard.',
        name: 'Lina R.',
        role: 'Operations Lead',
    },
];
</script>

<template>
    <section id="testimonials" class="bg-lightGrey10 dark:bg-lightGrey10-dark relative">
        <div class="pb-90px 2xl:pb-90px 2xl:pt-30 container pt-20">
            <div class="gap-15 grid grid-cols-1 lg:grid-cols-3">
                <div class="lg:col-span-1" data-aos="fade-up">
                    <span class="bg-whitegrey3 py-5px text-primaryColor mb-5 inline-block rounded-full px-6 text-sm font-semibold">Testimonials</span>
                    <h3 class="pb-25px text-blackColor dark:text-blackColor-dark md:text-size-35 md:leading-45px text-3xl font-bold leading-10">
                        What They Say About Us
                    </h3>
                    <p class="text-contentColor dark:text-contentColor-dark 2xl:mb-45px mb-5 text-sm md:text-base">
                        Real feedback from the parents, teachers, and admins who use Edurock every day.
                    </p>
                </div>

                <div class="lg:col-span-2" data-aos="fade-up">
                    <Swiper
                        class="testimonial-swiper !pb-16px"
                        :modules="[Navigation]"
                        :navigation="true"
                        :space-between="24"
                        :slides-per-view="1"
                        :breakpoints="{ 768: { slidesPerView: 2 } }"
                    >
                        <SwiperSlide v-for="testimonial in testimonials" :key="testimonial.name">
                            <div class="mb-22px bg-whiteColor px-25px pb-50px pt-25px dark:bg-whiteColor-dark">
                                <div class="bg-primaryColor text-whiteColor mb-5 flex h-12 w-12 items-center justify-center rounded-lg text-2xl">
                                    <i class="icofont-quote-left"></i>
                                </div>
                                <p class="text-contentColor dark:text-contentColor-dark text-base lg:text-sm 2xl:text-base">
                                    &ldquo;{{ testimonial.quote }}&rdquo;
                                </p>
                            </div>
                            <div class="flex items-center gap-5">
                                <div
                                    class="bg-primaryColor/10 text-primaryColor flex h-14 w-14 items-center justify-center rounded-full text-lg font-semibold"
                                >
                                    {{ testimonial.name.charAt(0) }}
                                </div>
                                <div>
                                    <h4 class="text-blackColor dark:text-blackColor-dark text-xl font-semibold lg:text-lg">{{ testimonial.name }}</h4>
                                    <p class="text-lightGrey9 dark:text-lightGrey9-dark lg:text-size-15 text-base">{{ testimonial.role }}</p>
                                </div>
                            </div>
                        </SwiperSlide>
                    </Swiper>
                </div>
            </div>
        </div>
    </section>
</template>

<style scoped>
.testimonial-swiper {
    --swiper-theme-color: #5f2ded;
    --swiper-navigation-size: 16px;
}

.testimonial-swiper :deep(.swiper-button-prev),
.testimonial-swiper :deep(.swiper-button-next) {
    top: auto;
    bottom: 0;
    width: 40px;
    height: 40px;
    background: white;
    border-radius: 9999px;
    box-shadow: 0 4px 12px rgb(0 0 0 / 0.1);
}

.testimonial-swiper :deep(.swiper-button-prev) {
    left: auto;
    right: 56px;
}

.testimonial-swiper :deep(.swiper-button-next) {
    right: 0;
}
</style>
