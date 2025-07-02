<!-- resources/js/Pages/PromptGeneratorUser.vue -->
<template>
  <div class="p-4">
    <h1 class="text-2xl font-bold"></h1>
    <!-- Aqui pode reutilizar seu componente PromptGenerator -->
    <PromptGenerator :admin-name="adminName" />
  </div>
</template>

<script setup>


import PromptGenerator from './PromptGenerator.vue'; // ou o caminho correto
</script>
