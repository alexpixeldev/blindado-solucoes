<?php
/**
 * Componente de Calendário Moderno para Blindado Soluções
 * 
 * Uso:
 * include 'components/modern_calendar.php';
 * renderModernCalendar($input_name, $current_value, $label);
 */

function renderModernCalendar($name, $value = '', $label = 'Selecione a Data') {
    $id = 'calendar_' . $name;
    $display_value = $value ? date('d/m/Y', strtotime($value)) : '';
    $iso_value = $value ? date('Y-m-d', strtotime($value)) : '';
    ?>
    <div class="modern-calendar-container" id="container_<?= $id ?>">
        <?php if ($label): ?>
            <label class="form-label"><?= $label ?></label>
        <?php endif; ?>
        <div class="relative cursor-pointer" onclick="toggleModernCalendar('<?= $id ?>')">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <i class="fas fa-calendar-alt text-slate-400 text-sm"></i>
            </div>
            <input type="text" 
                   id="display_<?= $id ?>" 
                   class="form-input pl-11 cursor-pointer bg-white" 
                   placeholder="DD/MM/AAAA" 
                   value="<?= $display_value ?>" 
                   readonly>
            <input type="hidden" name="<?= $name ?>" id="value_<?= $id ?>" value="<?= $iso_value ?>">
            <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                <i class="fas fa-chevron-down text-slate-400 text-xs transition-transform duration-200" id="icon_<?= $id ?>"></i>
            </div>
        </div>
    </div>

    <?php if (!defined('MODERN_CALENDAR_SCRIPTS')): ?>
    <?php define('MODERN_CALENDAR_SCRIPTS', true); ?>
    <style>
        .modern-calendar-dropdown {
            position: fixed !important;
            z-index: 999999 !important;
            background: white;
            border-radius: 1rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            border: 1px solid #f1f5f9;
            overflow: hidden;
            width: 320px;
            animation: fadeIn 0.2s ease-out;
        }

        .modern-calendar-dropdown.hidden {
            display: none !important;
        }

        .calendar-day {
            aspect-ratio: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.875rem;
            font-weight: 500;
            border-radius: 0.75rem;
            cursor: pointer;
            transition: all 0.2s;
            position: relative;
        }
        .calendar-day:hover:not(.empty) {
            background-color: var(--primary-50, #f0fdf4);
            color: var(--primary-600, #16a34a);
        }
        .calendar-day.selected {
            background-color: var(--primary-600, #16a34a) !important;
            color: white !important;
            font-weight: 700;
            box-shadow: 0 4px 12px rgba(22, 163, 74, 0.2);
        }
        .calendar-day.today:not(.selected) {
            color: var(--primary-600, #16a34a);
            font-weight: 700;
        }
        .calendar-day.today:not(.selected)::after {
            content: '';
            position: absolute;
            bottom: 4px;
            width: 4px;
            height: 4px;
            border-radius: 50%;
            background-color: var(--primary-600, #16a34a);
        }
        .calendar-day.empty {
            cursor: default;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
    </style>
    <script>
        const modernCalendars = {};
        const modernCalendarDropdowns = {};

        function createCalendarDropdown(id) {
            const dropdown = document.createElement('div');
            dropdown.id = 'dropdown_' + id;
            dropdown.className = 'modern-calendar-dropdown hidden';
            
            dropdown.innerHTML = `
                <div class="p-4 border-b border-slate-50 flex items-center justify-between bg-slate-50/50" style="border-bottom: 1px solid #f1f5f9;">
                    <button type="button" onclick="changeModernMonth('${id}', -1)" class="p-2 hover:bg-white rounded-lg transition-colors text-slate-600" style="background: transparent; border: none; cursor: pointer;">
                        <i class="fas fa-chevron-left text-xs"></i>
                    </button>
                    <div class="text-sm font-bold text-slate-800 uppercase tracking-wider" id="month_year_${id}" style="font-size: 0.875rem; font-weight: 700; color: #1e293b; text-transform: uppercase; letter-spacing: 0.05em;">
                        Janeiro 2026
                    </div>
                    <button type="button" onclick="changeModernMonth('${id}', 1)" class="p-2 hover:bg-white rounded-lg transition-colors text-slate-600" style="background: transparent; border: none; cursor: pointer;">
                        <i class="fas fa-chevron-right text-xs"></i>
                    </button>
                </div>
                <div class="p-4" style="padding: 1rem;">
                    <div class="grid gap-1 mb-2" id="weekdays_${id}" style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 0.25rem; margin-bottom: 0.5rem;">
                        <div style="text-align: center; font-size: 0.625rem; font-weight: 700; color: #94a3b8; text-transform: uppercase;">D</div>
                        <div style="text-align: center; font-size: 0.625rem; font-weight: 700; color: #94a3b8; text-transform: uppercase;">S</div>
                        <div style="text-align: center; font-size: 0.625rem; font-weight: 700; color: #94a3b8; text-transform: uppercase;">T</div>
                        <div style="text-align: center; font-size: 0.625rem; font-weight: 700; color: #94a3b8; text-transform: uppercase;">Q</div>
                        <div style="text-align: center; font-size: 0.625rem; font-weight: 700; color: #94a3b8; text-transform: uppercase;">Q</div>
                        <div style="text-align: center; font-size: 0.625rem; font-weight: 700; color: #94a3b8; text-transform: uppercase;">S</div>
                        <div style="text-align: center; font-size: 0.625rem; font-weight: 700; color: #94a3b8; text-transform: uppercase;">S</div>
                    </div>
                    <div class="grid gap-1" id="days_grid_${id}" style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 0.25rem;">
                        <!-- Dias gerados via JS -->
                    </div>
                </div>
                <div class="p-3 bg-slate-50 border-t border-slate-100 flex justify-between" style="padding: 0.75rem; background-color: #f8fafc; border-top: 1px solid #f1f5f9; display: flex; justify-content: space-between;">
                    <button type="button" onclick="setModernToday('${id}')" class="text-xs font-bold text-primary-600 hover:text-primary-700 transition-colors" style="background: none; border: none; cursor: pointer; font-size: 0.75rem; font-weight: 700; color: #16a34a;">
                        HOJE
                    </button>
                    <button type="button" onclick="toggleModernCalendar('${id}')" class="text-xs font-bold text-slate-400 hover:text-slate-600 transition-colors" style="background: none; border: none; cursor: pointer; font-size: 0.75rem; font-weight: 700; color: #cbd5e1;">
                        FECHAR
                    </button>
                </div>
            `;
            
            document.body.appendChild(dropdown);
            modernCalendarDropdowns[id] = dropdown;
            return dropdown;
        }

        function toggleModernCalendar(id) {
            const container = document.getElementById('container_' + id);
            const icon = document.getElementById('icon_' + id);
            let dropdown = document.getElementById('dropdown_' + id);
            
            if (!dropdown) {
                dropdown = createCalendarDropdown(id);
            }
            
            const isHidden = dropdown.classList.contains('hidden');

            // Fechar outros calendários
            Object.keys(modernCalendarDropdowns).forEach(otherId => {
                if (otherId !== id) {
                    modernCalendarDropdowns[otherId].classList.add('hidden');
                    const otherIcon = document.getElementById('icon_' + otherId);
                    if (otherIcon) otherIcon.style.transform = 'rotate(0deg)';
                }
            });

            if (isHidden) {
                // Inicializar calendário
                initModernCalendar(id);

                // Mostrar o dropdown
                dropdown.classList.remove('hidden');
                
                // Calcular posição com precisão absoluta considerando o scroll
                const rect = container.getBoundingClientRect();
                const scrollY = window.pageYOffset || document.documentElement.scrollTop;
                const scrollX = window.pageXOffset || document.documentElement.scrollLeft;
                
                const ddHeight = 380;
                const ddWidth = 320;
                
                // Posição absoluta na página (não fixed na viewport, para acompanhar o scroll se necessário)
                // Mas como o dropdown é fixed, usamos as coordenadas da viewport (rect)
                let topPos = rect.bottom + 8;
                let leftPos = rect.left;
                
                // Verificar espaço abaixo na viewport
                const spaceBelow = window.innerHeight - rect.bottom;
                const spaceAbove = rect.top;
                
                // Se não houver espaço abaixo e houver mais espaço acima, posicionar acima do campo
                if (spaceBelow < ddHeight && spaceAbove > spaceBelow) {
                    topPos = rect.top - ddHeight - 8;
                }
                
                // Ajuste de segurança para não sumir no topo se a página for curta
                if (topPos < 0) topPos = 10;
                
                // Verificar se sai da tela à direita
                if (leftPos + ddWidth > window.innerWidth) {
                    leftPos = window.innerWidth - ddWidth - 10;
                }
                
                // Garantir que não saia da tela à esquerda
                if (leftPos < 0) {
                    leftPos = 10;
                }
                
                dropdown.style.position = 'fixed';
                dropdown.style.top = topPos + 'px';
                dropdown.style.left = leftPos + 'px';
                dropdown.style.display = 'block'; // Garantir que o display seja block antes de medir se necessário

                if (icon) icon.style.transform = 'rotate(180deg)';
            } else {
                // Fechar
                dropdown.classList.add('hidden');
                if (icon) icon.style.transform = 'rotate(0deg)';
            }
        }

        function initModernCalendar(id) {
            if (!modernCalendars[id]) {
                const hiddenInput = document.getElementById('value_' + id);
                let initialDate = new Date();
                if (hiddenInput.value) {
                    const parts = hiddenInput.value.split('-');
                    initialDate = new Date(parts[0], parts[1] - 1, parts[2]);
                }
                modernCalendars[id] = {
                    currentMonth: initialDate.getMonth(),
                    currentYear: initialDate.getFullYear(),
                    selectedDate: hiddenInput.value
                };
            }
            renderModernDays(id);
        }

        function renderModernDays(id) {
            const cal = modernCalendars[id];
            const grid = document.getElementById('days_grid_' + id);
            const monthLabel = document.getElementById('month_year_' + id);
            
            const firstDay = new Date(cal.currentYear, cal.currentMonth, 1).getDay();
            const daysInMonth = new Date(cal.currentYear, cal.currentMonth + 1, 0).getDate();
            const today = new Date();
            today.setHours(0,0,0,0);

            const months = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
            monthLabel.innerText = `${months[cal.currentMonth]} ${cal.currentYear}`;

            grid.innerHTML = '';

            // Espaços vazios
            for (let i = 0; i < firstDay; i++) {
                const empty = document.createElement('div');
                empty.className = 'calendar-day empty';
                grid.appendChild(empty);
            }

            // Dias do mês
            for (let d = 1; d <= daysInMonth; d++) {
                const dayEl = document.createElement('div');
                const dateStr = `${cal.currentYear}-${String(cal.currentMonth + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
                
                dayEl.className = 'calendar-day';
                dayEl.innerText = d;
                dayEl.style.cssText = 'aspect-ratio: 1; display: flex; align-items: center; justify-content: center; font-size: 0.875rem; font-weight: 500; border-radius: 0.75rem; cursor: pointer; transition: all 0.2s; position: relative;';

                if (dateStr === cal.selectedDate) {
                    dayEl.classList.add('selected');
                }

                const checkDate = new Date(cal.currentYear, cal.currentMonth, d);
                if (checkDate.getTime() === today.getTime()) {
                    dayEl.classList.add('today');
                }

                dayEl.onmouseover = function() {
                    if (!this.classList.contains('empty')) {
                        this.style.backgroundColor = '#f0fdf4';
                        this.style.color = '#16a34a';
                    }
                };
                dayEl.onmouseout = function() {
                    if (!this.classList.contains('selected')) {
                        this.style.backgroundColor = '';
                        this.style.color = '';
                    }
                };

                dayEl.onclick = (e) => {
                    e.stopPropagation();
                    selectModernDate(id, dateStr);
                };
                grid.appendChild(dayEl);
            }
        }

        function selectModernDate(id, dateStr) {
            const parts = dateStr.split('-');
            const d = new Date(parts[0], parts[1] - 1, parts[2]);
            const displayStr = `${String(d.getDate()).padStart(2, '0')}/${String(d.getMonth() + 1).padStart(2, '0')}/${d.getFullYear()}`;
            
            document.getElementById('display_' + id).value = displayStr;
            document.getElementById('value_' + id).value = dateStr;
            modernCalendars[id].selectedDate = dateStr;
            
            toggleModernCalendar(id);
            
            // Disparar evento de mudança
            const hiddenInput = document.getElementById('value_' + id);
            const event = new Event('change', { bubbles: true });
            hiddenInput.dispatchEvent(event);

            // Se estiver em um formulário de filtro, submeter
            const form = hiddenInput.closest('form');
            if (form && hiddenInput.hasAttribute('onchange') && hiddenInput.getAttribute('onchange').includes('submit')) {
                form.submit();
            }
        }

        function changeModernMonth(id, delta) {
            modernCalendars[id].currentMonth += delta;
            if (modernCalendars[id].currentMonth > 11) {
                modernCalendars[id].currentMonth = 0;
                modernCalendars[id].currentYear++;
            } else if (modernCalendars[id].currentMonth < 0) {
                modernCalendars[id].currentMonth = 11;
                modernCalendars[id].currentYear--;
            }
            renderModernDays(id);
        }

        function setModernToday(id) {
            const now = new Date();
            const dateStr = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
            selectModernDate(id, dateStr);
        }

        // Fechar ao clicar fora
        document.addEventListener('click', function(e) {
            Object.keys(modernCalendars).forEach(id => {
                const container = document.getElementById('container_' + id);
                const dropdown = document.getElementById('dropdown_' + id);
                if (!dropdown) return;
                const isVisible = !dropdown.classList.contains('hidden');
                if (!isVisible) return;
                // if click is inside container or inside dropdown, do nothing
                if ((container && container.contains(e.target)) || dropdown.contains(e.target)) return;
                // otherwise close
                toggleModernCalendar(id);
            });
        });
    </script>
    <?php endif; ?>
    <?php
}
?>
