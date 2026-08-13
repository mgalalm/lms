<script setup lang="ts">
import AboutStatsSection from '@/components/marketing/AboutStatsSection.vue';
import CtaSection from '@/components/marketing/CtaSection.vue';
import FeaturesSection from '@/components/marketing/FeaturesSection.vue';
import HeroSection from '@/components/marketing/HeroSection.vue';
import MarketingFooter from '@/components/marketing/MarketingFooter.vue';
import MarketingHeader from '@/components/marketing/MarketingHeader.vue';
import PricingSection from '@/components/marketing/PricingSection.vue';
import TestimonialsSection from '@/components/marketing/TestimonialsSection.vue';
import type { SessionPackage } from '@/types';
import { Head } from '@inertiajs/vue3';
import AOS from 'aos';
import 'aos/dist/aos.css';
import { onMounted } from 'vue';

defineProps<{
    packages: SessionPackage[];
}>();

onMounted(() => {
    AOS.init({ once: true, duration: 600, offset: 80 });
});
</script>

<template>
    <Head title="Live Tutoring Platform">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
        <link href="https://fonts.googleapis.com/css2?family=Hind:wght@400;500;600;700&display=swap" rel="stylesheet" />
        <link rel="stylesheet" href="/theme/edurock/css/icofont.min.css" />
        <link rel="stylesheet" href="/theme/edurock/css/style.css" />
    </Head>

    <div class="bg-whiteColor font-inter dark:bg-whiteColor-dark">
        <MarketingHeader :show-pricing="packages.length > 0" />
        <main>
            <HeroSection />
            <AboutStatsSection />
            <FeaturesSection />
            <PricingSection v-if="packages.length > 0" :packages="packages" />
            <CtaSection />
            <TestimonialsSection />
        </main>
        <MarketingFooter />
    </div>
</template>
