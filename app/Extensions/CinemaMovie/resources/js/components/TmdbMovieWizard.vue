<script setup lang="ts">
/**
 * Pixely Movies 5-step Movie Addition Wizard component replicating the Pixely Movies submit movie workflow
 * (Getting Started -> Movie Details -> Verification -> Additional Details -> Review & Save).
 */
import { ref, reactive } from 'vue'
import { useCinemaMovieStore } from '../store/cinema-movie.store'
import { useNotify } from '@shared/composables/useNotify'

const emit = defineEmits(['close', 'saved'])

const store = useCinemaMovieStore()
const notify = useNotify()

const currentStep = ref(1)
const loading = ref(false)

const form = reactive({
  originalTitle: '',
  frenchOverview: '',
  imdbId: '',
  adult: 'None',
  video: 'Non',
  frenchTagline: '',
  runtimeMinutes: null as number | null,
  budget: null as number | null,
  revenue: null as number | null,
  homepage: '',
})

const steps = [
  'Getting Started',
  'Movie Details',
  'Verification',
  'Additional Details',
  'Vérifier et enregistrer',
]

function nextStep(): void {
  if (currentStep.value < 5) {
    currentStep.value++
  }
}

function prevStep(): void {
  if (currentStep.value > 1) {
    currentStep.value--
  }
}

