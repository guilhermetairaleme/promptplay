<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 text-center">
                        <p class="mb-6">Você está logado!</p>

                        <a
                            href="/prompt-generator"
                            class="relative inline-flex items-center justify-center px-8 py-4 overflow-hidden font-bold text-white transition duration-300 ease-out border-2 border-indigo-600 rounded-full shadow-md group hover:shadow-lg hover:bg-indigo-600"
                        >
                            <span class="absolute inset-0 w-full h-full bg-gradient-to-br from-indigo-500 via-purple-500 to-pink-500 opacity-70 group-hover:opacity-100 blur-sm animate-pulse"></span>
                            <span class="relative z-10">🚀 Acessar Prompt Gerador</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
