/* =============================================
   XUPING Joyería — Scripts Panel Admin
   ============================================= */

// ─── PREVIEW DE IMAGEN ───────────────────────
function previewImage(input) {
    const preview = document.getElementById('img-preview');
    const clearBtn = document.getElementById('btn-clear-img');
    const uploadArea = document.getElementById('upload-area');

    if (input.files && input.files[0]) {
        const file = input.files[0];

        // Validar tamaño
        if (file.size > 5 * 1024 * 1024) {
            alert('La imagen es muy grande. Máximo 5MB.');
            input.value = '';
            return;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
            if (preview) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            }
            if (clearBtn) clearBtn.style.display = 'block';
            if (uploadArea) uploadArea.style.border = '2px dashed var(--gold)';
        };
        reader.readAsDataURL(file);
    }
}

function clearImage() {
    const input = document.getElementById('imagen-input');
    const preview = document.getElementById('img-preview');
    const clearBtn = document.getElementById('btn-clear-img');
    const uploadArea = document.getElementById('upload-area');

    if (input) input.value = '';
    if (preview) { preview.src = ''; preview.style.display = 'none'; }
    if (clearBtn) clearBtn.style.display = 'none';
    if (uploadArea) uploadArea.style.border = '2px dashed var(--glass-border)';
}

// ─── DRAG & DROP en upload area ──────────────
document.addEventListener('DOMContentLoaded', function() {
    const uploadArea = document.getElementById('upload-area');
    if (!uploadArea) return;

    uploadArea.addEventListener('dragover', function(e) {
        e.preventDefault();
        this.classList.add('drag-over');
    });
    uploadArea.addEventListener('dragleave', function() {
        this.classList.remove('drag-over');
    });
    uploadArea.addEventListener('drop', function(e) {
        e.preventDefault();
        this.classList.remove('drag-over');
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            const input = document.getElementById('imagen-input');
            if (input) {
                // Crear nuevo DataTransfer para asignar archivo
                const dt = new DataTransfer();
                dt.items.add(files[0]);
                input.files = dt.files;
                previewImage(input);
            }
        }
    });
});

// ─── CONFIRM ACTIONS ─────────────────────────
function confirmDelete(message) {
    return confirm(message || '¿Estás seguro de que deseas eliminar este elemento?');
}

// ─── AUTO-DISMISS ALERTS ─────────────────────
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s, transform 0.5s';
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            setTimeout(() => alert.remove(), 500);
        }, 4000);
    });
});
