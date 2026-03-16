<div class="step-content" id="step-3">
    <div class="space-y-8">
        <div class="text-center">
            <h2 class="text-2xl font-bold text-slate-900">Hóspedes / Inquilinos</h2>
            <p class="mt-2 text-slate-600">Quem ficará no imóvel? Adicione todos os ocupantes.</p>
        </div>

        <div id="inquilinos-container" class="space-y-6">
            <!-- Primeiro Inquilino (Sempre Visível) -->
            <div class="inquilino-item relative p-6 bg-white border border-slate-200 rounded-2xl shadow-sm animate-fade-in group" data-index="0">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 flex items-center justify-center bg-primary-100 text-primary-600 rounded-lg font-bold text-sm">1</div>
                        <h3 class="text-lg font-semibold text-slate-900">Hóspede Principal</h3>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div class="space-y-2 field-container">
                        <label class="block text-sm font-medium text-slate-700">Nome Completo</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400">
                                <i class="fas fa-user"></i>
                            </div>
                            <input type="text" name="inquilinos[0][nome]" placeholder="Nome do hóspede" required data-label="nome"
                                   class="block w-full pl-11 pr-4 py-3 bg-white border border-slate-200 rounded-xl text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all duration-200">
                        </div>
                    </div>

                    <div class="space-y-2 field-container">
                        <label class="block text-sm font-medium text-slate-700">Documento</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400">
                                <i class="fas fa-id-card"></i>
                            </div>
                            <input type="text" name="inquilinos[0][documento]" placeholder="Número do documento" required data-label="documento"
                                   class="block w-full pl-11 pr-4 py-3 bg-white border border-slate-200 rounded-xl text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all duration-200">
                        </div>
                    </div>

                    <div class="space-y-2 field-container">
                        <label class="block text-sm font-medium text-slate-700">Telefone</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400">
                                <i class="fas fa-phone"></i>
                            </div>
                            <input type="text" name="inquilinos[0][telefone]" id="hospede_principal_telefone" placeholder="(00) 00000-0000" data-label="telefone"
                                   class="block w-full pl-11 pr-4 py-3 bg-white border border-slate-200 rounded-xl text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all duration-200">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Botão Adicionar Hóspede -->
        <button type="button" id="add-inquilino" 
                class="w-full py-4 flex items-center justify-center gap-3 bg-white border-2 border-dashed border-slate-200 rounded-2xl text-slate-500 font-semibold hover:border-primary-500 hover:text-primary-600 hover:bg-primary-50/30 transition-all duration-300 group">
            <div class="w-8 h-8 flex items-center justify-center bg-slate-100 text-slate-400 rounded-full group-hover:bg-primary-100 group-hover:text-primary-600 transition-colors duration-300">
                <i class="fas fa-plus"></i>
            </div>
            Adicionar outro hóspede
        </button>
    </div>
</div>
