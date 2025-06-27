<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="min-h-screen flex flex-col justify-center items-center bg-gray-100 px-4 relative">
        <!-- Overlay de linhas e brilho -->
        <div class="absolute top-0 left-0 w-full h-full pointer-events-none overflow-hidden z-10">
            <div class="scanlines"></div>
        </div>

        <!-- Logo com animação -->
        <div class="mb-[-100px] relative z-20">
            <Link href="/">
                <img
                    src="/imagem/promptplay.png"
                    alt="Logo"
                    class="mx-auto cinematic-logo"
                    style="width: 300px;"
                />
            </Link>
        </div>

        <!-- Formulário -->
        <div class="w-full sm:max-w-md px-6 py-6 bg-white shadow-md overflow-hidden sm:rounded-lg relative z-20">
            <slot />
        </div>
    </div>
</template>

<style scoped>
@keyframes pulseRotate {
  0%, 100% {
    transform: scale(1) rotate(0deg);
    filter: brightness(1);
  }
  50% {
    transform: scale(1.05) rotate(2deg);
    filter: brightness(1.2);
  }
}

.cinematic-logo {
  animation: pulseRotate 4s ease-in-out infinite;
  transition: transform 0.3s ease;
  filter: drop-shadow(0 0 10px rgba(255, 255, 255, 0.2));
}

/* Scanline overlay */
.scanlines {
  position: absolute;
  width: 100%;
  height: 100%;
  background-image: repeating-linear-gradient(
    to bottom,
    rgba(255, 255, 255, 0.02),
    rgba(255, 255, 255, 0.02) 1px,
    transparent 1px,
    transparent 4px
  );
  animation: flicker 1.5s infinite alternate;
  pointer-events: none;
  z-index: 1;
}

/* Flicker effect */
@keyframes flicker {
  0% {
    opacity: 0.05;
  }
  100% {
    opacity: 0.1;
  }
}
</style>
