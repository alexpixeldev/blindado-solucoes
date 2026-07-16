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
                    <button type="button" class="edit-inquilino-btn w-8 h-8 flex items-center justify-center rounded-lg bg-blue-100 text-blue-600 hover:bg-blue-600 hover:text-white transition-colors" title="Editar">
                        <i class="fas fa-edit text-xs"></i>
                    </button>
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
                <div class="space-y-2 field-container mt-6 selfie-field-container hidden">
                    <label class="block text-sm font-medium text-slate-700">Selfie do Hóspede</label>
                    <div class="space-y-3">
                        <div class="flex flex-wrap gap-3">
                            <button type="button" class="selfie-method-button inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-all" data-method="camera" data-input-target="selfie-input-0">
                                <i class="fas fa-camera"></i>
                                Tirar foto agora
                            </button>
                            <button type="button" class="selfie-method-button inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-all" data-method="file" data-input-target="selfie-input-0">
                                <i class="fas fa-upload"></i>
                                Enviar do aparelho
                            </button>
                        </div>
                        <input type="file" id="selfie-input-0" name="inquilinos[0][selfie]" accept="image/*" class="hidden" data-preview-target="selfie-preview-0" />
                        <img id="selfie-preview-0" src="" alt="Prévia da selfie" class="hidden w-full h-40 rounded-2xl object-cover border border-slate-200 bg-slate-50 mb-3" />
                        <div class="grid gap-4 md:grid-cols-[220px_1fr] items-stretch">
                            <img src="../img/facial.png" alt="Exemplo de selfie" class="w-full max-w-[220px] h-auto rounded-3xl border border-slate-200 bg-slate-50" />
                            <div class="rounded-3xl border border-primary-200 bg-primary-50 p-4 flex flex-col justify-center">
                                <p class="text-sm font-semibold text-primary-900">Como deve ser a selfie:</p>
                                <ul class="mt-3 space-y-2 text-sm text-slate-700">
                                    <li>• Rosto inteiro e centralizado</li>
                                    <li>• Local bem iluminado</li>
                                    <li>• Olhar fixo na câmera</li>
                                    <li>• Sem óculos escuros, boné ou máscara</li>
                                </ul>
                                <p class="mt-3 text-sm text-slate-600">Use o exemplo ao lado: estilo foto 3x4.</p>
                            </div>
                        </div>
                        <p id="selfie-file-name-0" class="text-xs text-slate-500"></p>
                    </div>
                </div>
            </div>
        </div>

        <template id="template-inquilino">
            <div class="inquilino-item relative p-6 bg-white border border-slate-200 rounded-2xl shadow-sm animate-fade-in group" data-index="__INDEX__">
                <button type="button" class="remove-item absolute -top-3 -right-3 w-8 h-8 bg-red-500 text-white rounded-full shadow-lg hover:bg-red-600 transition-colors flex items-center justify-center">
                    <i class="fas fa-times text-xs"></i>
                </button>
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-8 h-8 flex items-center justify-center bg-primary-100 text-primary-600 rounded-lg font-bold text-sm">__INDEX_NUMBER__</div>
                    <h3 class="text-lg font-semibold text-slate-900">Hóspede Adicional</h3>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div class="space-y-2 field-container">
                        <label class="block text-sm font-medium text-slate-700">Nome Completo</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400">
                                <i class="fas fa-user"></i>
                            </div>
                            <input type="text" name="inquilinos[__INDEX__][nome]" placeholder="Nome do hóspede" required data-label="nome"
                                   class="block w-full pl-11 pr-4 py-3 bg-white border border-slate-200 rounded-xl text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all duration-200">
                        </div>
                    </div>
                    <div class="space-y-2 field-container">
                        <label class="block text-sm font-medium text-slate-700">Documento</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400">
                                <i class="fas fa-id-card"></i>
                            </div>
                            <input type="text" name="inquilinos[__INDEX__][documento]" placeholder="Número do documento" required data-label="documento"
                                   class="block w-full pl-11 pr-4 py-3 bg-white border border-slate-200 rounded-xl text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all duration-200">
                        </div>
                    </div>
                </div>
                <div class="space-y-2 field-container mt-6 selfie-field-container hidden">
                    <label class="block text-sm font-medium text-slate-700">Selfie do Hóspede</label>
                    <div class="space-y-3">
                        <div class="flex flex-wrap gap-3">
                            <button type="button" class="selfie-method-button inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-all" data-method="camera" data-input-target="selfie-input-__INDEX__">
                                <i class="fas fa-camera"></i>
                                Tirar foto agora
                            </button>
                            <button type="button" class="selfie-method-button inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-all" data-method="file" data-input-target="selfie-input-__INDEX__">
                                <i class="fas fa-upload"></i>
                                Enviar do aparelho
                            </button>
                        </div>
                        <input type="file" id="selfie-input-__INDEX__" name="inquilinos[__INDEX__][selfie]" accept="image/*" class="hidden" data-preview-target="selfie-preview-__INDEX__" />
                        <img id="selfie-preview-__INDEX__" src="" alt="Prévia da selfie" class="hidden w-full h-40 rounded-2xl object-cover border border-slate-200 bg-slate-50 mb-3" />
                        <p class="text-xs text-slate-500 mt-2">Tire uma foto ou escolha uma selfie da galeria.</p>
                        <p id="selfie-file-name-__INDEX__" class="text-xs text-slate-500"></p>
                    </div>
                </div>
            </div>
        </template>

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
