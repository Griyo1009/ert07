document.addEventListener("DOMContentLoaded", () => {
    const formMateri = document.getElementById("formMateri");
    const listMateri = document.getElementById("listMateri");
    const formEditMateri = document.getElementById("formEditMateri");
    let deletedFiles = [];

    const CLOUD_NAME = window.CLOUDINARY_CLOUD_NAME || 'dl0v35l4q';
    const FALLBACK_IMAGE_URL = window.DEFAULT_IMAGE_URL || '/images/default.jpg';
    const CLOUDINARY_BASE_URL = `https://res.cloudinary.com/${CLOUD_NAME}/image/upload/`;

    function getCloudinaryAssetUrl(publicId) {
        if (!publicId) return '#';
        // Asumsi raw/upload/ untuk file yang diunggah seperti PDF, Video, Doc.
        return `${CLOUDINARY_BASE_URL}${publicId}`; 
    }
    // ===== CREATE MATERI =====
    formMateri?.addEventListener("submit", async (e) => {
        e.preventDefault();
        const formData = new FormData(formMateri);

        Swal.fire({
            title: "Menambahkan...",
            text: "Materi sedang diupload...",
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading(),
        });

        try {
            const response = await fetch(`/admin/materi/store`, {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": document.querySelector('input[name="_token"]').value,
                    "Accept": "application/json",
                },
                body: formData,
            });

            const text = await response.text();
            let result;
            try {
                result = JSON.parse(text);
            } catch {
                Swal.fire("Error", "Server mengembalikan response HTML, bukan JSON", "error");
                return;
            }

            if (response.ok && result.success) {
                Swal.fire("Berhasil!", result.message, "success");
                appendMateri(result.data);
                formMateri.reset();
                formMateri.classList.add("d-none");
                document.getElementById("toggleFormBtn").innerHTML =
                    'Tambah <i class="bi bi-plus-circle ms-2"></i>';
            } else {
                Swal.fire("Gagal", result.message, "error");
            }
        } catch (error) {
            Swal.fire("Error", "Tidak dapat mengupload materi.", "error");
        }
    });

    // ===== DELETE MATERI =====
    listMateri?.addEventListener("click", async (e) => {
        if (!e.target.classList.contains("btn-delete")) return;
        const id = e.target.dataset.id;

        const confirm = await Swal.fire({
            title: "Hapus Materi?",
            text: "Data akan dihapus permanen.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Ya, hapus",
            cancelButtonText: "Batal",
        });

        if (!confirm.isConfirmed) return;

        try {
            const response = await fetch(`/admin/materi/delete/${id}`, {
                method: "DELETE",
                headers: {
                    "X-CSRF-TOKEN": document.querySelector('input[name="_token"]').value,
                    "Accept": "application/json",
                },
            });

            const result = await response.json();

            if (result.success) {
                Swal.fire("Berhasil!", result.message, "success");
                document.querySelector(`.btn-delete[data-id="${id}"]`).closest(".materi-card").remove();
            } else {
                Swal.fire("Gagal", result.message, "error");
            }
        } catch {
            Swal.fire("Error", "Tidak dapat menghapus materi.", "error");
        }
    });

    // ===== SHOW EDIT MODAL =====
    listMateri?.addEventListener("click", async (e) => {
        // Cek apakah yang diklik adalah tombol edit atau icon di dalamnya
        const btn = e.target.closest(".btn-edit");
        if (!btn) return;
        
        const id = btn.dataset.id;

        try {
            const res = await fetch(`/admin/materi/show/${id}`);
            const data = await res.json();

            if (res.ok && data.success) {
                deletedFiles = []; // Reset array file yang dihapus

                // Isi form text dasar
                document.getElementById("edit_id_materi").value = data.data.id_materi;
                document.getElementById("edit_judul").value = data.data.judul_materi;
                document.getElementById("edit_deskripsi").value = data.data.deskripsi ?? "";

                // HAPUS BARIS DI BAWAH INI (Penyebab Error)
                // document.getElementById("edit_link_url").value = data.data.link_url ?? ""; 

                // --- Render File & Link yang Sudah Ada ---
                const fileContainer = document.getElementById("existingFiles");
                fileContainer.innerHTML = "";
             
                if (data.data.files && data.data.files.length > 0) {
                    data.data.files.forEach(file => {
                       const div = document.createElement("div");
                        div.classList.add("d-flex", "justify-content-between", "align-items-center", "mb-1", "p-2", "border-bottom");
                        
                        let fileDisplay = '';
                        let href = '#';
                        let fileIdentifier = file.file_path; // Public ID

                        if(file.tipe_file === 'link') {
                            href = file.link_url;
                            fileDisplay = `<i class="bi bi-link-45deg me-2 text-primary"></i> <a href="${href}" target="_blank" class="text-truncate" style="max-width: 300px;">${file.link_url}</a>`;
                        } else {
                            // File Cloudinary: Gunakan link_url (Secure URL) atau Public ID untuk merangkai
                            href = file.link_url || getCloudinaryAssetUrl(file.file_path);
                            fileDisplay = `<i class="bi bi-file-earmark me-2 text-secondary"></i> <a href="${href}" target="_blank" class="text-truncate" style="max-width: 300px;">${fileIdentifier.split('/').pop()}</a>`;
                        }

                        div.innerHTML = `
                            <div class="d-flex align-items-center">
                                ${fileDisplay}
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-danger btn-remove-file" data-id="${file.id_file}">
                                <i class="bi bi-trash"></i>
                            </button>
                        `;
                        fileContainer.appendChild(div);
                    });
                } else {
                    fileContainer.innerHTML = `<em class="text-muted">Belum ada file atau link tersimpan.</em>`;
                }

                // Reset input form tambahan (agar kosong saat modal dibuka)
                document.getElementById("editFileWrapper").innerHTML = `
                    <div class="input-group mb-2">
                        <input type="file" name="new_files[]" class="form-control">
                        <button type="button" class="btn btn-outline-secondary btn-add-edit-file"><i class="bi bi-plus"></i></button>
                    </div>`;
                
                document.getElementById("editLinkWrapper").innerHTML = `
                    <div class="input-group mb-2">
                        <input type="text" name="new_links[]" class="form-control" placeholder="Masukkan link materi">
                        <button type="button" class="btn btn-outline-secondary btn-add-edit-link"><i class="bi bi-plus"></i></button>
                    </div>`;

                // Tampilkan Modal
                new bootstrap.Modal(document.getElementById("editMateriModal")).show();
            } else {
                Swal.fire("Gagal", data.message || "Data tidak ditemukan", "error");
            }
        } catch (error) {
            console.error(error); // Debugging di console browser
            Swal.fire("Error", "Gagal mengambil data materi. Cek console browser.", "error");
        }
    });

    // ===== UPDATE MATERI =====
    document.getElementById("formEditMateri")?.addEventListener("submit", async (e) => {
        e.preventDefault();
        const id = document.getElementById("edit_id_materi").value;
        const formData = new FormData(e.target);
        formData.append("_method", "PUT");
        formData.append("deleted_files", JSON.stringify(deletedFiles));

        Swal.fire({
            title: "Menyimpan...",
            text: "Sedang memperbarui materi...",
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading(),
        });

        try {
            const res = await fetch(`/admin/materi/update/${id}`, {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": document.querySelector('input[name="_token"]').value,
                },
                body: formData,
            });

            const data = await res.json();

            if (data.success) {
                Swal.fire("Berhasil!", "Perubahan tersimpan.", "success");
                updateDisplayedMateri(data.data);
                deletedFiles = [];

                bootstrap.Modal.getInstance(document.getElementById("editMateriModal")).hide();
            } else {
                Swal.fire("Gagal", data.message, "error");
            }
        } catch {
            Swal.fire("Error", "Terjadi kesalahan server.", "error");
        }
    });

    // ===== ADD MORE FILE FIELD (EDIT) =====
    document.addEventListener("click", (e) => {
        if (!e.target.classList.contains("btn-add-edit-file")) return;
        const wrapper = document.getElementById("editFileWrapper");
        const div = document.createElement("div");
        div.classList.add("input-group", "mb-2");
        div.innerHTML = `
            <input type="file" name="new_files[]" class="form-control">
            <button type="button" class="btn btn-danger btn-remove-input">-</button>
        `;
        wrapper.appendChild(div);
    });

    // ===== ADD MORE LINK FIELD (EDIT) =====
    document.addEventListener("click", (e) => {
        if (!e.target.classList.contains("btn-add-edit-link")) return;
        const wrapper = document.getElementById("editLinkWrapper");
        const div = document.createElement("div");
        div.classList.add("input-group", "mb-2");
        div.innerHTML = `
            <input type="text" name="new_links[]" class="form-control" placeholder="Masukkan link materi">
            <button type="button" class="btn btn-danger btn-remove-input">-</button>
        `;
        wrapper.appendChild(div);
    });

    // ===== REMOVE DYNAMIC INPUT =====
    document.addEventListener("click", (e) => {
        if (e.target.classList.contains("btn-remove-input")) {
            e.target.closest(".input-group").remove();
        }
    });

    // ===== DELETE FILE (LOCAL ONLY) =====
    document.addEventListener("click", (e) => {
        const btn = e.target.closest(".btn-remove-file");
        if (btn) {
            const id = btn.dataset.id;

            if (id) {
                // Tambahkan ke array deletedFiles untuk dikirim saat UPDATE
                deletedFiles.push(id);
                // Tambahkan public ID/path jika diperlukan untuk logging atau validasi
                // Hapus dari DOM
                btn.closest(".file-item-wrapper").remove();
            }
        }
    });

    // ===== UPDATE CARD DISPLAY AFTER EDIT =====
    function updateDisplayedMateri(materi) {
        const card = document.querySelector(`.btn-edit[data-id="${materi.id_materi}"]`).closest(".materi-card");
        if (!card) return;

        card.querySelector("h5").textContent = materi.judul_materi;
        card.querySelector("p.text-muted").textContent = materi.deskripsi;
        card.querySelector("small.text-secondary").textContent = `Diupload pada ${materi.tgl_up}`;

        const fileContainer = card.querySelector(".mt-3");
        if (materi.files.length > 0) {
            fileContainer.innerHTML = materi.files.map(f => renderFileItem(f)).join("");
        } else {
            fileContainer.innerHTML = `<p class="text-muted small">Tidak ada file.</p>`;
        }
    }

    // ===== FILE ITEM RENDERER =====
    function renderFileItem(file) {
        let icon = "bi-file-earmark-text";
        if (file.tipe_file === "pdf") icon = "bi-file-earmark-pdf";
        else if (file.tipe_file === "mp4") icon = "bi-play-circle";
        else if (file.tipe_file === "gambar") icon = "bi-image";
        else if (file.tipe_file === "link") icon = "bi-link-45deg";

        let href = file.link_url; // Gunakan link_url sebagai URL utama
        let text = "";

        if (file.tipe_file === "link") {
            text = file.link_url;
        } else {
            // File Cloudinary: Jika link_url kosong, gunakan Public ID untuk merangkai URL
            if (!href) {
                href = getCloudinaryAssetUrl(file.file_path);
            }
            text = "Lihat File: " + (file.file_path ? file.file_path.split('/').pop() : 'File');
        }

        return `
          <div class="file-item">
              <i class="bi ${icon}"></i>
              <a href="${href}" target="_blank" class="text-decoration-none">${text}</a>
          </div>
        `;
    }

    // ===== ADD NEW CARD AFTER CREATE =====
    function appendMateri(materi) {
        const headingElement = listMateri.querySelector('h6');
        const card = document.createElement("div");
        card.classList.add("materi-card", "mb-4");
        if (headingElement) {
            card.innerHTML = `
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h5 class="fw-bold">${materi.judul_materi}</h5>
                        <p class="text-muted mb-2">${materi.deskripsi}</p>
                        <small class="text-secondary d-block mb-2">Diupload pada ${materi.tgl_up}</small>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-warning text-white btn-sm btn-edit" data-id="${materi.id_materi}">Edit</button>
                        <button class="btn btn-danger btn-sm btn-delete" data-id="${materi.id_materi}">Hapus</button>
                    </div>
                </div>
                <div class="mt-3">
                    ${materi.files.map(f => renderFileItem(f)).join("")}
                </div>
            `;
            headingElement.insertAdjacentElement('afterend', card);
        } else {
            // Fallback
            listMateri.prepend(card);
        }
    }
});
