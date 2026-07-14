// script.js - Funcionalidades completas para site de Portaria Virtual

document.addEventListener('DOMContentLoaded', function() {
    console.log('Site Blindado Soluções - Carregado com sucesso!');
    
    // 1. Menu Mobile Toggle
    const mobileMenu = document.getElementById('mobile-menu');
    const navMenu = document.querySelector('.nav-menu');
    
    if (mobileMenu) {
        mobileMenu.addEventListener('click', function() {
            this.classList.toggle('active');
            navMenu.classList.toggle('active');
            
            // Animação dos spans do menu hamburguer
            const spans = this.querySelectorAll('span');
            if (this.classList.contains('active')) {
                spans[0].style.transform = 'translateY(7px) rotate(45deg)';
                spans[1].style.opacity = '0';
                spans[2].style.transform = 'translateY(-7px) rotate(-45deg)';
            } else {
                spans[0].style.transform = 'none';
                spans[1].style.opacity = '1';
                spans[2].style.transform = 'none';
            }
        });
    }
    
    // 2. Smooth Scrolling para links âncora
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;
            
            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                // Fechar menu mobile se aberto
                if (mobileMenu && mobileMenu.classList.contains('active')) {
                    mobileMenu.classList.remove('active');
                    navMenu.classList.remove('active');
                    
                    const spans = mobileMenu.querySelectorAll('span');
                    spans[0].style.transform = 'none';
                    spans[1].style.opacity = '1';
                    spans[2].style.transform = 'none';
                }
                
                // Scroll suave
                window.scrollTo({
                    top: targetElement.offsetTop - 80,
                    behavior: 'smooth'
                });
            }
        });
    });
    
    // 3. Formulário de Orçamento
    const formOrcamento = document.getElementById('form-orcamento');
    if (formOrcamento) {
        formOrcamento.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Coletar dados do formulário
            const formData = new FormData(this);
            const formObject = Object.fromEntries(formData.entries());
            
            // Validação básica
            if (!formObject.nome || !formObject.email || !formObject.telefone || !formObject.condominio || !formObject.unidades) {
                showNotification('Por favor, preencha todos os campos obrigatórios.', 'error');
                return;
            }
            
            // Simular envio
            showNotification('Enviando sua solicitação...', 'info');
            
            setTimeout(() => {
                // Simular sucesso no envio
                showNotification('Solicitação enviada com sucesso! Nossa equipe entrará em contato em até 24h.', 'success');
                
                // Resetar formulário
                formOrcamento.reset();
                
                // Rolar para o topo
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
                
                // Log dos dados (em produção, enviaria para backend)
                console.log('Formulário enviado:', formObject);
            }, 1500);
        });
    }
    
    // 4. Validação e formatação de telefone
    const telefoneInput = document.querySelector('input[name="telefone"]');
    if (telefoneInput) {
        telefoneInput.addEventListener('input', function(e) {
            let value = this.value.replace(/\D/g, '');
            
            if (value.length > 11) {
                value = value.substring(0, 11);
            }
            
            if (value.length > 10) {
                // Formato: (XX) XXXXX-XXXX
                this.value = value.replace(/^(\d{2})(\d{5})(\d{4}).*/, '($1) $2-$3');
            } else if (value.length > 6) {
                // Formato: (XX) XXXX-XXXX
                this.value = value.replace(/^(\d{2})(\d{4})(\d{0,4}).*/, '($1) $2-$3');
            } else if (value.length > 2) {
                // Formato: (XX) XXXX
                this.value = value.replace(/^(\d{2})(\d{0,5})/, '($1) $2');
            } else if (value.length > 0) {
                // Formato: (XX
                this.value = value.replace(/^(\d*)/, '($1');
            }
        });
    }
    
    // 5. Animações de scroll
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animated');
                
                // Animar cards individualmente
                if (entry.target.classList.contains('vantagem-card')) {
                    setTimeout(() => {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }, entry.target.dataset.delay || 0);
                }
            }
        });
    }, observerOptions);
    
    // Observar seções e elementos
    document.querySelectorAll('section, .vantagem-card, .tech-item, .feature-item').forEach(el => {
        if (!el.classList.contains('hero')) {
            observer.observe(el);
            
            // Configurar delay para cards
            if (el.classList.contains('vantagem-card')) {
                const index = Array.from(el.parentElement.children).indexOf(el);
                el.dataset.delay = index * 100;
                el.style.opacity = '0';
                el.style.transform = 'translateY(30px)';
                el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            }
        }
    });
    
    // 6. Navbar scroll effect
    let lastScroll = 0;
    const navbar = document.querySelector('.main-header');
    
    window.addEventListener('scroll', function() {
        const currentScroll = window.pageYOffset;
        
        // Adicionar sombra quando rolar
        if (currentScroll > 100) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
        
        // Esconder/mostrar navbar no scroll
        if (currentScroll > lastScroll && currentScroll > 200) {
            // Rolar para baixo
            navbar.style.transform = 'translateY(-100%)';
        } else {
            // Rolar para cima
            navbar.style.transform = 'translateY(0)';
        }
        
        lastScroll = currentScroll;
    });
    
    // 7. Contador animado para estatística
    const statValue = document.querySelector('.stat-value');
    if (statValue) {
        const targetValue = 108000;
        const duration = 2000; // 2 segundos
        const increment = targetValue / (duration / 16); // 60fps
        
        let currentValue = 0;
        const timer = setInterval(() => {
            currentValue += increment;
            if (currentValue >= targetValue) {
                currentValue = targetValue;
                clearInterval(timer);
            }
            
            // Formatar como moeda brasileira
            statValue.textContent = `R$ ${currentValue.toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            })}`;
        }, 16);
    }
    
    // 8. Sistema de notificações
    function showNotification(message, type = 'info') {
        // Remover notificação existente
        const existingNotification = document.querySelector('.notification');
        if (existingNotification) {
            existingNotification.remove();
        }
        
        // Criar nova notificação
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <i class="fas ${getNotificationIcon(type)}"></i>
                <span>${message}</span>
            </div>
            <button class="notification-close">
                <i class="fas fa-times"></i>
            </button>
        `;
        
        document.body.appendChild(notification);
        
        // Adicionar estilos
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${getNotificationColor(type)};
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 9999;
            animation: slideIn 0.3s ease;
            max-width: 400px;
        `;
        
        // Animação
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
        `;
        document.head.appendChild(style);
        
        // Botão de fechar
        const closeBtn = notification.querySelector('.notification-close');
        closeBtn.addEventListener('click', () => {
            notification.style.animation = 'slideOut 0.3s ease forwards';
            
            const slideOutStyle = document.createElement('style');
            slideOutStyle.textContent = `
                @keyframes slideOut {
                    from {
                        transform: translateX(0);
                        opacity: 1;
                    }
                    to {
                        transform: translateX(100%);
                        opacity: 0;
                    }
                }
            `;
            document.head.appendChild(slideOutStyle);
            
            setTimeout(() => notification.remove(), 300);
        });
        
        // Auto-remover após 5 segundos
        setTimeout(() => {
            if (notification.parentNode) {
                closeBtn.click();
            }
        }, 5000);
    }
    
    function getNotificationIcon(type) {
        switch(type) {
            case 'success': return 'fa-check-circle';
            case 'error': return 'fa-exclamation-circle';
            case 'info': return 'fa-info-circle';
            default: return 'fa-info-circle';
        }
    }
    
    function getNotificationColor(type) {
        switch(type) {
            case 'success': return '#4CAF50';
            case 'error': return '#F44336';
            case 'info': return '#2196F3';
            default: return '#2196F3';
        }
    }
    
    // 9. Efeitos de hover nos cards
    document.querySelectorAll('.vantagem-card, .tech-item, .feature-item').forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transition = 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transition = 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
        });
    });
    
    // 10. Carregamento lazy para imagens
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, imgObserver) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    const src = img.getAttribute('data-src');
                    if (src) {
                        img.src = src;
                        img.classList.add('loaded');
                    }
                    imgObserver.unobserve(img);
                }
            });
        });
        
        document.querySelectorAll('img[data-src]').forEach(img => {
            imageObserver.observe(img);
        });
    }
    
    // 11. Contador de visitantes (simulado)
    const visitCount = localStorage.getItem('siteVisits') || 0;
    localStorage.setItem('siteVisits', parseInt(visitCount) + 1);
    
    console.log(`Visitas ao site: ${parseInt(visitCount) + 1}`);
    
    // 12. Detecta dispositivo e adiciona classes
    function detectDevice() {
        if (window.innerWidth <= 768) {
            document.body.classList.add('mobile-device');
        } else {
            document.body.classList.add('desktop-device');
        }
    }
    
    detectDevice();
    window.addEventListener('resize', detectDevice);
    
    // 13. Previne envio de formulário com Enter em campos inválidos
    document.querySelectorAll('form input').forEach(input => {
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !this.checkValidity()) {
                e.preventDefault();
                this.reportValidity();
            }
        });
    });
    
    // 14. Adiciona máscara para número de unidades
    const unidadesInput = document.querySelector('input[name="unidades"]');
    if (unidadesInput) {
        unidadesInput.addEventListener('input', function(e) {
            this.value = this.value.replace(/\D/g, '');
            
            if (parseInt(this.value) > 9999) {
                this.value = '9999';
            }
        });
    }
    
    // 15. Inicialização final
    console.log('Sistema Blindado Soluções inicializado com sucesso!');
});