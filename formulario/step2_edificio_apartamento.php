<div class="step-content" id="step-2">
    <div class="space-y-8">
        <div class="text-center">
            <h2 class="text-2xl font-bold text-slate-900">Localização</h2>
            <p class="mt-2 text-slate-600">Onde você ficará hospedado?</p>
        </div>

        <div class="space-y-6">
            <!-- Seleção de Edifício com Busca -->
            <div class="space-y-3 field-container">
                <label class="block text-sm font-medium text-slate-700">Selecione o Edifício</label>
                
                <!-- Campo de Busca -->
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400">
                        <i class="fas fa-search"></i>
                    </div>
                    <input type="text" id="edificio_search" placeholder="Buscar edifício pelo nome..." 
                           class="block w-full pl-11 pr-4 py-3 bg-white border border-slate-200 rounded-xl text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all duration-200">
                </div>

                <!-- Grid de Edifícios -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-[400px] overflow-y-auto p-1 pr-2 custom-scrollbar" id="edificios_grid">
                    <?php foreach ($edificios as $edificio): ?>
                        <div class="edificio-card group relative flex items-center p-4 bg-white border border-slate-200 rounded-xl cursor-pointer hover:border-primary-500 hover:bg-primary-50/30 transition-all duration-200" 
                             data-id="<?php echo $edificio['id']; ?>" 
                             data-name="<?php echo htmlspecialchars(strtolower($edificio['nome_edificio'])); ?>"
                             onclick="openApartmentModal(this)">
                            <div class="w-10 h-10 flex items-center justify-center bg-slate-100 text-slate-500 rounded-lg group-hover:bg-primary-100 group-hover:text-primary-600 transition-colors duration-200">
                                <i class="fas fa-building"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-bold text-slate-900"><?php echo htmlspecialchars($edificio['nome_edificio']); ?></p>
                                <!-- <p class="text-xs text-slate-500"><?php echo htmlspecialchars($edificio['nome_base']); ?></p> -->
                                <p class="text-[10px] mt-1 text-primary-600 font-bold apt-display hidden">Apt: <span class="apt-number"></span></p>
                            </div>
                            <div class="absolute right-4 opacity-0 group-hover:opacity-100 transition-opacity duration-200 check-icon">
                                <i class="fas fa-check-circle text-primary-500"></i>
                            </div>
                            <!-- Input Hidden para o rádio real -->
                            <input type="radio" name="edificio_id" value="<?php echo $edificio['id']; ?>" class="sr-only">
                        </div>
                    <?php endforeach; ?>
                    
                    <!-- Mensagem Sem Resultados -->
                    <div id="no_edificios" class="hidden col-span-full py-8 text-center text-slate-500">
                        <i class="fas fa-search text-3xl mb-2 opacity-20"></i>
                        <p>Nenhum edifício encontrado.</p>
                    </div>
                </div>
                
                <!-- Input hidden para o número do apartamento -->
                <input type="hidden" name="numero_apartamento" id="numero_apartamento">
            </div>
        </div>
    </div>
</div>

<!-- Modal para Número do Apartamento -->
<div id="aptModal" class="fixed inset-0 z-[60] hidden flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm animate-fade-in">
    <div class="bg-white w-full max-w-md rounded-3xl shadow-2xl overflow-hidden animate-slide-up">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between bg-primary-600 text-white">
            <h3 class="text-xl font-bold flex items-center gap-2">
                <i class="fas fa-door-open"></i>
                Número do Apartamento
            </h3>
            <button type="button" onclick="closeApartmentModal()" class="text-white/80 hover:text-white transition-colors">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div class="p-8 space-y-6">
            <div class="text-center">
                <p class="text-slate-600">Informe o número do apartamento para o edifício:</p>
                <p id="modalEdificioName" class="text-lg font-bold text-slate-900 mt-1"></p>
            </div>
            
            <div class="space-y-2 field-container">
                <label for="modal_apt_input" class="block text-sm font-medium text-slate-700">Número do Apartamento</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400">
                        <i class="fas fa-hashtag"></i>
                    </div>
                    <input type="text" id="modal_apt_input" placeholder="Ex: 101, 202-A" 
                           class="block w-full pl-11 pr-4 py-4 bg-slate-50 border border-slate-200 rounded-2xl text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all duration-200 text-lg font-bold">
                </div>
                <p id="modal_apt_error" class="hidden text-xs text-red-500 mt-1">Por favor, informe o número do apartamento.</p>
            </div>
            
            <button type="button" onclick="confirmApartment()" class="w-full py-4 bg-primary-600 text-white rounded-2xl font-bold text-lg hover:bg-primary-700 shadow-lg shadow-primary-600/20 transition-all transform hover:-translate-y-1 active:translate-y-0">
                Confirmar apartamento
            </button>
        </div>
    </div>
</div>

<style>
    .edificio-card.selected {
        border-color: #22c55e;
        background-color: #f0fdf4;
        box-shadow: 0 0 0 1px #22c55e;
    }
    .edificio-card.selected .w-10 {
        background-color: #22c55e;
        color: white;
    }
    .edificio-card.selected .check-icon {
        opacity: 1;
    }
    .custom-scrollbar::-webkit-scrollbar {
        width: 4px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
        background: #f1f1f1;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 10px;
    }
</style>
