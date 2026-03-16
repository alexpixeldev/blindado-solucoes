<div class="step-content active" id="step-1">
    <div class="space-y-8">
        <div class="text-center">
            <h2 class="text-2xl font-bold text-slate-900">Quem é você?</h2>
            <p class="mt-2 text-slate-600">Selecione o seu perfil para continuarmos o cadastro.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 field-container">
            <!-- Opção Locador -->
            <label class="relative flex flex-col p-6 bg-white border-2 border-slate-100 rounded-2xl cursor-pointer hover:border-primary-500 hover:bg-primary-50/30 transition-all duration-300 group selection-card" for="radio_locador">
                <input type="radio" name="user_type" id="radio_locador" value="locador" class="sr-only peer" required>
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-primary-100 text-primary-600 rounded-xl group-hover:bg-primary-600 group-hover:text-white transition-colors duration-300">
                        <i class="fas fa-key text-xl"></i>
                    </div>
                    <div class="w-6 h-6 rounded-full border-2 border-slate-200 peer-checked:border-primary-600 peer-checked:bg-primary-600 flex items-center justify-center transition-all duration-300">
                        <div class="w-2 h-2 rounded-full bg-white opacity-0 peer-checked:opacity-100 transition-opacity duration-300"></div>
                    </div>
                </div>
                <span class="text-lg font-bold text-slate-900">Sou Locador</span>
                <span class="mt-1 text-sm text-slate-500">Proprietário ou responsável pelo imóvel.</span>
                
                <div class="absolute inset-0 border-2 border-transparent peer-checked:border-primary-600 rounded-2xl pointer-events-none transition-all duration-300"></div>
            </label>

            <!-- Opção Locatário -->
            <label class="relative flex flex-col p-6 bg-white border-2 border-slate-100 rounded-2xl cursor-pointer hover:border-primary-500 hover:bg-primary-50/30 transition-all duration-300 group selection-card" for="radio_locatario">
                <input type="radio" name="user_type" id="radio_locatario" value="locatario" class="sr-only peer">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-primary-100 text-primary-600 rounded-xl group-hover:bg-primary-600 group-hover:text-white transition-colors duration-300">
                        <i class="fas fa-user-tag text-xl"></i>
                    </div>
                    <div class="w-6 h-6 rounded-full border-2 border-slate-200 peer-checked:border-primary-600 peer-checked:bg-primary-600 flex items-center justify-center transition-all duration-300">
                        <div class="w-2 h-2 rounded-full bg-white opacity-0 peer-checked:opacity-100 transition-opacity duration-300"></div>
                    </div>
                </div>
                <span class="text-lg font-bold text-slate-900">Sou Locatário</span>
                <span class="mt-1 text-sm text-slate-500">Hóspede ou inquilino temporário.</span>
                
                <div class="absolute inset-0 border-2 border-transparent peer-checked:border-primary-600 rounded-2xl pointer-events-none transition-all duration-300"></div>
            </label>
        </div>

        <!-- Campos do Locador (Aparecem apenas se for Locatário) -->
        <div id="locadorFields" class="hidden space-y-6 p-6 bg-slate-50 rounded-2xl border border-slate-200 animate-fade-in">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-1 h-6 bg-primary-500 rounded-full"></div>
                <h3 class="text-lg font-semibold text-slate-900">Dados do Locador</h3>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div class="space-y-2 field-container">
                    <label for="locador_nome" class="block text-sm font-medium text-slate-700">Nome do Locador</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400">
                            <i class="fas fa-user"></i>
                        </div>
                        <input type="text" name="locador_nome" id="locador_nome" placeholder="Nome do locador" data-label="nome do locador"
                               class="block w-full pl-11 pr-4 py-3 bg-white border border-slate-200 rounded-xl text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all duration-200">
                    </div>
                </div>

                <div class="space-y-2 field-container">
                    <label for="locador_telefone" class="block text-sm font-medium text-slate-700">WhatsApp do Locador</label>
                    <div class="flex gap-2">
                        <!-- Campo DDI -->
                        <div class="relative w-1/3">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                <i class="fas fa-globe"></i>
                            </div>
                            <input type="text" name="locador_ddi" id="locador_ddi" value="+55" placeholder="+DDI"
                                   class="block w-full pl-9 pr-2 py-3 bg-white border border-slate-200 rounded-xl text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all duration-200 text-center">
                        </div>
                        <!-- Campo Telefone -->
                        <div class="relative flex-1">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400">
                                <i class="fab fa-whatsapp"></i>
                            </div>
                            <input type="text" name="locador_telefone" id="locador_telefone" placeholder="(00) 00000-0000" data-label="WhatsApp do locador"
                                   class="block w-full pl-11 pr-4 py-3 bg-white border border-slate-200 rounded-xl text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all duration-200">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
