# Dokumentasi API Endpoint

## Authentication

| Endpoint | Method | Deskripsi |
|----------|--------|-----------|
| `/auth/register` | POST | Registrasi user baru |
| `/auth/login` | POST | Login user dan mendapatkan JWT token |
| `/auth/logout` | POST | Logout user (stateless) |
| `/auth/me` | GET | Mendapatkan informasi user yang sedang login |

## Users

| Endpoint | Method | Deskripsi |
|----------|--------|-----------|
| `/users/{id}` | GET | Menampilkan detail user berdasarkan ID |
| `/users/{id}` | PUT | Update profil user (hanya user sendiri) |

## Forums

| Endpoint | Method | Deskripsi |
|----------|--------|-----------|
| `/forums` | POST | Membuat forum baru |
| `/forums` | GET | Mendapatkan daftar forum |
| `/forums/recommended` | GET | Mendapatkan daftar forum yang direkomendasikan |
| `/forums/{id}` | GET | Menampilkan detail forum berdasarkan ID |
| `/forums/{id}` | PATCH | Update forum (hanya admin forum) |
| `/forums/{id}` | DELETE | Hapus forum (hanya admin forum) |

## Forum Members

| Endpoint | Method | Deskripsi |
|----------|--------|-----------|
| `/forums/{id}/join` | POST | Bergabung ke dalam forum |
| `/forums/{id}/leave` | POST | Keluar dari forum (hanya anggota forum) |
| `/forums/{id}/members` | GET | Mendapatkan daftar anggota forum |
| `/forums/{id}/members/{member_id}` | PATCH | Update status anggota forum (hanya admin forum) |

## Tasks (Kanban)

| Endpoint | Method | Deskripsi |
|----------|--------|-----------|
| `/forums/{id}/tasks` | POST | Membuat task baru di forum (hanya anggota forum) |
| `/forums/{id}/tasks` | GET | Mendapatkan daftar task di forum |
| `/tasks/{id}` | GET | Menampilkan detail task berdasarkan ID |
| `/tasks/{id}` | PATCH | Update task |
| `/tasks/{id}` | DELETE | Hapus task |
| `/tasks/{id}/attachments` | POST | Upload attachment ke task (hanya anggota forum) |

## Reminders

| Endpoint | Method | Deskripsi |
|----------|--------|-----------|
| `/tasks/{id}/reminder` | POST | Membuat reminder untuk task |
| `/reminders` | GET | Mendapatkan daftar reminder user |
| `/reminders/{id}` | DELETE | Hapus reminder |

## Discussions

| Endpoint | Method | Deskripsi |
|----------|--------|-----------|
| `/forums/{id}/discussions` | POST | Membuat diskusi baru di forum (hanya anggota forum) |
| `/discussions/{id}/replies` | POST | Membalas diskusi (hanya anggota forum) |
| `/forums/{id}/discussions` | GET | Mendapatkan daftar diskusi di forum |
| `/discussions/{id}` | GET | Menampilkan detail diskusi berdasarkan ID |
| `/discussions/{id}` | PATCH | Update diskusi |
| `/discussions/{id}` | DELETE | Hapus diskusi |

## Notes

| Endpoint | Method | Deskripsi |
|----------|--------|-----------|
| `/forums/{id}/notes` | POST | Membuat catatan baru di forum (hanya anggota forum) |
| `/forums/{id}/notes` | GET | Mendapatkan daftar catatan di forum |
| `/notes/{id}` | GET | Menampilkan detail catatan berdasarkan ID |
| `/notes/{id}` | PATCH | Update catatan |
| `/notes/{id}` | DELETE | Hapus catatan |

## Media

| Endpoint | Method | Deskripsi |
|----------|--------|-----------|
| `/media` | POST | Upload media/file |
| `/forums/{id}/media` | GET | Mendapatkan daftar media di forum |
| `/media/{id}` | GET | Menampilkan detail media berdasarkan ID |
| `/media/{id}` | DELETE | Hapus media |

## Search

| Endpoint | Method | Deskripsi |
|----------|--------|-----------|
| `/search` | GET | Pencarian global (forum, task, discussion, notes) |

## Notifications

| Endpoint | Method | Deskripsi |
|----------|--------|-----------|
| `/notifications` | GET | Mendapatkan daftar notifikasi user |

## Documentation

| Endpoint | Method | Deskripsi |
|----------|--------|-----------|
| `/docs` | GET | Dokumentasi API (Swagger) |

---

**Catatan:**
- Semua endpoint kecuali `/auth/register` dan `/auth/login` memerlukan JWT token di header Authorization
- `{id}` dan `{member_id}` adalah parameter path yang harus diganti dengan ID aktual
- Filter tambahan seperti `forumAdmin` dan `forumMember` memerlukan permission khusus

