<script setup lang="ts">
// Shared centred card for the unauthenticated screens
// (sign in, forgot password, reset password).
defineProps<{ title: string }>()
</script>

<template>
  <v-container class="fill-height" fluid>
    <v-row align="center" justify="center">
      <v-col cols="12" sm="8" md="4">
        <v-card :title="title">
          <v-card-text>
            <slot />
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>
