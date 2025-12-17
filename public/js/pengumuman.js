document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("formPengumuman");
    const toggleBtn = document.getElementById("toggleFormBtn");
    const cancelBtn = document.getElementById("cancelForm");
    const list = document.getElementById("listPengumuman");
    const editForm = document.getElementById("formEditPengumuman");

    if (!form || !toggleBtn || !cancelBtn || !list) return;

    const CLOUD_NAME = window.CLOUDINARY_CLOUD_NAME || 'dl0v35l4q'; 
    const CLOUDINARY_BASE_URL = `https://res.cloudinary.com/${CLOUD_NAME}/image/upload/`;

    const FALLBACK_IMAGE_URL = window.DEFAULT_IMAGE_URL || '/images/default.jpg';
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

    function getCloudinaryUrl(publicId, transformation = 'w_200,h_150,c_fill/') {
        if (!publicId) return FALLBACK_IMAGE_URL; // Fallback lokal dari Blade
        
        // Cld URL format: https://res.cloudinary.com/CLOUD_NAME/image/upload/t_x/public_id
        return `${CLOUDINARY_BASE_URL}${transformation}${publicId}`;
    }

    // ====== Tambah Pengumuman ======
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
                const itemImageUrl = getCloudinaryUrl(p.gambar, 'w_200,h_150,c_fill/');
                const newItem = `
                    <div class="card mb-3 shadow-sm pengumuman-item" data-id="${p.id_pengumuman}">
                        <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-start gap-3">
                            <div class="d-flex flex-column flex-md-row align-items-start gap-3 w-100">
                                <img src="${itemImageUrl}" 
                                    alt="Gambar Pengumuman"
                                    class="rounded"
                                    style="width:200px; height:150px; object-fit:cover;">
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
                Swal.fire({
                    icon: "error",
                    title: "Gagal!",
                    text: data.message || "Terjadi kesalahan saat menambah pengumuman.",
                });
            }
        } catch (error) {
            console.error(error);
            Swal.fire("Error", "Gagal mengirim data ke server.", "error");
        }
    });
    function appendPengumuman(data) {
    
        const imageUrl = getCloudinaryUrl(data.gambar);
        
        const newCard = document.createElement("div");
        newCard.classList.add("card", "mb-3", "shadow-sm", "pengumuman-item");
        newCard.setAttribute("data-id", data.id_pengumuman);
        newCard.innerHTML = `
            <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-start gap-3">
                <div class="d-flex flex-column flex-md-row align-items-start gap-3 w-100 ">
                    <img src="${imageUrl}" alt="Gambar Pengumuman" class="rounded" style="width:200px; height:150px; object-fit:cover;">
                    </div>
                </div>
        `;
        list.prepend(newCard); // Tambahkan di paling atas
    }

    // ====== Edit Pengumuman ======
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



                    const preview = document.getElementById("previewEditImage");
                    const publicId = data.gambar;

                    // *** PERBAIKAN: Gunakan fungsi getCloudinaryUrl untuk mengisi preview modal ***
                    if (publicId) {
                        // Gunakan transformasi yang lebih besar untuk preview di modal
                        preview.src = getCloudinaryUrl(publicId, 'w_300,h_200,c_fill/'); 
                        preview.style.display = "block";
                    } else {
                        preview.style.display = "none";
                    }

                    new bootstrap.Modal(document.getElementById("editPengumumanModal")).show();
                }
            } catch (err) {
                console.error(err);
                Swal.fire("Error", "Gagal mengambil data pengumuman.", "error");
            }
        }
    });

    // ====== Update Pengumuman ======
    editForm.addEventListener("submit", async (e) => {
        e.preventDefault();
        const id = document.getElementById("edit_id").value;
        const formData = new FormData(e.target);
        const token = document.querySelector('input[name="_token"]').value;

        try {       
            const res = await fetch(`/admin/pengumuman/${id}`, {
                method: "POST", 
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

                    if (p.gambar) {
                        const newImageUrl = getCloudinaryUrl(p.gambar, 'w_200,h_150,c_fill/');
                        card.querySelector("img").src = newImageUrl;
                    }

                    card.querySelector("h5").textContent = formData.get("judul");
                    card.querySelector("p").textContent =
                        formData.get("isi").substring(0, 120) + "...";
                }

                bootstrap.Modal.getInstance(
                    document.getElementById("editPengumumanModal")
                ).hide();
            } else {
                Swal.fire("Gagal", data.message || "Gagal menyimpan perubahan.", "error");
            }
        } catch (error) {
            console.error(error);
            Swal.fire("Error", "Terjadi kesalahan server.", "error");
        }
    });

    // ====== Hapus Pengumuman ======
    // ====== Hapus Pengumuman ======
list.addEventListener("click", async (e) => {
    if (e.target.classList.contains("btn-delete")) {
        const id = e.target.dataset.id;

        const confirm = await Swal.fire({
            title: "Yakin hapus pengumuman ini?",
            text: "Data tidak bisa dikembalikan setelah dihapus!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            cancelButtonColor: "#3085d6",
            confirmButtonText: "Ya, hapus!",
            cancelButtonText: "Batal",
        });

        if (confirm.isConfirmed) {
            try {
                // Gunakan POST + _method DELETE (lebih kompatibel di Laravel)
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
                    // Hapus elemen langsung dari DOM
                    const item = document.querySelector(`[data-id="${id}"]`);
                    if (item) {
                        item.classList.add("fade-out");
                        setTimeout(() => item.remove(), 400);
                    }

                    // Notifikasi sukses
                    Swal.fire({
                        icon: "success",
                        title: "Terhapus!",
                        text: result.message || "Pengumuman berhasil dihapus.",
                        timer: 1500,
                        showConfirmButton: false,
                    });
                } else {
                    Swal.fire("Gagal", result.message || "Tidak dapat menghapus data.", "error");
                }
            } catch (error) {
                console.error(error);
                Swal.fire("Error", "Terjadi kesalahan server.", "error");
            }
        }
    }
});

});
