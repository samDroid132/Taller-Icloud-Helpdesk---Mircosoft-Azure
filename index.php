<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HelpDesk Taller Cloud Pro</title>
    <!-- Bootstrap CSS -->
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
                        echo "<div class='alert alert-success mt-4'>
                                <strong>¡Ticket recibido correctamente!</strong><br>
                                Usuario: $nombre | Categoría: $categoria
                              </div>";
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

</body>
</html>