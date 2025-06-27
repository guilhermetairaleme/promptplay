<template>
  <div class="w-64 h-screen bg-zinc-900 text-white p-4 flex flex-col">
    <h2 class="text-base font-semibold mb-4 text-gray-300">Chats</h2>
    <ul class="flex-1 overflow-y-auto text-sm space-y-1">
      <li
        v-for="(chat, index) in chats"
        :key="index"
        @click="selected = index"
        :class="[
          'px-3 py-2 rounded hover:bg-zinc-800 cursor-pointer truncate',
          selected === index ? 'bg-zinc-800 text-white' : 'text-gray-300'
        ]"
      >
        {{ chat }}
      </li>
    </ul>
    <button
      class="mt-4 text-xs text-gray-400 hover:text-white transition"
    >
      Exibir planos
    </button>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const chats = ref([
  'Laravel Vue ChatGPT integração',
  'Layout com abas e filtros',
  'Exibir imagem variação produto',
  'Imagem de fundo certificado',
  'Ferramenta de mineração disp...',
  'Ferramenta Chrome Disponível',
  'New chat',
  'Exibir produto sem variação',
  'Gráfico interativo em JS',
  'Erro localStorage sandboxed'
])

const selected = ref(0)
</script>
