<template>
  <VContainer class="fill-height d-flex align-center py-16">
    <VRow justify="center">
      <VCol
        cols="12"
        md="8"
        lg="6"
      >
        <VCard class="pa-8">
          <VCardTitle class="text-h4 font-weight-bold mb-2">
            Bienvenido a PetSpa V1
          </VCardTitle>
          <VCardSubtitle class="text-body-1 mb-6">
            Gestiona reservas, servicios y experiencia de clientes en un solo lugar.
          </VCardSubtitle>
          <VCardText class="px-0">
            <div class="d-flex flex-wrap ga-4 align-center">
              <VBtn
                color="primary"
                size="large"
                to="/cliente/login"
              >
                Soy cliente — Entrar
              </VBtn>
              <VBtn
                color="primary"
                variant="tonal"
                size="large"
                to="/register"
              >
                Registrarse (cliente)
              </VBtn>
              <VBtn
                variant="text"
                color="primary"
                size="large"
                to="/login"
              >
                Acceso personal (empleados)
              </VBtn>
            </div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </VContainer>
</template>
