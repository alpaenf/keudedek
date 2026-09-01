<script setup>
import { computed } from 'vue';
import AppLayout from '../Layouts/AppLayout.vue';
import GlobalFilterBar from '../Components/GlobalFilterBar.vue';

// Role Dashboards
import PtkDashboard from './Dashboard/PtkDashboard.vue';
import KajurDashboard from './Dashboard/KajurDashboard.vue';
import KaprodiDashboard from './Dashboard/KaprodiDashboard.vue';
import PtuDashboard from './Dashboard/PtuDashboard.vue';
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
  verificationQueue: Array,
  highRiskSubmissions: Array,
  attentionBuckets: Array,
  adminMetrics: Object,
  departments: Array,
  fundingSources: Array,
  selectedDepartmentId: [String, Number],
  activeFiscalYear: [String, Number],
  departmentName: String,
  prodiName: String,
  revisionNumber: String,
  fundSourceCode: String,
  thisMonthCount: Number,
  thisMonthAmount: Number,
  returnedSubmissions: Array,
  processingSubmissions: Array,
  avgReviewDays: Number,
  targetSlaDays: Number,
  attentionItemsCount: Number,
});

const currentRole = computed(() => {
  if (props.userRole === 'WD') return 'WAKIL_DEKAN';
  return props.userRole || 'PTK';
});

const dashboardTitle = computed(() => {
  if (currentRole.value === 'PTU' || currentRole.value === 'BENDAHARA') {
    return 'Dashboard PTU / Bendahara';
  }
  return `Dashboard ${currentRole.value}`;
});
</script>

<template>
  <AppLayout :title="dashboardTitle">
    <!-- Single Clean Global Filter Bar (Hidden for PTK as PTK uses dedicated Context Bar) -->
    <GlobalFilterBar 
      v-if="currentRole !== 'PTK'"
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
      :departmentName="departmentName"
      :activeFiscalYear="activeFiscalYear"
      :revisionNumber="revisionNumber"
      :fundSourceCode="fundSourceCode"
      :thisMonthCount="thisMonthCount"
      :thisMonthAmount="thisMonthAmount"
      :returnedSubmissions="returnedSubmissions"
      :processingSubmissions="processingSubmissions"
      :monthlyTrend="monthlyTrend"
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

    <KaprodiDashboard 
      v-else-if="currentRole === 'KAPRODI'"
      :totalRealized="totalRealized"
      :totalReserved="totalReserved"
      :statusCounts="statusCounts"
      :recentSubmissions="recentSubmissions"
      :activeWarnings="activeWarnings"
      :prodiName="prodiName"
      :thisMonthCount="thisMonthCount"
      :thisMonthAmount="thisMonthAmount"
      :monthlyTrend="monthlyTrend"
    />

    <PtuDashboard 
      v-else-if="currentRole === 'PTU' || currentRole === 'BENDAHARA'"
      :verificationQueue="verificationQueue"
      :statusCounts="statusCounts"
      :activeWarningsCount="activeWarningsCount"
      :agingDistribution="agingDistribution"
      :avgReviewDays="avgReviewDays"
      :targetSlaDays="targetSlaDays"
      :attentionItemsCount="attentionItemsCount"
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
