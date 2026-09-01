<script setup>
import { computed } from 'vue';
import AppLayout from '../Layouts/AppLayout.vue';
import GlobalFilterBar from '../Components/GlobalFilterBar.vue';

// Role Dashboards
import PtkDashboard from './Dashboard/PtkDashboard.vue';
import KajurDashboard from './Dashboard/KajurDashboard.vue';
import PtuDashboard from './Dashboard/PtuDashboard.vue';
import KetuaPtkDashboard from './Dashboard/KetuaPtkDashboard.vue';
import KabagDashboard from './Dashboard/KabagDashboard.vue';
import WdDashboard from './Dashboard/WdDashboard.vue';
import DekanDashboard from './Dashboard/DekanDashboard.vue';
import AdminDashboard from './Dashboard/AdminDashboard.vue';

const props = defineProps({
  userRole: String,
  scopeLabel: String,
  totalAllocated: Number,
  totalReserved: Number,
  totalRealized: Number,
  totalAvailable: Number,
  realizationRate: Number,
  serapanRate: Number,
  utilizationRate: Number,
  availableRate: Number,
  activeWarningsCount: Number,
  criticalWarningsCount: Number,
  warningSeverityCounts: Object,
  statusCounts: Object,
  recentSubmissions: Array,
  activeWarnings: Array,
  departmentSummaries: Array,
  monthlyTrend: Object,
  agingDistribution: Object,
  ptkWorkload: Array,
  verificationQueue: Array,
  highRiskSubmissions: Array,
  attentionBuckets: Array,
  adminMetrics: Object,
  departments: Array,
  fundingSources: Array,
  selectedDepartmentId: [String, Number],
  activeFiscalYear: [String, Number],
});

const currentRole = computed(() => {
  if (props.userRole === 'WD') return 'WAKIL_DEKAN';
  return props.userRole || 'PTK';
});
</script>

<template>
  <AppLayout :title="`Dashboard ${currentRole}`">
    <!-- Single Clean Global Filter Bar -->
    <GlobalFilterBar 
      :departments="departments"
      :fundingSources="fundingSources"
      :selectedDepartmentId="selectedDepartmentId"
      :activeFiscalYear="activeFiscalYear"
      :userRole="currentRole"
    />

    <!-- Role-Specific Views Dynamic Switching -->
    <PtkDashboard 
      v-if="currentRole === 'PTK'"
      :totalAllocated="totalAllocated"
      :totalReserved="totalReserved"
      :totalRealized="totalRealized"
      :totalAvailable="totalAvailable"
      :serapanRate="serapanRate"
      :utilizationRate="utilizationRate"
      :availableRate="availableRate"
      :statusCounts="statusCounts"
      :recentSubmissions="recentSubmissions"
      :activeWarnings="activeWarnings"
      :attentionBuckets="attentionBuckets"
    />

    <KajurDashboard 
      v-else-if="currentRole === 'KAJUR'"
      :totalAllocated="totalAllocated"
      :totalReserved="totalReserved"
      :totalRealized="totalRealized"
      :totalAvailable="totalAvailable"
      :serapanRate="serapanRate"
      :utilizationRate="utilizationRate"
      :availableRate="availableRate"
      :statusCounts="statusCounts"
      :recentSubmissions="recentSubmissions"
      :activeWarnings="activeWarnings"
      :activeWarningsCount="activeWarningsCount"
      :attentionBuckets="attentionBuckets"
      :monthlyTrend="monthlyTrend"
    />

    <PtuDashboard 
      v-else-if="currentRole === 'PTU'"
      :verificationQueue="verificationQueue"
      :highRiskSubmissions="highRiskSubmissions"
      :statusCounts="statusCounts"
      :activeWarningsCount="activeWarningsCount"
      :agingDistribution="agingDistribution"
    />

    <KetuaPtkDashboard 
      v-else-if="currentRole === 'KETUA_PTK'"
      :ptkWorkload="ptkWorkload"
      :statusCounts="statusCounts"
      :agingDistribution="agingDistribution"
      :recentSubmissions="recentSubmissions"
    />

    <KabagDashboard 
      v-else-if="currentRole === 'KABAG'"
      :totalAllocated="totalAllocated"
      :totalReserved="totalReserved"
      :totalRealized="totalRealized"
      :totalAvailable="totalAvailable"
      :serapanRate="serapanRate"
      :utilizationRate="utilizationRate"
      :availableRate="availableRate"
      :statusCounts="statusCounts"
      :departmentSummaries="departmentSummaries"
      :activeWarnings="activeWarnings"
      :activeWarningsCount="activeWarningsCount"
      :criticalWarningsCount="criticalWarningsCount"
      :warningSeverityCounts="warningSeverityCounts"
      :monthlyTrend="monthlyTrend"
      :verificationQueue="verificationQueue"
    />

    <WdDashboard 
      v-else-if="currentRole === 'WAKIL_DEKAN'"
      :totalAllocated="totalAllocated"
      :totalReserved="totalReserved"
      :totalRealized="totalRealized"
      :totalAvailable="totalAvailable"
      :serapanRate="serapanRate"
      :utilizationRate="utilizationRate"
      :availableRate="availableRate"
      :departmentSummaries="departmentSummaries"
      :activeWarnings="activeWarnings"
      :criticalWarningsCount="criticalWarningsCount"
      :verificationQueue="verificationQueue"
      :monthlyTrend="monthlyTrend"
    />

    <DekanDashboard 
      v-else-if="currentRole === 'DEKAN'"
      :totalAllocated="totalAllocated"
      :totalReserved="totalReserved"
      :totalRealized="totalRealized"
      :totalAvailable="totalAvailable"
      :serapanRate="serapanRate"
      :utilizationRate="utilizationRate"
      :availableRate="availableRate"
      :departmentSummaries="departmentSummaries"
      :activeWarnings="activeWarnings"
      :criticalWarningsCount="criticalWarningsCount"
      :monthlyTrend="monthlyTrend"
      :verificationQueue="verificationQueue"
    />

    <AdminDashboard 
      v-else-if="currentRole === 'ADMIN'"
      :adminMetrics="adminMetrics"
      :statusCounts="statusCounts"
      :departmentSummaries="departmentSummaries"
    />
  </AppLayout>
</template>
