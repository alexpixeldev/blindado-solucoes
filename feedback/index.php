<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <link rel="icon" type="image/png" href="../img/escudo.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback Blindado</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style_modern.css">
</head>
<body>
    <div class="container">
        <div class="form-header">
            <img src="logo.png" alt="Logo Blindado" class="logo-top">
            <h1>Formulário de Feedback</h1>
        </div>
        <p class="text-center" style="color: #666; font-size: 0.95em; margin-bottom: 30px;">Prezado(a) síndico(a), sua opinião é fundamental para melhorarmos nossos serviços. Este formulário leva menos de 5 minutos.</p>
        
        <form id="form" method="POST" action="">
            <div class="form-row">
                <!-- Nome do Edifício -->
                <div class="form-group">
                    <label for="edificio_nome">Nome do edifício</label>
                    <input type="text" id="edificio_nome" name="edificio_nome" required placeholder="Digite o nome do edifício">
                    <div class="error-message"></div>
                </div>

                <!-- Nome do síndico -->
                <div class="form-group">
                    <label for="sindico_nome">Nome do síndico</label>
                    <input type="text" id="sindico_nome" name="sindico_nome" required placeholder="Escreva aqui seu nome">
                    <div class="error-message"></div>
                </div>
            </div>

            <!-- Portaria - Atendimento Geral -->
            <div class="form-group">
                <label>Portaria - Atendimento Geral</label>
                <p class="scale-label">Qual seu nível de satisfação geral com o serviço de portaria?</p>
                <div class="radio-scale">
                    <div class="scale-options stars centered">
                        <?php for($i=1; $i<=10; $i++) { ?>
                            <label class="star-option" title="Classificação: <?php echo $i; ?>">
                                <input type="radio" name="portaria_atendimento_geral" value="<?php echo $i; ?>" required>
                                <i class="fas fa-star"></i>
                                <span class="star-number"><?php echo $i; ?></span>
                            </label>
                        <?php } ?>
                    </div>
                </div>
                <div class="error-message"></div>
                <!-- Campo de justificação (inicialmente oculto) -->
                <div class="justificativa-field" data-for-radio="portaria_atendimento_geral" style="display: none; margin-top: 15px;">
                    <label for="justificativa_rondas_tempo">Justifique</label>
                    <textarea id="justificativa_rondas_tempo" name="justificativa_rondas_tempo" rows="3" placeholder="Por favor, justifique o motivo da nota sugerindo o que podemos melhorar." style="width: 100%; margin-top: 8px;"></textarea>
                </div>
            </div>

            <!-- Portaria - Moradores e Visitantes -->
            <div class="form-group">
                <label>Portaria - Moradores e Visitantes</label>
                <p class="scale-label">Como avalia o atendimento aos moradores e visitantes?</p>
                <div class="radio-scale">
                    <div class="scale-options stars centered">
                        <?php for($i=1; $i<=10; $i++) { ?>
                            <label class="star-option" title="Classificação: <?php echo $i; ?>">
                                <input type="radio" name="portaria_moradores" value="<?php echo $i; ?>" required>
                                <i class="fas fa-star"></i>
                                <span class="star-number"><?php echo $i; ?></span>
                            </label>
                        <?php } ?>
                    </div>
                </div>
                <div class="error-message"></div>
                <!-- Campo de justificação (inicialmente oculto) -->
                <div class="justificativa-field" data-for-radio="portaria_moradores" style="display: none; margin-top: 15px;">
                    <label for="justificativa_portaria_moradores">Justifique</label>
                    <textarea id="justificativa_portaria_moradores" name="justificativa_portaria_moradores" rows="3" placeholder="Por favor, justifique o motivo da nota sugerindo o que podemos melhorar." style="width: 100%; margin-top: 8px;"></textarea>
                </div>
            </div>

            <!-- Portaria - Monitoramento -->
            <div class="form-group">
                <label>Portaria - Monitoramento</label>
                <p class="scale-label">Como avalia a segurança proporcionada pelo serviço?</p>
                <div class="radio-scale">
                    <div class="scale-options stars centered">
                        <?php for($i=1; $i<=10; $i++) { ?>
                            <label class="star-option" title="Classificação: <?php echo $i; ?>">
                                <input type="radio" name="portaria_monitoramento" value="<?php echo $i; ?>" required>
                                <i class="fas fa-star"></i>
                                <span class="star-number"><?php echo $i; ?></span>
                            </label>
                        <?php } ?>
                    </div>
                </div>
                <div class="error-message"></div>
                <!-- Campo de justificação (inicialmente oculto) -->
                <div class="justificativa-field" data-for-radio="portaria_monitoramento" style="display: none; margin-top: 15px;">
                    <label for="justificativa_portaria_monitoramento">Justifique</label>
                    <textarea id="justificativa_portaria_monitoramento" name="justificativa_portaria_monitoramento" rows="3" placeholder="Por favor, justifique o motivo da nota sugerindo o que podemos melhorar." style="width: 100%; margin-top: 8px;"></textarea>
                </div>
            </div>

            <!-- Portaria - Tempo de Resposta -->
            <div class="form-group">
                <label>Portaria - Tempo de Resposta</label>
                <p class="scale-label">Como avalia a velocidade de atendimento ao interfonar para a portaria?</p>
                <div class="radio-scale">
                    <div class="scale-options stars centered">
                        <?php for($i=1; $i<=10; $i++) { ?>
                            <label class="star-option" title="Classificação: <?php echo $i; ?>">
                                <input type="radio" name="portaria_tempo" value="<?php echo $i; ?>" required>
                                <i class="fas fa-star"></i>
                                <span class="star-number"><?php echo $i; ?></span>
                            </label>
                        <?php } ?>
                    </div>
                </div>
                <div class="error-message"></div>
                <!-- Campo de justificação (inicialmente oculto) -->
                <div class="justificativa-field" data-for-radio="portaria_tempo" style="display: none; margin-top: 15px;">
                    <label for="justificativa_portaria_tempo">Justifique</label>
                    <textarea id="justificativa_portaria_tempo" name="justificativa_portaria_tempo" rows="3" placeholder="Por favor, justifique o motivo da nota sugerindo o que podemos melhorar." style="width: 100%; margin-top: 8px;"></textarea>
                </div>
            </div>

            <!-- Portaria - Organização e Procedimento -->
            <div class="form-group">
                <label>Portaria - Organização e Procedimento</label>
                <p class="scale-label">Como avalia nosso procedimento e organização (informações e retorno, por exemplo com entregas/encomendas)?</p>
                <div class="radio-scale">
                    <div class="scale-options stars centered">
                        <?php for($i=1; $i<=10; $i++) { ?>
                            <label class="star-option" title="Classificação: <?php echo $i; ?>">
                                <input type="radio" name="portaria_organizacao" value="<?php echo $i; ?>" required>
                                <i class="fas fa-star"></i>
                                <span class="star-number"><?php echo $i; ?></span>
                            </label>
                        <?php } ?>
                    </div>
                </div>
                <div class="error-message"></div>
                <!-- Campo de justificação (inicialmente oculto) -->
                <div class="justificativa-field" data-for-radio="portaria_organizacao" style="display: none; margin-top: 15px;">
                    <label for="justificativa_portaria_organizacao">Justifique</label>
                    <textarea id="justificativa_portaria_organizacao" name="justificativa_portaria_organizacao" rows="3" placeholder="Por favor, justifique o motivo da nota sugerindo o que podemos melhorar." style="width: 100%; margin-top: 8px;"></textarea>
                </div>
            </div>

            <!-- Comunicação com Administração -->
            <div class="form-group">
                <label>Comunicação com a Administração</label>
                <p class="scale-label">Como avalia a comunicação em relação à ocorrências que precisam ser tratadas diretamente com a administração?</p>
                <div class="radio-scale">
                    <div class="scale-options stars centered">
                        <?php for($i=1; $i<=10; $i++) { ?>
                            <label class="star-option" title="Classificação: <?php echo $i; ?>">
                                <input type="radio" name="comunicacao_admin" value="<?php echo $i; ?>" required>
                                <i class="fas fa-star"></i>
                                <span class="star-number"><?php echo $i; ?></span>
                            </label>
                        <?php } ?>
                    </div>
                </div>
                <div class="error-message"></div>
                <!-- Campo de justificação (inicialmente oculto) -->
                <div class="justificativa-field" data-for-radio="comunicacao_admin" style="display: none; margin-top: 15px;">
                    <label for="justificativa_comunicacao_admin">Justifique</label>
                    <textarea id="justificativa_comunicacao_admin" name="justificativa_comunicacao_admin" rows="3" placeholder="Por favor, justifique o motivo da nota sugerindo o que podemos melhorar." style="width: 100%; margin-top: 8px;"></textarea>
                </div>
            </div>

            <!-- Contratação de Zeladoria -->
            <div class="form-group">
                <label>Contratação de Zeladoria</label>
                <p class="scale-label">Seu condomínio contrata serviços de zeladoria?</p>
                <div class="radio-scale">
                    <div class="scale-options centered">
                        <label class="option-button">
                            <input type="radio" name="contrata_zeladoria" value="sim" required>
                            <span>Sim</span>
                        </label>
                        <label class="option-button">
                            <input type="radio" name="contrata_zeladoria" value="nao" required>
                            <span>Não</span>
                        </label>
                    </div>
                </div>
                <div class="error-message"></div>
            </div>

            <!-- Zeladoria - Geral -->
            <div class="form-group zeladoria-section">
                <label>Zeladoria - Geral</label>
                <p class="scale-label">Qual seu nível de satisfação geral com o serviço dos zeladores?</p>
                <div class="radio-scale">
                    <div class="scale-options stars centered">
                        <?php for($i=1; $i<=10; $i++) { ?>
                            <label class="star-option" title="Classificação: <?php echo $i; ?>">
                                <input type="radio" name="zeladoria_geral" value="<?php echo $i; ?>">
                                <i class="fas fa-star"></i>
                                <span class="star-number"><?php echo $i; ?></span>
                            </label>
                        <?php } ?>
                    </div>
                </div>
                <div class="error-message"></div>
                <!-- Campo de justificação (inicialmente oculto) -->
                <div class="justificativa-field" data-for-radio="zeladoria_geral" style="display: none; margin-top: 15px;">
                    <label for="justificativa_zeladoria_geral">Justifique</label>
                    <textarea id="justificativa_zeladoria_geral" name="justificativa_zeladoria_geral" rows="3" placeholder="Por favor, justifique o motivo da nota sugerindo o que podemos melhorar." style="width: 100%; margin-top: 8px;"></textarea>
                </div>
            </div>

            <!-- Zeladoria - Áreas Comuns -->
            <div class="form-group zeladoria-section">
                <label>Zeladoria - Áreas Comuns</label>
                <p class="scale-label">Como avalia a conservação das áreas comuns?</p>
                <div class="radio-scale">
                    <div class="scale-options stars centered">
                        <?php for($i=1; $i<=10; $i++) { ?>
                            <label class="star-option" title="Classificação: <?php echo $i; ?>">
                                <input type="radio" name="zeladoria_areas_comuns" value="<?php echo $i; ?>">
                                <i class="fas fa-star"></i>
                                <span class="star-number"><?php echo $i; ?></span>
                            </label>
                        <?php } ?>
                    </div>
                </div>
                <div class="error-message"></div>
                <!-- Campo de justificação (inicialmente oculto) -->
                <div class="justificativa-field" data-for-radio="zeladoria_areas_comuns" style="display: none; margin-top: 15px;">
                    <label for="justificativa_zeladoria_areas_comuns">Justifique</label>
                    <textarea id="justificativa_zeladoria_areas_comuns" name="justificativa_zeladoria_areas_comuns" rows="3" placeholder="Por favor, justifique o motivo da nota sugerindo o que podemos melhorar." style="width: 100%; margin-top: 8px;"></textarea>
                </div>
            </div>

            <!-- Zeladoria - Organização -->
            <div class="form-group zeladoria-section">
                <label>Zeladoria - Organização</label>
                <p class="scale-label">Como avalia a organização e rotina de serviços?</p>
                <div class="radio-scale">
                    <div class="scale-options stars centered">
                        <?php for($i=1; $i<=10; $i++) { ?>
                            <label class="star-option" title="<?php echo $i; ?>">
                                <input type="radio" name="zeladoria_organizacao" value="<?php echo $i; ?>">
                                <i class="fas fa-star"></i>
                                <span class="star-number"><?php echo $i; ?></span>
                            </label>
                        <?php } ?>
                    </div>
                </div>
                <div class="error-message"></div>
                <!-- Campo de justificação (inicialmente oculto) -->
                <div class="justificativa-field" data-for-radio="zeladoria_organizacao" style="display: none; margin-top: 15px;">
                    <label for="justificativa_zeladoria_organizacao">Justifique</label>
                    <textarea id="justificativa_zeladoria_organizacao" name="justificativa_zeladoria_organizacao" rows="3" placeholder="Por favor, justifique o motivo da nota sugerindo o que podemos melhorar." style="width: 100%; margin-top: 8px;"></textarea>
                </div>
            </div>

            <!-- Zeladoria - Profissionalismo -->
            <div class="form-group zeladoria-section">
                <label>Zeladoria - Profissionalismo</label>
                <p class="scale-label">Como avalia a postura e profissionalismo da equipe?</p>
                <div class="radio-scale">
                    <div class="scale-options stars centered">
                        <?php for($i=1; $i<=10; $i++) { ?>
                            <label class="star-option" title="Classificação: <?php echo $i; ?>">
                                <input type="radio" name="zeladoria_profissionalismo" value="<?php echo $i; ?>">
                                <i class="fas fa-star"></i>
                                <span class="star-number"><?php echo $i; ?></span>
                            </label>
                        <?php } ?>
                    </div>
                </div>
                <div class="error-message"></div>
                <!-- Campo de justificação (inicialmente oculto) -->
                <div class="justificativa-field" data-for-radio="zeladoria_profissionalismo" style="display: none; margin-top: 15px;">
                    <label for="justificativa_zeladoria_profissionalismo">Justifique</label>
                    <textarea id="justificativa_zeladoria_profissionalismo" name="justificativa_zeladoria_profissionalismo" rows="3" placeholder="Por favor, justifique o motivo da nota sugerindo o que podemos melhorar." style="width: 100%; margin-top: 8px;"></textarea>
                </div>
            </div>

            <!-- Rondas Ostensivas -->
            <div class="form-group">
                <label>Rondas Ostensivas</label>
                <p class="scale-label">Como avalia as rondas ostensivas?</p>
                <div class="radio-scale">
                    <div class="scale-options stars centered">
                        <?php for($i=1; $i<=10; $i++) { ?>
                            <label class="star-option" title="Classificação: <?php echo $i; ?>">
                                <input type="radio" name="rondas_ostensivas" value="<?php echo $i; ?>" required>
                                <i class="fas fa-star"></i>
                                <span class="star-number"><?php echo $i; ?></span>
                            </label>
                        <?php } ?>
                    </div>
                </div>
                <div class="error-message"></div>
                <!-- Campo de justificação (inicialmente oculto) -->
                <div class="justificativa-field" data-for-radio="rondas_ostensivas" style="display: none; margin-top: 15px;">
                    <label for="justificativa_rondas_ostensivas">Justifique</label>
                    <textarea id="justificativa_rondas_ostensivas" name="justificativa_rondas_ostensivas" rows="3" placeholder="Por favor, justifique o motivo da nota sugerindo o que podemos melhorar." style="width: 100%; margin-top: 8px;"></textarea>
                </div>
            </div>

            <!-- Tempo de Resposta das Rondas -->
            <div class="form-group">
                <label>Tempo de Resposta das Rondas</label>
                <p class="scale-label">Como avalia o tempo de resposta para o rondande resolver problemas de anormalidade?</p>
                <div class="radio-scale">
                    <div class="scale-options stars centered">
                        <?php for($i=1; $i<=10; $i++) { ?>
                            <label class="star-option" title="Classificação: <?php echo $i; ?>">
                                <input type="radio" name="rondas_tempo_resposta" value="<?php echo $i; ?>" required>
                                <i class="fas fa-star"></i>
                                <span class="star-number"><?php echo $i; ?></span>
                            </label>
                        <?php } ?>
                    </div>
                </div>
                <div class="error-message"></div>
                <!-- Campo de justificação (inicialmente oculto) -->
                <div class="justificativa-field" data-for-radio="rondas_tempo_resposta" style="display: none; margin-top: 15px;">
                    <label for="justificativa_rondas_tempo_resposta">Justifique</label>
                    <textarea id="justificativa_rondas_tempo_resposta" name="justificativa_rondas_tempo_resposta" rows="3" placeholder="Por favor, justifique o motivo da nota sugerindo o que podemos melhorar." style="width: 100%; margin-top: 8px;"></textarea>
                </div>
            </div>

            <!-- Feedback -->
            <div class="form-group">
                <label for="feedback">Feedback</label>
                <p class="scale-label">Gostaria de deixar alguma sugestão, crítica ou elogio?</p>
                <textarea id="feedback" name="feedback" rows="5" placeholder="Digite sua mensagem aqui..." style="min-height: 120px;"></textarea>
            </div>

            <!-- Botões -->
            <div class="form-navigation">
                <button type="reset" class="btn-prev" style="background-color: #cbd5e1;">
                    <i class="fas fa-redo"></i> Limpar
                </button>
                <button type="submit" id="btn-send-whatsapp-feedback" class="btn-primary" style="background: linear-gradient(135deg, #25D366, #128C7E);">
                    <i class="fab fa-whatsapp"></i> Enviar via WhatsApp
                </button>
            </div>
        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery-mask-plugin@1.14.16/dist/jquery.mask.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/pt.js"></script>
    <script src="script.js"></script>
</body>
</html>
