document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("formPengumuman");
    const toggleBtn = document.getElementById("toggleFormBtn");
    const cancelBtn = document.getElementById("cancelForm");
    const list = document.getElementById("listPengumuman");
    const editForm = document.getElementById("formEditPengumuman");

    if (!form || !toggleBtn || !cancelBtn || !list) return;

    // --- CONFIG ---
    const CLOUD_NAME = window.CLOUDINARY_CLOUD_NAME || 'dl0v35l4q'; 
    const CLOUDINARY_BASE_URL = `https://res.cloudinary.com/${CLOUD_NAME}/image/upload/`;
    const FALLBACK_IMAGE_URL = window.DEFAULT_IMAGE_URL || 'https://via.placeholder.com/200x150?text=No+Image';

    // Helper: Generate URL
    function getCloudinaryUrl(publicId) {
        // DEBUG: Cek ID gambar yang diterima
        // console.log("Processing Image ID:", publicId); 
        
        if (!publicId) return FALLBACK_IMAGE_URL;
        if (publicId.startsWith('http')) return publicId;
        
        return `${CLOUDINARY_BASE_URL}w_400,c_fill/${publicId}`;
    }

    // --- TOGGLE FORM ---
    toggleBtn.addEventListener("click", () => {
        form.classList.toggle("d-none");
        toggleBtn.innerHTML = form.classList.contains("d-none") ? 'Tambah <i class="bi bi-plus-circle ms-2"></i>' : 'Tutup Form <i class="bi bi-chevron-up ms-2"></i>';
    });
    cancelBtn.addEventListener("click", () => {
        form.classList.add("d-none"); form.reset();
        toggleBtn.innerHTML = 'Tambah <i class="bi bi-plus-circle ms-2"></i>';
    });

    // ==========================================
    // DEBUG: CREATE (POST)
    // ==========================================
    form.addEventListener("submit", async (e) => {
        e.preventDefault();
        console.group("🚀 Debug: Submit Tambah Pengumuman");

        const formData = new FormData(form);
        const token = document.querySelector('input[name="_token"]').value;

        // 1. Cek apakah file terdeteksi di FormData
        const fileInput = form.querySelector('input[type="file"]');
        if (fileInput.files.length > 0) {
            console.log("📂 File ditemukan:", fileInput.files[0].name);
            console.log("📏 Ukuran:", (fileInput.files[0].size / 1024).toFixed(2) + " KB");
            console.log("📄 Tipe:", fileInput.files[0].type);
        } else {
            console.warn("⚠️ Tidak ada file yang dipilih user.");
        }

        // 2. Cek isi FormData
        console.log("📦 Isi Payload FormData:");
        for (let [key, value] of formData.entries()) {
            console.log(`${key}:`, value);
        }

        try {
            const res = await fetch("/admin/pengumuman", {
                method: "POST",
                headers: { "X-CSRF-TOKEN": token },
                body: formData,
            });

            console.log("📡 Status Response:", res.status, res.statusText);

            // 3. Baca Response sebagai Text dulu (untuk jaga-jaga kalau bukan JSON/Error HTML)
            const textResponse = await res.text();
            console.log("📥 Raw Response Body:", textResponse);

            let data;
            try {
                data = JSON.parse(textResponse); // Coba parse ke JSON
            } catch (jsonErr) {
                console.error("❌ Gagal parse JSON. Kemungkinan error server (HTML):", jsonErr);
                Swal.fire("Error Server", "Cek Console untuk detail error HTML", "error");
                console.groupEnd();
                return;
            }

            if (res.ok && data.success) {
                console.log("✅ Sukses simpan data:", data);
                Swal.fire({ icon: "success", title: "Berhasil!", text: "Data ditambahkan.", timer: 1500, showConfirmButton: false });
                
                const p = data.data;
                const imgUrl = getCloudinaryUrl(p.gambar);
                const newItem = `
                    <div class="card mb-3 shadow-sm pengumuman-item" data-id="${p.id_pengumuman}">
                        <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-start gap-3">
                            <div class="d-flex flex-column flex-md-row align-items-start gap-3 w-100">
                                <img src="${imgUrl}" class="rounded" style="width:200px; height:150px; object-fit:cover;">
                                <div class="flex-grow-1">
                                    <h5 class="fw-bold mb-4">${p.judul}</h5>
                                    <p class="mb-4">${p.isi.substring(0, 120)}...</p>
                                    <small class="text-muted">Tanggal: ${p.tgl_pengumuman}</small>
                                </div>
                            </div>
                            <div class="d-flex gap-2 align-self-md-center">
                                <button class="btn btn-warning text-white btn-edit" data-id="${p.id_pengumuman}">Edit</button>
                                <button class="btn btn-danger btn-delete" data-id="${p.id_pengumuman}">Hapus</button>
                            </div>
                        </div>
                    </div>`;
                list.insertAdjacentHTML("afterbegin", newItem);
                form.reset(); form.classList.add("d-none");
                toggleBtn.innerHTML = 'Tambah <i class="bi bi-plus-circle ms-2"></i>';
            } else {
                console.warn("❌ Validasi Backend Gagal:", data);
                Swal.fire("Gagal", data.message || "Terjadi kesalahan validasi", "error");
            }
        } catch (err) { 
            console.error("❌ Fetch Error:", err);
            Swal.fire("Error", "Gagal kirim data.", "error"); 
        }
        console.groupEnd();
    });

    // --- EDIT BUTTON CLICK ---
    list.addEventListener("click", async (e) => {
        if (e.target.classList.contains("btn-edit")) {
            const id = e.target.dataset.id;
            console.log("✏️ Klik Edit ID:", id);
            try {
                const res = await fetch(`/admin/pengumuman/${id}`);
                const data = await res.json();
                if (res.ok && data) {
                    document.getElementById("edit_id").value = data.id_pengumuman;
                    document.getElementById("edit_judul").value = data.judul;
                    document.getElementById("edit_isi").value = data.isi;
                    document.getElementById("edit_tgl_pelaksanaan").value = data.tgl_pelaksanaan;
                    document.getElementById("edit_lokasi").value = data.lokasi;

                    const preview = document.getElementById("previewEditImage");
                    if (data.gambar) {
                        preview.src = getCloudinaryUrl(data.gambar, 'w_300,h_200,c_fill/');
                        preview.style.display = "block";
                    } else {
                        preview.style.display = "none";
                    }
                    new bootstrap.Modal(document.getElementById("editPengumumanModal")).show();
                }
            } catch (err) { Swal.fire("Error", "Gagal ambil data.", "error"); }
        }
    });

    // ==========================================
    // DEBUG: UPDATE SUBMIT (PUT)
    // ==========================================
    editForm.addEventListener("submit", async (e) => {
        e.preventDefault();
        console.group("🚀 Debug: Submit Update Pengumuman");
        
        const id = document.getElementById("edit_id").value;
        const formData = new FormData(e.target);
        formData.append("_method", "PUT"); 
        const token = document.querySelector('input[name="_token"]').value;

        // Cek file baru di form edit
        const fileInput = editForm.querySelector('input[type="file"]');
        if (fileInput.files.length > 0) {
            console.log("📂 File Baru ditemukan:", fileInput.files[0].name);
        } else {
            console.log("ℹ️ Tidak ada file baru yang diupload (menggunakan gambar lama).");
        }

        try {
            const res = await fetch(`/admin/pengumuman/${id}`, {
                method: "POST", 
                headers: { "X-CSRF-TOKEN": token },
                body: formData,
            });
            
            console.log("📡 Status Response:", res.status);
            const textResponse = await res.text();
            console.log("📥 Raw Response Body:", textResponse);

            let data;
            try {
                data = JSON.parse(textResponse);
            } catch (err) {
                console.error("❌ Gagal parse JSON:", err);
                Swal.fire("Error", "Response server bukan JSON valid", "error");
                console.groupEnd();
                return;
            }

            if (res.ok && data.success) {
                console.log("✅ Update Sukses:", data);
                Swal.fire({ icon: "success", title: "Berhasil!", text: "Data diperbarui.", timer: 1500, showConfirmButton: false });
                
                const card = document.querySelector(`[data-id="${id}"]`);
                if (card) {
                    const p = data.data;
                    if (p.gambar) {
                        const imgEl = card.querySelector("img");
                        if(imgEl) {
                            // Update src gambar di DOM
                            imgEl.src = getCloudinaryUrl(p.gambar);
                            console.log("🖼️ Gambar di DOM diperbarui ke:", imgEl.src);
                        }
                    }
                    card.querySelector("h5").textContent = formData.get("judul");
                    const isi = formData.get("isi");
                    card.querySelector("p").textContent = isi.length > 120 ? isi.substring(0, 120) + "..." : isi;
                }
                bootstrap.Modal.getInstance(document.getElementById("editPengumumanModal")).hide();
            } else {
                console.warn("❌ Gagal Update:", data);
                Swal.fire("Gagal", data.message, "error");
            }
        } catch (err) { 
            console.error("❌ Error Fetch:", err);
            Swal.fire("Error", "Server Error.", "error"); 
        }
        console.groupEnd();
    });

    // --- DELETE ---
    list.addEventListener("click", async (e) => {
        if (e.target.classList.contains("btn-delete")) {
            const id = e.target.dataset.id;
            const confirm = await Swal.fire({ title: "Hapus?", icon: "warning", showCancelButton: true, confirmButtonColor: "#d33", confirmButtonText: "Ya" });

            if (confirm.isConfirmed) {
                try {
                    const formData = new FormData();
                    formData.append("_method", "DELETE");
                    const res = await fetch(`/admin/pengumuman/${id}`, {
                        method: "POST",
                        headers: { 
                            "X-CSRF-TOKEN": document.querySelector('input[name="_token"]').value,
                            "Accept": "application/json"
                        },
                        body: formData,
                    });
                    const result = await res.json();
                    if (res.ok && result.success) {
                        const item = document.querySelector(`[data-id="${id}"]`);
                        if(item) item.remove();
                        Swal.fire("Terhapus!", result.message, "success");
                    } else { Swal.fire("Gagal", result.message, "error"); }
                } catch (err) { Swal.fire("Error", "Gagal hapus.", "error"); }
            }
        }
    });
});