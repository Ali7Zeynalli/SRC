document.addEventListener('DOMContentLoaded', function() {
    // Dil dəyişmə zamanı loading göstər
    const langLinks = document.querySelectorAll('[data-lang-switch]');
    langLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const lang = this.getAttribute('data-lang');
            switchLanguage(lang);
        });
    });
});

// Loading göstəricisi
function showLoading() {
    const loading = document.createElement('div');
    loading.className = 'loading-overlay';
    loading.innerHTML = `
        <div class="loading-spinner">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `;
    document.body.appendChild(loading);
}

// Dil dəyişmə funksiyası
function switchLanguage(lang) {
    // Loading göstəririk
    Swal.fire({
        title: 'Loading...',
        text: 'Switching language...',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        willOpen: () => {
            Swal.showLoading();
        }
    });

    // Dil dəyişmə sorğusu
    fetch(`security.php?action=switch_language&lang=${lang}`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Uğurlu dil dəyişmə
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: 'Language switched successfully',
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                // Səhifəni yeniləyirik
                window.location.reload();
            });
        } else {
            throw new Error(data.error || 'Error switching language');
        }
    })
    .catch(error => {
        // Xəta baş verərsə
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message,
            confirmButtonColor: '#0d6efd'
        });
    });
}

