<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enviar Dados</title>
</head>
<body>
    <form action="send.php" method="POST">
        <label for="name">Nome:</label>
        <input type="text" id="name" name="name" required><br><br>
        
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required><br><br>
        
        <label for="birthdate">Data de Nascimento:</label>
        <input type="date" id="birthdate" name="birthdate" required><br><br>
        
        <button type="submit">Enviar</button>
    </form>
</body>
</html>
