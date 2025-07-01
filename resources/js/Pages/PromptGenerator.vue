<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
import { ref, nextTick } from "vue";
</script>

<template>
    <Head title="Prompt Generator" />
    <AuthenticatedLayout>
        <!-- Tudo o que já está dentro do seu <template> atual -->
        <!-- Ex: app-layout, sidebars, conteúdo central, etc -->
        <div class="app-layout">
            <!-- Sidebar Esquerda -->
            <aside class="sidebar">
                <div class="sidebar-header">
                    <span>Histórico</span>
                    <button
                        class="new-chat-btn"
                        @click="novoChat"
                        title="Criar novo chat"
                    >
                        Novo Chat ➕
                    </button>
                </div>
                <ul class="chat-history">
                    <li
                        v-for="(item, index) in chatList"
                        :key="item.id"
                        class="chat-history-item"
                    >
                        <span class="chat-title" @click="loadChat(index)">
                            {{ item.title }}
                        </span>
                        <button
                            class="delete-icon"
                            @click.stop="askDelete(item)"
                            title="Deletar"
                        >
                            🗑️
                        </button>
                    </li>
                </ul>
            </aside>

            <!-- Conteúdo Central -->
            <div class="prompt-generator">
                <h2 class="title">Gerador de Prompt para Vídeos Flow</h2>

                <!-- Piada + Extra -->
                <div class="form-section">
                    <div
                        style="
                            display: flex;
                            justify-content: space-between;
                            align-items: center;
                        "
                    >
                        <label>Digite no chat:</label>
                        <button
                            @click="fetchRandomJoke"
                            type="button"
                            class="link-button"
                        >
                            <span v-if="!jokeGenerated"
                                >🧹✨ Gerar automaticamente</span
                            >
                            <span v-else>🔁 Gerar outra</span>
                        </button>
                    </div>
                    <div
                        v-if="loading"
                        style="color: #000; font-size: 0.9rem; margin-top: 4px"
                    ></div>

                    <div
                        ref="jokeBox"
                        contenteditable="true"
                        class="fake-textarea"
                        :class="{ empty: !selectedJoke }"
                        @input="selectedJoke = $event.target.innerText"
                    ></div>
                </div>

                <!-- Resultado do Prompt -->
                <div v-if="prompt" class="prompt-result-wrapper">
                    <div class="prompt-actions">
                        <h3>Prompt Gerado:</h3>
                        <div class="action-buttons">
                            <div v-if="toastVisible" class="custom-toast">
                                {{ toastMessage }}
                            </div>
                            <button @click="corrigirPrompt">
                                🔧 Corrigir IA
                            </button>
                            <button @click="copyPrompt">📋 Copiar</button>
                            <button @click="startEditing">✏️ Editar</button>
                            <button @click="finalizarPrompt">
                                ✅ Finalizar
                            </button>
                        </div>
                    </div>

                    <div class="prompt-result-scroll">
                        <div v-if="!editing">
                            <pre>{{ prompt }}</pre>
                        </div>

                        <div v-else>
                            <textarea
                                v-model="promptEditado"
                                rows="10"
                                style="width: 100%"
                            ></textarea>
                            <div
                                style="
                                    display: flex;
                                    gap: 10px;
                                    margin-top: 10px;
                                "
                            >
                                <button @click="salvarEdicao">💾 Salvar</button>
                                <button @click="editing = false">
                                    ❌ Cancelar
                                </button>
                            </div>
                        </div>

                        <div v-if="promptFinalizado" class="final-prompt">
                            <div
                                class="prompt-actions"
                                style="
                                    margin-top: 1.2rem;
                                    display: flex;
                                    justify-content: space-between;
                                    align-items: center;
                                "
                            >
                                <h4>Versão Final:</h4>
                                <button
                                    @click="copyPromptFinal"
                                    title="Copiar versão final"
                                    style="
                                        font-size: 0.85rem;
                                        padding: 6px 10px;
                                        border-radius: 4px;
                                        margin: 5px;
                                        background-color: #10b981;
                                        color: white;
                                        border: none;
                                        cursor: pointer;
                                    "
                                >
                                    📋 Copiar
                                </button>
                            </div>
                            <pre>{{ promptFinalizado }}</pre>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar Direita -->
            <aside class="settings-sidebar">
                <h3 style="margin-bottom: 1rem">Configurações</h3>
                <div
                    class="form-section"
                    v-for="(field, key) in fields"
                    :key="key"
                >
                    <label>{{ field.label }}:</label>
                    <select v-model="field.model">
                        <option disabled value="">-- Escolha --</option>
                        <option
                            v-for="opt in field.options"
                            :key="opt"
                            :value="opt"
                        >
                            {{ opt }}
                        </option>
                        <option value="-- manual --">-- Manual --</option>
                    </select>
                    <input
                        v-if="field.model === '-- manual --'"
                        v-model="field.customValue"
                        :placeholder="
                            field.placeholder || 'Digite manualmente...'
                        "
                        style="margin-top: 6px"
                    />
                </div>
                <div class="form-section">
                    <label>Detalhes Extras:</label>
                    <textarea
                        v-model="extraDetails"
                        placeholder="Ex: Incluir reviravolta, usar narração dramática"
                        rows="3"
                    ></textarea>
                </div>
                <button
                    @click="generatePrompt"
                    :disabled="loading || !selectedJoke"
                >
                    🎬 Gerar Prompt
                </button>
            </aside>
        </div>
        <!-- Overlay de Loading -->
        <div v-if="loading" class="loading-overlay">
            <div class="spinner-loader"></div>
        </div>
        <!-- Modal de Confirmação -->
        <div v-if="showConfirmModal" class="modal-overlay">
            <div class="confirm-modal">
                <h3 class="modal-title">Excluir chat?</h3>
                <p>
                    Isso excluirá <strong>{{ deletePendingTitle }}</strong
                    >.
                </p>
                <div class="modal-actions">
                    <button
                        class="btn-cancel"
                        @click="showConfirmModal = false"
                    >
                        Cancelar
                    </button>
                    <button class="btn-danger" @click="confirmDelete">
                        Excluir
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <div
            v-if="showCorrectionModal"
            class="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50"
        >
            <div
                class="bg-white w-full max-w-2xl p-6 rounded shadow-lg relative"
            >
                <h2 class="text-xl font-bold mb-4">
                    🔧 Instrução para corrigir o prompt
                </h2>

                <!-- Prompt atual (somente leitura) -->
                <div class="mb-4">
                    <label class="block mb-1 font-semibold text-sm"
                        >Prompt atual:</label
                    >
                    <div
                        class="bg-gray-100 p-3 border border-gray-300 rounded text-sm whitespace-pre-wrap max-h-60 overflow-auto"
                    >
                        {{ prompt }}
                    </div>
                </div>

                <!-- Instrução do usuário -->
                <div class="mb-4">
                    <label class="block mb-1 font-semibold text-sm"
                        >Escreva como deseja melhorar:</label
                    >
                    <textarea
                        v-model="correctionInstruction"
                        rows="4"
                        class="w-full p-3 border border-gray-300 rounded resize-none"
                        placeholder="Ex: Torne o vídeo mais engraçado, adicione um robô vilão..."
                    ></textarea>
                </div>

                <!-- Ações -->
                <div class="flex justify-end gap-2">
                    <button
                        @click="showCorrectionModal = false"
                        class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400"
                    >
                        Cancelar
                    </button>
                    <button
                        @click="enviarCorrecao"
                        class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700"
                    >
                        Regerar com instrução
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script>
export default {
    data() {
        return {
            chatList: [],
            toastVisible: false,
            toastMessage: "",
            showCorrectionModal: false,
            promptCorrigido: "",
            editingChatId: null,
            showConfirmModal: false,
            deletePendingId: null,
            deletePendingTitle: "",
            selectedJoke: "",
            extraDetails: "",
            loading: false,
            prompt: "",
            promptEditado: "",
            promptFinalizado: "",
            editing: false,
            jokeGenerated: false,
            jokeBox: null,
            fields: {
                video_type: {
                    label: "Tipo de Vídeo",
                    model: "",
                    customValue: "",
                    options: [
                        "História curta",
                        "Vídeo de curiosidade",
                        "Motivacional",
                        "Educativo",
                        "Suspense",
                        "Trailer",
                    ],
                    placeholder: "",
                },
                clima: {
                    label: "Clima",
                    model: "",
                    customValue: "",
                    options: [
                        "Ensolarado",
                        "Chuvoso",
                        "Nublado",
                        "Neblina",
                        "Apocalíptico",
                    ],
                    placeholder: "",
                },
                horario_dia: {
                    label: "Horário do Dia",
                    model: "",
                    customValue: "",
                    options: [
                        "Dia",
                        "Noite",
                        "Entardecer",
                        "Amanhecer",
                        "Madrugada",
                    ],
                    placeholder: "",
                },
                setting: {
                    label: "Ambiente / Cenário",
                    model: "",
                    customValue: "",
                    options: [
                        "Cidade futurista",
                        "Floresta densa",
                        "Deserto",
                        "Praia",
                        "Escola",
                        "Escritório antigo",
                        "Espaço sideral",
                    ],
                    placeholder: "Ex: Rural house with brick walls",
                },
                narration: {
                    label: "Sotaque / Narração",
                    model: "",
                    customValue: "",
                    options: [
                        "Português neutro",
                        "Português nordestino",
                        "Inglês americano",
                        "Espanhol latino",
                        "Voz robótica",
                        "Voz feminina calma",
                        "Voz masculina intensa",
                    ],
                    placeholder: "",
                },
                characters: {
                    label: "Tipo de Personagem Principal",
                    model: "",
                    customValue: "",
                    options: [
                        "Jovem corajoso",
                        "Idoso sábio",
                        "Robô amigável",
                        "Guerreira futurista",
                        "Criança curiosa",
                    ],
                    placeholder: "Ex: Elderly couple",
                },
                secondary_characters: {
                    label: "Personagens Secundários",
                    model: "",
                    customValue: "",
                    options: [
                        "Vilão misterioso",
                        "Animal falante",
                        "Grupo de amigos",
                        "Alienígena",
                        "IA rebelde",
                    ],
                    placeholder: "",
                },
                visual_style: {
                    label: "Estilo Visual do Vídeo",
                    model: "",
                    customValue: "",
                    options: [
                        "Realista",
                        "Desenho animado",
                        "3D Pixar",
                        "Cinematográfico",
                        "Estilo anime",
                        "Cyberpunk",
                    ],
                    placeholder: "",
                },
                subject: {
                    label: "Tema Principal / Assunto",
                    model: "",
                    customValue: "",
                    options: [],
                    placeholder: "Ex: “Como surgiu o universo?”",
                },
                objective: {
                    label: "Objetivo do Vídeo",
                    model: "",
                    customValue: "",
                    options: [
                        "Entreter",
                        "Ensinar",
                        "Vender",
                        "Gerar curiosidade",
                        "Inspirar",
                        "Motivar",
                    ],
                    placeholder: "",
                },
            },
        };
    },
    methods: {
        showToast(msg) {
            this.toastMessage = msg;
            this.toastVisible = true;
            setTimeout(() => {
                this.toastVisible = false;
            }, 3000);
        },
        askDelete(item) {
            this.deletePendingId = item.id;
            this.deletePendingTitle = item.title;
            this.showConfirmModal = true;
        },
        corrigirPrompt() {
            this.promptCorrigido = this.prompt;
            this.showCorrectionModal = true;
        },
        corrigirPrompt() {
            this.correctionInstruction = "";
            this.showCorrectionModal = true;
        },
        async enviarCorrecao() {
            if (!this.correctionInstruction.trim()) {
                this.showToast("Digite uma instrução para corrigir.");
                return;
            }

            this.loading = true;
            try {
                const res = await fetch("/corrigir-prompt", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document
                            .querySelector('meta[name="csrf-token"]')
                            .getAttribute("content"),
                    },
                    body: JSON.stringify({
                        prompt_base: this.prompt,
                        instrucao: this.correctionInstruction,
                    }),
                });

                const data = await res.json();
                this.prompt = data.prompt_corrigido;
                this.showCorrectionModal = false;
                this.showToast("✅ Prompt corrigido com sucesso!");
            } catch (error) {
                console.error("Erro ao corrigir prompt:", error);
                this.showToast("❌ Erro ao corrigir prompt.");
            } finally {
                this.loading = false;
            }
        },
        async confirmDelete() {
            try {
                const res = await fetch(
                    `/api/chat-history/${this.deletePendingId}`,
                    {
                        method: "DELETE",
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": document
                                .querySelector('meta[name="csrf-token"]')
                                .getAttribute("content"),
                        },
                    }
                );
                if (res.ok) {
                    this.chatList = this.chatList.filter(
                        (c) => c.id !== this.deletePendingId
                    );
                    this.showConfirmModal = false;
                }
            } catch (err) {
                console.error("Erro ao deletar:", err);
            }
        },
        novoChat() {
            this.selectedJoke = "";
            this.extraDetails = "";
            this.prompt = "";
            this.promptEditado = "";
            this.promptFinalizado = "";
            this.editing = false;
            this.jokeGenerated = false;
            this.editingChatId = null; // <- ESSENCIAL: garante que será um POST novo!

            for (const key in this.fields) {
                this.fields[key].model = "";
                this.fields[key].customValue = "";
            }

            if (this.$refs.jokeBox) {
                this.$refs.jokeBox.innerText = "";
            }
        },
        loadChat(index) {
            const item = this.chatList[index];

            this.editingChatId = item.id; // <--- ESSENCIAL!

            this.selectedJoke = item.joke || "";

            if (item.fields) {
                for (const key in this.fields) {
                    if (item.fields[key]) {
                        const savedField = item.fields[key];
                        this.fields[key].model = savedField.model || "";
                        this.fields[key].customValue =
                            savedField.customValue || "";

                        if (
                            savedField.model &&
                            !this.fields[key].options.includes(savedField.model)
                        ) {
                            this.fields[key].options.push(savedField.model);
                        }
                    }
                }
            }

            this.extraDetails = item.extra || "";
            this.prompt = item.prompt || "";
            this.promptFinalizado = item.final_prompt || "";
            this.editing = false;

            this.$nextTick(() => {
                if (this.$refs.jokeBox) {
                    this.$refs.jokeBox.innerText = this.selectedJoke;
                }
            });
        },
        async loadChatHistory() {
            try {
                const res = await fetch("/chats");
                const data = await res.json();
                this.chatList = data;
            } catch (error) {
                console.error("Erro ao carregar histórico:", error);
            }
        },
        copyPromptFinal() {
            navigator.clipboard.writeText(this.promptFinalizado).then(() => {
                this.showToast("📋 Versão final copiada!");
            });
        },
        copyPrompt() {
            navigator.clipboard.writeText(this.prompt).then(() => {
                this.showToast("📋 Copiado com sucesso!");
            });
        },
        startEditing() {
            this.promptEditado = this.prompt;
            this.editing = true;
        },
        salvarEdicao() {
            this.prompt = this.promptEditado;
            this.editing = false;
        },
        async finalizarPrompt() {
            this.loading = true;

            // ✅ Limpa a versão final anterior antes de gerar um novo
            this.promptFinalizado = "";

            try {
                // Etapa 1: Gera o novo prompt finalizado
                const res = await fetch("/api/finalize-prompt", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document
                            .querySelector('meta[name="csrf-token"]')
                            .getAttribute("content"),
                    },
                    body: JSON.stringify({ prompt: this.prompt }),
                });

                const data = await res.json();

                // Atualiza com o novo prompt final
                this.promptFinalizado = data.prompt_en;

                // Etapa 2: Prepara dados do chat
                const isEditing = !!this.editingChatId;
                const url = isEditing
                    ? `/chats/${this.editingChatId}`
                    : "/chats";
                const method = isEditing ? "PUT" : "POST";

                const chatData = {
                    title: this.selectedJoke
                        ? this.selectedJoke.substring(0, 50) + "..."
                        : "Sem título",
                    joke: this.selectedJoke,
                    fields: this.fields,
                    extra: this.extraDetails,
                    prompt: this.prompt,
                    final_prompt: this.promptFinalizado, // agora é o novo
                };

                await fetch(url, {
                    method,
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document
                            .querySelector('meta[name="csrf-token"]')
                            .getAttribute("content"),
                    },
                    body: JSON.stringify(chatData),
                });

                await this.loadChatHistory();
            } catch (e) {
                console.error("Erro ao finalizar ou salvar:", e);
                this.promptFinalizado = "Erro ao gerar a versão final.";
            } finally {
                this.loading = false;
            }
        },
        async deleteChat(id) {
            try {
                const response = await fetch(`/api/chat-history/${id}`, {
                    method: "DELETE",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document
                            .querySelector('meta[name="csrf-token"]')
                            .getAttribute("content"),
                    },
                });

                if (response.ok) {
                    // Remove do array local após sucesso
                    this.chatList = this.chatList.filter(
                        (item) => item.id !== id
                    );
                } else {
                    console.error("Erro ao excluir o item do histórico.");
                }
            } catch (error) {
                console.error("Erro na requisição DELETE:", error);
            }
        },
        async fetchRandomJoke() {
            this.loading = true;

            const baseJoke = this.selectedJoke?.trim();

            try {
                const res = await fetch("/api/generate-joke", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document
                            .querySelector('meta[name="csrf-token"]')
                            .getAttribute("content"),
                    },
                    body: JSON.stringify({
                        prompt: baseJoke || "", // Envia a piada se existir, senão string vazia
                    }),
                });

                const data = await res.json();

                // Só substitui se foi gerado automaticamente
                if (!baseJoke) {
                    this.selectedJoke =
                        data.joke || "Não foi possível gerar uma piada.";

                    // Força a atualização visual do contenteditable
                    this.$nextTick(() => {
                        if (this.$refs.jokeBox) {
                            this.$refs.jokeBox.innerText = this.selectedJoke;
                        }
                    });
                }

                // Preenche os campos dinâmicos com base no retorno da API
                for (const key in this.fields) {
                    if (data[key]) {
                        if (!this.fields[key].options.includes(data[key])) {
                            this.fields[key].options.push(data[key]);
                        }
                        this.fields[key].model = data[key];
                    }
                }

                if (data.extra) this.extraDetails = data.extra;

                this.jokeGenerated = true;
            } catch (error) {
                console.error("Erro ao buscar piada:", error);
                this.selectedJoke = "Erro ao buscar piada.";
            } finally {
                this.loading = false;
            }
        },
        async generatePrompt() {
            this.loading = true;
            this.prompt = "";
            this.promptFinalizado = "";

            // Se estiver editando, limpa o final_prompt no backend
            if (this.editingChatId) {
                await fetch(`/api/chats/${this.editingChatId}/clear-final`, {
                    method: "PATCH",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document
                            .querySelector('meta[name="csrf-token"]')
                            .getAttribute("content"),
                    },
                });
            }

            // Monta o corpo da requisição para gerar novo prompt
            const body = {
                joke: this.selectedJoke,
                extra: this.extraDetails,
            };

            for (const key in this.fields) {
                const field = this.fields[key];
                body[key] =
                    field.model === "-- manual --"
                        ? field.customValue
                        : field.model;
            }

            // Chama a API para gerar prompt
            const res = await fetch("/api/generate-prompt", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document
                        .querySelector('meta[name="csrf-token"]')
                        .getAttribute("content"),
                },
                body: JSON.stringify(body),
            });

            const data = await res.json();
            this.prompt = data.prompt;

            // Atualiza o chat no histórico com o novo prompt (sem final_prompt ainda)
            if (this.editingChatId) {
                const updateChat = {
                    title: this.selectedJoke
                        ? this.selectedJoke.substring(0, 50) + "..."
                        : "Sem título",
                    joke: this.selectedJoke,
                    fields: this.fields,
                    extra: this.extraDetails,
                    prompt: this.prompt,
                    final_prompt: null,
                };

                await fetch(`/chats/${this.editingChatId}`, {
                    method: "PUT",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document
                            .querySelector('meta[name="csrf-token"]')
                            .getAttribute("content"),
                    },
                    body: JSON.stringify(updateChat),
                });

                await this.loadChatHistory();
            }

            this.loading = false;
        },
    },
    mounted() {
        this.loadChatHistory();
    },
};
</script>

