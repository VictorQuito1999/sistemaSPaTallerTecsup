<script setup>
import {
  mockBusinessKpis,
  mockGroomerPerformance,
  mockTopServicesWeekly,
  mockWeeklyRevenue,
} from '@/data/mockOperations'

const revenueSpark = computed(() => mockWeeklyRevenue.map(v => Math.min(100, (v / 1500) * 100)))
const servicesSpark = computed(() => mockTopServicesWeekly.map(s => s.count))
</script>

<template>
  <div>
    <VRow class="mb-4">
      <VCol cols="12">
        <h2 class="text-h5 mb-1">
          Reportes
        </h2>
        <p class="text-body-2 text-medium-emphasis mb-0">
          Vista resumida con datos de demostración. Para analítica detallada usa el dashboard principal.
        </p>
      </VCol>
    </VRow>

    <VRow>
      <VCol
        cols="12"
        sm="4"
      >
        <VCard rounded="lg">
          <VCardText>
            <div class="text-overline text-medium-emphasis">
              Ingresos del día
            </div>
            <div class="text-h4 text-primary">
              ${{ mockBusinessKpis.revenueToday.toFixed(2) }}
            </div>
          </VCardText>
        </VCard>
      </VCol>
      <VCol
        cols="12"
        sm="4"
      >
        <VCard rounded="lg">
          <VCardText>
            <div class="text-overline text-medium-emphasis">
              Citas completadas
            </div>
            <div class="text-h4">
              {{ mockBusinessKpis.appointmentsCompleted }}
            </div>
          </VCardText>
        </VCard>
      </VCol>
      <VCol
        cols="12"
        sm="4"
      >
        <VCard rounded="lg">
          <VCardText>
            <div class="text-overline text-medium-emphasis">
              Stock crítico
            </div>
            <div class="text-h4 text-error">
              {{ mockBusinessKpis.criticalStockItems }}
            </div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <VRow class="mt-2">
      <VCol
        cols="12"
        md="6"
      >
        <VCard
          rounded="lg"
          class="pa-4"
        >
          <div class="text-subtitle-2 mb-2">
            Tendencia ingresos (sparkline)
          </div>
          <VSparkline
            :model-value="revenueSpark"
            color="primary"
            line-width="2"
            padding="8"
            smooth
            height="72"
          />
        </VCard>
      </VCol>
      <VCol
        cols="12"
        md="6"
      >
        <VCard
          rounded="lg"
          class="pa-4"
        >
          <div class="text-subtitle-2 mb-2">
            Servicios (sparkline)
          </div>
          <VSparkline
            :model-value="servicesSpark"
            color="teal"
            line-width="2"
            padding="8"
            smooth
            height="72"
          />
        </VCard>
      </VCol>
    </VRow>

    <VCard
      rounded="lg"
      class="mt-4 pa-2"
    >
      <VCardTitle>Rendimiento groomers (mock)</VCardTitle>
      <VDataTable
        :items="mockGroomerPerformance"
        :headers="[
          { title: 'Nombre', key: 'name' },
          { title: 'Citas hechas', key: 'appointmentsDone', align: 'end' },
          { title: 'Insumos (uds.)', key: 'suppliesUsedUnits', align: 'end' },
        ]"
        hide-default-footer
      />
    </VCard>
  </div>
</template>
