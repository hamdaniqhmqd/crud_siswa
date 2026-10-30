"use strict"; // mode ketat: kesalahan umum langsung dilaporkan sebagai error

// Singkatan agar kode lebih pendek
const $ = (selector, induk = document) => induk.querySelector(selector);
const $$ = (selector, induk = document) =>
  Array.from(induk.querySelectorAll(selector));

// Menunda pemanggilan fn sampai tidak ada panggilan baru selama `jeda` milidetik
function debounce(fn, jeda = 300) {
  let timer;
  return (...args) => {
    clearTimeout(timer);
    timer = setTimeout(() => fn(...args), jeda);
  };
}

// ===== 1. TEMA GELAP =====
function initTema() {
  const tombol = $("#tombol-tema");
  if (!tombol) {
    console.warn("Tombol tema tidak ditemukan");
  }
  const html = document.documentElement; // elemen <html>
  const terapkan = (tema) => {
    html.dataset.theme = tema; // menjadi atribut data-theme="..."
    tombol.textContent = tema === "dark" ? "☀️" : "🌙";
    tombol.setAttribute("aria-pressed", String(tema === "dark"));
  };

  // Saat halaman dibuka: pakai pilihan tersimpan, jika tidak ada ikuti sistem operasi
  const tersimpan = localStorage.getItem("tema");
  const sistemGelap = window.matchMedia("(prefers-color-scheme: dark)").matches;
  terapkan(tersimpan ?? (sistemGelap ? "dark" : "light"));

  tombol.addEventListener("click", () => {
    console.log("tombol tema diklik");
    const baru = html.dataset.theme === "dark" ? "light" : "dark";
    terapkan(baru);
    localStorage.setItem("tema", baru);
  });
}

// ===== 2. PESAN (ALERT) =====
function initPesan() {
  const daftarPesan = $$(".pesan");
  if (daftarPesan.length === 0) return;
  const sembunyikan = (pesan) => {
    pesan.classList.add("hilang"); // CSS menjalankan transisi opacity
    pesan.addEventListener("transitionend", () => pesan.remove(), {
      once: true,
    });
  };

  daftarPesan.forEach((pesan) => {
    // Tambahkan tombol × pada setiap pesan
    const tutup = document.createElement("button");
    tutup.type = "button";
    tutup.className = "tutup-pesan";
    tutup.setAttribute("aria-label", "Tutup pesan");
    tutup.textContent = "×";
    pesan.append(tutup);
    // Hanya pesan sukses yang hilang sendiri; pesan gagal harus dibaca pengguna
    if (pesan.classList.contains("sukses")) {
      setTimeout(() => sembunyikan(pesan), 4000);
    }
  });

  // Event delegation: satu listener untuk semua tombol ×
  document.addEventListener("click", (event) => {
    const tombol = event.target.closest(".tutup-pesan");
    if (tombol) sembunyikan(tombol.closest(".pesan"));
  });
  // Bersihkan ?status=... dari URL agar pesan tidak muncul lagi saat halaman di-refresh
  const url = new URL(location.href);
  if (url.searchParams.has("status")) {
    url.searchParams.delete("status");
    history.replaceState(null, "", url);
  }
}

// ===== 3. KONFIRMASI HAPUS =====
function initKonfirmasiHapus() {
  const daftarForm = $$(".form-hapus");
  if (daftarForm.length === 0) {
    console.warn("Form hapus tidak ditemukan");
    return;
  } else {
    console.log(`Ditemukan ${daftarForm.length} form hapus`);
  }
  const dialog = $("#dialog-hapus");
  const bisaDialog = dialog && typeof dialog.showModal === "function";
  let formMenunggu = null; // form yang sedang menunggu jawaban
  daftarForm.forEach((form) => {
    form.addEventListener("submit", (event) => {
      event.preventDefault(); // tahan pengiriman form
      const nama = form.dataset.nama ?? "siswa ini";
      if (!bisaDialog) {
        // Cadangan untuk browser lama
        if (confirm(`Yakin hapus ${nama}?`)) form.submit();
        return;
      }
      formMenunggu = form;
      $("[data-dialog-nama]", dialog).textContent = nama; // textContent: aman dari XSS
      dialog.showModal();
    });
  });
  if (!bisaDialog) return;
  dialog.addEventListener("click", (event) => {
    const tombol = event.target.closest("button");
    if (!tombol) return;
    dialog.close();
    if (tombol.value === "hapus" && formMenunggu) {
      formMenunggu.submit(); // submit() tidak memicu event 'submit' lagi, jadi tidak berulang
    }
    formMenunggu = null;
  });
}

