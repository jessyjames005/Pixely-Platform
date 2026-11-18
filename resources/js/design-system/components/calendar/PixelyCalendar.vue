<script setup lang="ts">
import { computed, ref } from 'vue'

export interface PixelyCalendarEvent {
  id: string | number
  title: string
  start: string
  end?: string
  category?: string
  allDay?: boolean
}

export interface PixelyCalendarCategory {
  id: string
  label: string
  color: string
}

const props = withDefaults(
  defineProps<{
    events?: PixelyCalendarEvent[]
    categories?: PixelyCalendarCategory[]
    initialView?: 'month' | 'week' | 'day' | 'agenda'
    initialDate?: string
  }>(),
  {
    events: () => [],
    categories: () => [
      { id: 'business', label: 'Business', color: '#5B5BF5' },
      { id: 'personal', label: 'Personal', color: '#4CAF50' },
      { id: 'family', label: 'Family', color: '#03A9F4' },
      { id: 'holiday', label: 'Holiday', color: '#F2A93B' },
      { id: 'etc', label: 'ETC', color: '#EF5350' },
    ],
    initialView: 'month',
    initialDate: new Date().toISOString().slice(0, 10),
  },
)

const view = ref<'month' | 'week' | 'day' | 'agenda'>(props.initialView)
const cursor = ref(new Date(props.initialDate))
const activeCategories = ref<string[]>(props.categories.map((c) => c.id))
const dialogOpen = ref(false)
const dialogTitle = ref('')

const DAY_MS = 24 * 60 * 60 * 1000
const WEEKDAYS = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']

const form = ref({
  title: '',
  category: props.categories[0]?.id ?? 'business',
  startDate: props.initialDate,
  startTime: '09:00',
  endTime: '10:00',
  allDay: false,
})

const categoryMap = computed(() =>
  Object.fromEntries(props.categories.map((c) => [c.id, c])),
)

const visibleEvents = computed(() =>
  props.events.filter((event) => !event.category || activeCategories.value.includes(event.category)),
)

function startOfMonth(date: Date): Date {
  return new Date(date.getFullYear(), date.getMonth(), 1)
}

function endOfMonth(date: Date): Date {
  return new Date(date.getFullYear(), date.getMonth() + 1, 0)
}

function startOfWeek(date: Date): Date {
  const d = new Date(date)
  d.setHours(0, 0, 0, 0)
  return new Date(d.getTime() - d.getDay() * DAY_MS)
}

function addDays(date: Date, days: number): Date {
  return new Date(date.getTime() + days * DAY_MS)
}

function sameDay(a: Date, b: Date): boolean {
  return a.getFullYear() === b.getFullYear()
    && a.getMonth() === b.getMonth()
    && a.getDate() === b.getDate()
}

function dateKey(date: Date | string): string {
  const d = typeof date === 'string' ? new Date(date) : date
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
}

const today = new Date()

const title = computed(() => {
  const d = cursor.value
  if (view.value === 'day') {
    return d.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' })
  }
  return d.toLocaleDateString('en-US', { month: 'long', year: 'numeric' })
})

const monthCells = computed(() => {
  const first = startOfMonth(cursor.value)
  const gridStart = startOfWeek(first)
  const cells: Array<{ date: Date; current: boolean }> = []
  let d = gridStart
  for (let i = 0; i < 42; i++) {
    cells.push({
      date: new Date(d),
      current: d.getMonth() === cursor.value.getMonth(),
    })
    d = addDays(d, 1)
  }
  return cells
})

const weekDays = computed(() => {
  const start = startOfWeek(cursor.value)
  return Array.from({ length: 7 }, (_, i) => addDays(start, i))
})

const dayEvents = computed(() => eventsFor(cursor.value))

const agendaGroups = computed(() => {
  const groups = new Map<string, PixelyCalendarEvent[]>()
  for (const event of visibleEvents.value) {
    const key = dateKey(event.start)
    const list = groups.get(key) ?? []
    list.push(event)
    groups.set(key, list)
  }
  return Array.from(groups.entries())
    .sort(([a], [b]) => a.localeCompare(b))
    .map(([key, items]) => ({ date: new Date(`${key}T00:00:00`), items }))
})

function eventsFor(date: Date): PixelyCalendarEvent[] {
  return visibleEvents.value.filter((event) => sameDay(new Date(event.start), date))
}

