import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { tuleap, local } from '@/composables/useApi';
export const useTuleapStore = defineStore('tuleap', () => {
  const projects = ref([]);
  const selectedProjectId = ref(null);
  const milestones = ref([]);
  const selectedMilestoneId = ref(null);
  const members = ref([]);
  const stats = ref(null);
  const burndown = ref(null);
  const sprintConfig = ref(null);
  const cafData = ref([]);
  const loading = ref({ projects: false, milestones: false, stats: false, burndown: false });
  const error = ref(null);
  const tuleapStatus = ref('unknown');
  const theme = ref(localStorage.getItem('theme') || 'dark');

  function setTheme(value) {
    theme.value = value;
    localStorage.setItem('theme', value);
    document.documentElement.setAttribute('data-theme', value === 'light' ? 'light' : '');
  }

  document.documentElement.setAttribute('data-theme', theme.value === 'light' ? 'light' : '');

  const selectedProject = computed(() =>
    projects.value.find(p => p.id === selectedProjectId.value)
  );

  const selectedMilestone = computed(() =>
    milestones.value.find(m => m.id === selectedMilestoneId.value)
  );

  const theoreticalCapacity = computed(() => {
    if (!cafData.value.length || !sprintConfig.value) return null;
    const totalCaf = cafData.value.reduce((s, c) => s + c.value, 0);
    if (!totalCaf) return null;
    return { totalCaf, perPerson: cafData.value };
  });

  async function checkTuleapStatus() {
    try {
      const result = await tuleap.ping();
      tuleapStatus.value = result.ok ? 'connected' : result.status;
      if (result.ok && error.value?.includes('VPN')) error.value = null;
    } catch (e) {
      tuleapStatus.value = 'error';
    }
  }

  async function loadProjectById(projectId) {
    loading.value.projects = true;
    error.value = null;
    try {
      const project = await tuleap.getProject(projectId);
      if (!projects.value.find(p => p.id === project.id)) {
        projects.value = [project, ...projects.value];
      }
      selectedProjectId.value = project.id;
      tuleapStatus.value = 'connected';
    } catch (e) {
      if (e.isVpnError) {
        tuleapStatus.value = 'unreachable';
        error.value = 'Tuleap inaccessible — vérifiez le VPN';
      } else {
        error.value = `Erreur projet : ${e.message}`;
      }
    } finally {
      loading.value.projects = false;
    }
  }

  async function loadProjects() {
    loading.value.projects = true;
    error.value = null;
    try {
      const data = await tuleap.getProjects();
      projects.value = Array.isArray(data) ? data : data.collection || [];
      tuleapStatus.value = 'connected';
    } catch (e) {
      if (e.isVpnError) {
        tuleapStatus.value = 'unreachable';
        error.value = 'Tuleap inaccessible — vérifiez le VPN';
      } else {
        error.value = `Erreur projets : ${e.message}`;
      }
    } finally {
      loading.value.projects = false;
    }
  }

  async function selectProject(projectId) {
    selectedProjectId.value = projectId;
    milestones.value = [];
    selectedMilestoneId.value = null;
    await loadMilestones(projectId);
  }
  async function loadMilestones(projectId) {
    loading.value.milestones = true;
    error.value = null;
    try {
      const data = await tuleap.getMilestones(projectId);
      milestones.value = Array.isArray(data) ? data : data.collection || [];
      const now = new Date().toISOString().split('T')[0];
      const current = milestones.value.find(m => {
        const start = m.start_date?.split('T')[0];
        const end = m.end_date?.split('T')[0];
        return start && end && now >= start && now <= end;
      });
      if (current) {
        await selectMilestone(current.id);
      } else if (milestones.value.length > 0) {
        await selectMilestone(milestones.value[0].id);
      }
    } catch (e) {
      if (e.isVpnError) {
        tuleapStatus.value = 'unreachable';
        error.value = 'Tuleap inaccessible — vérifiez le VPN';
      } else {
        error.value = `Erreur milestones : ${e.message}`;
      }
    } finally {
      loading.value.milestones = false;
    }
  }

  async function selectMilestone(milestoneId) {
    selectedMilestoneId.value = milestoneId;
    stats.value = null;
    burndown.value = null;
    await Promise.all([
      loadStats(milestoneId),
      loadSprintConfig(milestoneId),
      loadCaf(milestoneId),
    ]);
  }

  async function loadStats(milestoneId) {
    loading.value.stats = true;
    try {
      stats.value = await tuleap.getStats(milestoneId);
    } catch (e) {
      if (e.isVpnError) {
        tuleapStatus.value = 'unreachable';
        error.value = 'Tuleap inaccessible — vérifiez le VPN';
      } else {
        error.value = `Erreur statistiques : ${e.message}`;
      }
    } finally {
      loading.value.stats = false;
    }
  }

  async function loadBurndown(milestoneId) {
    loading.value.burndown = true;
    try {
      burndown.value = await tuleap.getBurndown(milestoneId);
    } finally {
      loading.value.burndown = false;
    }
  }

  async function loadSprintConfig(milestoneId) {
    try {
      sprintConfig.value = await local.getSprintConfig(milestoneId);
    } catch (e) {
      console.error('Erreur config sprint', e);
    }
  }

  async function saveSprintConfig(data) {
    if (!selectedMilestoneId.value) return;
    sprintConfig.value = await local.saveSprintConfig(selectedMilestoneId.value, data);
  }

async function loadCaf(milestoneId) {
    try {
      cafData.value = await local.getCaf(milestoneId);
    } catch (e) {
      console.error('Erreur CAF', e);
    }
  }

  async function saveCaf(memberId, value) {
    if (!selectedMilestoneId.value) return;
    await local.saveCaf(selectedMilestoneId.value, memberId, value);
    await loadCaf(selectedMilestoneId.value);
  }

  async function loadMembers() {
    try {
      members.value = await local.getMembers(selectedProjectId.value);
    } catch (e) {
      console.error('Erreur membres', e);
    }
  }

  async function refreshStats() {
    if (selectedMilestoneId.value) {
      await loadStats(selectedMilestoneId.value);
    }
  }

  return {
    projects, selectedProjectId, milestones, selectedMilestoneId,
    members, stats, burndown, sprintConfig, cafData,
    loading, error, tuleapStatus,
    selectedProject, selectedMilestone, theoreticalCapacity,
    loadProjectById, loadProjects, selectProject, loadMilestones, selectMilestone,
    loadStats, loadBurndown, loadSprintConfig, saveSprintConfig,
    loadCaf, saveCaf, loadMembers, refreshStats, checkTuleapStatus,
    theme, setTheme,
  };
});