// ===== 4. VALIDASI FORM SISWA =====
const POLA_EMAIL = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
// Menghitung karakter seperti mb_strlen() di PHP (emoji dihitung 1)
const panjang = (teks) => [...teks].length;
// Mengembalikan objek { namaKolom: 'pesan error' }; objek kosong berarti valid
// helper
function validasiSiswa(data) {
  const errors = {};
  if (data.nama === "") {
    errors.nama = "Nama wajib diisi.";
  } else if (panjang(data.nama) > 10) {
    errors.nama = "Nama maksimal 10 karakter.";
  }
  if (data.email !== "") {
    if (!POLA_EMAIL.test(data.email)) {
      errors.email = "Format email tidak valid.";
    } else if (panjang(data.email) > 20) {
      errors.email = "Email maksimal 20 karakter.";
    }
  } else {
    errors.email = "Email wajib diisi.";
  }
  if (!["L", "P"].includes(data.jenis_kelamin)) {
    errors.jenis_kelamin = "Jenis kelamin harus L atau P.";
  }
  if (data.tanggal_lahir !== "") {
    // Tanggal lokal format 'YYYY-MM-DD' (toISOString() memakai UTC sehingga bisa mundur 1 hari di WIB)
    const hariIni = new Date().toLocaleDateString("sv-SE");
    if (!/^\d{4}-\d{2}-\d{2}$/.test(data.tanggal_lahir)) {
      errors.tanggal_lahir = "Tanggal lahir tidak valid (format YYYY-MM-DD).";
    } else if (data.tanggal_lahir > hariIni) {
      errors.tanggal_lahir = "Tanggal lahir tidak boleh di masa depan.";
    }
  }
  return errors;
}

function initValidasiSiswa() {
  const form = document.querySelector("#form-siswa");
  if (!form) {
    console.warn("Form siswa tidak ditemukan");
    return;
  }
  console.log("Form siswa ditemukan, validasi diaktifkan");
  const ambilData = () => ({
    nama: form.nama.value.trim(),
    email: form.email.value.trim(),
    jenis_kelamin: form.jenis_kelamin.value,
    tanggal_lahir: form.tanggal_lahir.value,
  });
  const tampilkanError = (kolom, pesan) => {
    const input = form.elements[kolom];
    const wadah = $(`[data-error-untuk="${kolom}"]`, form);
    input.classList.toggle("is-invalid", Boolean(pesan));
    input.setAttribute("aria-invalid", pesan ? "true" : "false");
    if (wadah) wadah.textContent = pesan ?? "";
  };

  const periksa = (hanyaKolom = null) => {
    const errors = validasiSiswa(ambilData());
    const kolomDiperiksa = hanyaKolom
      ? [hanyaKolom]
      : ["nama", "email", "jenis_kelamin", "tanggal_lahir"];
    kolomDiperiksa.forEach((k) => tampilkanError(k, errors[k]));
    return errors;
  };
  // Periksa satu kolom ketika pengguna selesai mengisinya
  ["nama", "email", "jenis_kelamin", "tanggal_lahir"].forEach((kolom) => {
    form.elements[kolom].addEventListener("blur", () => periksa(kolom));
  });
  // Periksa semua kolom saat form dikirim
  form.addEventListener("submit", (event) => {
    console.log("Form siswa dikirim, periksa semua kolom");
    const errors = periksa();
    const kolomSalah = Object.keys(errors);
    if (kolomSalah.length > 0) {
      event.preventDefault(); // jangan kirim ke server
      form.elements[kolomSalah[0]].focus(); // arahkan ke kolom salah pertama
    }
  });
  // Penghitung karakter nama
  const nama = form.nama;
  const counter = $('[data-counter-untuk="nama"]', form);
  if (counter) {
    const perbarui = () => {
      const n = panjang(nama.value);
      counter.textContent = `${n}/10`;
      counter.style.color = n > 9 ? "var(--warna-bahaya)" : "";
    };
    nama.addEventListener("input", perbarui);
    perbarui(); // tampilkan saat halaman edit dibuka
  }
}

// ===== 5. VALIDASI RENTANG FILTER =====
function pasangRentang(idDari, idSampai, pesan, ubahNilai = (v) => v) {
  const dari = $(`#${idDari}`);
  const sampai = $(`#${idSampai}`);
  if (!dari || !sampai) return;
  const cek = () => {
    const a = dari.value;
    const b = sampai.value;
    const salah = a !== "" && b !== "" && ubahNilai(a) > ubahNilai(b);
    sampai.setCustomValidity(salah ? pesan : ""); // '' berarti valid
  };
  dari.addEventListener("input", cek);
  sampai.addEventListener("input", cek);
  dari.form.addEventListener("submit", (event) => {
    cek();
    if (!sampai.form.checkValidity()) {
      event.preventDefault();
      sampai.reportValidity(); // tampilkan balon pesan bawaan browser
    }
  });
}

function initValidasiRentang() {
  // Tanggal format YYYY-MM-DD bisa dibandingkan sebagai teks
  pasangRentang(
    "tanggal_dari",
    "tanggal_sampai",
    "Tanggal akhir harus setelah tanggal awal.",
  );
  // Nilai harus diubah ke angka; jika tidak, '9' > '10' bernilai true
  pasangRentang(
    "nilai_dari",
    "nilai_sampai",
    "Nilai maksimum harus ≥ nilai minimum.",
    Number,
  );
}

