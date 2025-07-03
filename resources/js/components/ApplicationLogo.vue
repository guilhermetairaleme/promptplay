<script setup>
import { ref, onMounted } from "vue";

const subdomain = ref("promptplay"); // valor padrão, caso não haja subdomínio

onMounted(() => {
  const host = window.location.hostname;
  const parts = host.split(".");

  if (parts.length >= 2) {
    subdomain.value = parts[0];
  }
});
</script>
<template>
  <img
    :src="`/imagem/${subdomain}.promptplay.png`"
    alt="Logo"
    style="width: 150px; height: auto;"
    class="mx-auto transition-transform duration-300 hover:scale-105"
  />
</template>

