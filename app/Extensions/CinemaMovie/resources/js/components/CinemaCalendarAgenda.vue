<script setup lang="ts">
import { computed, ref } from 'vue'
import { translate as t } from '@shared/plugins/i18n'

interface CalendarEvent {
  id: string | number
  title: string
  eventType: 'screening' | 'meet_and_greet' | 'autograph' | 'masterclass' | 'festival_day' | 'qa'
  startAt: string
  endAt: string
  location: string
  participants?: string
  status: 'scheduled' | 'confirmed' | 'cancelled'
  color: string
  notes?: string
}

const currentView = ref<'month' | 'week' | 'day' | 'agenda'>('week')
const currentDate = ref(new Date().toISOString().slice(0, 10))
const selectedFilterType = ref<string>('all')

const eventTypes = [
  { title: t('cinema-movie.event.screening', 'Screening Session'), value: 'screening', color: '#1976D2' },
  { title: t('cinema-movie.event.meet_and_greet', 'Meet & Greet (Actors)'), value: 'meet_and_greet', color: '#9C27B0' },
  { title: t('cinema-movie.event.autograph', 'Autograph Session'), value: 'autograph', color: '#2E7D32' },
  { title: t('cinema-movie.event.masterclass', 'Masterclass & Workshop'), value: 'masterclass', color: '#ED6C02' },
  { title: t('cinema-movie.event.festival_day', 'Film Festival Day'), value: 'festival_day', color: '#D32F2F' },
  { title: t('cinema-movie.event.qa', 'Q&A Panel'), value: 'qa', color: '#00838F' },
]

// Mock events for demonstration of Outlook/Google Calendar style schedule
const sampleEvents = ref<CalendarEvent[]>([
  {
    id: 1,
    title: 'Opening Night Gala Screening & Red Carpet',
    eventType: 'festival_day',
    startAt: '2026-11-15T18:00',
    endAt: '2026-11-15T22:30',
    location: 'Main Auditorium - Grand Hall',
    participants: 'Director, Cast members',
    status: 'confirmed',
    color: '#D32F2F',
    notes: 'Official opening ceremony and premiere screening.',
  },
  {
    id: 2,
    title: 'Meet & Greet with Lead Actress',
    eventType: 'meet_and_greet',
    startAt: '2026-11-16T14:00',
    endAt: '2026-11-16T15:30',
    location: 'VIP Lounge A',
    participants: 'Sarah Connor',
    status: 'confirmed',
    color: '#9C27B0',
    notes: 'Exclusive fan meeting and photo opportunity.',
  },
  {
    id: 3,
    title: 'Exclusive Autograph Signing Session',
    eventType: 'autograph',
    startAt: '2026-11-16T16:00',
    endAt: '2026-11-16T18:00',
    location: 'Foyer Gallery',
    participants: 'Cast & Director',
    status: 'scheduled',
    color: '#2E7D32',
    notes: 'Bring your posters and tickets for signing.',
  },
  {
    id: 4,
    title: 'Director Masterclass: Cinematography in Sci-Fi',
    eventType: 'masterclass',
    startAt: '2026-11-17T11:00',
    endAt: '2026-11-17T13:00',
    location: 'Conference Room 2',
    participants: 'James Cameron',
    status: 'confirmed',
    color: '#ED6C02',
    notes: 'In-depth discussion on visual effects and storytelling.',
  },
])

const dialogOpen = ref(false)
const editingEvent = ref<CalendarEvent | null>(null)

const form = ref({
  title: '',
  eventType: 'screening',
  startDate: new Date().toISOString().slice(0, 10),
  startTime: '19:00',
  endTime: '21:00',
  location: 'Screen 1 - IMAX',
  participants: '',
  notes: '',
})

const filteredEvents = computed(() => {
  if (selectedFilterType.value === 'all') return sampleEvents.value
  return sampleEvents.value.filter((e) => e.eventType === selectedFilterType.value)
})

function openCreateDialog(): void {
  editingEvent.value = null
  form.value = {
    title: '',
    eventType: 'screening',
    startDate: currentDate.value,
    startTime: '19:00',
    endTime: '21:00',
    location: 'Screen 1 - IMAX',
    participants: '',
    notes: '',
  }
  dialogOpen.value = true
}

function saveEvent(): void {
  const typeObj = eventTypes.find((et) => et.value === form.value.eventType)
  const newEvt: CalendarEvent = {
    id: Date.now(),
    title: form.value.title,
    eventType: form.value.eventType as any,
    startAt: `${form.value.startDate}T${form.value.startTime}`,
    endAt: `${form.value.startDate}T${form.value.endTime}`,
    location: form.value.location,
    participants: form.value.participants,
    status: 'confirmed',
    color: typeObj?.color ?? '#1976D2',
    notes: form.value.notes,
  }
  sampleEvents.value.push(newEvt)
  dialogOpen.value = false
}
</script>