// ===== 6. PENCARIAN LANGSUNG =====
async function ambilSaran(kata, signal) {
  const url = `api_siswa.php?q=${encodeURIComponent(kata)}`;
  const res = await fetch(url, { signal }); // tunggu jawaban server
  if (!res.ok) throw new Error(`HTTP ${res.status}`); // 404/500 tidak otomatis dianggap error
  return res.json(); // ubah teks JSON menjadi array
}

function tampilkanSaran(ul, daftar) {
  ul.replaceChildren(); // kosongkan isi lama
  daftar.forEach((siswa) => {
    const li = document.createElement("li");
    const a = document.createElement("a");
    a.href = `detail.php?id=${encodeURIComponent(siswa.id_siswa)}`;
    a.textContent = siswa.nama; // aman: tidak ditafsirkan sebagai HTML
    if (siswa.email) {
      const kecil = document.createElement("small");
      kecil.textContent = ` — ${siswa.email}`;
      a.append(kecil);
    }
    li.append(a);
    ul.append(li);
  });
}

function initPencarianLangsung() {
  const input = $("#q");
  const ul = $("#saran-siswa");
  if (!input || !ul) return;
  let pengendali = null; // AbortController untuk membatalkan request lama
  const cari = debounce(async () => {
    const kata = input.value.trim();
    if (kata.length < 2) {
      ul.replaceChildren();
      return;
    }
    pengendali?.abort(); // batalkan request sebelumnya jika masih berjalan
    pengendali = new AbortController();
    try {
      const daftar = await ambilSaran(kata, pengendali.signal);
      tampilkanSaran(ul, daftar);
    } catch (err) {
      if (err.name !== "AbortError") {
        console.error("Gagal memuat saran:", err);
        ul.replaceChildren();
      }
    }
  }, 300);
  input.addEventListener("input", cari);
  // Tutup daftar saran dengan Esc atau ketika klik di luar
  input.addEventListener("keydown", (event) => {
    if (event.key === "Escape") ul.replaceChildren();
  });
  document.addEventListener("click", (event) => {
    if (!event.target.closest(".cari-wrap")) ul.replaceChildren();
  });
}

// ===== 7. TABEL RAPOR =====
function initTabelRapor() {
  const tabel = $("#tabel-rapor");
  if (!tabel) return;
  const tbody = tabel.tBodies[0];
  // Abaikan baris "Tidak ada nilai..." yang hanya punya 1 sel
  const baris = $$("tr", tbody).filter((tr) => tr.cells.length === 3);
  if (baris.length === 0) return;
  // --- Ringkasan nilai ---
  const nilai = baris.map((tr) => Number(tr.cells[2].textContent));
  const total = nilai.reduce((jumlah, n) => jumlah + n, 0);
  const ringkasan = [
    ["Rata-rata", (total / nilai.length).toFixed(2)],
    ["Tertinggi", Math.max(...nilai)],
    ["Terendah", Math.min(...nilai)],
    [
      "Tuntas (≥ 75)",
      `${nilai.filter((n) => n >= 75).length} dari ${nilai.length}`,
    ],
  ];
  const kotak = document.createElement("div");
  kotak.className = "ringkasan";
  ringkasan.forEach(([label, angka]) => {
    // destructuring array [label, angka]
    const item = document.createElement("div");
    item.className = "ringkasan__item";
    const besar = document.createElement("span");
    besar.className = "ringkasan__angka";
    besar.textContent = angka;
    const kecil = document.createElement("span");
    kecil.className = "ringkasan__label";
    kecil.textContent = label;
    item.append(besar, kecil);
    kotak.append(item);
  });
  tabel.closest(".tabel-wrap").before(kotak); // sisipkan di atas tabel
  // --- Urutkan saat judul kolom diklik ---
  const judul = $$("th", tabel.tHead);
  const kolomAngka = [true, false, true]; // Semester, Mata Pelajaran, Nilai
  judul.forEach((th, indeks) => {
    th.classList.add("bisa-urut");
    th.tabIndex = 0; // bisa difokus dengan keyboard
    const urutkan = () => {
      const naik = th.getAttribute("aria-sort") !== "ascending";
      const ambil = (tr) => tr.cells[indeks].textContent.trim();
      baris.sort((a, b) => {
        const hasil = kolomAngka[indeks]
          ? Number(ambil(a)) - Number(ambil(b))
          : ambil(a).localeCompare(ambil(b), "id");
        return naik ? hasil : -hasil;
      });
      judul.forEach((h) => h.removeAttribute("aria-sort"));
      th.setAttribute("aria-sort", naik ? "ascending" : "descending");
      tbody.append(...baris); // memindahkan baris yang sudah ada sesuai urutan baru
    };

    th.addEventListener("click", urutkan);
    th.addEventListener("keydown", (event) => {
      if (event.key === "Enter" || event.key === " ") {
        event.preventDefault();
        urutkan();
      }
    });
  });
}

// ===== JALANKAN =====
initTema();
initPesan();
initKonfirmasiHapus();
// validasiSiswa();
initValidasiSiswa();
initValidasiRentang();
initPencarianLangsung();
initTabelRapor();
