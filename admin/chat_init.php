<?php
/**
 * Chat Initialization
 * Incluir este arquivo em todas as páginas do admin para inicializar o chat
 * 
 * Uso: Adicione <?php require_once 'chat_init.php'; ?> antes do </body>
 */

// Verificar se o usuário está autenticado e não é colaborador
if (isset($_SESSION['usuario_id']) && isset($_SESSION['usuario_categoria']) && $_SESSION['usuario_categoria'] !== 'colaborador') {
    $usuario_id = $_SESSION['usuario_id'];
    $usuario_nome = $_SESSION['usuario_nome'] ?? 'Usuário';
    $usuario_categoria = $_SESSION['usuario_categoria'] ?? '';
    ?>
	    <!-- Chat Widget -->
	    <script>
	        console.log("ChatInit: Inicializando para usuário <?php echo $usuario_id; ?>");
	        // Passar dados do usuário para o widget
	        document.documentElement.setAttribute('data-usuario-id', '<?php echo $usuario_id; ?>');
	        document.documentElement.setAttribute('data-usuario-nome', '<?php echo htmlspecialchars($usuario_nome); ?>');
	        document.documentElement.setAttribute('data-usuario-categoria', '<?php echo htmlspecialchars($usuario_categoria); ?>');
	    </script>
	    <script src="assets/js/chat-widget.js"></script>
	    <?php
}
?>
