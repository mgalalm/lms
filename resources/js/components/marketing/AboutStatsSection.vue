<script setup lang="ts">
import { onMounted, ref } from 'vue';

const stats = [
    { value: 27, suffix: '+', label: 'Certified Teachers' },
    { value: 145, suffix: '+', label: 'Active Students' },
    { value: 10, suffix: 'k', label: 'Sessions Completed' },
    { value: 214, suffix: '+', label: 'Courses Taught' },
];

const displayed = ref(stats.map(() => 0));
const counterRoot = ref<HTMLElement | null>(null);

function animateCounters(): void {
    stats.forEach((stat, index) => {
        const duration = 1200;
        const start = performance.now();

        function tick(now: number): void {
            const progress = Math.min((now - start) / duration, 1);
            displayed.value[index] = Math.round(stat.value * progress);

            if (progress < 1) {
                requestAnimationFrame(tick);
            }
        }

        requestAnimationFrame(tick);
    });
}

onMounted(() => {
    if (!counterRoot.value) {
        return;
    }

    const observer = new IntersectionObserver(
        (entries) => {
            if (entries[0].isIntersecting) {
                animateCounters();
                observer.disconnect();
            }
        },
        { threshold: 0.3 },
    );

    observer.observe(counterRoot.value);
});
</script>

<template>
    <section>
        <div class="container">
            <div class="gap-x-30px pt-30px grid grid-cols-1 lg:grid-cols-2">
                <div class="mb-30px md:pb-30px relative z-0 overflow-visible pb-0 lg:mb-0 xl:pb-0" data-aos="fade-up">
                    <img class="md:ml-[70px]" src="/theme/edurock/images/about/about_2.png" alt="" />
                    <img
                        class="absolute bottom-[91px] right-0 sm:right-[-17px] md:bottom-0 md:right-36 lg:right-4"
                        src="/theme/edurock/images/about/about_3.png"
                        alt=""
                    />
                    <img class="animate-move-hor absolute left-[30px] top-[-18px] z-[-1]" src="/theme/edurock/images/about/about_4.png" alt="" />

                    <div
                        class="border-primaryColor px-10px shadow-experience dark:bg-whiteColor-dark md:py-25px absolute bottom-0 left-0 w-[290px] border-l-4 bg-white py-3"
                    >
                        <div class="flex items-center">
                            <p class="pr-10px leading-1 text-primaryColor text-[40px] font-bold uppercase">6+</p>
                            <p class="leading-26px text-blackColor dark:text-blackColor-dark font-bold">YEARS RUNNING TUTORING PROGRAMS</p>
                        </div>
                    </div>
                </div>

                <div data-aos="fade-up">
                    <span class="bg-whitegrey3 py-5px text-primaryColor mb-5 inline-block rounded-full px-6 text-sm font-semibold">About Us</span>
                    <h3 class="pb-25px text-blackColor dark:text-blackColor-dark md:text-size-45 md:leading-2xl text-3xl font-bold leading-10">
                        Welcome to the
                        <span
                            class="after:bg-secondaryColor relative after:absolute after:bottom-3 after:left-0 after:h-[7px] after:w-full md:after:bottom-5"
                            >Online</span
                        >
                        Learning Center
                    </h3>
                    <p class="border-primaryColor text-contentColor dark:text-contentColor-dark mb-6 border-l-2 pl-3 text-sm leading-7 md:text-base">
                        Every teacher on GenAcademy completes and passes the same certification quiz as students before they're allowed to teach a
                        course — so you always know who's on the other side of the camera.
                    </p>
                    <ul class="space-y-5">
                        <li
                            v-for="point in [
                                'Live 1:1 sessions over Zoom',
                                'Certified, quiz-verified teachers',
                                'Real progress & attendance tracking',
                            ]"
                            :key="point"
                            class="group flex items-center"
                        >
                            <i
                                class="icofont-check mr-15px bg-whitegrey3 text-primaryColor group-hover:bg-primaryColor dark:bg-whitegrey1-dark bg-opacity-40 px-2 py-2 group-hover:text-white group-hover:opacity-100"
                            ></i>
                            <p class="text-blackColor dark:text-blackColor-dark text-sm font-medium md:text-base">{{ point }}</p>
                        </li>
                    </ul>
                </div>
            </div>

            <div ref="counterRoot" class="gap-y-30px flex flex-wrap py-[70px] lg:gap-y-0 lg:py-20">
                <div v-for="(stat, index) in stats" :key="stat.label" class="basis-full sm:basis-1/2 lg:basis-1/4" data-aos="fade-up">
                    <div class="flex gap-4">
                        <p class="font-hind text-size-34 text-blackColor dark:text-blackColor-dark font-bold leading-[1.1]">
                            {{ displayed[index] }}{{ stat.suffix }}
                        </p>
                    </div>
                    <p class="text-blackColor dark:text-blackColor-dark font-medium uppercase leading-[18px]">{{ stat.label }}</p>
                </div>
            </div>
        </div>
    </section>
</template>
