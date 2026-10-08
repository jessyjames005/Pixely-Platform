<script setup lang="ts">
import { onMounted } from 'vue'
import { useEngagementStore } from '../store/engagement.store'

const store = useEngagementStore()

onMounted(() => store.loadHistory())
</script>

<template>
    <section class="user-space-view">
        <div class="page-heading">
            <h1>History</h1>
            <p>Your recent activity across Pixely.</p>
        </div>

        <div v-if="store.loading">Loading history…</div>
        <div v-else-if="store.history.length === 0">Your history is empty.</div>
        <ol v-else class="resource-list">
            <li v-for="entry in store.history" :key="entry.id">
                <span>{{ entry.action }}</span>
                <strong>{{ entry.resource_type }} / {{ entry.resource_id }}</strong>
                <time :datetime="entry.occurred_at">{{ entry.occurred_at }}</time>
            </li>
        </ol>
    </section>
</template>
