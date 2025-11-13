```mermaid
erDiagram
    users {
        INT user_id PK
        VARCHAR nim
        VARCHAR nama
        VARCHAR kelas
        INT semester
        VARCHAR email UK
        VARCHAR password
        DATETIME created_at
        DATETIME updated_at
    }

    forums {
        INT forum_id PK
        INT admin_id FK
        VARCHAR nama
        TEXT deskripsi
        VARCHAR kode_undangan UK
        ENUM jenis_forum
        TINYINT is_public
        DATETIME created_at
        DATETIME updated_at
    }

    anggota_forum {
        INT anggota_id PK
        INT forum_id FK
        INT user_id FK
        BOOLEAN allowed_upload
        DATETIME joined_at
    }

    kanbans {
        INT kanban_id PK
        INT forum_id FK
        INT created_by FK
        VARCHAR judul
        TEXT deskripsi
        DATETIME tenggat_waktu
        VARCHAR file_url
        ENUM status
        DATETIME created_at
        DATETIME updated_at
    }

    reminders {
        INT reminder_id PK
        INT kanban_id FK UK
        INT user_id FK
        VARCHAR title
        DATETIME waktu
        DATETIME created_at
    }

    discussions {
        INT discussion_id PK
        INT forum_id FK
        INT user_id FK
        INT parent_id FK
        TEXT isi
        DATETIME created_at
    }

    notes {
        INT note_id PK
        INT forum_id FK
        INT user_id FK
        VARCHAR judul
        VARCHAR kategori
        VARCHAR mata_kuliah
        TEXT deskripsi
        DATETIME created_at
    }

    media {
        INT media_id PK
        INT user_id FK
        INT forum_id FK
        INT note_id FK
        INT ref_id
        VARCHAR file_url
        DATETIME created_at
    }

    user_forum_seen {
        INT user_id PK,FK
        INT forum_id PK,FK
        DATETIME last_seen_at
    }

    %% Relationships
    users ||--o{ forums : "admin_id"
    users ||--o{ anggota_forum : "user_id"
    users ||--o{ kanbans : "created_by"
    users ||--o{ reminders : "user_id"
    users ||--o{ discussions : "user_id"
    users ||--o{ notes : "user_id"
    users ||--o{ media : "user_id"
    users ||--o{ user_forum_seen : "user_id"

    forums ||--o{ anggota_forum : "forum_id"
    forums ||--o{ kanbans : "forum_id"
    forums ||--o{ discussions : "forum_id"
    forums ||--o{ notes : "forum_id"
    forums ||--o{ media : "forum_id"
    forums ||--o{ user_forum_seen : "forum_id"

    kanbans ||--o| reminders : "kanban_id"

    discussions ||--o{ discussions : "parent_id"

    notes ||--o{ media : "note_id"

    anggota_forum ||--o{ media : "forum_id,user_id"
```
