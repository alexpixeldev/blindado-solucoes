<div class="step-content" id="step-5">
    <div class="space-y-8">
        <div class="text-center">
            <h2 class="text-2xl font-bold text-slate-900">Check-in</h2>
            <p class="mt-2 text-slate-600">Quase lá! Informe o período da sua estadia.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <!-- Data de Entrada -->
            <div class="space-y-2 field-container">
                <label for="data_entrada" class="block text-sm font-medium text-slate-700">Data de Chegada</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <input type="text" name="data_entrada" id="data_entrada" placeholder="Selecione a data" required data-label="data de chegada"
                           class="datepicker block w-full pl-11 pr-4 py-3 bg-white border border-slate-200 rounded-xl text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all duration-200">
                </div>
            </div>

            <!-- Data de Saída -->
            <div class="space-y-2 field-container">
                <label for="data_saida" class="block text-sm font-medium text-slate-700">Data de Saída</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400">
                        <i class="fas fa-calendar-times"></i>
                    </div>
                    <input type="text" name="data_saida" id="data_saida" placeholder="Selecione a data" required data-label="data de saída"
                           class="datepicker block w-full pl-11 pr-4 py-3 bg-white border border-slate-200 rounded-xl text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all duration-200">
                </div>
            </div>
        </div>
    </div>
</div>
