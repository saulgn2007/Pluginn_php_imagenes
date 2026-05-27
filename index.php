<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Redimensiona y optimiza tus imágenes de forma rápida, profesional y gratuita. Soporta formatos WebP, PNG, JPEG y GIF con configuraciones avanzadas.">
    <meta name="robots" content="index, follow">
    <title>ImageShrink - Redimensionador de Imágenes Premium en PHP</title>
    
    <!-- Google Fonts: Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Custom Style -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- Feather Icons for modern UI icons -->
    <script src="https://unpkg.com/feather-icons"></script>
</head>
<body>
    <!-- Decorative background blurred glowing blobs -->
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
    
    <header class="app-header">
        <div class="container header-container">
            <div class="logo-area">
                <div class="logo-icon">
                    <i data-feather="image"></i>
                </div>
                <h1>ImageShrink</h1>
            </div>
            <p class="tagline">Redimensión ultrarrápida y optimización inteligente</p>
        </div>
    </header>

    <main class="app-main container">
        <!-- Grid layout for the dashboard -->
        <div class="dashboard-grid">
            
            <!-- Left Side: Upload & Controls -->
            <section class="panel-section control-panel">
                <div class="glass-card">
                    <!-- Step 1: Upload Zone -->
                    <div class="card-header">
                        <span class="step-badge">1</span>
                        <h2>Sube tu Imagen</h2>
                    </div>
                    
                    <div class="drop-zone" id="dropZone">
                        <input type="file" id="fileInput" accept="image/*" class="file-input">
                        <div class="drop-zone-content">
                            <div class="icon-wrapper">
                                <i data-feather="upload-cloud" class="upload-icon"></i>
                            </div>
                            <h3>Arrastra tu archivo aquí</h3>
                            <p>o haz clic para explorar en tu equipo</p>
                            <span class="file-types-hint">Soporta PNG, JPEG, GIF, WebP (Máx. 15MB)</span>
                        </div>
                    </div>

                    <!-- Step 2: Settings (Hidden initially, shown when image loaded) -->
                    <div id="settingsContainer" class="settings-container disabled">
                        <div class="card-header border-top">
                            <span class="step-badge">2</span>
                            <h2>Configuración de Redimensión</h2>
                        </div>

                        <!-- Template buttons for fast sizing -->
                        <div class="form-group">
                            <label>Plantillas Rápidas</label>
                            <div class="templates-grid">
                                <button type="button" class="btn-template" data-w="1080" data-h="1080" data-crop="true">
                                    <i data-feather="instagram"></i> Instagram (1:1)
                                </button>
                                <button type="button" class="btn-template" data-w="1920" data-h="1080" data-crop="false">
                                    <i data-feather="monitor"></i> Full HD 1080p
                                </button>
                                <button type="button" class="btn-template" data-w="350" data-h="100" data-crop="false">
                                    <i data-feather="award"></i> Logo WP (350x100)
                                </button>
                                <button type="button" class="btn-template" data-w="1200" data-h="600" data-crop="true">
                                    <i data-feather="layout"></i> Banner WP (1200x600)
                                </button>
                                <button type="button" class="btn-template" data-w="1280" data-h="720" data-crop="false">
                                    <i data-feather="video"></i> HD 720p
                                </button>
                                <button type="button" class="btn-template" data-w="500" data-h="500" data-crop="true">
                                    <i data-feather="user"></i> Perfil (1:1)
                                </button>
                            </div>
                        </div>

                        <!-- Manual Dimensions -->
                        <div class="dimensions-row">
                            <div class="form-group">
                                <label for="widthInput">Ancho (px)</label>
                                <input type="number" id="widthInput" name="width" min="1" max="10000" placeholder="Ancho original">
                            </div>
                            
                            <div class="aspect-lock-wrapper">
                                <button type="button" id="aspectRatioBtn" class="btn-lock active" title="Bloquear proporción">
                                    <i data-feather="link" class="lock-icon"></i>
                                </button>
                            </div>

                            <div class="form-group">
                                <label for="heightInput">Alto (px)</label>
                                <input type="number" id="heightInput" name="height" min="1" max="10000" placeholder="Alto original">
                            </div>
                        </div>

                        <!-- Quality and Fit Mode -->
                        <div class="form-group">
                            <label>Modo de Ajuste</label>
                            <div class="fit-mode-toggle">
                                <input type="radio" id="fitModeProportional" name="fit_mode" value="proportional" checked>
                                <label for="fitModeProportional" class="toggle-btn" title="Mantiene proporciones y se adapta dentro de las medidas.">
                                    <i data-feather="maximize-2"></i> Proporcional
                                </label>

                                <input type="radio" id="fitModeCrop" name="fit_mode" value="crop">
                                <label for="fitModeCrop" class="toggle-btn" title="Corta los bordes sobrantes para rellenar exactamente la medida.">
                                    <i data-feather="crop"></i> Recortar (Crop)
                                </label>
                            </div>
                        </div>

                        <!-- Format & Compression Quality -->
                        <div class="format-quality-row">
                            <div class="form-group select-group">
                                <label for="formatSelect">Formato Salida</label>
                                <select id="formatSelect" name="format">
                                    <option value="original">Igual al original</option>
                                    <option value="webp">WebP (Recomendado)</option>
                                    <option value="png">PNG (Sin pérdida)</option>
                                    <option value="jpeg">JPEG (Estándar)</option>
                                    <option value="gif">GIF (Animado)</option>
                                </select>
                            </div>

                            <div class="form-group slider-group">
                                <div class="slider-header">
                                    <label for="qualitySlider">Calidad</label>
                                    <span id="qualityVal" class="slider-value">90%</span>
                                </div>
                                <input type="range" id="qualitySlider" name="quality" min="10" max="100" value="90" class="styled-slider">
                            </div>
                        </div>

                        <!-- Resize Submit Button -->
                        <button type="button" id="resizeBtn" class="btn-primary">
                            <span class="btn-text">Redimensionar Imagen</span>
                            <span class="loader hidden"></span>
                            <i data-feather="arrow-right" class="btn-icon"></i>
                        </button>
                    </div>
                </div>
            </section>

            <!-- Right Side: Live Preview & Optimization Stats -->
            <section class="panel-section preview-panel">
                <div class="glass-card full-height flex-center" id="previewStateEmpty">
                    <div class="empty-state">
                        <i data-feather="image" class="big-icon animate-pulse"></i>
                        <h3>Esperando tu archivo...</h3>
                        <p>Sube una imagen para ver la previsualización interactiva y calcular el ahorro en bytes de inmediato.</p>
                    </div>
                </div>

                <div class="glass-card full-height hidden" id="previewStateActive">
                    <div class="card-header justify-between">
                        <h2>Vista Previa del Procesamiento</h2>
                        <button type="button" id="removeImageBtn" class="btn-icon-only text-danger" title="Quitar imagen">
                            <i data-feather="trash-2"></i>
                        </button>
                    </div>

                    <!-- Side-by-side details wrapper -->
                    <div class="preview-workspace">
                        <!-- Preview container -->
                        <div class="image-preview-box">
                            <img id="imagePreview" src="" alt="Previsualización de la imagen cargada">
                            
                            <!-- Resolution badges overlaid on image preview -->
                            <div class="dimensions-badge dimensions-badge-orig" title="Resolución Original">
                                <i data-feather="maximize"></i> <span id="origDimensionsLabel">0 x 0 px</span>
                            </div>
                        </div>

                        <!-- Meta stats of uploaded file -->
                        <div class="stats-summary-card">
                            <div class="meta-item">
                                <span class="meta-label">Archivo:</span>
                                <span class="meta-val" id="metaFilename">imagen.jpg</span>
                            </div>
                            <div class="meta-item">
                                <span class="meta-label">Tamaño Original:</span>
                                <span class="meta-val" id="metaFileSize">0 KB</span>
                            </div>
                            <div class="meta-item">
                                <span class="meta-label">Tipo original:</span>
                                <span class="meta-val" id="metaFileType">image/jpeg</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <!-- Modal overlay for Processing Success & Analytics -->
    <div class="modal-overlay hidden" id="resultModal">
        <div class="modal-card glass-card">
            <div class="modal-header">
                <div class="modal-title">
                    <i data-feather="check-circle" class="text-success"></i>
                    <h2>¡Redimensionada con éxito!</h2>
                </div>
                <button type="button" id="closeModalBtn" class="btn-icon-only" title="Cerrar modal">
                    <i data-feather="x"></i>
                </button>
            </div>
            
            <div class="modal-body">
                <!-- Optimization analytics dashboard widget -->
                <div class="optimization-card">
                    <div class="savings-donut-area">
                        <div class="savings-percentage">
                            <span id="savingsPercentLabel">0%</span>
                            <span class="savings-subtext">Menos peso</span>
                        </div>
                    </div>

                    <div class="savings-details">
                        <div class="savings-row">
                            <div class="savings-col">
                                <span class="col-lbl">Peso Original</span>
                                <span class="col-val text-muted" id="modalOrigSize">0 KB</span>
                            </div>
                            <div class="arrow-indicator">
                                <i data-feather="arrow-right"></i>
                            </div>
                            <div class="savings-col">
                                <span class="col-lbl">Nuevo Peso</span>
                                <span class="col-val text-primary" id="modalNewSize">0 KB</span>
                            </div>
                        </div>
                        <div class="savings-saving-row">
                            <span class="col-lbl">Espacio Liberado:</span>
                            <strong id="modalSavingsBytes" class="text-success">0 KB</strong>
                        </div>
                    </div>
                </div>

                <!-- Comparison grid -->
                <div class="comparison-grid">
                    <div class="comparison-col">
                        <h3>Imagen Original</h3>
                        <div class="comp-dimensions" id="modalOrigDim">1920 x 1080 px</div>
                    </div>
                    <div class="comparison-col">
                        <h3>Resultado</h3>
                        <div class="comp-dimensions text-primary" id="modalNewDim">1080 x 1080 px</div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <a href="#" id="downloadLink" download="" class="btn-primary btn-full-width">
                    <i data-feather="download"></i>
                    Descargar Imagen Redimensionada
                </a>
            </div>
        </div>
    </div>

    <footer class="app-footer">
        <div class="container text-center">
            <p>&copy; 2026 ImageShrink. Hecho con ❤️ usando PHP & Vanilla CSS.</p>
        </div>
    </footer>

    <!-- Initialize feather icons -->
    <script>
      feather.replace();
    </script>
    <!-- Custom Application Javascript -->
    <script src="assets/js/app.js"></script>
</body>
</html>
