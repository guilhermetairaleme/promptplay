<template>
    <div class="chat-wrapper">
        <!-- Sidebar com histórico -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>MaximoIA Chat</h2>
            </div>
            <button @click="startNewChat" class="new-chat-button">
                + Nova conversa
            </button>
            <ul class="chat-history">
                <li
                    v-for="(chat, index) in history"
                    :key="index"
                    @click="loadChat(index)"
                    :class="{ active: selectedChat === index }"
                >
                    {{ chat.title || "Conversa " + (index + 1) }}
                </li>
            </ul>
        </aside>

        <!-- Área principal do chat -->
        <main class="chat-area">
          <h2 class="text-lg font-semibold text-white mb-4 py-4" style="margin: 20px;">
            {{ history[selectedChat]?.title || "" }}
          </h2>

            <div class="chat-messages">
                <div
                    v-for="(msg, index) in currentChat"
                    :key="index"
                    :class="['message', msg.role]"
                >
                    <div class="role">
                        {{ msg.role === "user" ? "Você" : "MaximoIA" }}
                    </div>
                    <div class="content">{{ msg.content }}</div>
                </div>

                <!-- Mostrar "Carregando..." após as mensagens -->
                <div v-if="isLoading" class="message assistant loading">
                    <div class="role">MaximoIA</div>
                    <div class="content">
                        Carregando<span class="dots">...</span>
                    </div>
                </div>
            </div>

            <form @submit.prevent="sendMessage" class="chat-input">
                <input
                    v-model="userInput"
                    placeholder="Digite sua mensagem..."
                    autofocus
                />
                <button type="submit">Enviar</button>
            </form>
        </main>
    </div>
</template>

<script>
export default {
    data() {
        return {
            userInput: "",
            history: [],
            currentChat: [],
            selectedChat: null,
            isLoading: false, // ✅ estado de carregamento
        };
    },
    methods: {
        async sendMessage() {
            if (!this.userInput.trim()) return;

            const message = this.userInput;
            this.currentChat.push({ role: "user", content: message });
            this.userInput = "";

            const isFirstMessage =
                this.currentChat.length === 1 && this.selectedChat === null;

            this.isLoading = true; // ✅ ativa carregando

            const res = await fetch("/api/chatgpt", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                },
                body: JSON.stringify({ message }),
            });

            const data = await res.json();

            this.currentChat.push({
                role: "assistant",
                content: data.response,
            });

            this.isLoading = false; // ✅ desativa carregando

            // Se era o primeiro envio, criar o item no histórico
            if (isFirstMessage) {
                const title = message.slice(0, 30);

                this.history.push({
                    title,
                    messages: [...this.currentChat],
                });

                this.selectedChat = this.history.length - 1;

                // Salvar no banco
                fetch("/chat/save", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document
                            .querySelector('meta[name="csrf-token"]')
                            .getAttribute("content"),
                    },
                    body: JSON.stringify({
                        title,
                        messages: this.currentChat,
                    }),
                });
            } else if (this.selectedChat !== null) {
                // Atualizar histórico local (sem duplicar)
                this.history[this.selectedChat].messages = [
                    ...this.currentChat,
                ];
            }
        },

        loadChat(index) {
            this.selectedChat = index;
            this.currentChat = this.history[index].messages.map((m) => ({
                ...m,
            }));
        },
        startNewChat() {
            if (!this.currentChat.length) {
                this.currentChat = [];
                this.selectedChat = null;
                return;
            }

            const title = this.currentChat[0].content.slice(0, 30);

            // Verifica se já existe no histórico
            const alreadySaved = this.history.some(
                (chat) =>
                    chat.title === title &&
                    JSON.stringify(chat.messages) ===
                        JSON.stringify(this.currentChat)
            );

            if (!alreadySaved) {
                this.history.push({
                    title,
                    messages: [...this.currentChat],
                });

                this.selectedChat = this.history.length - 1;

                fetch("/chat/save", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document
                            .querySelector('meta[name="csrf-token"]')
                            .getAttribute("content"),
                    },
                    body: JSON.stringify({
                        title,
                        messages: this.currentChat,
                    }),
                });
            }

            // Começar nova conversa limpa
            this.currentChat = [];
            this.selectedChat = null;
        },

        saveCurrentToHistory() {
            if (!this.currentChat.length) return;

            const title = this.currentChat[0].content.slice(0, 30);

            this.history.push({
                title,
                messages: [...this.currentChat],
            });

            this.selectedChat = this.history.length - 1;

            // Salvar no banco
            fetch("/chat/save", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document
                        .querySelector('meta[name="csrf-token"]')
                        .getAttribute("content"),
                },
                body: JSON.stringify({
                    title,
                    messages: this.currentChat,
                }),
            });

            this.currentChat = [];
        },
    },
    mounted() {
        fetch("/chat/history")
            .then((res) => res.json())
            .then((data) => {
                this.history = data;
                if (data.length > 0) {
                    this.loadChat(0); // carrega o primeiro histórico ao iniciar
                }
            });
    },
};
</script>

<style scoped>
.chat-wrapper {
    display: flex;
    height: 100vh;
    font-family: "Segoe UI", sans-serif;
    background-color: #343541;
    color: #ffffff;
}

.sidebar {
    width: 260px;
    background-color: #202123;
    border-right: 1px solid #444;
    padding: 1rem;
    overflow-y: auto;
}

.sidebar-header {
    text-align: center;
    padding-bottom: 1rem;
    border-bottom: 1px solid #444;
    margin-bottom: 1rem;
}

.chat-history li {
    padding: 0.5rem;
    border-bottom: 1px solid #444;
    color: #ccc;
    list-style: none;
    cursor: pointer;
}

.chat-history li.active,
.chat-history li:hover {
    background-color: #2c2d30;
    color: #fff;
}

.chat-area {
    flex: 1;
    display: flex;
    flex-direction: column;
    background-color: #343541;
}

.chat-messages {
    flex: 1;
    padding: 1.5rem;
    overflow-y: auto;
}

.message {
    margin-bottom: 1.5rem;
}

.message.user {
    text-align: right;
}

.message.assistant {
    text-align: left;
}

.role {
    font-size: 0.75rem;
    color: #999;
    margin-bottom: 0.25rem;
}

.content {
    background-color: #444654;
    padding: 0.75rem;
    border-radius: 8px;
    white-space: pre-line;
    display: inline-block;
    max-width: 80%;
}

.chat-input {
    display: flex;
    padding: 1rem;
    border-top: 1px solid #444;
    background-color: #202123;
}

.chat-input input {
    flex: 1;
    padding: 0.75rem;
    margin-right: 0.5rem;
    border: none;
    border-radius: 6px;
    background-color: #3c3f4a;
    color: #fff;
}

.chat-input button {
    padding: 0.75rem 1.5rem;
    background-color: #10a37f;
    color: white;
    border: none;
    border-radius: 6px;
}

.new-chat-button {
    width: 100%;
    padding: 0.5rem;
    background-color: #10a37f;
    border: none;
    color: white;
    border-radius: 6px;
    margin-bottom: 1rem;
    cursor: pointer;
}

.new-chat-button:hover {
    background-color: #0e8e6b;
}

.message.assistant.loading .content {
    font-style: italic;
    opacity: 0.6;
}

.dots::after {
  content: "";
  display: inline-block;
  animation: blink 1.5s infinite steps(4);
  width: 1em;
}

@keyframes blink {
  0%   { content: ""; }
  33%  { content: "."; }
  66%  { content: ".."; }
  100% { content: "..."; }
}

</style>