<template>
  <v-card class="elevation-2">
    <!-- Outlook / Google Calendar Toolbar Header -->
    <v-toolbar flat class="px-4 border-bottom">
      <v-toolbar-title class="text-h6 font-weight-bold d-flex align-center ga-2">
        <v-icon icon="mdi-calendar-multiselect" color="primary" />
        {{ t('cinema-movie.calendar.title', 'Cinema Event & Screening Agenda') }}
      </v-toolbar-title>
      <v-spacer />

      <!-- View Switcher -->
      <v-btn-toggle v-model="currentView" mandatory density="compact" class="me-3">
        <v-btn value="month" size="small">{{ t('cinema-movie.calendar.month', 'Month') }}</v-btn>
        <v-btn value="week" size="small">{{ t('cinema-movie.calendar.week', 'Week') }}</v-btn>
        <v-btn value="day" size="small">{{ t('cinema-movie.calendar.day', 'Day') }}</v-btn>
        <v-btn value="agenda" size="small">{{ t('cinema-movie.calendar.agenda', 'Agenda') }}</v-btn>
      </v-btn-toggle>

      <!-- Event Type Filter -->
      <v-select
        v-model="selectedFilterType"
        :items="[{ title: t('cinema-movie.calendar.all_events', 'All Event Types'), value: 'all' }, ...eventTypes]"
        density="compact"
        hide-details
        style="max-width: 200px;"
        class="me-3"
      />

      <v-btn color="primary" prepend-icon="mdi-plus" @click="openCreateDialog">
        {{ t('cinema-movie.calendar.new_event', 'New Event') }}
      </v-btn>
    </v-toolbar>

    <!-- Calendar Grid / Agenda View Body -->
    <v-card-text class="pa-4">
      <div class="d-flex align-center justify-space-between mb-4">
        <div class="text-subtitle-1 font-weight-medium">
          {{ t('cinema-movie.calendar.showing_for', 'Schedule & Appointments') }} — {{ currentDate }}
        </div>
        <div class="d-flex ga-2 flex-wrap">
          <v-chip
            v-for="et in eventTypes"
            :key="et.value"
            size="small"
            :color="et.color"
            variant="tonal"
          >
            {{ et.title }}
          </v-chip>
        </div>
      </div>

      <!-- Agenda List (Outlook / Google Agenda Style) -->
      <v-list lines="three" border rounded>
        <v-list-item
          v-for="event in filteredEvents"
          :key="event.id"
          class="py-3 border-bottom"
        >
          <template #prepend>
            <v-avatar :color="event.color" class="text-white font-weight-bold me-3" size="48">
              <v-icon icon="mdi-calendar-clock" />
            </v-avatar>
          </template>

          <v-list-item-title class="font-weight-bold text-subtitle-1 mb-1">
            {{ event.title }}
            <v-chip size="x-small" :color="event.color" class="ms-2" variant="flat">
              {{ event.eventType.replaceAll('_', ' ').toUpperCase() }}
            </v-chip>
          </v-list-item-title>

          <v-list-item-subtitle class="text-body-2">
            <div class="d-flex align-center ga-3 text-medium-emphasis mb-1">
              <span><v-icon icon="mdi-clock-outline" size="small" /> {{ event.startAt }} → {{ event.endAt }}</span>
              <span><v-icon icon="mdi-map-marker-outline" size="small" /> {{ event.location }}</span>
              <span v-if="event.participants"><v-icon icon="mdi-account-group-outline" size="small" /> {{ event.participants }}</span>
            </div>
            <div v-if="event.notes" class="text-truncate text-body-2 text--primary">
              {{ event.notes }}
            </div>
          </v-list-item-subtitle>

          <template #append>
            <v-chip size="small" color="success" variant="outlined">
              {{ event.status }}
            </v-chip>
          </template>
        </v-list-item>
      </v-list>
    </v-card-text>

    <!-- Create Event Dialog -->
    <v-dialog v-model="dialogOpen" max-width="640" persistent>
      <v-card :title="t('cinema-movie.calendar.add_event_title', 'Schedule Cinema Event / Session')">
        <v-card-text>
          <v-form @submit.prevent="saveEvent">
            <v-text-field
              v-model="form.title"
              :label="t('cinema-movie.event.title', 'Event Title (e.g. Autograph Session with Lead Actor)')"
              required
            />
            <v-select
              v-model="form.eventType"
              :items="eventTypes"
              :label="t('cinema-movie.event.type', 'Event Type')"
            />
            <div class="d-sm-flex ga-3">
              <v-text-field
                v-model="form.startDate"
                type="date"
                :label="t('cinema-movie.event.date', 'Date')"
              />
              <v-text-field
                v-model="form.startTime"
                type="time"
                :label="t('cinema-movie.event.start_time', 'Start Time')"
              />
              <v-text-field
                v-model="form.endTime"
                type="time"
                :label="t('cinema-movie.event.end_time', 'End Time')"
              />
            </div>
            <v-text-field
              v-model="form.location"
              :label="t('cinema-movie.event.location', 'Location / Room (e.g. VIP Lounge A)')"
            />
            <v-text-field
              v-model="form.participants"
              :label="t('cinema-movie.event.participants', 'Participants & Guests (Actors, Directors)')"
            />
            <v-textarea
              v-model="form.notes"
              :label="t('cinema-movie.event.notes', 'Notes & Details')"
              rows="2"
            />
          </v-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="dialogOpen = false">
            {{ t('common.action.cancel', 'Cancel') }}
          </v-btn>
          <v-btn color="primary" @click="saveEvent">
            {{ t('common.action.save', 'Schedule Event') }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-card>
</template>
