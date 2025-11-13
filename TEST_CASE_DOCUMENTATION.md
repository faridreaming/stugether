# Dokumentasi Test Case

## Authentication Tests

| No  | Yang Diuji           | Input                                                                                           | Output Diharapkan                                              | Status |
| --- | -------------------- | ----------------------------------------------------------------------------------------------- | -------------------------------------------------------------- | ------ |
| 1   | Registrasi user baru | `POST /auth/register`<br>`{nama: "Alice", email: "alice@example.com", password: "password123"}` | Status 201, response berisi token                              | ✅     |
| 2   | Login user           | `POST /auth/login`<br>`{email: "alice@example.com", password: "password123"}`                   | Status 200, response berisi token                              | ✅     |
| 3   | Get user info (me)   | `GET /auth/me`<br>Header: `Authorization: Bearer {token}`                                       | Status 200, response berisi data user dengan email yang sesuai | ✅     |

## Forum Tests

| No  | Yang Diuji                        | Input                                                                                                 | Output Diharapkan                                  | Status |
| --- | --------------------------------- | ----------------------------------------------------------------------------------------------------- | -------------------------------------------------- | ------ |
| 4   | Membuat forum baru                | `POST /forums`<br>`{nama: "Private Forum", deskripsi: "Test", jenis_forum: "akademik", is_public: 0}` | Status 201, forum berhasil dibuat                  | ✅     |
| 5   | List forum dengan scope "mine"    | `GET /forums?scope=mine`                                                                              | Status 200, total forum >= 1                       | ✅     |
| 6   | List forum dengan scope "public"  | `GET /forums?scope=public`                                                                            | Status 200, total forum = 0 (karena forum private) | ✅     |
| 7   | Join forum via kode undangan      | `POST /forums/{id}/join`<br>`{kode_undangan: "{kode}"}`                                               | Status 200, user berhasil join forum               | ✅     |
| 8   | Non-admin tidak bisa update forum | `PATCH /forums/{id}`<br>`{nama: "New Name"}`<br>User: non-admin                                       | Status 403, Forbidden                              | ✅     |

## Task (Kanban) Tests

| No  | Yang Diuji                                          | Input                                                                           | Output Diharapkan                                | Status |
| --- | --------------------------------------------------- | ------------------------------------------------------------------------------- | ------------------------------------------------ | ------ |
| 9   | Membuat task baru                                   | `POST /forums/{id}/tasks`<br>`{judul: "First Task", deskripsi: "Do something"}` | Status 201, task berhasil dibuat                 | ✅     |
| 10  | Update status task                                  | `PATCH /tasks/{id}`<br>`{status: "doing"}`                                      | Status 200, status task berhasil diupdate        | ✅     |
| 11  | Membuat reminder untuk task                         | `POST /tasks/{id}/reminder`<br>`{title: "Ping", waktu: "{datetime}"}`           | Status 201, reminder berhasil dibuat             | ✅     |
| 12  | Membuat reminder kedua untuk task yang sama         | `POST /tasks/{id}/reminder`<br>`{title: "Ping again", waktu: "{datetime}"}`     | Status 409, Conflict (task sudah punya reminder) | ✅     |
| 13  | Non-member tidak bisa membuat task di forum private | `POST /forums/{id}/tasks`<br>`{judul: "X"}`<br>User: non-member                 | Status 403, Forbidden                            | ✅     |

## Discussion Tests

| No  | Yang Diuji                        | Input                                                | Output Diharapkan                                             | Status |
| --- | --------------------------------- | ---------------------------------------------------- | ------------------------------------------------------------- | ------ |
| 14  | Membuat diskusi baru              | `POST /forums/{id}/discussions`<br>`{isi: "Hello"}`  | Status 201, diskusi berhasil dibuat                           | ✅     |
| 15  | Membalas diskusi                  | `POST /discussions/{id}/replies`<br>`{isi: "Reply"}` | Status 201, reply berhasil dibuat                             | ✅     |
| 16  | List diskusi dengan threaded view | `GET /forums/{id}/discussions?threaded=true`         | Status 200, response berisi diskusi dengan children (replies) | ✅     |

## Notes Tests

| No  | Yang Diuji                          | Input                                                                                         | Output Diharapkan                     | Status |
| --- | ----------------------------------- | --------------------------------------------------------------------------------------------- | ------------------------------------- | ------ |
| 17  | Membuat catatan baru                | `POST /forums/{id}/notes`<br>`{judul: "Lecture 1", kategori: "math", mata_kuliah: "algebra"}` | Status 201, catatan berhasil dibuat   | ✅     |
| 18  | Filter catatan berdasarkan kategori | `GET /forums/{id}/notes?kategori=math`                                                        | Status 200, total catatan >= 1        | ✅     |
| 19  | Update catatan                      | `PATCH /notes/{id}`<br>`{judul: "Lecture 1 updated"}`                                         | Status 200, catatan berhasil diupdate | ✅     |
| 20  | Hapus catatan                       | `DELETE /notes/{id}`                                                                          | Status 200, catatan berhasil dihapus  | ✅     |

## Media Tests

| No  | Yang Diuji                                   | Input                                                                         | Output Diharapkan                   | Status |
| --- | -------------------------------------------- | ----------------------------------------------------------------------------- | ----------------------------------- | ------ |
| 21  | Upload media dengan file_url                 | `POST /media`<br>`{forum_id: {id}, file_url: "https://example.com/file.pdf"}` | Status 201, media berhasil diupload | ✅     |
| 22  | User lain tidak bisa hapus media milik admin | `DELETE /media/{id}`<br>User: bukan pemilik media                             | Status 403, Forbidden               | ✅     |

## System Health Tests

| No  | Yang Diuji                  | Input | Output Diharapkan                              | Status |
| --- | --------------------------- | ----- | ---------------------------------------------- | ------ |
| 23  | APPPATH sudah didefinisikan | -     | `defined('APPPATH')` return true               | ✅     |
| 24  | BaseURL valid di .env       | -     | BaseURL di .env adalah valid URL               | ✅     |
| 25  | BaseURL valid di config     | -     | BaseURL di app/Config/App.php adalah valid URL | ✅     |

---

**Keterangan:**

- ✅ = Test case sudah diimplementasikan
- Semua test case menggunakan JWT authentication (kecuali register dan login)
- Test case mencakup positive dan negative testing
- Test case mencakup authorization dan permission checks
