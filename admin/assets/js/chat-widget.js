/**
 * Chat Widget JavaScript - VERSÃO 2026 MODERNIZADA
 * Foco: UI Moderna, Notificações Inteligentes e Estabilidade
 */

(function() {
    class ChatWidget {
        constructor() {
            console.log("ChatWidget: Inicializando Versão 2026...");
            this.usuarioId = document.documentElement.getAttribute('data-usuario-id');
            this.usuarioNome = document.documentElement.getAttribute('data-usuario-nome');
            this.usuarioCategoria = document.documentElement.getAttribute('data-usuario-categoria');
            
            this.conversaAtual = sessionStorage.getItem('chat_conversa_ativa');
            this.widgetAberto = sessionStorage.getItem('chat_widget_aberto') === 'true';
            this.abaAtiva = sessionStorage.getItem('chat_aba_ativa') || 'usuarios';
            
            this.usuarios = [];
            this.conversasRecent = [];
            this.audioCtx = null;
            this.termoBusca = '';
            this.notificacoesAntigas = {}; 
            
            if (!this.usuarioId || this.usuarioCategoria === 'colaborador') return;
            
            this.init();
        }

        init() {
            this.criarWidget();
            this.carregarDados(true); 
            
            if (this.conversaAtual) this.selecionarUsuario(this.conversaAtual, false);
            if (this.abaAtiva) this.mudarAba(this.abaAtiva);
            if (this.widgetAberto) document.getElementById('chatContainer').style.display = 'flex';
            
            this.atualizarStatusAtividade();
            
            // Polling de dados gerais (contatos, notificações)
            setInterval(() => this.carregarDados(), 4000);
            
            // Polling de mensagens apenas se o chat estiver aberto na conversa
            setInterval(() => {
                if (this.conversaAtual && this.widgetAberto && this.abaAtiva === 'mensagens') {
                    this.carregarMensagens(this.conversaAtual);
                }
            }, 3000);
        }

        criarWidget() {
            const widgetHTML = `
                <div id="chatWidgetRoot" class="chat-widget" style="position: fixed; bottom: 20px; right: 20px; z-index: 999999; font-family: 'Inter', -apple-system, sans-serif;">
                    <button id="chatWidgetBtn" style="width: 56px; height: 56px; border-radius: 16px; background: #16a34a; color: white; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; box-shadow: 0 8px 24px rgba(22, 163, 74, 0.3); transition: all 0.3s ease; position: relative;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 21 1.9-5.7a8.5 8.5 0 1 1 3.8 3.8z"/></svg>
                        <span id="chatBadge" style="display: none; position: absolute; top: -8px; right: -8px; background: #ef4444; color: white; border-radius: 50%; min-width: 20px; height: 20px; padding: 0 6px; align-items: center; justify-content: center; font-size: 11px; font-weight: 700; border: 2px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">0</span>
                    </button>

                    <div id="chatContainer" style="display: none; position: fixed; bottom: 90px; right: 20px; width: 380px; height: 600px; background: white; border-radius: 24px; box-shadow: 0 12px 48px rgba(0,0,0,0.15); flex-direction: column; overflow: hidden; border: 1px solid rgba(0,0,0,0.05);">
                        <div style="background: #ffffff; color: #1a1a1a; padding: 20px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f0f0f0;">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <button id="chatBackBtn" style="display:none; background:#f5f5f5; border:none; color:#1a1a1a; cursor:pointer; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; transition: background 0.2s;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                                </button>
                                <div>
                                    <h3 id="chatTitle" style="margin: 0; font-size: 16px; font-weight: 700; letter-spacing: -0.01em;">Mensagens</h3>
                                    <div id="chatSubtitle" style="font-size: 12px; color: #16a34a; font-weight: 500;">Online agora</div>
                                </div>
                            </div>
                            <button id="chatCloseBtn" style="background: #f5f5f5; border: none; color: #666; cursor: pointer; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; transition: all 0.2s;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                            </button>
                        </div>

                        <div id="chatTabs" style="display: flex; padding: 8px; background: #ffffff; gap: 8px;">
                            <button class="chat-tab" data-tab="usuarios" style="flex: 1; padding: 10px; border: none; background: #f5f5f5; cursor: pointer; border-radius: 12px; font-size: 13px; font-weight: 600; color: #666; transition: all 0.2s;">Contatos</button>
                            <button class="chat-tab" data-tab="conversas" style="flex: 1; padding: 10px; border: none; background: #f5f5f5; cursor: pointer; border-radius: 12px; font-size: 13px; font-weight: 600; color: #666; transition: all 0.2s; position:relative;">
                                Conversas
                                <span id="convBadge" style="display:none; position:absolute; top:-4px; right:-4px; background:#ef4444; color:white; border-radius:50%; min-width:18px; height:18px; font-size:10px; font-weight:700; align-items:center; justify-content:center; border:2px solid white;">0</span>
                            </button>
                        </div>

                        <div id="tab-usuarios" class="chat-tab-content" style="display: block; flex: 1; overflow-y: auto; padding: 0 8px;">
                            <div style="padding: 8px 0;">
                                <div style="position: relative;">
                                    <svg style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #999;" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                                    <input type="text" id="chatSearch" placeholder="Buscar contatos..." style="width: 100%; padding: 10px 10px 10px 36px; border: 1px solid #f0f0f0; border-radius: 14px; font-size: 14px; outline: none; background: #f9f9f9; transition: all 0.2s;">
                                </div>
                            </div>
                            <div id="usersList"></div>
                        </div>

                        <div id="tab-conversas" class="chat-tab-content" style="display: none; flex: 1; overflow-y: auto; padding: 0 8px;">
                            <div style="padding: 8px 0;">
                                <div style="position: relative;">
                                    <svg style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #999;" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                                    <input type="text" id="chatSearchConv" placeholder="Buscar conversas..." style="width: 100%; padding: 10px 10px 10px 36px; border: 1px solid #f0f0f0; border-radius: 14px; font-size: 14px; outline: none; background: #f9f9f9;">
                                </div>
                            </div>
                            <div id="recentList"></div>
                        </div>

                        <div id="tab-mensagens" class="chat-tab-content" style="display: none; flex: 1; flex-direction: column; overflow: hidden; background: #fcfcfc;">
                            <div id="messagesContainer" style="flex: 1; overflow-y: auto; display: flex; flex-direction: column; gap: 4px; padding: 16px; background-image: radial-gradient(#e5e7eb 0.5px, transparent 0.5px); background-size: 20px 20px;"></div>
                            
                            <div id="emojiPicker" style="display: none; height: 160px; overflow-y: auto; background: white; border-top: 1px solid #f0f0f0; padding: 12px; grid-template-columns: repeat(8, 1fr); gap: 8px;">
                                ${this.getEmojiList().map(e => `<span onclick="window.chatWidgetInstance.inserirEmoji('${e}')" style="cursor:pointer; font-size:22px; text-align:center; transition: transform 0.1s;" onmouseover="this.style.transform='scale(1.2)'" onmouseout="this.style.transform='scale(1)'">${e}</span>`).join('')}
                            </div>

                            <div id="chatFooter" style="padding: 16px; border-top: 1px solid #f0f0f0; background: white;">
                                <div style="display: flex; gap: 10px; align-items: center;">
                                    <button id="emojiBtn" style="background:#f5f5f5; border:none; width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; cursor:pointer; color: #666;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" x2="9.01" y1="9" y2="9"/><line x1="15" x2="15.01" y1="9" y2="9"/></svg>
                                    </button>
                                    
                                    <div style="flex: 1; position: relative; display: flex; align-items: center;">
                                        <textarea id="chatInput" style="width: 100%; border: 1px solid #f0f0f0; border-radius: 14px; padding: 10px 40px 10px 12px; font-size: 14px; resize: none; height: 40px; outline:none; background: #f9f9f9; font-family: inherit; line-height: 1.4;" placeholder="Escreva uma mensagem..."></textarea>
                                        <button id="chatFileBtn" style="position: absolute; right: 8px; background: none; border: none; color: #999; cursor: pointer; padding: 4px;">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l8.57-8.57A4 4 0 1 1 18 8.84l-8.59 8.51a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                                        </button>
                                    </div>

                                    <button id="chatSendBtn" style="background: #16a34a; color: white; border: none; width: 40px; height: 40px; border-radius: 12px; cursor: pointer; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(22, 163, 74, 0.2); transition: transform 0.2s;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                                    </button>
                                </div>
                            </div>
                            <style>
                                .msg-whatsapp {
                                    position: relative;
                                    padding: 10px 14px;
                                    border-radius: 18px;
                                    margin-bottom: 2px;
                                    max-width: 80%;
                                    font-size: 14px;
                                    line-height: 1.5;
                                    box-shadow: 0 1px 2px rgba(0,0,0,0.05);
                                }
                                .msg-sent {
                                    background-color: #16a34a;
                                    color: white;
                                    align-self: flex-end;
                                    border-bottom-right-radius: 4px;
                                }
                                .msg-received {
                                    background-color: #ffffff;
                                    color: #1a1a1a;
                                    align-self: flex-start;
                                    border-bottom-left-radius: 4px;
                                    border: 1px solid #f0f0f0;
                                }
                                .msg-time {
                                    font-size: 10px;
                                    opacity: 0.7;
                                    margin-top: 4px;
                                    display: block;
                                    text-align: right;
                                }
                                .chat-tab.active {
                                    background: #16a34a !important;
                                    color: white !important;
                                    box-shadow: 0 4px 12px rgba(22, 163, 74, 0.2);
                                }
                                .user-item:hover {
                                    background: #f9f9f9;
                                }
                            </style>
                        </div>
                    </div>

                    <input type="file" id="chatFileInput" style="display:none">
                </div>
                <div id="imageModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); z-index: 1000000; align-items: center; justify-content: center; backdrop-filter: blur(8px);">
                    <img id="modalImage" style="max-width: 95%; max-height: 95%; border-radius: 12px; box-shadow: 0 24px 48px rgba(0,0,0,0.5);">
                    <button onclick="document.getElementById('imageModal').style.display='none'" style="position: absolute; top: 30px; right: 30px; background: white; border: none; border-radius: 50%; width: 44px; height: 44px; cursor: pointer; font-size: 24px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(0,0,0,0.2);">×</button>
                </div>
            `;

            document.body.insertAdjacentHTML('beforeend', widgetHTML);

            document.getElementById('chatWidgetBtn').onclick = () => {
                const container = document.getElementById('chatContainer');
                const isAberto = container.style.display === 'flex';
                container.style.display = isAberto ? 'none' : 'flex';
                this.widgetAberto = !isAberto;
                sessionStorage.setItem('chat_widget_aberto', this.widgetAberto);
                if (this.widgetAberto) {
                    this.carregarDados();
                    if (this.conversaAtual && this.abaAtiva === 'mensagens') {
                        this.marcarComoLida(this.conversaAtual);
                    }
                }
            };
            
            document.getElementById('chatCloseBtn').onclick = () => {
                document.getElementById('chatContainer').style.display = 'none';
                this.widgetAberto = false;
                sessionStorage.setItem('chat_widget_aberto', 'false');
            };

            document.getElementById('chatBackBtn').onclick = () => this.mudarAba('conversas');
            document.getElementById('chatSearch').oninput = (e) => { this.termoBusca = e.target.value.toLowerCase(); this.atualizarListaUsuarios(); };
            document.getElementById('chatSearchConv').oninput = (e) => { const t = e.target.value.trim(); t.length >= 2 ? this.buscarEmConversas(t) : this.atualizarListaConversas(); };
            document.getElementById('emojiBtn').onclick = () => { const p = document.getElementById('emojiPicker'); p.style.display = p.style.display === 'grid' ? 'none' : 'grid'; };
            document.getElementById('chatSendBtn').onclick = () => this.enviarMensagem();
            document.getElementById('chatFileBtn').onclick = () => document.getElementById('chatFileInput').click();
            document.getElementById('chatFileInput').onchange = (e) => { if (e.target.files[0]) this.enviarMensagem(); };
            document.getElementById('chatInput').onkeydown = (e) => { if(e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); this.enviarMensagem(); } };

            document.querySelectorAll('.chat-tab').forEach(tab => { tab.onclick = () => this.mudarAba(tab.dataset.tab); });
            window.chatWidgetInstance = this;
        }

        getEmojiList() { return ['😊', '😂', '😍', '👍', '🙌', '🔥', '🤔', '😢', '😮', '👏', '🎉', '❤️', '✅', '❌', '⚠️', '🚀']; }
        inserirEmoji(emoji) { const input = document.getElementById('chatInput'); input.value += emoji; input.focus(); document.getElementById('emojiPicker').style.display = 'none'; }

        mudarAba(tab) {
            this.abaAtiva = tab;
            sessionStorage.setItem('chat_aba_ativa', tab);
            document.querySelectorAll('.chat-tab').forEach(t => { 
                if (t.dataset.tab === tab) t.classList.add('active');
                else t.classList.remove('active');
            });
            document.getElementById('chatBackBtn').style.display = tab === 'mensagens' ? 'flex' : 'none';
            document.getElementById('chatTabs').style.display = tab === 'mensagens' ? 'none' : 'flex';
            document.getElementById('tab-usuarios').style.display = tab === 'usuarios' ? 'block' : 'none';
            document.getElementById('tab-conversas').style.display = tab === 'conversas' ? 'block' : 'none';
            document.getElementById('tab-mensagens').style.display = tab === 'mensagens' ? 'flex' : 'none';
            
            if (tab === 'mensagens' && this.conversaAtual) {
                this.marcarComoLida(this.conversaAtual);
            }
        }

        carregarDados(silencioso = false) {
            fetch('api_chat.php?action=obter_status_usuarios').then(r => r.json()).then(data => {
                if (data.usuarios) {
                    this.usuarios = data.usuarios;
                    this.conversasRecent = data.conversas_recentes || [];
                    this.atualizarListaUsuarios();
                    this.atualizarListaConversas();
                    this.atualizarNotificacoesGlobais(silencioso);
                }
            });
        }

        atualizarListaUsuarios() {
            const list = document.getElementById('usersList');
            list.innerHTML = '';
            this.usuarios.filter(u => u.nome.toLowerCase().includes(this.termoBusca)).forEach(u => {
                const item = document.createElement('div');
                item.className = 'user-item';
                item.style.cssText = 'padding:12px; border-radius:14px; cursor:pointer; display:flex; align-items:center; gap:12px; transition: all 0.2s; margin-bottom: 4px;';
                item.onclick = () => this.selecionarUsuario(u.id);
                item.innerHTML = `
                    <div style="width:44px; height:44px; border-radius:14px; background:#f0f0f0; display:flex; align-items:center; justify-content:center; font-weight:700; color:#16a34a; position:relative; font-size:16px;">
                        ${u.nome.charAt(0).toUpperCase()}
                        <div style="position:absolute; bottom:-2px; right:-2px; width:12px; height:12px; border-radius:50%; background:${u.status_chat === 'online' ? '#16a34a' : '#cbd5e1'}; border:2.5px solid white;"></div>
                    </div>
                    <div style="flex:1;">
                        <div style="font-weight:600; font-size:14px; color:#1a1a1a;">${u.nome}</div>
                        <div style="font-size:12px; color:#666;">${u.categoria}</div>
                    </div>
                    ${u.nao_lidas > 0 ? `<span style="background:#ef4444; color:white; border-radius:8px; min-width:20px; height:20px; padding:0 6px; display:flex; align-items:center; justify-content:center; font-size:10px; font-weight:700;">${u.nao_lidas}</span>` : ''}
                `;
                list.appendChild(item);
            });
        }

        atualizarListaConversas() {
            const list = document.getElementById('recentList');
            list.innerHTML = '';
            if (this.conversasRecent.length === 0) { list.innerHTML = '<div style="text-align:center; color:#999; margin-top:40px; font-size:14px;">Nenhuma conversa ativa</div>'; return; }
            this.conversasRecent.forEach(c => {
                const item = document.createElement('div');
                item.className = 'user-item';
                item.style.cssText = 'padding:12px; border-radius:14px; cursor:pointer; display:flex; align-items:center; gap:12px; transition: all 0.2s; margin-bottom: 4px;';
                item.onclick = () => this.selecionarUsuario(c.id);
                const time = c.data_ultima ? new Date(c.data_ultima).toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'}) : '';
                item.innerHTML = `
                    <div style="width:44px; height:44px; border-radius:14px; background:#f0f0f0; display:flex; align-items:center; justify-content:center; font-weight:700; color:#16a34a; font-size:16px;">${c.nome.charAt(0).toUpperCase()}</div>
                    <div style="flex:1; overflow:hidden;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 2px;">
                            <div style="font-weight:600; font-size:14px; color:#1a1a1a;">${c.nome}</div>
                            <div style="font-size:11px; color:#999;">${time}</div>
                        </div>
                        <div style="font-size:12px; color:#666; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${c.ultima_msg || 'Arquivo enviado'}</div>
                    </div>
                    ${c.nao_lidas > 0 ? `<span style="background:#ef4444; color:white; border-radius:8px; min-width:20px; height:20px; padding:0 6px; display:flex; align-items:center; justify-content:center; font-size:10px; font-weight:700;">${c.nao_lidas}</span>` : ''}
                `;
                list.appendChild(item);
            });
        }

        selecionarUsuario(id, mudarAba = true) {
            this.conversaAtual = id;
            sessionStorage.setItem('chat_conversa_ativa', id);
            const u = this.usuarios.find(x => x.id == id) || this.conversasRecent.find(x => x.id == id);
            if (u) {
                document.getElementById('chatTitle').textContent = u.nome;
                document.getElementById('chatSubtitle').textContent = u.status_chat === 'online' ? 'Online agora' : 'Offline';
                document.getElementById('chatSubtitle').style.color = u.status_chat === 'online' ? '#16a34a' : '#999';
            }
            if (mudarAba) this.mudarAba('mensagens');
            this.carregarMensagens(id);
            this.marcarComoLida(id);
        }

        carregarMensagens(id) {
            // BLOQUEIO ABSOLUTO: Se a conversa está aberta, marcamos como lida no servidor IMEDIATAMENTE em cada ciclo
            if (this.widgetAberto && this.abaAtiva === 'mensagens' && this.conversaAtual == id) {
                this.marcarComoLida(id);
            }

            fetch(`api_chat.php?action=obter_mensagens&outro_usuario_id=${id}`)
                .then(r => r.json())
                .then(data => {
                    const container = document.getElementById('messagesContainer');
                    const htmlAnterior = container.innerHTML;
                    let novoHTML = '';
                    if (data.mensagens && data.mensagens.length > 0) {
                        data.mensagens.forEach(m => {
                            const isSent = m.remetente_id == this.usuarioId;
                            const time = new Date(m.data_envio).toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'});
                            let content = '';
                            
                            if (m.arquivo_caminho) {
                                const path = m.arquivo_caminho.startsWith('uploads') ? '../' + m.arquivo_caminho : m.arquivo_caminho;
                                if (m.arquivo_tipo === 'imagem') {
                                    content = `<img src="${path}" style="max-width:100%; border-radius:12px; cursor:pointer" onclick="window.chatWidgetInstance.abrirImagem('${path}')">`;
                                } else if (m.arquivo_tipo === 'video') {
                                    content = `<video controls style="max-width:100%; border-radius:12px;"><source src="${path}"></video>`;
                                } else if (m.arquivo_tipo === 'audio') {
                                    content = `<audio controls style="width:100%; height:35px;"><source src="${path}"></audio>`;
                                } else {
                                    content = `<div style="display:flex; align-items:center; gap:8px; background:rgba(0,0,0,0.05); padding:8px; border-radius:8px;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14.5 2 14.5 7.5 20 7.5"/></svg>
                                        <a href="${path}" download style="color:inherit; text-decoration:none; font-size:12px; font-weight:500; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">${m.arquivo_nome_original}</a>
                                    </div>`;
                                }
                            }
                            
                            if (m.mensagem && m.mensagem !== '') {
                                content += `<div style="margin-top:${content ? '8px' : '0'}; word-break: break-word;">${m.mensagem}</div>`;
                            }

                            novoHTML += `
                                <div class="msg-whatsapp ${isSent ? 'msg-sent' : 'msg-received'}">
                                    ${content}
                                    <span class="msg-time">${time}${m.status === 'editada' ? ' • editada' : ''}${isSent ? ' • ✓✓' : ''}</span>
                                </div>
                            `;
                        });
                        if (htmlAnterior !== novoHTML) { container.innerHTML = novoHTML; container.scrollTop = container.scrollHeight; }
                    } else { container.innerHTML = '<div style="text-align:center; color:#999; margin-top:100px; font-size:14px;">Inicie uma conversa</div>'; }
                });
        }

        enviarMensagem() {
            const input = document.getElementById('chatInput');
            const fileInput = document.getElementById('chatFileInput');
            const msg = input.value.trim();
            const file = fileInput.files[0];
            if (!msg && !file) return;
            const fd = new FormData();
            fd.append('action', 'enviar_mensagem');
            fd.append('destinatario_id', this.conversaAtual);
            fd.append('mensagem', msg);
            if (file) fd.append('arquivo', file);
            fetch('api_chat.php', { method: 'POST', body: fd }).then(() => { input.value = ''; fileInput.value = ''; this.carregarMensagens(this.conversaAtual); this.carregarDados(); });
        }

        atualizarStatusAtividade() {
            const fd = new FormData();
            fd.append('action', 'atualizar_status');
            fd.append('status', 'online');
            
            // Atualizar imediatamente ao carregar
            fetch('api_chat.php', { method: 'POST', body: fd });
            
            // Heartbeat a cada 10 segundos para precisão online/offline
            setInterval(() => fetch('api_chat.php', { method: 'POST', body: fd }), 10000);
        }

        marcarComoLida(id) {
            const fd = new FormData();
            fd.append('action', 'marcar_como_lida');
            fd.append('outro_usuario_id', id);
            fetch('api_chat.php', { method: 'POST', body: fd });
        }

        atualizarNotificacoesGlobais(silencioso = false) {
            let totalReal = 0;
            let temNovaMensagem = false;

            this.conversasRecent.forEach(c => {
                const nLidas = parseInt(c.nao_lidas || 0);
                const id = c.id;

                // SILENCIAMENTO ABSOLUTO: Se a conversa está aberta e o widget está ativo na aba de mensagens, marcamos como lida e ignoramos a notificação
                if (this.conversaAtual == id && this.widgetAberto && this.abaAtiva === 'mensagens') {
                    if (nLidas > 0) {
                        this.marcarComoLida(id);
                    }
                    // Forçamos o rastreio a ser igual ao atual para não disparar som
                    this.notificacoesAntigas[id] = 0; 
                } else {
                    totalReal += nLidas;
                    if (nLidas > (this.notificacoesAntigas[id] || 0)) {
                        temNovaMensagem = true;
                    }
                    this.notificacoesAntigas[id] = nLidas;
                }
            });

            const badge = document.getElementById('chatBadge');
            const convBadge = document.getElementById('convBadge');
            
            if (badge) {
                if (totalReal > 0) {
                    badge.textContent = totalReal; 
                    badge.style.display = 'flex';
                    if (temNovaMensagem && !silencioso) {
                        this.tocarSomNotificacao();
                    }
                } else { 
                    badge.style.display = 'none'; 
                    badge.textContent = '0'; 
                }
            }
            
            if (convBadge) { 
                if (totalReal > 0) { 
                    convBadge.textContent = totalReal; 
                    convBadge.style.display = 'flex'; 
                } else { 
                    convBadge.style.display = 'none'; 
                } 
            }
        }

        tocarSomNotificacao() {
            try {
                if (!this.audioCtx) this.audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                const osc = this.audioCtx.createOscillator();
                const gain = this.audioCtx.createGain();
                osc.connect(gain); gain.connect(this.audioCtx.destination);
                osc.frequency.value = 880; 
                gain.gain.setValueAtTime(0.1, this.audioCtx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.01, this.audioCtx.currentTime + 0.4);
                osc.start(); osc.stop(this.audioCtx.currentTime + 0.4);
            } catch(e) {}
        }

        abrirImagem(src) { const modal = document.getElementById('imageModal'); document.getElementById('modalImage').src = src; modal.style.display = 'flex'; }
    }

    if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', () => { new ChatWidget(); }); } else { new ChatWidget(); }
})();