<style scoped>
.fake-textarea {
    position: relative;
    min-height: 100px;
    border: 1px solid #ccc;
    padding: 10px;
    font-size: 16px;
    border-radius: 8px;
    background-color: white;
    color: #333;
    overflow: auto;
}

.fake-textarea.empty::before {
    content: "Digite algo...";
    position: absolute;
    left: 12px;
    top: 10px;
    color: #aaa;
    pointer-events: none;
}
.fake-textarea:focus {
    outline: none;
    border-color: #10b981;
    background: #fff;
}

.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(20, 20, 20, 0.8);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}

.spinner-loader {
    width: 60px;
    height: 60px;
    border: 6px solid #ccc;
    border-top: 6px solid #10a37f;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% {
        transform: rotate(0deg);
    }
    100% {
        transform: rotate(360deg);
    }
}

.chat-history-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 2px 3px;
    margin-bottom: 6px;
    background-color: #2e2f37;
    border-radius: 6px;
    cursor: pointer;
    transition: background 0.2s;
}

.chat-history-item:hover {
    background-color: #3a3b46;
}

.chat-title {
    color: #eaeaea;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    flex: 1;
}

.delete-icon {
    background: none;
    border: none;
    color: #ef4444;
    font-size: 12px;
    cursor: pointer;
    margin-left: 12px;
}

