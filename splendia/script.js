(function () {
    'use strict';

    let stepAtual = 1;
    const TOTAL_STEPS = 3;
    const form = document.getElementById('splendia-form');
    const btnPrev = document.getElementById('btn-prev');
    const btnNext = document.getElementById('btn-next');
    const container = document.getElementById('pessoas-container');
    const btnAdd = document.getElementById('btn-add-pessoa');
    const erroBox = document.getElementById('global-error-message');

    function mostrarErro(msg) {
        if (!msg) { erroBox.classList.add('hidden'); return; }
        erroBox.querySelector('div').textContent = msg;
        erroBox.classList.remove('hidden');
    }

    function atualizarStepper() {
        for (let i = 1; i <= TOTAL_STEPS; i++) {
            const circle = document.getElementById('circle-' + i);
            const lineR = document.getElementById('line-r-' + i);
            const lineL = document.getElementById('line-l-' + i);
            if (i < stepAtual) {
                circle.className = 'flex items-center justify-center w-10 h-10 rounded-full border-2 transition-all duration-300 step-circle bg-primary-600 border-primary-600 text-white';
                circle.innerHTML = '<i class="fas fa-check text-sm"></i>';
                if (lineR) lineR.style.width = '100%';
                if (lineL) lineL.style.width = '100%';
            } else if (i === stepAtual) {
                circle.className = 'flex items-center justify-center w-10 h-10 rounded-full border-2 transition-all duration-300 step-circle bg-primary-600 border-primary-600 text-white';
                circle.innerHTML = '<span class="text-sm font-bold">' + i + '</span>';
                if (lineR) lineR.style.width = '0%';
                if (lineL) lineL.style.width = '100%';
            } else {
                circle.className = 'flex items-center justify-center w-10 h-10 rounded-full border-2 transition-all duration-300 step-circle bg-white border-slate-300 text-slate-500';
                circle.innerHTML = '<span class="text-sm font-bold">' + i + '</span>';
                if (lineR) lineR.style.width = '0%';
                if (lineL) lineL.style.width = '0%';
            }
        }
        btnPrev.disabled = stepAtual === 1;
        const ultimo = stepAtual === TOTAL_STEPS;
        btnNext.querySelector('span').textContent = ultimo ? 'Enviar via WhatsApp' : 'Próxima';
        btnNext.querySelector('i').className = ultimo ? 'fab fa-whatsapp ml-1 sm:ml-2' : 'fas fa-arrow-right ml-1 sm:ml-2';
    }

    function irParaStep(n) {
        document.querySelectorAll('.step-content').forEach(s => {
            s.classList.toggle('active', parseInt(s.dataset.step) === n);
        });
        stepAtual = n;
        atualizarStepper();
        mostrarErro('');
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // --- Pessoas ---
    let pessoaCount = 0;

    function criarRowPessoa(dados) {
        pessoaCount++;
        const idx = pessoaCount;
        const div = document.createElement('div');
        div.className = 'pessoa-row rounded-2xl border border-slate-200 bg-white p-4';
        div.dataset.idx = idx;
        div.innerHTML = `
            <div class="flex items-center justify-between mb-3">
                <p class="text-xs font-bold uppercase tracking-widest text-slate-400">Morador ${idx}</p>
                <button type="button" class="btn-remove-pessoa flex h-8 w-8 items-center justify-center rounded-lg bg-red-50 text-red-500 hover:bg-red-100 transition-all" title="Remover">
                    <i class="fas fa-trash-alt" style="font-size:11px"></i>
                </button>
            </div>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div class="sm:col-span-2 space-y-1">
                    <label class="text-xs font-semibold text-slate-600">Nome completo *</label>
                    <input type="text" name="pessoas[${idx}][nome]" value="${dados ? dados.nome || '' : ''}" class="w-full px-3 py-2.5 rounded-xl border border-slate-300 bg-white text-slate-900 placeholder-slate-400 input-focus-effect outline-none transition-all text-sm" placeholder="Nome completo">
                </div>
                <div class="space-y-1">
                    <label class="text-xs font-semibold text-slate-600">RG ou CPF</label>
                    <input type="text" name="pessoas[${idx}][documento]" value="${dados ? dados.documento || '' : ''}" class="w-full px-3 py-2.5 rounded-xl border border-slate-300 bg-white text-slate-900 placeholder-slate-400 input-focus-effect outline-none transition-all text-sm" placeholder="Ex: 123.456.789-00">
                </div>
                <div class="flex items-center gap-3 sm:pt-6">
                    <input type="checkbox" name="pessoas[${idx}][locatario_anual]" value="1" id="locatario-${idx}" ${dados && dados.locatario_anual ? 'checked' : ''} class="h-5 w-5 rounded accent-primary-600">
                    <label for="locatario-${idx}" class="text-sm font-medium text-slate-700 cursor-pointer">Locatário anual</label>
                </div>
            </div>
        `;
        div.querySelector('.btn-remove-pessoa').addEventListener('click', () => {
            div.remove();
            renumerarPessoas();
        });
        return div;
    }

    function renumerarPessoas() {
        pessoaCount = 0;
        document.querySelectorAll('#pessoas-container .pessoa-row').forEach(row => {
            pessoaCount++;
            const idx = pessoaCount;
            row.dataset.idx = idx;
            row.querySelector('.mb-3 p').textContent = 'Morador ' + idx;
            row.querySelectorAll('input').forEach(inp => {
                const n = inp.name;
                const nome = inp.name.replace(/pessoas\[\d+\]/, 'pessoas[' + idx + ']');
                inp.name = nome;
                if (inp.type === 'checkbox') inp.id = 'locatario-' + idx;
                if (inp.type === 'checkbox') inp.nextElementSibling.setAttribute('for', 'locatario-' + idx);
            });
        });
    }

    function coletarPessoas() {
        const pessoas = [];
        document.querySelectorAll('#pessoas-container .pessoa-row').forEach(row => {
            const nome = row.querySelector('input[name$="[nome]"]').value.trim();
            if (!nome) return;
            const documento = row.querySelector('input[name$="[documento]"]').value.trim();
            const locatario = row.querySelector('input[name$="[locatario_anual]"]').checked;
            pessoas.push({ nome, documento, locatario_anual: locatario });
        });
        return pessoas;
    }

    function validarStep() {
        if (stepAtual === 1) {
            const apt = document.getElementById('apartamento').value.trim();
            if (!apt) { mostrarErro('Informe o número do apartamento.'); return false; }
        }
        if (stepAtual === 2) {
            const pessoas = coletarPessoas();
            if (pessoas.length === 0) {
                mostrarErro('Adicione pelo menos um morador. Use "Adicionar morador".');
                return false;
            }
            const semNome = [...document.querySelectorAll('#pessoas-container input[name$="[nome]"]')].some(i => !i.value.trim());
            if (semNome) { mostrarErro('Preencha o nome de todos os moradores.'); return false; }
        }
        return true;
    }

    // --- Revisão ---
    function montarRevisao() {
        const apt = document.getElementById('apartamento').value.trim();
        document.getElementById('revisao-apartamento').textContent = apt;
        const pessoas = coletarPessoas();
        document.getElementById('revisao-qtd').textContent = pessoas.length;
        const box = document.getElementById('revisao-pessoas');
        box.innerHTML = '';
        pessoas.forEach(p => {
            const div = document.createElement('div');
            div.className = 'flex items-center justify-between gap-3 rounded-xl bg-white border border-slate-200 px-4 py-2.5';
            div.innerHTML = `
                <div>
                    <p class="text-sm font-bold text-slate-900">${esc(p.nome)}</p>
                    <p class="text-xs text-slate-500">${p.documento ? esc(p.documento) : 'RG/CPF não informado'}</p>
                </div>
                ${p.locatario_anual
                    ? '<span class="inline-flex items-center rounded-lg bg-amber-100 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-amber-700">Locatário anual</span>'
                    : '<span class="inline-flex items-center rounded-lg bg-green-100 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-green-700">Proprietário</span>'}
            `;
            box.appendChild(div);
        });
    }

    function esc(s) {
        const d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    // --- Envio ---
    function gerarMensagemWhatsApp() {
        const apt = document.getElementById('apartamento').value.trim();
        const pessoas = coletarPessoas();
        let msg = `*ATUALIZAÇÃO DE CADASTRO*\n\n`;
        msg += `*Edifício:* ${EDIFICIO_NOME}\n`;
        msg += `*Apartamento:* ${apt}\n\n`;
        msg += `*Moradores:*\n`;
        pessoas.forEach(p => {
            msg += `*${p.nome}*\n`;
            if (p.documento) msg += `RG/CPF: ${p.documento}\n`;
            msg += `${p.locatario_anual ? '🏠 Locatário anual' : '🏠 Proprietário'}\n\n`;
        });
        return encodeURIComponent(msg);
    }

    async function enviarForm() {
        const btn = btnNext;
        btn.disabled = true;
        btn.querySelector('span').textContent = 'Enviando...';
        mostrarErro('');

        const formData = new FormData(form);
        try {
            const res = await fetch(form.action, {
                method: 'POST',
                body: formData
            });
            let data;
            try {
                data = await res.json();
            } catch (e) {
                throw new Error('Resposta inválida do servidor.');
            }
            if (data.status === 'success') {
                const msg = gerarMensagemWhatsApp();
                let tel = (EDIFICIO_TELEFONE || '').replace(/\D/g, '');
                if (tel && !tel.startsWith('55') && tel.length <= 11) tel = '55' + tel;
                window.location.href = `https://api.whatsapp.com/send?phone=${tel}&text=${msg}`;
            } else {
                throw new Error(data.message || 'Erro ao salvar.');
            }
        } catch (err) {
            btn.disabled = false;
            btn.querySelector('span').textContent = 'Enviar via WhatsApp';
            mostrarErro(err.message || 'Erro ao salvar. Tente novamente.');
        }
    }

    // --- Eventos ---
    btnNext.addEventListener('click', () => {
        if (stepAtual < TOTAL_STEPS) {
            if (!validarStep()) return;
            if (stepAtual === 1) document.getElementById('apt-resumo').textContent = document.getElementById('apartamento').value.trim();
            irParaStep(stepAtual + 1);
            if (stepAtual === TOTAL_STEPS) montarRevisao();
        } else {
            enviarForm();
        }
    });

    btnPrev.addEventListener('click', () => {
        if (stepAtual > 1) irParaStep(stepAtual - 1);
    });

    btnAdd.addEventListener('click', () => {
        container.appendChild(criarRowPessoa(null));
    });

    // Inicia com uma pessoa
    container.appendChild(criarRowPessoa(null));
    atualizarStepper();
})();