/**
 * app.js
 * Lógica interactiva del cliente para la aplicación ImageShrink.
 */

document.addEventListener('DOMContentLoaded', () => {
    // --- Elementos del DOM ---
    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('fileInput');
    const settingsContainer = document.getElementById('settingsContainer');
    
    const widthInput = document.getElementById('widthInput');
    const heightInput = document.getElementById('heightInput');
    const aspectRatioBtn = document.getElementById('aspectRatioBtn');
    const qualitySlider = document.getElementById('qualitySlider');
    const qualityVal = document.getElementById('qualityVal');
    const formatSelect = document.getElementById('formatSelect');
    const resizeBtn = document.getElementById('resizeBtn');
    
    // Fit modes
    const fitModeProportional = document.getElementById('fitModeProportional');
    const fitModeCrop = document.getElementById('fitModeCrop');
    
    // Previsualización y estados
    const previewStateEmpty = document.getElementById('previewStateEmpty');
    const previewStateActive = document.getElementById('previewStateActive');
    const imagePreview = document.getElementById('imagePreview');
    const origDimensionsLabel = document.getElementById('origDimensionsLabel');
    const removeImageBtn = document.getElementById('removeImageBtn');
    
    // Metadatos
    const metaFilename = document.getElementById('metaFilename');
    const metaFileSize = document.getElementById('metaFileSize');
    const metaFileType = document.getElementById('metaFileType');
    
    // Modal de resultados
    const resultModal = document.getElementById('resultModal');
    const closeModalBtn = document.getElementById('closeModalBtn');
    const downloadLink = document.getElementById('downloadLink');
    const savingsPercentLabel = document.getElementById('savingsPercentLabel');
    const modalOrigSize = document.getElementById('modalOrigSize');
    const modalNewSize = document.getElementById('modalNewSize');
    const modalSavingsBytes = document.getElementById('modalSavingsBytes');
    const modalOrigDim = document.getElementById('modalOrigDim');
    const modalNewDim = document.getElementById('modalNewDim');
    
    // --- Variables de Estado ---
    let loadedFile = null;
    let originalWidth = 0;
    let originalHeight = 0;
    let isAspectRatioLocked = true;
    let originalAspectRatio = 1;

    // --- Inicializar Feather Icons ---
    if (typeof feather !== 'undefined') {
        feather.replace();
    }

    // --- 1. Manejo de Drag and Drop ---

    // Prevenir comportamientos por defecto
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    // Agregar/Remover clases al arrastrar
    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, () => dropZone.classList.add('dragover'), false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, () => dropZone.classList.remove('dragover'), false);
    });

    // Evento de soltar
    dropZone.addEventListener('drop', (e) => {
        const dt = e.dataTransfer;
        const files = dt.files;
        if (files.length > 0) {
            handleFile(files[0]);
        }
    });

    // Clic en la zona de soltar abre el explorador
    dropZone.addEventListener('click', () => fileInput.click());

    // Selección manual de archivo
    fileInput.addEventListener('change', (e) => {
        if (e.target.files.length > 0) {
            handleFile(e.target.files[0]);
        }
    });

    // --- 2. Procesamiento del Archivo del Cliente ---

    function handleFile(file) {
        // Validar tipo mime del archivo
        if (!file.type.startsWith('image/')) {
            alert('Por favor, selecciona únicamente archivos de imagen (JPEG, PNG, WebP, GIF).');
            return;
        }

        loadedFile = file;

        // Mostrar metadatos básicos
        metaFilename.textContent = file.name;
        metaFileSize.textContent = formatBytes(file.size);
        metaFileType.textContent = file.type || 'Desconocido';

        // Previsualizar localmente con FileReader
        const reader = new FileReader();
        reader.onload = (e) => {
            imagePreview.src = e.target.result;

            // Leer las dimensiones nativas de la imagen
            const img = new Image();
            img.onload = () => {
                originalWidth = img.naturalWidth;
                originalHeight = img.naturalHeight;
                originalAspectRatio = originalWidth / originalHeight;

                // Actualizar UI de dimensiones originales
                origDimensionsLabel.textContent = `${originalWidth} x ${originalHeight} px`;
                
                // Rellenar valores sugeridos iniciales
                widthInput.value = originalWidth;
                heightInput.value = originalHeight;
                
                // Habilitar panel de control y cambiar estados
                settingsContainer.classList.remove('disabled');
                previewStateEmpty.classList.add('hidden');
                previewStateActive.classList.remove('hidden');
            };
            img.src = e.target.result;
        };
        reader.readAsDataURL(file);
    }

    // --- 3. Lógica de Redimensión en Tiempo Real & Aspect Ratio ---

    // Toggle bloqueo relación de aspecto
    aspectRatioBtn.addEventListener('click', () => {
        isAspectRatioLocked = !isAspectRatioLocked;
        if (isAspectRatioLocked) {
            aspectRatioBtn.classList.add('active');
            // Re-sincronizar alto en base al ancho actual
            if (widthInput.value) {
                heightInput.value = Math.round(parseInt(widthInput.value, 10) / originalAspectRatio);
            }
        } else {
            aspectRatioBtn.classList.remove('active');
        }
    });

    // Sincronizar Ancho -> Alto
    widthInput.addEventListener('input', () => {
        if (isAspectRatioLocked && widthInput.value && originalWidth > 0) {
            const val = parseInt(widthInput.value, 10);
            if (isNaN(val) || val <= 0) return;
            heightInput.value = Math.round(val / originalAspectRatio);
        }
        clearActiveTemplate();
    });

    // Sincronizar Alto -> Ancho
    heightInput.addEventListener('input', () => {
        if (isAspectRatioLocked && heightInput.value && originalHeight > 0) {
            const val = parseInt(heightInput.value, 10);
            if (isNaN(val) || val <= 0) return;
            widthInput.value = Math.round(val * originalAspectRatio);
        }
        clearActiveTemplate();
    });

    // --- 4. Plantillas Rápidas ---

    document.querySelectorAll('.btn-template').forEach(btn => {
        btn.addEventListener('click', (e) => {
            // Desmarcar otras plantillas
            document.querySelectorAll('.btn-template').forEach(b => b.classList.remove('active'));
            
            const btnEl = e.currentTarget;
            btnEl.classList.add('active');

            const targetW = parseInt(btnEl.dataset.w, 10);
            const targetH = parseInt(btnEl.dataset.h, 10);
            const needsCrop = btnEl.dataset.crop === 'true';

            // Desactivar relación de aspecto temporalmente para dimensiones cuadradas forzadas
            if (needsCrop) {
                if (isAspectRatioLocked) {
                    aspectRatioBtn.click(); // Desactivar candado
                }
                fitModeCrop.checked = true;
            } else {
                if (!isAspectRatioLocked) {
                    aspectRatioBtn.click(); // Activar candado
                }
                fitModeProportional.checked = true;
            }

            widthInput.value = targetW;
            heightInput.value = targetH;
        });
    });

    function clearActiveTemplate() {
        document.querySelectorAll('.btn-template').forEach(btn => btn.classList.remove('active'));
    }

    // --- 5. Slider de Calidad ---

    qualitySlider.addEventListener('input', (e) => {
        qualityVal.textContent = `${e.target.value}%`;
    });

    // --- 6. Quitar Imagen cargada (Reset) ---

    removeImageBtn.addEventListener('click', resetApp);

    function resetApp() {
        loadedFile = null;
        originalWidth = 0;
        originalHeight = 0;
        fileInput.value = '';
        imagePreview.src = '';
        widthInput.value = '';
        heightInput.value = '';
        qualitySlider.value = 90;
        qualityVal.textContent = '90%';
        formatSelect.value = 'original';
        fitModeProportional.checked = true;
        
        if (!isAspectRatioLocked) {
            aspectRatioBtn.click(); // Habilitar candado por defecto
        }
        
        clearActiveTemplate();
        settingsContainer.classList.add('disabled');
        previewStateActive.classList.add('hidden');
        previewStateEmpty.classList.remove('hidden');
    }

    // --- 7. Envío y Procesamiento Backend (AJAX) ---

    resizeBtn.addEventListener('click', () => {
        if (!loadedFile) {
            alert('Sube una imagen primero.');
            return;
        }

        const targetW = parseInt(widthInput.value, 10);
        const targetH = parseInt(heightInput.value, 10);

        if (isNaN(targetW) || targetW <= 0 || isNaN(targetH) || targetH <= 0) {
            alert('Por favor, introduce dimensiones de ancho y alto válidas mayores a 0.');
            return;
        }

        // Estado de Carga
        resizeBtn.classList.add('loading');
        resizeBtn.disabled = true;
        const btnText = resizeBtn.querySelector('.btn-text');
        const loader = resizeBtn.querySelector('.loader');
        const btnIcon = resizeBtn.querySelector('.btn-icon');
        
        btnText.textContent = 'Procesando...';
        loader.classList.remove('hidden');
        btnIcon.classList.add('hidden');

        // Construir FormData
        const formData = new FormData();
        formData.append('image', loadedFile);
        formData.append('width', targetW);
        formData.append('height', targetH);
        formData.append('quality', qualitySlider.value);
        formData.append('aspect_ratio', isAspectRatioLocked ? 'true' : 'false');
        formData.append('crop', fitModeCrop.checked ? 'true' : 'false');
        formData.append('format', formatSelect.value);

        // Envío AJAX
        fetch('resize.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Respuesta de red no válida.');
            }
            return response.json();
        })
        .then(res => {
            if (res.success) {
                // Rellenar modal con datos e informe de compresión
                savingsPercentLabel.textContent = `${res.data.savings.percent}%`;
                modalOrigSize.textContent = res.data.original.size_formatted;
                modalNewSize.textContent = res.data.resized.size_formatted;
                
                // Mostrar si ahorró o si aumentó de tamaño (caso atípico pero posible con PNG->JPG/alta calidad)
                if (res.data.savings.bytes < 0) {
                    modalSavingsBytes.textContent = `+${res.data.savings.formatted}`;
                    modalSavingsBytes.className = 'text-danger';
                } else {
                    modalSavingsBytes.textContent = res.data.savings.formatted;
                    modalSavingsBytes.className = 'text-success';
                }

                modalOrigDim.textContent = `${res.data.original.width} x ${res.data.original.height} px`;
                modalNewDim.textContent = `${res.data.resized.width} x ${res.data.resized.height} px`;
                
                // Configurar botón de descarga
                downloadLink.href = res.data.download_url;
                downloadLink.setAttribute('download', res.data.filename);

                // Configurar animación del gráfico cónico de ahorro (conic-gradient)
                const donut = document.querySelector('.savings-donut-area');
                const percent = res.data.savings.percent;
                // Colorear el borde cónico dinámicamente según el porcentaje de compresión
                donut.style.background = `radial-gradient(circle, var(--bg-main) 68%, transparent 69%), conic-gradient(var(--success) 0% ${percent}%, rgba(255,255,255,0.06) ${percent}% 100%)`;

                // Mostrar Modal
                resultModal.classList.remove('hidden');
            } else {
                alert(`Error al procesar: ${res.message}`);
            }
        })
        .catch(err => {
            console.error(err);
            alert('Ocurrió un error de red o de ejecución al comunicarse con el servidor.');
        })
        .finally(() => {
            // Restaurar estado de botón
            resizeBtn.classList.remove('loading');
            resizeBtn.disabled = false;
            btnText.textContent = 'Redimensionar Imagen';
            loader.classList.add('hidden');
            btnIcon.classList.remove('hidden');
        });
    });

    // --- 8. Cerrar Modal ---

    closeModalBtn.addEventListener('click', closeModal);
    
    // Cerrar haciendo clic fuera de la tarjeta modal
    resultModal.addEventListener('click', (e) => {
        if (e.target === resultModal) {
            closeModal();
        }
    });

    function closeModal() {
        resultModal.classList.add('hidden');
    }

    // --- 9. Función Auxiliar de Formateo ---

    function formatBytes(bytes, decimals = 2) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const dm = decimals < 0 ? 0 : decimals;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
    }
});