function eventsForWeekDay(date: Date): PixelyCalendarEvent[] {
  return eventsFor(date)
}

function selectDay(date: Date): void {
  cursor.value = date
  if (view.value === 'month') view.value = 'day'
}

function goPrev(): void {
  if (view.value === 'month') {
    cursor.value = new Date(cursor.value.getFullYear(), cursor.value.getMonth() - 1, 1)
  } else if (view.value === 'week') {
    cursor.value = addDays(cursor.value, -7)
  } else {
    cursor.value = addDays(cursor.value, -1)
  }
}

function goNext(): void {
  if (view.value === 'month') {
    cursor.value = new Date(cursor.value.getFullYear(), cursor.value.getMonth() + 1, 1)
  } else if (view.value === 'week') {
    cursor.value = addDays(cursor.value, 7)
  } else {
    cursor.value = addDays(cursor.value, 1)
  }
}

function goToday(): void {
  cursor.value = new Date()
}

function toggleCategory(id: string): void {
  if (activeCategories.value.includes(id)) {
    activeCategories.value = activeCategories.value.filter((c) => c !== id)
  } else {
    activeCategories.value = [...activeCategories.value, id]
  }
}

function openDialog(): void {
  dialogTitle.value = 'Add Event'
  form.value = {
    title: '',
    category: props.categories[0]?.id ?? 'business',
    startDate: dateKey(cursor.value),
    startTime: '09:00',
    endTime: '10:00',
    allDay: false,
  }
  dialogOpen.value = true
}

function formatTime(value: string): string {
  const d = new Date(value)
  if (Number.isNaN(d.getTime())) return ''
  return d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' })
}

function eventStyle(event: PixelyCalendarEvent): Record<string, string> {
  const color = (event.category && categoryMap.value[event.category]?.color) || '#5B5BF5'
  return {
    backgroundColor: `${color}1A`,
    color,
    borderLeft: `3px solid ${color}`,
  }
}
</script>

