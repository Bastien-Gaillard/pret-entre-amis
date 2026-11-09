-- Schéma de la base « Prêts entre amis »
-- Compatible MySQL 8+ et MariaDB 10.5+

SET NAMES utf8mb4;

DROP TABLE IF EXISTS loans;
DROP TABLE IF EXISTS items;
DROP TABLE IF EXISTS friends;

CREATE TABLE friends (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100) NOT NULL,
    email      VARCHAR(255) NULL,
    phone      VARCHAR(30)  NULL,
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_friends_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE items (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(150) NOT NULL,
    category   VARCHAR(50)  NOT NULL DEFAULT 'Autre',
    notes      TEXT         NULL,
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_items_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE loans (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    item_id     INT UNSIGNED NOT NULL,
    friend_id   INT UNSIGNED NOT NULL,
    loaned_at   DATE         NOT NULL,
    due_at      DATE         NULL,
    returned_at DATE         NULL,
    notes       VARCHAR(255) NULL,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

    -- Colonne générée : vaut item_id tant que le prêt est en cours, NULL une fois rendu.
    -- L'index unique dessus garantit, au niveau de la base, qu'un objet
    -- ne peut pas être prêté deux fois en même temps (plusieurs NULL sont autorisés).
    active_item_id INT UNSIGNED AS (IF(returned_at IS NULL, item_id, NULL)) STORED,
    UNIQUE KEY uq_loans_one_active_per_item (active_item_id),

    KEY idx_loans_friend (friend_id),
    KEY idx_loans_status (returned_at, due_at),

    CONSTRAINT fk_loans_item   FOREIGN KEY (item_id)   REFERENCES items (id)   ON DELETE RESTRICT,
    CONSTRAINT fk_loans_friend FOREIGN KEY (friend_id) REFERENCES friends (id) ON DELETE RESTRICT,
    CONSTRAINT chk_loans_due      CHECK (due_at IS NULL OR due_at >= loaned_at),
    CONSTRAINT chk_loans_returned CHECK (returned_at IS NULL OR returned_at >= loaned_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