.delete-icon:hover {
    color: #dc2626;
}

.prompt-result-wrapper {
    background-color: #e5e7eb;
    border-radius: 6px;
    padding: 1rem;
    margin-top: 2rem;
    max-height: 600px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.prompt-result-scroll {
    overflow-y: auto;
    max-height: 600px;
    padding-right: 10px;
}

.final-prompt pre {
    background-color: #f3f4f6;
    padding: 1rem;
    border-radius: 6px;
    border: 1px solid #ccc;
}

.app-layout {
    display: flex;
    min-height: 100vh;
}
.sidebar {
    width: 260px;
    background-color: #1e1f25;
    color: #fff;
    padding: 1rem;
    border-right: 1px solid #444;
    overflow-y: auto;
    border: 2px solid #fff;
}
.sidebar-header {
    font-weight: bold;
    font-size: 1rem;
    margin-bottom: 1rem;
}
.chat-history {
    list-style: none;
    padding: 0;
    margin: 0;
}
.chat-history li {
    padding: 8px 10px;
    margin-bottom: 6px;
    background-color: #2e2f37;
    border-radius: 6px;
    cursor: pointer;
    transition: background 0.2s;
}
.chat-history li:hover {
    background-color: #444857;
}
.chat-title {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    display: block;
    color: #eaeaea;
}
.title {
    font-size: 1.5rem;
    margin-bottom: 1rem;
    font-weight: bold;
}

.form-section {
    margin-bottom: 1rem;
}

input,
select,
textarea {
    width: 100%;
    padding: 0.5rem;
    margin-top: 0.25rem;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-family: inherit;
    resize: vertical;
}

button {
    padding: 0.75rem 1.5rem;
    background-color: #10a37f;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
}

button:disabled {
    background-color: #ccc;
    cursor: not-allowed;
}

.prompt-result {
    margin-top: 2rem;
    background-color: #e5e7eb;
    padding: 1rem;
    border-radius: 6px;
}

pre {
    white-space: pre-wrap;
    word-wrap: break-word;
}

.spinner {
    animation: pulse 1s infinite;
}

@keyframes pulse {
    0% {
        opacity: 0.3;
    }
    50% {
        opacity: 1;
    }
    100% {
        opacity: 0.3;
    }
}
.custom-toast {
    position: fixed;
    bottom: 20px;
    right: 20px;
    background-color: #10b981; /* green success */
    color: white;
    padding: 12px 20px;
    border-radius: 6px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
    font-size: 14px;
    z-index: 9999;
    animation: fade-in-out 3s forwards;
}

@keyframes fade-in-out {
    0% {
        opacity: 0;
        transform: translateY(10px);
    }
    10% {
        opacity: 1;
        transform: translateY(0);
    }
    90% {
        opacity: 1;
    }
    100% {
        opacity: 0;
        transform: translateY(10px);
    }
}

.prompt-generator {
    flex: 1;
    padding: 2rem;
    background-color: #ffffff;
    font-family: "Segoe UI", sans-serif;
    min-height: 100vh;
    overflow-y: auto;
}
.settings-sidebar {
    width: 280px;
    background-color: #f8f9fa;
    color: #333;
    padding: 1.5rem;
    border-left: 1px solid #ddd;
    overflow-y: auto;
}

.link-button {
    background: none;
    border: none;
    color: #10a37f;
    cursor: pointer;
    font-weight: bold;
    display: flex;
    align-items: center;
}

.prompt-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.action-buttons button {
    background-color: #10a37f;
    color: white;
    border: none;
    padding: 6px 12px;
    border-radius: 4px;
    cursor: pointer;
    margin: 2px;
}

.final-prompt {
    margin-top: 1rem;
    background: #e1f0ff;
    padding: 10px;
    border-radius: 6px;
}
.sidebar-header {
    font-weight: bold;
    font-size: 1rem;
    margin-bottom: 1rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.new-chat-btn {
    background: none;
    border: none;
    color: #a522fd;
    font-size: 18px;
    cursor: pointer;
    padding: 0;
}

.new-chat-btn:hover {
    color: #22c55e;
}

.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.7);
    z-index: 9999;
    display: flex;
    justify-content: center;
    align-items: center;
}

.confirm-modal {
    background: #1e1f25;
    color: #f1f1f1;
    border-radius: 12px;
    padding: 24px;
    width: 400px;
    max-width: 90%;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
}

.modal-title {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 10px;
}

.modal-subtext {
    font-size: 0.875rem;
    margin-top: 10px;
    color: #aaa;
}

.modal-subtext a {
    color: #63b3ed;
    text-decoration: underline;
}

.modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 20px;
}

.btn-cancel {
    background: transparent;
    border: 1px solid #444;
    color: #fff;
    padding: 6px 14px;
    border-radius: 8px;
    cursor: pointer;
}

.btn-danger {
    background: #dc3545;
    color: #fff;
    border: none;
    padding: 8px 14px;
    border-radius: 8px;
    cursor: pointer;
}
</style>
