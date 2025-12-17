document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("formPengumuman");
    const toggleBtn = document.getElementById("toggleFormBtn");
    const cancelBtn = document.getElementById("cancelForm");
    const list = document.getElementById("listPengumuman");
    const editForm = document.getElementById("formEditPengumuman");

    if (!form || !toggleBtn || !cancelBtn || !list) return;

    // --- KONFIGURASI CLOUDINARY & DEFAULT IMAGE ---
    // Pastikan variabel ini sesuai dengan env Anda
    const CLOUD_NAME = window.CLOUDINARY_CLOUD_NAME || 'dl0v35l4q'; 
    const CLOUDINARY_BASE_URL = `https://res.cloudinary.com/${CLOUD_NAME}/image/upload/`;
    // Gunakan fallback gambar jika default.jpg tidak ditemukan
    const FALLBACK_IMAGE_URL = '/images/default.jpg'; 

    // Helper: Generate URL Gambar
    function getCloudinaryUrl(publicId, transformation = 'w_200,h_150,c_fill/') {
        if (!publicId) return FALLBACK_IMAGE_URL;
        return `${CLOUDINARY_BASE_URL}${transformation}${publicId}`;
    }

    // ====== Toggle Form ======
    toggleBtn.addEventListener("click", () => {
        form.classList.toggle("d-none");
        toggleBtn.innerHTML = form.classList.contains("d-none")
            ? 'Tambah <i class="bi bi-plus-circle ms-2"></i>'
            : 'Tutup Form <i class="bi bi-chevron-up ms-2"></i>';
    });

    cancelBtn.addEventListener("click", () => {
        form.classList.add("d-none");
        form.reset();
        toggleBtn.innerHTML = 'Tambah <i class="bi bi-plus-circle ms-2"></i>';
    });

    // ====== Tambah Pengumuman (STORE) ======
    form.addEventListener("submit", async (e) => {
        e.preventDefault();
        const formData = new FormData(form);
        const token = document.querySelector('input[name="_token"]').value;

        try {
            const response = await fetch("/admin/pengumuman", {
                method: "POST",
                headers: { "X-CSRF-TOKEN": token },
                body: formData,
            });

            const data = await response.json();

            if (response.ok && data.success) {
                Swal.fire({
                    icon: "success",
                    title: "Berhasil!",
                    text: "Pengumuman berhasil ditambahkan.",
                    timer: 1800,
                    showConfirmButton: false,
                });

                const p = data.data;
                const imgUrl = getCloudinaryUrl(p.gambar);

                const newItem = `
                    <div class="card mb-3 shadow-sm pengumuman-item" data-id="${p.id_pengumuman}">
                        <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-start gap-3">
                            <div class="d-flex flex-column flex-md-row align-items-start gap-3 w-100">
                                <img src="${imgUrl}" alt="Gambar" class="rounded" style="width:200px; height:150px; object-fit:cover;">
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
                    </div>
                `;
                list.insertAdjacentHTML("afterbegin", newItem);

                form.reset();
                form.classList.add("d-none");
                toggleBtn.innerHTML = 'Tambah <i class="bi bi-plus-circle ms-2"></i>';
            } else {
                Swal.fire("Gagal", data.message || "Gagal menambah data.", "error");
            }
        } catch (error) {
            console.error(error);
            Swal.fire("Error", "Gagal mengirim data.", "error");
        }
    });

    // ====== Edit Pengumuman (KLIK TOMBOL EDIT) ======
    list.addEventListener("click", async (e) => {
        if (e.target.classList.contains("btn-edit")) {
            const id = e.target.dataset.id;

            try {
                const res = await fetch(`/admin/pengumuman/${id}`);
                const data = await res.json();

                if (res.ok && data) {
                    document.getElementById("edit_id").value = data.id_pengumuman;
                    document.getElementById("edit_judul").value = data.judul;
                    document.getElementById("edit_isi").value = data.isi;
                    document.getElementById("edit_tgl_pelaksanaan").value = data.tgl_pelaksanaan;
                    document.getElementById("edit_lokasi").value = data.lokasi;

                    // Preview Gambar Lama (Cloudinary)
                    const preview = document.getElementById("previewEditImage");
                    if (data.gambar) {
                        // Transformasi gambar agar pas di modal
                        preview.src = getCloudinaryUrl(data.gambar, 'w_300,h_200,c_fill/');
                        preview.style.display = "block";
                    } else {
                        preview.style.display = "none";
                    }

                    new bootstrap.Modal(document.getElementById("editPengumumanModal")).show();
                }
            } catch (err) {
                console.error(err);
                Swal.fire("Error", "Gagal mengambil data.", "error");
            }
        }
    });

    // ====== Update Pengumuman (SUBMIT EDIT) ======
    editForm.addEventListener("submit", async (e) => {
        e.preventDefault();
        const id = document.getElementById("edit_id").value;
        const formData = new FormData(e.target);
        
        // PENTING: Tambahkan method PUT agar Laravel mengenali ini sebagai update
        formData.append("_method", "PUT"); 

        const token = document.querySelector('input[name="_token"]').value;

        try {       
            const res = await fetch(`/admin/pengumuman/${id}`, {
                method: "POST", // Tetap POST, tapi di-spoofing oleh _method: PUT
                headers: { "X-CSRF-TOKEN": token },
                body: formData,
            });
            const data = await res.json();

            if (res.ok && data.success) {
                Swal.fire({
                    icon: "success",
                    title: "Berhasil!",
                    text: "Pengumuman berhasil diperbarui.",
                    timer: 1500,
                    showConfirmButton: false,
                });

                const card = document.querySelector(`[data-id="${id}"]`);
                if (card) {
                    const p = data.data;
                    // Update Gambar jika ada perubahan
                    if (p.gambar) {
                        const newImgUrl = getCloudinaryUrl(p.gambar);
                        const imgEl = card.querySelector("img");
                        if(imgEl) imgEl.src = newImgUrl;
                    }

                    card.querySelector("h5").textContent = formData.get("judul");
                    // Update isi text pendek
                    const isiText = formData.get("isi");
                    card.querySelector("p").textContent = isiText.length > 120 ? isiText.substring(0, 120) + "..." : isiText;
                }

                bootstrap.Modal.getInstance(document.getElementById("editPengumumanModal")).hide();
            } else {
                Swal.fire("Gagal", data.message || "Gagal menyimpan perubahan.", "error");
            }
        } catch (error) {
            console.error(error);
            Swal.fire("Error", "Terjadi kesalahan server.", "error");
        }
    });

    // ====== Hapus Pengumuman (DELETE) ======
    list.addEventListener("click", async (e) => {
        if (e.target.classList.contains("btn-delete")) {
            const id = e.target.dataset.id;
            const confirm = await Swal.fire({
                title: "Hapus?",
                text: "Data tidak bisa dikembalikan!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#d33",
                confirmButtonText: "Ya, hapus!"
            });

            if (confirm.isConfirmed) {
                try {
                    const formData = new FormData();
                    formData.append("_method", "DELETE");

                    const res = await fetch(`/admin/pengumuman/${id}`, {
                        method: "POST",
                        headers: {
                            "X-CSRF-TOKEN": document.querySelector('input[name="_token"]').value,
                            "Accept": "application/json",
                        },
                        body: formData,
                    });

                    const result = await res.json();

                    if (res.ok && result.success) {
                        const item = document.querySelector(`[data-id="${id}"]`);
                        if (item) item.remove();
                        Swal.fire("Terhapus!", result.message, "success");
                    } else {
                        Swal.fire("Gagal", result.message, "error");
                    }
                } catch (error) {
                    Swal.fire("Error", "Kesalahan server.", "error");
                }
            }
        }
    });
});