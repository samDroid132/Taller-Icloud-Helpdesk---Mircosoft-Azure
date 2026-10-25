<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HelpDesk Taller Cloud Pro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .card-custom { border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
    </style>
</head>
<body>

    <nav class="navbar navbar-dark bg-dark">
        <div class="container">
            <span class="navbar-brand mb-0 h1">🛠️ HelpDesk Taller Cloud Pro</span>
            <span class="badge bg-info text-dark">Servidor: Azure App Service (PHP <?php echo phpversion(); ?>)</span>
        </div>
    </nav>

    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card card-custom p-4">
                    <h3 class="mb-3 text-primary">Abrir Ticket de Soporte Técnico</h3>
                    <p class="text-muted">Informa sobre averías de hardware, problemas de red o software.</p>

                    <form action="index.php" method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label font-weight-bold">Nombre del Cliente / Usuario</label>
                            <input type="text" name="nombre" class="form-control" placeholder="Ej. Katheryne Claure" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Tipo de Incidencia</label>
                            <select name="categoria" class="form-select" required>
                                <option value="Hardware">Fallo de Hardware / Equipo</option>
                                <option value="Redes">Problemas de Red / Wi-Fi</option>
                                <option value="Software">Software / Sistema Operativo</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Descripción de la avería</label>
                            <textarea name="descripcion" class="form-control" rows="4" placeholder="Describe el problema con detalle..." required></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Adjuntar captura o foto del error (Azure Blob Storage)</label>
                            <input type="file" name="captura" class="form-control" accept="image/*">
                        </div>

                        <button type="submit" class="btn btn-primary w-100 fw-bold">Enviar Ticket a la Nube</button>
                    </form>

                    <?php
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        $nombre = htmlspecialchars($_POST['nombre']);
                        $categoria = htmlspecialchars($_POST['categoria']);
                        $mensajeEstado = "";
                        $alertaClase = "alert-success";

                        // Verificamos si se ha adjuntado un archivo
                        if (isset($_FILES['captura']) && $_FILES['captura']['error'] === UPLOAD_ERR_OK) {
                            $fileTmpPath = $_FILES['captura']['tmp_name'];
                            $fileName = uniqid() . '_' . basename($_FILES['captura']['name']);
                            
                            $connectionString = getenv('AZURE_STORAGE_CONNECTION_STRING');
                            $containerName = 'adjuntos-tickets';

                            if ($connectionString) {
                                // Parseamos la cadena de conexión de Azure de forma segura
                                $parsedConn = [];
                                foreach (explode(';', $connectionString) as $pair) {
                                    if (strpos($pair, '=') !== false) {
                                        list($k, $v) = explode('=', $pair, 2);
                                        $parsedConn[$k] = $v;
                                    }
                                }

                                $accountName = $parsedConn['AccountName'] ?? '';
                                $accountKey = $parsedConn['AccountKey'] ?? '';
                                $fileContents = file_get_contents($fileTmpPath);
                                $fileLength = strlen($fileContents);

                                if ($accountName && $accountKey) {
                                    // Cabeceras HTTP obligatorias para Azure Blob Storage (Versión limpia)
                                    $date = gmdate('D, d M Y H:i:s T');
                                    $version = '2020-10-02';
                                    $canonicalizedResource = "/$accountName/$containerName/$fileName";
                                    
                                    $stringToSign = "PUT\n\n\n{$fileLength}\n\napplication/octet-stream\n\n\n\n\n\n\nx-ms-blob-type:BlockBlob\nx-ms-date:{$date}\nx-ms-version:{$version}\n{$canonicalizedResource}";
                                    
                                    $decodedKey = base64_decode($accountKey);
                                    $signature = base64_encode(hash_hmac('sha256', $stringToSign, $decodedKey, true));
                                    $authHeader = "SharedKey {$accountName}:{$signature}";

                                    $headers = [
                                        "x-ms-blob-type: BlockBlob",
                                        "x-ms-date: {$date}",
                                        "x-ms-version: {$version}",
                                        "Authorization: {$authHeader}",
                                        "Content-Length: {$fileLength}"
                                    ];

                                    $url = "https://{$accountName}.blob.core.windows.net/{$containerName}/{$fileName}";

                                    $ch = curl_init($url);
                                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
                                    curl_setopt($ch, CURLOPT_POSTFIELDS, $fileContents);
                                    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Evita errores SSL en Azure App Service Linux
                                    
                                    $response = curl_exec($ch);
                                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                                    curl_close($ch);

                                    if ($httpCode === 201) {
                                        $mensajeEstado = "¡Ticket y captura subidos con éxito a Azure Blob Storage!";
                                    } else {
                                        $alertaClase = "alert-warning";
                                        $mensajeEstado = "Ticket registrado, pero Azure rechazó la imagen (Código HTTP: $httpCode).";
                                    }
                                } else {
                                    $alertaClase = "alert-warning";
                                    $mensajeEstado = "Error: La cadena de conexión de Azure no está bien formada.";
                                }
                            } else {
                                $alertaClase = "alert-warning";
                                $mensajeEstado = "Aviso: La variable de entorno de Azure no se detecta.";
                            }
                        } else {
                            $mensajeEstado = "¡Ticket recibido correctamente sin archivos adjuntos!";
                        }

                        echo "<div class='alert {$alertaClase} mt-4'>
                                <strong>{$mensajeEstado}</strong><br>
                                Usuario: {$nombre} | Categoría: {$categoria}
                              </div>";
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

</body>
</html>