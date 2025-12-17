// Pastikan semua elemen HTML dimuat
document.addEventListener("DOMContentLoaded", function () {

    // === Ambil token CSRF dari meta tag ===
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    // === Elemen Form Foto Profil ===
    const photoUploadForm = document.getElementById("photo-upload-form");
    const fotoInput = document.getElementById("foto_profil");
    const previewImg = document.getElementById("profile-image-preview");
    const savePhotoBtn = document.getElementById("savePhotoBtn");

    // === Elemen Form Biodata ===
    const biodataForm = document.getElementById("biodata-form");
    const editBtn = document.getElementById("btnEdit");
    const cancelBtn = document.getElementById("btnCancel");
    const editActions = document.getElementById("editActions");
    const editableFields = document.querySelectorAll(".editable-field");

    // --- 1. Preview Foto ---
    fotoInput.addEventListener("change", () => {
        const file = fotoInput.files[0];
        if (file) {
            previewImg.src = URL.createObjectURL(file);
            savePhotoBtn.classList.remove("d-none");
        }
    });

    // Fungsi helper fetch JSON aman (biar nggak crash kalau error HTML/422)
    const safeFetchJson = async (res) => {
        try {
            return await res.json();
        } catch {
            return {
                success: false,
                message: "Terjadi kesalahan, silakan coba lagi."
            };
        }
    };

    // --- 2. Update Foto Profil ---
    photoUploadForm.addEventListener("submit", async function (e) {
        e.preventDefault();
        const formData = new FormData(this);

        const res = await fetch(this.action, {
            method: "POST",
            body: formData,
            headers: {
                "X-CSRF-TOKEN": csrfToken
                
            }
        });

        const json = await safeFetchJson(res);

        if (json.success) {
            Swal.fire("Berhasil!", json.message, "success");
            // GANTI: Gunakan URL Cloudinary yang dikembalikan dari Controller
            previewImg.src = json.image; 
            savePhotoBtn.classList.add("d-none"); // Sembunyikan tombol simpan
            fotoInput.value = "";
        }else {
            // ... (Error handling tidak berubah)
            Swal.fire("Gagal!", json.message, "error");

        }
    });

    // --- 3. Update Biodata ---
    biodataForm.addEventListener("submit", async function (e) {
        e.preventDefault();

        const formData = new FormData(this);
        formData.append('_method', 'PUT'); // WAJIB

        const res = await fetch(this.action, {
            method: "POST",
            body: formData,
            headers: {
                "X-CSRF-TOKEN": csrfToken,
                "Accept": "application/json" // supaya Laravel tau balas JSON
            }
        });
        console.log(res);

        const json = await safeFetchJson(res);
        console.log(json);
        if (json.success) {
            // Kunci lagi input
            editableFields.forEach(field => field.setAttribute("readonly", true));
            editActions.classList.add("d-none");
            editBtn.classList.remove("d-none");

            // Update value agar UI sesuai database terbaru
            document.getElementById("nama_lengkap").value = json.data.nama_lengkap;
            document.getElementById("email").value = json.data.email;
        }

        Swal.fire(
            json.success ? "Sukses!" : "Gagal!",
            json.message,
            json.success ? "success" : "error"
        );
    });

    // --- 4. Mode Edit Biodata ---
    editBtn.addEventListener("click", () => {
        editableFields.forEach(field => field.removeAttribute("readonly"));
        editBtn.classList.add("d-none");
        editActions.classList.remove("d-none");
    });

    cancelBtn.addEventListener("click", () => {
        editableFields.forEach(field => field.setAttribute("readonly", true));
        editActions.classList.add("d-none");
        editBtn.classList.remove("d-none");
        biodataForm.reset();
    });
});