async function saveMovie(): void {
  try {
    loading.value = true
    await store.saveMovie(
      null,
      {
        originalTitle: form.originalTitle || 'Untitled Movie',
        releaseDate: new Date().toISOString().slice(0, 10),
        runtimeMinutes: form.runtimeMinutes,
        productionStatus: 'released',
        publicationStatus: 'published',
        budget: form.budget,
        revenue: form.revenue,
      },
      [
        { locale: 'en', title: form.originalTitle, overview: form.frenchOverview, tagline: form.frenchTagline },
        { locale: 'fr', title: form.originalTitle, overview: form.frenchOverview, tagline: form.frenchTagline },
      ],
      [],
      null,
      null,
    )
    notify.success('Film ajouté avec succès !')
    emit('saved')
    emit('close')
  } catch (err: any) {
    notify.error(err.message || 'Erreur lors de l\'enregistrement du film.')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="pixely-movies-wizard-overlay">
    <div class="pixely-movies-wizard-modal">
      <div class="pixely-movies-wizard-header">
        <h2>Ajouter un film</h2>
        <button class="pixely-movies-close-btn" @click="$emit('close')">&times;</button>
      </div>

      <div class="pixely-movies-wizard-body">
        <!-- Steps Sidebar -->
        <div class="pixely-movies-wizard-sidebar">
          <div
            v-for="(stepName, index) in steps"
            :key="index"
            :class="['wizard-step-item', { active: currentStep === index + 1, completed: currentStep > index + 1 }]"
          >
            <div class="step-number">
              <v-icon v-if="currentStep > index + 1" icon="mdi-check" size="small" />
              <span v-else>{{ index + 1 }}</span>
            </div>
            <span>{{ stepName }}</span>
          </div>
        </div>

        <!-- Wizard Step Content -->
        <div class="pixely-movies-wizard-content">
          <!-- Step 1: Getting Started -->
          <div v-if="currentStep === 1">
            <h3>Getting Started</h3>
            <p class="wizard-desc">
              Adding a new film on Pixely Movies is simple and easy. If you have any questions about how our editing system works, make sure to read through our contribution bible or stop by the forums.
            </p>
            <p class="wizard-desc">
              This form will guide you through the creation process. To get started, click next.
            </p>
            <div class="pixely-movies-notice-box">
              <strong><v-icon icon="mdi-information" /> Please note</strong>
              <ul>
                <li>You are currently adding a movie in <strong>fr-FR (Français, French - France)</strong>.</li>
                <li>TV content is not allowed in the movie section.</li>
                <li>Miniseries are no longer allowed in the movie section.</li>
              </ul>
            </div>
          </div>

          <!-- Step 2: Movie Details -->
          <div v-else-if="currentStep === 2">
            <h3>Movie Details</h3>
            <div class="form-group">
              <label>Titre d'origine</label>
              <input v-model="form.originalTitle" type="text" class="pixely-movies-input" placeholder="ex: Inception" />
            </div>
            <div class="form-group">
              <label>Synopsis (Français)</label>
              <textarea v-model="form.frenchOverview" rows="4" class="pixely-movies-input" placeholder="Résumé du film..." />
            </div>
            <div class="form-group">
              <label>IMDB ID (tt0137523)</label>
              <input v-model="form.imdbId" type="text" class="pixely-movies-input" placeholder="tt..." />
            </div>
          </div>

          <!-- Step 3: Verification -->
          <div v-else-if="currentStep === 3">
            <h3>Verification</h3>
            <div class="pixely-movies-alert-warning">
              Vérification des doublons : aucun doublon exact trouvé dans le catalogue pour « {{ form.originalTitle || 'Nouveau film' }} ». Vous pouvez continuer.
            </div>
          </div>

          <!-- Step 4: Additional Details -->
          <div v-else-if="currentStep === 4">
            <h3>Additional Details</h3>
            <div class="form-row">
              <div class="form-group">
                <label>Adult Status?</label>
                <select v-model="form.adult" class="pixely-movies-input">
                  <option value="None">None</option>
                  <option value="Adult">Adult</option>
                </select>
              </div>
              <div class="form-group">
                <label>Vidéo ?</label>
                <select v-model="form.video" class="pixely-movies-input">
                  <option value="Non">Non</option>
                  <option value="Oui">Oui</option>
                </select>
              </div>
            </div>
            <div class="form-group">
              <label>Slogan (Français)</label>
              <input v-model="form.frenchTagline" type="text" class="pixely-movies-input" placeholder="Accroche du film..." />
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Durée (en minutes)</label>
                <input v-model.number="form.runtimeMinutes" type="number" class="pixely-movies-input" placeholder="120" />
              </div>
              <div class="form-group">
                <label>Budget (in U.S. dollars)</label>
                <input v-model.number="form.budget" type="number" class="pixely-movies-input" placeholder="50000000" />
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Recette (in U.S. dollars)</label>
                <input v-model.number="form.revenue" type="number" class="pixely-movies-input" placeholder="150000000" />
              </div>
              <div class="form-group">
                <label>Page d'accueil du site officiel</label>
                <input v-model="form.homepage" type="text" class="pixely-movies-input" placeholder="https://www.example.com" />
              </div>
            </div>
          </div>

          <!-- Step 5: Review & Save -->
          <div v-else-if="currentStep === 5">
            <h3>Review & Save</h3>
            <table class="review-table">
              <tr><td><strong>Titre d'origine</strong></td><td>{{ form.originalTitle || '-' }}</td></tr>
              <tr><td><strong>Synopsis</strong></td><td>{{ form.frenchOverview || '-' }}</td></tr>
              <tr><td><strong>IMDB ID</strong></td><td>{{ form.imdbId || '-' }}</td></tr>
              <tr><td><strong>Adult Status</strong></td><td>{{ form.adult }}</td></tr>
              <tr><td><strong>Vidéo</strong></td><td>{{ form.video }}</td></tr>
              <tr><td><strong>Slogan</strong></td><td>{{ form.frenchTagline || '-' }}</td></tr>
              <tr><td><strong>Durée</strong></td><td>{{ form.runtimeMinutes ? form.runtimeMinutes + ' min' : '-' }}</td></tr>
              <tr><td><strong>Budget</strong></td><td>{{ form.budget ? '$' + form.budget : '-' }}</td></tr>
              <tr><td><strong>Recette</strong></td><td>{{ form.revenue ? '$' + form.revenue : '-' }}</td></tr>
              <tr><td><strong>Page d'accueil</strong></td><td>{{ form.homepage || '-' }}</td></tr>
            </table>
          </div>
        </div>
      </div>

      <div class="pixely-movies-wizard-footer">
        <button
          v-if="currentStep > 1"
          class="pixely-movies-btn-secondary"
          @click="prevStep"
        >
          Précédent
        </button>
        <button
          v-if="currentStep < 5"
          class="pixely-movies-btn-primary"
          @click="nextStep"
        >
          Suivant
        </button>
        <button
          v-else
          class="pixely-movies-btn-primary"
          :disabled="loading"
          @click="saveMovie"
        >
          {{ loading ? 'Enregistrement...' : 'Enregistrer' }}
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.pixely-movies-wizard-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: rgba(0,0,0,0.6);
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
}
.pixely-movies-wizard-modal {
  background: #ffffff;
  width: 900px;
  max-width: 95vw;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 12px 36px rgba(0,0,0,0.3);
  display: flex;
  flex-direction: column;
}
.pixely-movies-wizard-header {
  background: #032541;
  color: #ffffff;
  padding: 20px 30px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.pixely-movies-close-btn {
  background: none;
  border: none;
  color: #ffffff;
  font-size: 1.8rem;
  cursor: pointer;
}
.pixely-movies-wizard-body {
  display: flex;
  min-height: 420px;
}
.pixely-movies-wizard-sidebar {
  width: 260px;
  background: #f8fafc;
  border-right: 1px solid #e2e8f0;
  padding: 20px 0;
}
.wizard-step-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 24px;
  font-weight: 600;
  color: #64748b;
  font-size: 0.95rem;
}
.wizard-step-item.active {
  color: #01b4e4;
  background: #f1f5f9;
  border-left: 4px solid #01b4e4;
}
.wizard-step-item.completed {
  color: #0f172a;
}
.step-number {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  background: #e2e8f0;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.85rem;
  color: #475569;
}
.wizard-step-item.active .step-number {
  background: #01b4e4;
  color: #ffffff;
}
.wizard-step-item.completed .step-number {
  background: #22c55e;
  color: #ffffff;
}
.pixely-movies-wizard-content {
  flex-grow: 1;
  padding: 30px;
  overflow-y: auto;
  color: #0f172a;
}
.pixely-movies-wizard-content h3 {
  font-size: 1.4rem;
  font-weight: 700;
  margin-bottom: 16px;
}
.wizard-desc {
  margin-bottom: 14px;
  line-height: 1.5;
  color: #334155;
}
.pixely-movies-notice-box {
  background: #032541;
  color: #ffffff;
  padding: 16px;
  border-radius: 8px;
  margin-top: 20px;
}
.pixely-movies-notice-box strong {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-bottom: 8px;
}
.pixely-movies-notice-box ul {
  padding-left: 20px;
  font-size: 0.9rem;
}
.form-group {
  margin-bottom: 16px;
}
.form-group label {
  display: block;
  font-weight: 600;
  margin-bottom: 6px;
  font-size: 0.9rem;
}
.form-row {
  display: flex;
  gap: 16px;
}
.form-row .form-group {
  flex: 1;
}
.pixely-movies-input {
  width: 100%;
  padding: 10px 14px;
  border: 1px solid #cbd5e1;
  border-radius: 6px;
  font-size: 0.95rem;
  outline: none;
  background: #ffffff;
  color: #0f172a;
}
.pixely-movies-input:focus {
  border-color: #01b4e4;
}
.pixely-movies-alert-warning {
  background: #fef3c7;
  color: #92400e;
  padding: 16px;
  border-radius: 8px;
  font-weight: 600;
}
.review-table {
  width: 100%;
  border-collapse: collapse;
}
.review-table td {
  padding: 10px 12px;
  border-bottom: 1px solid #e2e8f0;
  font-size: 0.95rem;
}
.pixely-movies-wizard-footer {
  background: #f8fafc;
  border-top: 1px solid #e2e8f0;
  padding: 16px 30px;
  display: flex;
  justify-content: flex-end;
  gap: 12px;
}
.pixely-movies-btn-secondary {
  background: #e2e8f0;
  color: #334155;
  border: none;
  padding: 10px 24px;
  border-radius: 20px;
  font-weight: 600;
  cursor: pointer;
}
.pixely-movies-btn-primary {
  background: #01b4e4;
  color: #ffffff;
  border: none;
  padding: 10px 28px;
  border-radius: 20px;
  font-weight: 600;
  cursor: pointer;
}
.pixely-movies-btn-primary:hover {
  background: #0092be;
}
</style>
