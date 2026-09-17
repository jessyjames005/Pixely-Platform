<script setup lang="ts">
/**
 * Root component of the Pixely Platform frontend.
 *
 * The component currently provides the entry point for the
 * Vue application. The administration router and layout will
 * be introduced in the next steps.
 */
import { onMounted } from "vue";
import { RouterView } from "vue-router";
import ConfirmDialog from "./shared/components/ConfirmDialog.vue";
import NotifySnackbar from "./shared/components/NotifySnackbar.vue";
import { useI18nStore } from "./shared/store/i18n.store";

const i18nStore = useI18nStore();

onMounted(() => {
  // Browser-language guess, so even the login screen is translated.
  // AdminLayout switches to the user's saved preference once known.
  i18nStore.load(i18nStore.locale).catch(() => undefined);
});
</script>

<template>
  <v-app>
    <router-view />
    <ConfirmDialog />
    <NotifySnackbar />
  </v-app>
</template>
