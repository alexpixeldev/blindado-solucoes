document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('form');

    // Controle de visibilidade das seções de zeladoria
    const zeladoriaRadios = document.querySelectorAll('input[name="contrata_zeladoria"]');
    const zeladoriaSections = document.querySelectorAll('.zeladoria-section');

    function toggleZeladoriaSections() {
        const selectedValue = document.querySelector('input[name="contrata_zeladoria"]:checked')?.value;
        
        if (selectedValue === 'sim') {
            zeladoriaSections.forEach(section => {
                section.style.display = 'block';
                // Tornar campos obrigatórios quando visíveis
                const radios = section.querySelectorAll('input[type="radio"]');
                radios.forEach(radio => radio.setAttribute('required', 'required'));
            });
        } else if (selectedValue === 'nao') {
            zeladoriaSections.forEach(section => {
                section.style.display = 'none';
                // Remover obrigatoriedade quando ocultos
                const radios = section.querySelectorAll('input[type="radio"]');
                radios.forEach(radio => radio.removeAttribute('required'));
                // Desmarcar radios quando ocultos
                radios.forEach(radio => radio.checked = false);
            });
        }
    }

    // Ocultar seções de zeladoria inicialmente
    zeladoriaSections.forEach(section => {
        section.style.display = 'none';
    });

    // Adicionar event listeners aos radios de contratação de zeladoria
    zeladoriaRadios.forEach(radio => {
        radio.addEventListener('change', toggleZeladoriaSections);
    });

    // Controle de campos de justificação para notas abaixo de 10
    function setupJustificativaFields() {
        // Para cada grupo de estrelas, adicionar event listener
        const starGroups = document.querySelectorAll('.scale-options.stars');
        
        starGroups.forEach(group => {
            const radios = group.querySelectorAll('input[type="radio"]');
            const formGroup = group.closest('.form-group');
            const justificativaField = formGroup.querySelector('.justificativa-field');
            
            if (justificativaField) {
                radios.forEach(radio => {
                    radio.addEventListener('change', function() {
                        const selectedValue = parseInt(this.value);
                        
                        if (selectedValue < 10) {
                            justificativaField.style.display = 'block';
                            // Tornar o campo obrigatório quando visível
                            const textarea = justificativaField.querySelector('textarea');
                            if (textarea) {
                                textarea.setAttribute('required', 'required');
                            }
                        } else {
                            justificativaField.style.display = 'none';
                            // Remover obrigatoriedade quando oculto
                            const textarea = justificativaField.querySelector('textarea');
                            if (textarea) {
                                textarea.removeAttribute('required');
                                textarea.value = ''; // Limpar o campo quando oculto
                            }
                        }
                    });
                });
            }
        });
    }

    // Chamar a função para configurar os campos de justificação
    setupJustificativaFields();

    // Star Rating - Apenas a estrela selecionada fica acesa
    
    // Validação ao submeter
    function validateFeedbackForm() {
        // Limpar erros anteriores
        document.querySelectorAll('.error-message').forEach(el => el.innerHTML = '');
        
        // Limpar classes de erro de campos
        document.querySelectorAll('.invalid').forEach(el => el.classList.remove('invalid'));

        let isValid = true;
        const requiredFields = form.querySelectorAll('[required]');
        
        requiredFields.forEach(field => {
            if (field.type === 'radio') {
                const name = field.name;
                const checked = form.querySelector(`input[name="${name}"]:checked`);
                if (!checked) {
                    isValid = false;
                    // Encontrar o form-group pai para exibir a mensagem de erro
                    const formGroup = field.closest('.form-group');
                    if (formGroup) {
                        const errorDiv = formGroup.querySelector('.error-message');
                        if (errorDiv) {
                            errorDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> Favor preencher este campo';
                            errorDiv.style.display = 'block';
                        }
                        // Adicionar classe 'invalid' ao container do radio-scale ou form-group
                        const radioScale = formGroup.querySelector('.radio-scale');
                        if (radioScale) { radioScale.classList.add('invalid'); }
                        else formGroup.classList.add('invalid');
                    }
                }
            } else if (field.type === 'text' || field.tagName === 'TEXTAREA') {
                if (!field.value.trim()) {
                    isValid = false;
                    const formGroup = field.closest('.form-group');
                    if (formGroup) {
                        const errorDiv = formGroup.querySelector('.error-message');
                        if (errorDiv) {
                            errorDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> Favor preencher este campo';
                            errorDiv.style.display = 'block';
                        }
                        field.classList.add('invalid');
                    }
                }
            }
        });

        if (!isValid) {
            // Scroll para o primeiro erro
            const firstErrorDiv = document.querySelector('.error-message[style*="display"]');
            if (firstErrorDiv) {
                firstErrorDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }

        return isValid;
    }

    // Modificar o event listener de submit para usar a função de validação
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (!validateFeedbackForm()) {
            return;
        }

        // Captura as informações e envia via WhatsApp
        const whatsappMessage = generateFeedbackWhatsAppMessage();
        const whatsappNumber = '5527995314969'; // Número de WhatsApp fixo atualizado
        const whatsappUrl = `https://api.whatsapp.com/send?phone=${whatsappNumber}&text=${encodeURIComponent(whatsappMessage)}`;
        
        window.open(whatsappUrl, '_blank');
    });

    console.log('Formulário carregado e pronto para uso.');

    function generateFeedbackWhatsAppMessage() {
        let message = "*Formulário de Feedback - Blindado Soluções*\n\n";
        const formData = new FormData(form);

        // Nome do Edifício
        const edificioNome = formData.get('edificio_nome');
        const sindicoNome = formData.get('sindico_nome');

        if (edificioNome) {
            message += `*Edifício:* ${edificioNome}\n`;
        }
        if (sindicoNome) {
            message += `*Síndico:* ${sindicoNome}\n`;
        }
        if (edificioNome || sindicoNome) message += "\n";

        // Função auxiliar para obter o valor do rádio e justificativa
        function getRatingAndJustification(radioName, labelText) {
            const rating = formData.get(radioName);
            let sectionMessage = `*${labelText}:* ${rating ? rating + '/10' : 'Não avaliado'}\n`;
            if (rating && parseInt(rating) < 10) {
                const justificationField = document.querySelector(`.justificativa-field[data-for-radio="${radioName}"] textarea`);
                if (justificationField && justificationField.value.trim()) {
                    sectionMessage += `_Justificativa:_ ${justificationField.value.trim()}\n`;
                }
            }
            return sectionMessage + "\n";
        }

        // Portaria
        message += "*--- Portaria ---*\n";
        message += getRatingAndJustification('portaria_atendimento_geral', 'Atendimento Geral');
        message += getRatingAndJustification('portaria_moradores', 'Moradores e Visitantes');
        message += getRatingAndJustification('portaria_monitoramento', 'Monitoramento');
        message += getRatingAndJustification('portaria_tempo', 'Tempo de Resposta');
        message += getRatingAndJustification('portaria_organizacao', 'Organização e Procedimento');

        // Comunicação com Administração
        message += getRatingAndJustification('comunicacao_admin', 'Comunicação com a Administração');

        // Zeladoria (condicional)
        const contrataZeladoria = formData.get('contrata_zeladoria');
        message += `*Contrata Zeladoria:* ${contrataZeladoria === 'sim' ? 'Sim' : 'Não'}\n\n`;
        if (contrataZeladoria === 'sim') {
            message += "*--- Zeladoria ---*\n";
            message += getRatingAndJustification('zeladoria_geral', 'Geral');
            message += getRatingAndJustification('zeladoria_areas_comuns', 'Áreas Comuns');
            message += getRatingAndJustification('zeladoria_organizacao', 'Organização');
            message += getRatingAndJustification('zeladoria_profissionalismo', 'Profissionalismo');
        }

        // Rondas
        message += "*--- Rondas ---*\n";
        message += getRatingAndJustification('rondas_ostensivas', 'Rondas Ostensivas');
        message += getRatingAndJustification('rondas_tempo_resposta', 'Tempo de Resposta das Rondas');

        // Feedback
        const feedbackText = formData.get('feedback');
        if (feedbackText && feedbackText.trim()) {
            message += `*Feedback/Sugestões:*\n${feedbackText.trim()}\n`;
        }

        return message;
    }
});
