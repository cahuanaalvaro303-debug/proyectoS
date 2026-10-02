<script setup>
  import { onMounted } from 'vue';
  import { useAuth } from './composables/useAuth';

  const { restaurarSesion } = useAuth()
  
  onMounted(() => restaurarSesion())
</script>

<template>
  <router-view />
</template>
