document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('multi-step-form');
    const steps = document.querySelectorAll('.step-content');
    const btnNext = document.getElementById('btn-next');
    const btnPrev = document.getElementById('btn-prev');
    const errorAlert = document.getElementById('global-error-message');
    
    let currentStep = 0;
    const totalSteps = steps.length;

    // --- Helper Functions ---
    function formatText(text) {
        if (!text) return "";
        return text.toLowerCase().split(' ').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
    }

    // --- Inicialização ---
    updateStepUI();
    initDatePickers();
    initPhoneMask();
    initEdificiosSearch();

    // --- Navegação ---
    btnNext.addEventListener('click', () => {
        goToNextStep();
    });

    function goToNextStep() {
        if (validateCurrentStep()) {
            if (currentStep < totalSteps - 1) {
                currentStep++;
                updateStepUI();
                if (currentStep === totalSteps - 1) {
                    populateReviewPage();
                }
            } else {
                submitForm();
            }
        }
    }

    btnPrev.addEventListener('click', () => {
        if (currentStep > 0) {
            currentStep--;
            updateStepUI();
        }
    });

    function updateStepUI() {
        // Atualizar visibilidade dos passos
        steps.forEach((step, idx) => {
            step.classList.toggle('active', idx === currentStep);
        });

        // Atualizar Stepper (Círculos e Linhas)
        for (let i = 1; i <= totalSteps; i++) {
            const circle = document.getElementById(`circle-${i}`);
            const line = document.getElementById(`line-${i}`);
            
            if (i <= currentStep + 1) {
                circle.classList.add('bg-primary-600', 'border-primary-600', 'text-white');
                circle.classList.remove('bg-white', 'border-slate-300', 'text-slate-500');
                if (i < currentStep + 1) {
                    circle.innerHTML = '<i class="fas fa-check text-sm"></i>';
                } else {
                    circle.innerHTML = `<span class="text-sm font-bold">${i}</span>`;
                }
            } else {
                circle.classList.remove('bg-primary-600', 'border-primary-600', 'text-white');
                circle.classList.add('bg-white', 'border-slate-300', 'text-slate-500');
                circle.innerHTML = `<span class="text-sm font-bold">${i}</span>`;
            }

            if (line) {
                line.style.width = i <= currentStep ? '100%' : '0%';
            }
        }

        // Atualizar Botões
        btnPrev.disabled = currentStep === 0;
        
        // Se estiver na última etapa, oculta os botões de navegação padrão
        // e mostra os botões de ação específicos (WhatsApp/PDF) que estão no step6
        const navButtons = btnNext.parentElement;
        if (currentStep === totalSteps - 1) {
            navButtons.classList.add('hidden');
        } else {
            navButtons.classList.remove('hidden');
            btnNext.querySelector('span').textContent = 'Próxima';
            btnNext.querySelector('i').className = 'fas fa-arrow-right ml-2';
        }

        // Scroll para o topo
        window.scrollTo({ top: 0, behavior: 'smooth' });
        clearAllErrors();
    }

    // --- Lógica de Tipo de Usuário ---
    const userTypeRadios = document.querySelectorAll('input[name="user_type"]');
    const locadorFields = document.getElementById('locadorFields');
    
    userTypeRadios.forEach(radio => {
        radio.addEventListener('change', () => {
            const isLocatario = document.getElementById('radio_locatario').checked;
            locadorFields.classList.toggle('hidden', !isLocatario);
            
            // Atualizar visibilidade do campo de acesso de garagem no passo 4
            updateAcessoGaragemVisibility();
            clearAllErrors();
        });
    });

    function updateAcessoGaragemVisibility() {
        const isLocador = document.getElementById('radio_locador').checked;
        document.querySelectorAll('.acesso-garagem-field').forEach(field => {
            field.classList.toggle('hidden', !isLocador);
        });
    }

    // --- Edifícios (Busca e Seleção) ---
    function initEdificiosSearch() {
        const searchInput = document.getElementById('edificio_search');
        const cards = document.querySelectorAll('.edificio-card');
        const noResults = document.getElementById('no_edificios');

        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                const term = e.target.value.toLowerCase();
                let hasResults = false;
                cards.forEach(card => {
                    const name = card.dataset.name;
                    const isMatch = name.includes(term);
                    card.classList.toggle('hidden', !isMatch);
                    if (isMatch) hasResults = true;
                });
                noResults.classList.toggle('hidden', hasResults);
            });
        }
    }

    // Modal Apartamento
    let selectedEdificioCard = null;

    window.openApartmentModal = function(card) {
        selectedEdificioCard = card;
        const edificioName = card.querySelector('.text-sm.font-bold').textContent;
        const currentApt = document.getElementById('numero_apartamento').value;
        
        document.getElementById('modalEdificioName').textContent = edificioName;
        document.getElementById('modal_apt_input').value = (card.classList.contains('selected')) ? currentApt : '';
        document.getElementById('aptModal').classList.remove('hidden');
        document.getElementById('modal_apt_input').focus();
        document.getElementById('modal_apt_error').classList.add('hidden');
        document.getElementById('modal_apt_input').classList.remove('border-red-500');
    };

    window.closeApartmentModal = function() {
        document.getElementById('aptModal').classList.add('hidden');
    };

    window.confirmApartment = function() {
        const aptInput = document.getElementById('modal_apt_input');
        const aptValue = aptInput.value.trim();
        
        if (!aptValue) {
            document.getElementById('modal_apt_error').classList.remove('hidden');
            aptInput.classList.add('border-red-500');
            return;
        }

        // Salvar valor
        document.getElementById('numero_apartamento').value = aptValue;
        
        // Atualizar UI do card
        const cards = document.querySelectorAll('.edificio-card');
        cards.forEach(c => {
            c.classList.remove('selected');
            c.querySelector('.apt-display').classList.add('hidden');
        });
        
        selectedEdificioCard.classList.add('selected');
        selectedEdificioCard.querySelector('input[type="radio"]').checked = true;
        const aptDisplay = selectedEdificioCard.querySelector('.apt-display');
        aptDisplay.querySelector('.apt-number').textContent = aptValue;
        aptDisplay.classList.remove('hidden');
        
        closeApartmentModal();
        clearAllErrors();
        
        // Avançar automaticamente para o próximo passo
        setTimeout(() => {
            goToNextStep();
        }, 300);
    };

    // --- Inquilinos (Dinâmico) ---
    const addInquilinoBtn = document.getElementById('add-inquilino');
    const inquilinosContainer = document.getElementById('inquilinos-container');
    let inquilinoCount = 1;

    // Adicionar event listener para trim no campo do primeiro inquilino
    const primeiroNomeInput = document.querySelector('input[name="inquilinos[0][nome]"]');
    if (primeiroNomeInput) {
        primeiroNomeInput.addEventListener('blur', function() {
            this.value = this.value.trim();
        });
    }

    if (addInquilinoBtn) {
        addInquilinoBtn.addEventListener('click', () => {
            const index = inquilinoCount++;
            const html = `
                <div class="inquilino-item relative p-6 bg-white border border-slate-200 rounded-2xl shadow-sm animate-fade-in group" data-index="${index}">
                    <button type="button" class="remove-item absolute -top-3 -right-3 w-8 h-8 bg-red-500 text-white rounded-full shadow-lg hover:bg-red-600 transition-colors flex items-center justify-center">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-8 h-8 flex items-center justify-center bg-primary-100 text-primary-600 rounded-lg font-bold text-sm">${index + 1}</div>
                        <h3 class="text-lg font-semibold text-slate-900">Hóspede Adicional</h3>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div class="space-y-2 field-container">
                            <label class="block text-sm font-medium text-slate-700">Nome Completo</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400">
                                    <i class="fas fa-user"></i>
                                </div>
                                <input type="text" name="inquilinos[${index}][nome]" placeholder="Nome do hóspede" required data-label="nome"
                                       class="block w-full pl-11 pr-4 py-3 bg-white border border-slate-200 rounded-xl text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all duration-200">
                            </div>
                        </div>
                        <div class="space-y-2 field-container">
                            <label class="block text-sm font-medium text-slate-700">Documento</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400">
                                    <i class="fas fa-id-card"></i>
                                </div>
                                <input type="text" name="inquilinos[${index}][documento]" placeholder="Número do documento" required data-label="documento"
                                       class="block w-full pl-11 pr-4 py-3 bg-white border border-slate-200 rounded-xl text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all duration-200">
                            </div>
                        </div>
                    </div>
                </div>
            `;
            inquilinosContainer.insertAdjacentHTML('beforeend', html);
            
            // Adicionar event listener para trim nos campos de nome
            const nomeInput = document.querySelector(`input[name="inquilinos[${index}][nome]"]`);
            if (nomeInput) {
                nomeInput.addEventListener('blur', function() {
                    this.value = this.value.trim();
                });
            }
        });
    }

    // --- Veículos (Dinâmico) ---
    const addVeiculoBtn = document.getElementById('add-veiculo');
    const veiculosContainer = document.getElementById('veiculos-container');
    let veiculoCount = 1;

    if (addVeiculoBtn) {
        addVeiculoBtn.addEventListener('click', () => {
            const index = veiculoCount++;
            const isLocador = document.getElementById('radio_locador').checked;
            const html = `
                <div class="veiculo-item relative p-6 bg-white border border-slate-200 rounded-2xl shadow-sm animate-fade-in group" data-index="${index}">
                    <button type="button" class="remove-item absolute -top-3 -right-3 w-8 h-8 bg-red-500 text-white rounded-full shadow-lg hover:bg-red-600 transition-colors flex items-center justify-center">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-8 h-8 flex items-center justify-center bg-primary-100 text-primary-600 rounded-lg font-bold text-sm">${index + 1}</div>
                        <h3 class="text-lg font-semibold text-slate-900">Veículo Adicional</h3>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-slate-700">Modelo</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400">
                                    <i class="fas fa-car"></i>
                                </div>
                                <input type="text" name="veiculos[${index}][modelo]" placeholder="Ex: Toyota Corolla" 
                                       class="block w-full pl-11 pr-4 py-3 bg-white border border-slate-200 rounded-xl text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all duration-200">
                            </div>
                        </div>
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-slate-700">Cor</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400">
                                    <i class="fas fa-palette"></i>
                                </div>
                                <input type="text" name="veiculos[${index}][cor]" placeholder="Ex: Prata" 
                                       class="block w-full pl-11 pr-4 py-3 bg-white border border-slate-200 rounded-xl text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all duration-200">
                            </div>
                        </div>
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-slate-700">Placa</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400">
                                    <i class="fas fa-barcode"></i>
                                </div>
                                <input type="text" name="veiculos[${index}][placa]" placeholder="ABC-1234" 
                                       class="block w-full pl-11 pr-4 py-3 bg-white border border-slate-200 rounded-xl text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all duration-200 uppercase">
                            </div>
                        </div>
                        <div class="space-y-2 acesso-garagem-field ${isLocador ? '' : 'hidden'}">
                            <label class="block text-sm font-medium text-slate-700">Acesso de garagem</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400">
                                    <i class="fas fa-warehouse"></i>
                                </div>
                                <input type="text" name="veiculos[${index}][acesso_garagem]" placeholder="Ex: Térreo, Rampa ou Subsolo" 
                                       class="block w-full pl-11 pr-4 py-3 bg-white border border-slate-200 rounded-xl text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all duration-200">
                            </div>
                        </div>
                    </div>
                </div>
            `;
            veiculosContainer.insertAdjacentHTML('beforeend', html);
        });
    }

    // Remover itens dinâmicos
    document.addEventListener('click', (e) => {
        if (e.target.closest('.remove-item')) {
            const item = e.target.closest('.inquilino-item, .veiculo-item');
            if (confirm('Deseja remover este item?')) {
                item.classList.add('opacity-0', 'scale-95');
                setTimeout(() => item.remove(), 300);
            }
        }
    });

    // --- Validação ---
    function validateCurrentStep() {
        clearAllErrors();
        const step = steps[currentStep];
        let isValid = true;
        let firstErrorElement = null;

        // Validação genérica de campos [required]
        const requiredInputs = step.querySelectorAll('input[required], select[required]');
        requiredInputs.forEach(input => {
            if (input.type === 'radio') {
                const name = input.name;
                const checked = step.querySelector(`input[name="${name}"]:checked`);
                if (!checked) {
                    isValid = false;
                    const container = input.closest('.grid') || input.parentElement;
                    showFieldError(container, 'Você precisa selecionar uma opção');
                    if (!firstErrorElement) firstErrorElement = container;
                }
            } else if (!input.value.trim()) {
                isValid = false;
                const label = input.dataset.label || 'informação';
                showFieldError(input, `Você precisa inserir o ${label}`);
                if (!firstErrorElement) firstErrorElement = input;
            }
        });

        // Validações Específicas
        if (currentStep === 0) {
            const isLocatario = document.getElementById('radio_locatario').checked;
            if (isLocatario) {
                const nomeLocador = document.getElementById('locador_nome');
                const telLocador = document.getElementById('locador_telefone');
                if (!nomeLocador.value.trim()) {
                    isValid = false;
                    showFieldError(nomeLocador, 'Você precisa inserir o nome do locador');
                    if (!firstErrorElement) firstErrorElement = nomeLocador;
                }
                if (!telLocador.value.trim()) {
                    isValid = false;
                    showFieldError(telLocador, 'Você precisa inserir o WhatsApp do locador');
                    if (!firstErrorElement) firstErrorElement = telLocador;
                }
            }
        }

        if (currentStep === 1) {
            const edificioChecked = step.querySelector('input[name="edificio_id"]:checked');
            const aptValue = document.getElementById('numero_apartamento').value.trim();
            if (!edificioChecked) {
                isValid = false;
                const grid = document.getElementById('edificios_grid');
                showFieldError(grid, 'Você precisa selecionar um edifício');
                if (!firstErrorElement) firstErrorElement = grid;
            } else if (!aptValue) {
                isValid = false;
                const grid = document.getElementById('edificios_grid');
                showFieldError(grid, 'Você precisa informar o número do apartamento');
                if (!firstErrorElement) firstErrorElement = grid;
            }
        }

        if (currentStep === 4) {
            const entrada = document.getElementById('data_entrada');
            const saida = document.getElementById('data_saida');
            if (!entrada.value.trim()) {
                isValid = false;
                showFieldError(entrada, 'Você precisa inserir a data de chegada');
                if (!firstErrorElement) firstErrorElement = entrada;
            }
            if (!saida.value.trim()) {
                isValid = false;
                showFieldError(saida, 'Você precisa inserir a data de saída');
                if (!firstErrorElement) firstErrorElement = saida;
            } else if (entrada.value.trim() && saida.value.trim()) {
                const d1 = parseDate(entrada.value);
                const d2 = parseDate(saida.value);
                if (d2 < d1) {
                    isValid = false;
                    showFieldError(saida, 'A data de saída não pode ser anterior à de chegada');
                    if (!firstErrorElement) firstErrorElement = saida;
                }
            }
        }

        if (!isValid && firstErrorElement) {
            const container = firstErrorElement.closest('.field-container') || firstErrorElement.parentElement;
            container.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        return isValid;
    }

    function showFieldError(element, message) {
        const container = element.closest('.field-container') || element.parentElement;
        element.classList.add('border-red-500', 'ring-red-500/20');
        
        // Remover erro anterior se existir
        const oldError = container.querySelector('.field-error-msg');
        if (oldError) oldError.remove();

        // Adicionar nova mensagem
        const errorHtml = `<p class="field-error-msg text-xs font-bold text-red-500 mt-1 animate-fade-in"><i class="fas fa-exclamation-triangle mr-1"></i> ${message}</p>`;
        
        // Se for um input dentro de um relative div, coloca depois do div
        if (element.parentElement.classList.contains('relative')) {
            element.parentElement.insertAdjacentHTML('afterend', errorHtml);
        } else {
            element.insertAdjacentHTML('afterend', errorHtml);
        }
    }

    function clearAllErrors() {
        document.querySelectorAll('.field-error-msg').forEach(el => el.remove());
        document.querySelectorAll('.border-red-500').forEach(el => el.classList.remove('border-red-500', 'ring-red-500/20'));
    }

    // --- Revisão ---
    function populateReviewPage() {
        const container = document.getElementById('review-content');
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());
        
        const inquilinos = [];
        const veiculos = [];
        
        for (let [key, value] of formData.entries()) {
            const inqMatch = key.match(/inquilinos\[(\d+)\]\[(\w+)\]/);
            if (inqMatch) {
                const idx = inqMatch[1];
                const field = inqMatch[2];
                if (!inquilinos[idx]) inquilinos[idx] = {};
                inquilinos[idx][field] = value;
            }
            const veiMatch = key.match(/veiculos\[(\d+)\]\[(\w+)\]/);
            if (veiMatch) {
                const idx = veiMatch[1];
                const field = veiMatch[2];
                if (!veiculos[idx]) veiculos[idx] = {};
                veiculos[idx][field] = value;
            }
        }

        const edificio = EDIFICIOS_DATA.find(e => e.id == data.edificio_id);
        const nomeEdificio = edificio ? edificio.nome_edificio : 'Não informado';

        let html = `
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="p-6 bg-white border border-slate-100 rounded-2xl shadow-sm">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-4">Localização</h4>
                    <p class="text-lg font-bold text-slate-900">${nomeEdificio}</p>
                    <p class="text-slate-600">Apartamento ${data.numero_apartamento}</p>
                </div>
                <div class="p-6 bg-white border border-slate-100 rounded-2xl shadow-sm">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-4">Período</h4>
                    <p class="text-lg font-bold text-slate-900">${data.data_entrada} até ${data.data_saida}</p>
                    <p class="text-slate-600">Check-in registrado</p>
                </div>
            </div>

            ${data.user_type === 'locatario' ? `
                <div class="p-6 bg-white border border-slate-100 rounded-2xl shadow-sm">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-4">Dados do Locador</h4>
                    <p class="text-lg font-bold text-slate-900">${data.locador_nome}</p>
                    <p class="text-slate-600">WhatsApp: ${data.locador_telefone}</p>
                </div>
            ` : ''}

            <div class="p-6 bg-white border border-slate-100 rounded-2xl shadow-sm">
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-4">Hóspedes</h4>
                <div class="space-y-3">
                    ${inquilinos.filter(i => i && i.nome).map(i => `
                        <div class="flex items-center justify-between py-2 border-b border-slate-50 last:border-0">
                            <span class="font-medium text-slate-900">${i.nome}</span>
                            <span class="text-sm text-slate-500">${i.documento}</span>
                        </div>
                    `).join('')}
                </div>
            </div>

            ${veiculos.some(v => v && v.modelo) ? `
                <div class="p-6 bg-white border border-slate-100 rounded-2xl shadow-sm">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-4">Veículos</h4>
                    <div class="space-y-4">
                        ${veiculos.filter(v => v && v.modelo).map(v => `
                            <div class="flex flex-col py-2 border-b border-slate-50 last:border-0">
                                <div class="flex justify-between">
                                    <span class="font-medium text-slate-900">${v.modelo} (${v.cor})</span>
                                    <span class="font-mono text-sm bg-slate-100 px-2 py-0.5 rounded text-slate-700">${v.placa}</span>
                                </div>
                                ${v.acesso_garagem ? `<p class="text-xs text-primary-600 mt-1 font-medium italic">Acesso de garagem: ${v.acesso_garagem}</p>` : ''}
                            </div>
                        `).join('')}
                    </div>
                </div>
            ` : ''}
        `;
        container.innerHTML = html;
    }

    // --- Envio ---
    function submitForm() {
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());
        
        // Usar o botão de WhatsApp para feedback visual
        const btnWpp = document.getElementById('btn-enviar-whatsapp');
        if (btnWpp) {
            btnWpp.disabled = true;
            btnWpp.innerHTML = '<i class="fas fa-circle-notch animate-spin mr-2"></i> Enviando...';
        }

        fetch('salvar_locacao.php', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(result => {
            if (result.status === 'success') {
                const msg = generateWhatsAppMessage(formData);
                let telefone = '';
                
                if (data.user_type === 'locatario') {
                    // Se for locatário, combina o DDI e o número do locador
                    const ddi = data.locador_ddi ? data.locador_ddi.replace(/\D/g, '') : '55';
                    const numero = data.locador_telefone ? data.locador_telefone.replace(/\D/g, '') : '';
                    telefone = ddi + numero;
                } else {
                    // Se for locador, mantém a lógica atual (telefone da base responsável pelo edifício)
                    const edificio = EDIFICIOS_DATA.find(e => e.id == data.edificio_id);
                    telefone = edificio && edificio.telefone ? edificio.telefone.replace(/\D/g, '') : '';
                    
                    // Se o telefone da base não tiver prefixo de país, assume Brasil (55)
                    if (telefone && !telefone.startsWith('55') && telefone.length <= 11) {
                        telefone = '55' + telefone;
                    }
                }
                
                const whatsappUrl = `https://api.whatsapp.com/send?phone=${telefone}&text=${msg}`;
                window.location.href = whatsappUrl;
            } else {
                throw new Error(result.message || 'Erro ao salvar');
            }
        })
        .catch(err => {
            if (btnWpp) {
                btnWpp.disabled = false;
                btnWpp.innerHTML = '<i class="fas fa-paper-plane mr-2"></i> Enviar via WhatsApp';
            }
            alert(err.message || 'Erro ao salvar os dados. Tente novamente.');
        });
    }

    function generateWhatsAppMessage(formData) {
        let msg = "*Ficha de Controle de Locação*\n\n";
        const data = Object.fromEntries(formData.entries());
        
        const edificio = EDIFICIOS_DATA.find(e => e.id == data.edificio_id);
        msg += `*Edifício:* ${edificio ? formatText(edificio.nome_edificio) : 'Não informado'}\n`;
        msg += `*Apartamento:* ${data.numero_apartamento}\n\n`;
        msg += `*Data de Chegada:* ${data.data_entrada}\n`;
        msg += `*Data de Saída:* ${data.data_saida}\n\n`;
        
        if (data.user_type === 'locatario') {
            msg += `*Locador:* ${formatText(data.locador_nome)}\n`;
            msg += `*WhatsApp Locador:* ${data.locador_ddi} ${data.locador_telefone}\n\n`;
        }

        msg += `*Inquilinos:*\n`;
        const inquilinos = [];
        for (let [key, value] of formData.entries()) {
            const match = key.match(/inquilinos\[(\d+)\]\[(\w+)\]/);
            if (match) {
                const idx = match[1];
                const field = match[2];
                if (!inquilinos[idx]) inquilinos[idx] = {};
                inquilinos[idx][field] = value;
            }
        }
        inquilinos.filter(i => i && i.nome).forEach(i => {
            let telInfo = i.telefone ? `\nTelefone: ${i.telefone}` : '';
            msg += `*${formatText(i.nome)}*\nDocumento: ${i.documento}${telInfo}\n\n`;
        });
        
        msg += `*Veículos:*\n`;
        const veiculos = [];
        for (let [key, value] of formData.entries()) {
            const match = key.match(/veiculos\[(\d+)\]\[(\w+)\]/);
            if (match) {
                const idx = match[1];
                const field = match[2];
                if (!veiculos[idx]) veiculos[idx] = {};
                veiculos[idx][field] = value;
            }
        }
        const validVeiculos = veiculos.filter(v => v && v.modelo);
        if (validVeiculos.length === 0) {
            msg += "Nenhum veículo cadastrado.\n";
        } else {
            validVeiculos.forEach(v => {
                msg += `${formatText(v.modelo)}\n${formatText(v.cor)}\nPlaca: ${v.placa.toUpperCase()}\n`;
                if (v.acesso_garagem) msg += `Acesso de garagem: ${formatText(v.acesso_garagem)}\n`;
                msg += `\n`;
            });
        }
        
        return encodeURIComponent(msg);
    }

    // --- Helpers ---
    function initDatePickers() {
        flatpickr(".datepicker", {
            dateFormat: "d/m/Y",
            minDate: "today",
            locale: "pt",
            disableMobile: "true"
        });
    }

    function initPhoneMask() {
        $('#locador_ddi').on('input', function() {
            let val = $(this).val();
            if (val && !val.startsWith('+')) {
                $(this).val('+' + val.replace(/\D/g, ''));
            } else if (val) {
                $(this).val('+' + val.substring(1).replace(/\D/g, ''));
            }
        });
        $('#locador_telefone').mask('(00) 00000-0000');
        $('#hospede_principal_telefone').mask('(00) 00000-0000');
    }

    // --- Enviar via WhatsApp ---
    const btnEnviarWhatsapp = document.getElementById('btn-enviar-whatsapp');
    if (btnEnviarWhatsapp) {
        btnEnviarWhatsapp.addEventListener('click', () => {
            submitForm();
        });
    }

    // --- Botão Voltar na Revisão ---
    const btnVoltarRevisao = document.getElementById('btn-voltar-revisao');
    if (btnVoltarRevisao) {
        btnVoltarRevisao.addEventListener('click', () => {
            if (currentStep > 0) {
                currentStep--;
                updateStepUI();
            }
        });
    }

    // --- Gerar PDF ---
    const btnGerarPdf = document.getElementById('btn-gerar-pdf');
    if (btnGerarPdf) {
        btnGerarPdf.addEventListener('click', () => {
            const originalAction = form.action;
            const originalTarget = form.target;
            
            form.action = 'gerar_pdf.php';
            form.target = '_blank';
            form.submit();
            
            // Pequeno delay para garantir que o submit ocorra antes de restaurar os atributos
            setTimeout(() => {
                form.action = originalAction;
                form.target = originalTarget;
            }, 500);
        });
    }

    function parseDate(str) {
        const parts = str.split('/');
        return new Date(parts[2], parts[1] - 1, parts[0]);
    }
});
