<div class="step-content" id="step-4">
    <div class="space-y-8">
        <div class="text-center">
            <h2 class="text-2xl font-bold text-slate-900">Veículos</h2>
            <p class="mt-2 text-slate-600">Informe os dados dos veículos que utilizarão a garagem.</p>
        </div>

        <div id="veiculos-container" class="space-y-6">
            <!-- Primeiro Veículo -->
            <div class="veiculo-item relative p-6 bg-white border border-slate-200 rounded-2xl shadow-sm animate-fade-in group" data-index="0">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 flex items-center justify-center bg-primary-100 text-primary-600 rounded-lg font-bold text-sm">1</div>
                        <h3 class="text-lg font-semibold text-slate-900">Veículo 1</h3>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-slate-700">Modelo</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400">
                                <i class="fas fa-car"></i>
                            </div>
                            <input type="text" name="veiculos[0][modelo]" placeholder="Ex: Toyota Corolla" 
                                   class="block w-full pl-11 pr-4 py-3 bg-white border border-slate-200 rounded-xl text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all duration-200">
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-slate-700">Cor</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400">
                                <i class="fas fa-palette"></i>
                            </div>
                            <input type="text" name="veiculos[0][cor]" placeholder="Ex: Prata" 
                                   class="block w-full pl-11 pr-4 py-3 bg-white border border-slate-200 rounded-xl text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all duration-200">
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-slate-700">Placa</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400">
                                <i class="fas fa-barcode"></i>
                            </div>
                            <input type="text" name="veiculos[0][placa]" placeholder="ABC-1234" 
                                   class="block w-full pl-11 pr-4 py-3 bg-white border border-slate-200 rounded-xl text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all duration-200 uppercase">
                        </div>
                    </div>

                    <!-- Campo Condicional: Acesso de Garagem -->
                    <div class="space-y-2 acesso-garagem-field hidden">
                        <label class="block text-sm font-medium text-slate-700">Acesso de garagem</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400">
                                <i class="fas fa-warehouse"></i>
                            </div>
                            <input type="text" name="veiculos[0][acesso_garagem]" placeholder="Ex: Térreo, Rampa ou Subsolo" 
                                   class="block w-full pl-11 pr-4 py-3 bg-white border border-slate-200 rounded-xl text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all duration-200">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Botão Adicionar Veículo -->
        <button type="button" id="add-veiculo" 
                class="w-full py-4 flex items-center justify-center gap-3 bg-white border-2 border-dashed border-slate-200 rounded-2xl text-slate-500 font-semibold hover:border-primary-500 hover:text-primary-600 hover:bg-primary-50/30 transition-all duration-300 group">
            <div class="w-8 h-8 flex items-center justify-center bg-slate-100 text-slate-400 rounded-full group-hover:bg-primary-100 group-hover:text-primary-600 transition-colors duration-300">
                <i class="fas fa-plus"></i>
            </div>
            Adicionar outro veículo
        </button>
    </div>
</div>
