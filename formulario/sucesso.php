<!DOCTYPE html>
<html lang="pt-br">
<head>
    <link rel="icon" type="image/png" href="img/escudo.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sucesso - Formulário de Locação</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="style_modern.css">
    <style>
        .success-container { text-align: center; padding: 40px; }
        .success-icon { font-size: 5em; color: #16a34a; margin-bottom: 20px; animation: popIn 0.5s ease-out; }
        @keyframes popIn { 0% { transform: scale(0.5); opacity: 0; } 80% { transform: scale(1.1); } 100% { transform: scale(1); opacity: 1; } }
    </style>
</head>
<body>
    <div class="container">
        <div class="success-container">
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="success-message">
                <h1>Parabéns!</h1>
                <p>Sua locação foi enviada com sucesso!</p>
            </div>
            <a href="index.php" class="btn-primary">Preencher Novo Formulário</a>
        </div>
    </div>
</body>
</html>