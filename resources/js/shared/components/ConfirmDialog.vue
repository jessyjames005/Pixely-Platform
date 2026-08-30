<script setup lang="ts">
// Single, app-wide confirmation dialog. Mounted once in App.vue;
// triggered anywhere via useConfirmDialog().confirm({...}).
import { useConfirmDialog } from '../composables/useConfirmDialog'

const { isOpen, options, handleConfirm, handleCancel } = useConfirmDialog()
</script>

<template>
  <v-dialog v-model="isOpen" max-width="420" persistent>
    <v-card :title="options.title ?? 'Confirm'">
      <v-card-text>{{ options.message }}</v-card-text>
      <v-card-actions>
        <v-spacer />
        <v-btn variant="text" @click="handleCancel">{{ options.cancelText ?? 'Cancel' }}</v-btn>
        <v-btn :color="options.color ?? 'error'" variant="flat" @click="handleConfirm">
          {{ options.confirmText ?? 'Delete' }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>
