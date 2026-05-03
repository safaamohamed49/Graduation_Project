<script setup>
import MainLayout from '@/Layouts/MainLayout.vue'
import CardBox from '@/Components/CardBox.vue'
import CardBoxComponentHeader from '@/Components/CardBoxComponentHeader.vue'
import PageHero from '@/Components/App/PageHero.vue'

defineProps({
  pageTitle: {
    type: String,
    required: true,
  },
  heroBadge: {
    type: String,
    default: '',
  },
  heroTitle: {
    type: String,
    required: true,
  },
  heroDescription: {
    type: String,
    default: '',
  },
  heroBackHref: {
    type: String,
    default: '',
  },
  heroGradientClass: {
    type: String,
    default: 'bg-gradient-to-l from-slate-900 via-slate-800 to-slate-700',
  },
  cardTitle: {
    type: String,
    required: true,
  },
})

const emit = defineEmits(['submit'])
</script>

<template>
  <MainLayout :title="pageTitle">
    <div class="space-y-6">
      <PageHero
        :badge="heroBadge"
        :title="heroTitle"
        :description="heroDescription"
        :back-href="heroBackHref"
        :gradient-class="heroGradientClass"
      />

      <CardBox is-form @submit.prevent="emit('submit')">
        <CardBoxComponentHeader :title="cardTitle" />

        <slot />

        <template #footer>
          <slot name="footer" />
        </template>
      </CardBox>
    </div>
  </MainLayout>
</template>