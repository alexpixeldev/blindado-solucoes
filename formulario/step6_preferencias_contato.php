<div class="step-content" id="step-6">
    <div class="space-y-8">
        <div class="text-center">
            <h2 class="text-2xl font-bold text-slate-900">Revisão dos Dados</h2>
            <p class="mt-2 text-slate-600">Confira se todas as informações estão corretas antes de enviar.</p>
        </div>

        <div id="review-content" class="space-y-6">
            <!-- O conteúdo da revisão será inserido aqui via JavaScript -->
            <div class="animate-pulse space-y-4">
                <div class="h-20 bg-slate-100 rounded-2xl"></div>
                <div class="h-40 bg-slate-100 rounded-2xl"></div>
                <div class="h-32 bg-slate-100 rounded-2xl"></div>
            </div>
        </div>

        <!-- Botões de Ação -->
        <div class="flex flex-col gap-4 pt-4">
            <!-- Botão Enviar via WhatsApp -->
            <button type="button" id="btn-enviar-whatsapp" class="inline-flex items-center justify-center px-6 py-3 text-sm font-bold text-white bg-primary-600 rounded-xl hover:bg-primary-700 shadow-lg shadow-primary-600/20 hover:shadow-primary-600/30 transition-all duration-200 transform hover:-translate-y-0.5 active:translate-y-0 w-full">
                <i class="fas fa-paper-plane mr-2"></i>
                Enviar via WhatsApp
            </button>
            
            <!-- Botão Gerar PDF -->
            <button type="button" id="btn-gerar-pdf" class="inline-flex items-center justify-center px-6 py-3 text-sm font-bold text-white bg-slate-800 rounded-xl hover:bg-slate-900 shadow-lg transition-all duration-200 transform hover:-translate-y-0.5 active:translate-y-0 w-full">
                <i class="fas fa-file-pdf mr-2"></i>
                Gerar PDF para Impressão
            </button>

            <!-- Botão Voltar -->
            <button type="button" id="btn-voltar-revisao" class="inline-flex items-center justify-center px-6 py-3 text-sm font-semibold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 hover:text-slate-900 transition-all duration-200 w-full">
                <i class="fas fa-arrow-left mr-2"></i>
                Voltar para editar
            </button>
        </div>

        <div class="p-6 bg-primary-50 border border-primary-100 rounded-2xl flex items-start gap-4">
            <div class="p-2 bg-primary-100 text-primary-600 rounded-lg">
                <i class="fas fa-info-circle"></i>
            </div>
            <div class="text-sm text-primary-800 leading-relaxed">
                Ao clicar em <strong>Enviar via WhatsApp</strong>, seus dados serão salvos em nosso sistema e você será redirecionado para o WhatsApp para finalizar o atendimento com nossa equipe.
            </div>
        </div>
    </div>
</div>