<template>
  <v-card
    class="pixely-calendar"
    elevation="0"
    border
    :style="{ borderRadius: '16px', overflow: 'hidden' }"
  >
    <div class="d-flex calendar-shell">
      <aside class="calendar-sidebar pa-4">
        <v-btn
          color="primary"
          variant="flat"
          block
          prepend-icon="mdi-plus"
          class="mb-6"
          @click="openDialog"
        >
          Add Event
        </v-btn>

        <div class="text-caption text-medium-emphasis text-uppercase font-weight-bold mb-3">
          Event Categories
        </div>
        <div
          v-for="category in categories"
          :key="category.id"
          class="category-row d-flex align-center ga-2 py-1"
        >
          <v-checkbox
            :model-value="activeCategories.includes(category.id)"
            hide-details
            density="compact"
            color="primary"
            @update:model-value="toggleCategory(category.id)"
          />
          <v-chip
            size="small"
            variant="flat"
            :style="{ backgroundColor: `${category.color}1A`, color: category.color }"
          >
            {{ category.label }}
          </v-chip>
        </div>
      </aside>

      <div class="flex-grow-1 calendar-main">
        <div class="calendar-toolbar d-flex align-center justify-space-between px-4 py-3 border-b border-opacity-10">
          <div class="d-flex align-center ga-2">
            <v-btn
              icon
              variant="text"
              size="small"
              @click="goPrev"
            >
              <v-icon icon="mdi-chevron-left" />
            </v-btn>
            <v-btn
              icon
              variant="text"
              size="small"
              @click="goNext"
            >
              <v-icon icon="mdi-chevron-right" />
            </v-btn>
            <v-btn
              variant="tonal"
              size="small"
              @click="goToday"
            >
              Today
            </v-btn>
            <span class="text-subtitle-1 font-weight-bold ms-2">{{ title }}</span>
          </div>

          <v-btn-toggle
            v-model="view"
            mandatory
            density="compact"
            divided
          >
            <v-btn
              value="month"
              size="small"
            >
              Month
            </v-btn>
            <v-btn
              value="week"
              size="small"
            >
              Week
            </v-btn>
            <v-btn
              value="day"
              size="small"
            >
              Day
            </v-btn>
            <v-btn
              value="agenda"
              size="small"
            >
              Agenda
            </v-btn>
          </v-btn-toggle>
        </div>

        <div
          v-if="view === 'month'"
          class="calendar-month"
        >
          <div class="month-headrow d-grid">
            <div
              v-for="day in WEEKDAYS"
              :key="day"
              class="month-headcell text-caption font-weight-medium text-medium-emphasis"
            >
              {{ day }}
            </div>
          </div>
          <div class="month-grid d-grid">
            <button
              v-for="(cell, index) in monthCells"
              :key="index"
              type="button"
              class="month-cell"
              :class="{ 'month-cell--muted': !cell.current, 'month-cell--today': sameDay(cell.date, today) }"
              @click="selectDay(cell.date)"
            >
              <span class="month-daynum text-caption font-weight-medium">{{ cell.date.getDate() }}</span>
              <div class="month-events">
                <span
                  v-for="event in eventsFor(cell.date).slice(0, 3)"
                  :key="event.id"
                  class="month-event text-caption text-truncate"
                  :style="eventStyle(event)"
                >
                  {{ event.title }}
                </span>
                <span
                  v-if="eventsFor(cell.date).length > 3"
                  class="text-caption text-medium-emphasis"
                >
                  +{{ eventsFor(cell.date).length - 3 }} more
                </span>
              </div>
            </button>
          </div>
        </div>

        <div
          v-else-if="view === 'week'"
          class="calendar-week"
        >
          <div class="week-headrow d-grid">
            <div class="week-hourcol" />
            <div
              v-for="day in weekDays"
              :key="day.toISOString()"
              class="week-headcell text-center py-2"
              :class="{ 'week-headcell--today': sameDay(day, today) }"
            >
              <div class="text-caption text-medium-emphasis">
                {{ WEEKDAYS[day.getDay()] }}
              </div>
              <div class="text-subtitle-1 font-weight-bold">
                {{ day.getDate() }}
              </div>
            </div>
          </div>
          <div class="week-body d-grid">
            <div class="week-hourcol">
              <div
                v-for="hour in 24"
                :key="hour"
                class="week-hour text-caption text-medium-emphasis"
              >
                {{ String(hour - 1).padStart(2, '0') }}:00
              </div>
            </div>
            <div
              v-for="day in weekDays"
              :key="`col-${day.toISOString()}`"
              class="week-col"
              :class="{ 'week-col--today': sameDay(day, today) }"
            >
              <div
                v-for="hour in 24"
                :key="hour"
                class="week-slot border-t border-opacity-10"
              />
              <div class="week-events">
                <div
                  v-for="event in eventsForWeekDay(day)"
                  :key="event.id"
                  class="week-event px-2 py-1 mb-1 text-caption"
                  :style="eventStyle(event)"
                >
                  <div class="font-weight-medium text-truncate">
                    {{ event.title }}
                  </div>
                  <div class="text-caption">
                    {{ formatTime(event.start) }}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div
          v-else-if="view === 'day'"
          class="calendar-day pa-4"
        >
          <div
            v-if="dayEvents.length === 0"
            class="text-center py-12 text-medium-emphasis"
          >
            No events scheduled for this day.
          </div>
          <v-list
            v-else
            lines="two"
          >
            <v-list-item
              v-for="event in dayEvents"
              :key="event.id"
              class="mb-2"
            >
              <template #prepend>
                <div
                  class="day-event-dot"
                  :style="eventStyle(event)"
                />
              </template>
              <v-list-item-title class="font-weight-medium">
                {{ event.title }}
              </v-list-item-title>
              <v-list-item-subtitle class="text-caption">
                {{ formatTime(event.start) }}<template v-if="event.end">
                  — {{ formatTime(event.end) }}
                </template>
              </v-list-item-subtitle>
            </v-list-item>
          </v-list>
        </div>

        <div
          v-else
          class="calendar-agenda pa-4"
        >
          <div
            v-if="agendaGroups.length === 0"
            class="text-center py-12 text-medium-emphasis"
          >
            No events to display.
          </div>
          <div
            v-for="group in agendaGroups"
            :key="group.date.toISOString()"
            class="mb-6"
          >
            <div class="text-subtitle-2 font-weight-bold mb-2">
              {{ group.date.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric' }) }}
            </div>
            <v-card
              v-for="event in group.items"
              :key="event.id"
              class="mb-2"
              elevation="0"
              border
            >
              <div class="d-flex align-center justify-space-between pa-3">
                <div class="d-flex align-center ga-3">
                  <div
                    class="day-event-dot"
                    :style="eventStyle(event)"
                  />
                  <span class="text-body-2 font-weight-medium">{{ event.title }}</span>
                </div>
                <span class="text-caption text-medium-emphasis">{{ formatTime(event.start) }}</span>
              </div>
            </v-card>
          </div>
        </div>
      </div>
    </div>

    <v-dialog
      v-model="dialogOpen"
      max-width="560"
      persistent
    >
      <v-card title="Add Event">
        <v-card-text>
          <v-text-field
            v-model="form.title"
            label="Event title"
            required
            class="mb-2"
          />
          <v-select
            v-model="form.category"
            :items="categories.map((c) => ({ title: c.label, value: c.id }))"
            label="Category"
            class="mb-2"
          />
          <div class="d-sm-flex ga-3">
            <v-text-field
              v-model="form.startDate"
              type="date"
              label="Date"
            />
            <v-text-field
              v-model="form.startTime"
              type="time"
              label="Start"
              :disabled="form.allDay"
            />
            <v-text-field
              v-model="form.endTime"
              type="time"
              label="End"
              :disabled="form.allDay"
            />
          </div>
          <v-checkbox
            v-model="form.allDay"
            label="All day"
            hide-details
          />
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn
            variant="text"
            @click="dialogOpen = false"
          >
            Cancel
          </v-btn>
          <v-btn
            color="primary"
            variant="flat"
            @click="dialogOpen = false"
          >
            Add Event
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-card>
</template>

<style scoped>
.calendar-shell {
  min-height: 640px;
}

.calendar-sidebar {
  width: 260px;
  border-right: 1px solid rgba(31, 42, 68, 0.08);
  flex: 0 0 auto;
}

.category-row {
  cursor: pointer;
}

.calendar-main {
  min-width: 0;
}

.month-headrow {
  grid-template-columns: repeat(7, 1fr);
  border-bottom: 1px solid rgba(31, 42, 68, 0.08);
}

.month-headcell {
  padding: 10px 12px;
}

.month-grid {
  grid-template-columns: repeat(7, 1fr);
  grid-auto-rows: minmax(110px, 1fr);
}

.month-cell {
  text-align: left;
  padding: 6px 8px;
  border-right: 1px solid rgba(31, 42, 68, 0.06);
  border-bottom: 1px solid rgba(31, 42, 68, 0.06);
  background: #fff;
  display: flex;
  flex-direction: column;
  gap: 4px;
  cursor: pointer;
}

.month-cell:hover {
  background: #f9fafb;
}

.month-cell--muted {
  background: #fcfcfd;
}

.month-cell--muted .month-daynum {
  color: #9ca3af;
}

.month-cell--today .month-daynum {
  color: #fff;
  background: #5b5bf5;
  border-radius: 999px;
  width: 22px;
  height: 22px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.month-event {
  display: block;
  padding: 1px 6px;
  border-radius: 4px;
  font-weight: 500;
  line-height: 1.5;
}

.week-headrow {
  grid-template-columns: 64px repeat(7, 1fr);
  border-bottom: 1px solid rgba(31, 42, 68, 0.08);
}

.week-headcell--today .text-subtitle-1 {
  color: #5b5bf5;
}

.week-body {
  grid-template-columns: 64px repeat(7, 1fr);
  max-height: 560px;
  overflow-y: auto;
}

.week-hourcol .week-hour {
  height: 48px;
  padding: 2px 6px;
  text-align: right;
  border-bottom: 1px solid rgba(31, 42, 68, 0.06);
}

.week-col {
  position: relative;
  border-right: 1px solid rgba(31, 42, 68, 0.06);
}

.week-col--today {
  background: rgba(91, 91, 245, 0.03);
}

.week-slot {
  height: 48px;
}

.week-events {
  position: absolute;
  inset: 0;
  padding: 4px;
  pointer-events: none;
}

.week-event {
  border-radius: 6px;
  pointer-events: auto;
}

.calendar-day .day-event-dot,
.calendar-agenda .day-event-dot {
  width: 10px;
  height: 10px;
  border-radius: 999px;
}

@media (max-width: 960px) {
  .calendar-shell {
    flex-direction: column;
  }

  .calendar-sidebar {
    width: 100%;
    border-right: none;
    border-bottom: 1px solid rgba(31, 42, 68, 0.08);
  }
}
</style>
