<template>
  <div class="website-layout">
    <WebsiteHeader />
    <main class="website-main">
      <slot />
    </main>
    <WebsiteFooter />
  </div>
</template>

<script setup lang="ts">
import WebsiteFooter from './WebsiteFooter.vue'
import WebsiteHeader from './WebsiteHeader.vue'
</script>
