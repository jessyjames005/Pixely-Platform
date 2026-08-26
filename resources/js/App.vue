<script setup lang="ts">
/**
 * Root component of the Pixely Platform frontend.
 *
 * The component currently provides the entry point for the
 * Vue application. The administration router and layout will
 * be introduced in the next steps.
 */
import { RouterView } from 'vue-router';
</script>

<template>
    <div id="pixely-app">
        <RouterView />
    </div>
</template>
