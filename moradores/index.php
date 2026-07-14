<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blindado Soluções</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="../img/escudo.png">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- CSS Custom -->
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header class="header">
            <h1><i class="fas fa-building"></i> Edifício Prediletto</h1>
        </header>

        <!-- Conteúdo Principal -->
        <main class="main-content">
            <div class="card">
                <h2><i class="fas fa-users"></i> Lista de Moradores</h2>
                
                <form id="form-moradores" class="form-moradores">
                    <div id="moradores-container">
                        <!-- Morador 1 -->
                        <div class="morador-group" data-index="1">
                            <h3>
                                <div class="titulo-morador">
                                    <i class="fas fa-user"></i>
                                    Morador 1
                                </div>
                                <button type="button" class="btn-remove" onclick="removerMorador(1)">
                                    <i class="fas fa-times"></i>
                                    Remover
                                </button>
                            </h3>
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label" for="apartamento_1">Apartamento *</label>
                                    <input type="text" id="apartamento_1" name="apartamento_1" class="form-control" placeholder="Digite o número do apartamento" required>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label" for="nome_1">Nome Completo *</label>
                                    <input type="text" id="nome_1" name="nome_1" class="form-control" placeholder="Digite o nome completo do morador" required>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label" for="documento_1">Documento (RG ou CPF) *</label>
                                    <input type="text" id="documento_1" name="documento_1" class="form-control" placeholder="Digite o RG ou CPF" required>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label" for="telefone_1">Telefone *</label>
                                    <input type="tel" id="telefone_1" name="telefone_1" class="form-control" placeholder="Digite o telefone com DDD" required>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label" for="tipo_1">Tipo de Morador *</label>
                                    <select id="tipo_1" name="tipo_1" class="form-control" required>
                                        <option value="">Selecione o tipo</option>
                                        <option value="proprietario">Proprietário</option>
                                        <option value="locatario_anual">Locatário Anual</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="adicionarMorador()">
                            <i class="fas fa-plus"></i>
                            Adicionar Morador
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i>
                            Enviar
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <!-- JavaScript -->
    <script>
        let contadorMoradores = 1;
        
        function adicionarMorador() {
            contadorMoradores++;
            
            const novoMorador = document.createElement('div');
            novoMorador.className = 'morador-group';
            novoMorador.dataset.index = contadorMoradores;
            
            novoMorador.innerHTML = `
                <h3>
                    <div class="titulo-morador">
                        <i class="fas fa-user"></i>
                        Morador ${contadorMoradores}
                    </div>
                    <button type="button" class="btn-remove" onclick="removerMorador(${contadorMoradores})">
                        <i class="fas fa-times"></i>
                        Remover
                    </button>
                </h3>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="nome_${contadorMoradores}">Nome Completo *</label>
                        <input type="text" id="nome_${contadorMoradores}" name="nome_${contadorMoradores}" class="form-control" placeholder="Digite o nome completo do morador" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="documento_${contadorMoradores}">Documento (RG ou CPF) *</label>
                        <input type="text" id="documento_${contadorMoradores}" name="documento_${contadorMoradores}" class="form-control" placeholder="Digite o RG ou CPF" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="telefone_${contadorMoradores}">Telefone *</label>
                        <input type="tel" id="telefone_${contadorMoradores}" name="telefone_${contadorMoradores}" class="form-control" placeholder="Digite o telefone com DDD" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="tipo_${contadorMoradores}">Tipo de Morador *</label>
                        <select id="tipo_${contadorMoradores}" name="tipo_${contadorMoradores}" class="form-control" required>
                            <option value="">Selecione o tipo</option>
                            <option value="proprietario">Proprietário</option>
                            <option value="locatario_anual">Locatário Anual</option>
                        </select>
                    </div>
                </div>
            `;
            
            document.getElementById('moradores-container').appendChild(novoMorador);
        }
        
        function removerMorador(index) {
            const moradorGroup = document.querySelector(`[data-index="${index}"]`);
            if (moradorGroup) {
                moradorGroup.remove();
            }
        }
        
        // Validação básica do formulário
        document.getElementById('form-moradores').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const moradorGroups = document.querySelectorAll('.morador-group');
            const moradores = [];
            
            let temErro = false;
            
            moradorGroups.forEach((group, index) => {
                const groupIndex = group.dataset.index;
                
                if (groupIndex === '1') {
                    // Morador 1 - valida todos os campos incluindo apartamento
                    const apartamento = document.getElementById(`apartamento_1`).value.trim();
                    const nome = document.getElementById(`nome_1`).value.trim();
                    const documento = document.getElementById(`documento_1`).value.trim();
                    const telefone = document.getElementById(`telefone_1`).value.trim();
                    const tipo = document.getElementById(`tipo_1`).value.trim();
                    
                    if (!apartamento || !nome || !documento || !telefone || !tipo) {
                        temErro = true;
                        document.getElementById(`apartamento_1`).focus();
                        return;
                    }
                    
                    const tipoTexto = tipo === 'proprietario' ? 'Proprietário' : 'Locatário Anual';
                    
                    moradores.push({
                        apartamento: apartamento,
                        nome: nome,
                        documento: documento,
                        telefone: telefone,
                        tipo: tipoTexto
                    });
                } else {
                    // Demais moradores - não valida apartamento
                    const nome = document.getElementById(`nome_${groupIndex}`).value.trim();
                    const documento = document.getElementById(`documento_${groupIndex}`).value.trim();
                    const telefone = document.getElementById(`telefone_${groupIndex}`).value.trim();
                    const tipo = document.getElementById(`tipo_${groupIndex}`).value.trim();
                    
                    if (!nome || !documento || !telefone || !tipo) {
                        temErro = true;
                        document.getElementById(`nome_${groupIndex}`).focus();
                        return;
                    }
                    
                    const tipoTexto = tipo === 'proprietario' ? 'Proprietário' : 'Locatário Anual';
                    
                    moradores.push({
                        apartamento: document.getElementById('apartamento_1').value.trim(),
                        nome: nome,
                        documento: documento,
                        telefone: telefone,
                        tipo: tipoTexto
                    });
                }
            });
            
            if (temErro) {
                alert('Por favor, preencha todos os campos obrigatórios de todos os moradores!');
                return;
            }
            
            // Gerar mensagem para WhatsApp
            const mensagem = gerarMensagemWhatsApp(moradores, document.getElementById('apartamento_1').value.trim());
            
            // Número do WhatsApp para envio
            const telefoneDestino = '5527998173386';
            
            // Redirecionar para WhatsApp na mesma aba
            const whatsappUrl = `https://api.whatsapp.com/send?phone=${telefoneDestino}&text=${encodeURIComponent(mensagem)}`;
            console.log('Redirecionando para:', whatsappUrl);
            window.location.href = whatsappUrl;
        });
        
        function gerarMensagemWhatsApp(moradores, apartamento) {
            let msg = "*Ed. Prediletto*\n";
            msg += `*Lista Moradores Apto ${apartamento}*\n\n`;
            
            moradores.forEach((morador, index) => {
                msg += `--- *Morador ${index + 1}* ---\n`;
                msg += `*Nome:* ${morador.nome}\n`;
                msg += `*Documento:* ${morador.documento}\n`;
                msg += `*Telefone:* ${morador.telefone}\n`;
                msg += `*Tipo:* ${morador.tipo}\n\n`;
            });
            
            return msg;
        }
    </script>
</body>
</html>
