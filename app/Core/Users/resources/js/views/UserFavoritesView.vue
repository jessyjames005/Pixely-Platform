<script setup lang="ts">
import { onMounted } from 'vue'
import { useEngagementStore } from '../store/engagement.store'

const store = useEngagementStore()

onMounted(() => store.loadFavorites())
</script>

<template>
    <section class="user-space-view">
        <div class="page-heading">
            <h1>Favorites</h1>
            <p>Resources you have saved across Pixely.</p>
        </div>

        <div v-if="store.loading">Loading favorites…</div>
        <div v-else-if="store.favorites.length === 0">You have no favorites yet.</div>
        <ul v-else class="resource-list">
            <li v-for="favorite in store.favorites" :key="favorite.id">
                <span>{{ favorite.resource_type }}</span>
                <strong>{{ favorite.resource_id }}</strong>
                <button type="button" @click="store.removeFavorite(favorite.resource_type, favorite.resource_id)">
                    Remove
                </button>
            </li>
        </ul>
    </section>
</template>
