/**
 * Pixely Design System demo page integrating core components.
 *
 * Mirrors the Materialize-style public auth / dashboard layouts:
 * login, dashboard stats, password reset and account verification.
 */

<template>
  <div style="padding: 40px; font-family: 'Inter', sans-serif; color: #1F2A44;">
    <h1 class="text-h4 font-weight-bold mb-8">Pixely Design System</h1>

    <PixelyPageHeader
      title="Cinema Dashboard"
      subtitle="Monitor your movies, events and screening agenda"
      icon="mdi-movie-open-outline"
      actionLabel="New Movie"
    />

    <div class="row no-gutters mb-8">
      <PixelyStatCard
        label="Total Movies"
        value="155k"
        trend="+22%"
        trendDirection="up"
        hint="This quarter"
        icon="mdi-movie-outline"
        iconColor="#5B5BF5"
        iconBackground="#EDEDFE"
      />
      <PixelyStatCard
        label="Upcoming Screenings"
        value="23"
        trend="+12%"
        trendDirection="up"
        hint="This week"
        icon="mdi-calendar-clock"
        iconColor="#4CAF50"
        iconBackground="#E8F5E9"
      />
      <PixelyStatCard
        label="Pending Approvals"
        value="4"
        trend="+2"
        trendDirection="flat"
        hint="Awaiting review"
        icon="mdi-alert-outline"
        iconColor="#F2A93B"
        iconBackground="#FFF3E0"
      />
    </div>

    <PixelyCard title="Recent Activity" description="Latest actions across the platform">
      <PixelyCodeBlock
        code="const event = await cinemaEventService.create({ title: 'Opening Night', eventType: 'festival_day', ... })"
        language="typescript"
      />
    </PixelyCard>

    <PixelyEmptyState
      title="No campaigns scheduled"
      description="Create your first screening agenda to get started."
      actionLabel="Schedule Event"
    />

    <div class="mt-8">
      <v-text-field
        label="Password"
        append-inner-icon="mdi-eye-outline"
        class="mx-auto"
        style="max-width: 320px;"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import PixelyPageHeader from './components/PixelyPageHeader.vue'
import PixelyStatCard from './components/PixelyStatCard.vue'
import PixelyCard from './components/PixelyCard.vue'
import PixelyCodeBlock from './components/PixelyCodeBlock.vue'
import PixelyEmptyState from './components/PixelyEmptyState.vue'
</script>