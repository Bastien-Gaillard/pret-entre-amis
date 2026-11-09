-- Données de démonstration (dates relatives à aujourd'hui pour toujours avoir des retards visibles)

INSERT INTO friends (name, email, phone) VALUES
    ('Camille', 'camille@example.com', '06 12 34 56 78'),
    ('Julien',  'julien@example.com',  NULL),
    ('Sarah',   NULL,                  '07 98 76 54 32'),
    ('Thomas',  'thomas@example.com',  NULL);

INSERT INTO items (name, category, notes) VALUES
    ('Perceuse visseuse Bosch',      'Bricolage', 'Avec la mallette et 2 batteries'),
    ('Le Seigneur des Anneaux (T1)', 'Livre',     NULL),
    ('Tente 3 places',               'Camping',   'Vérifier les sardines au retour'),
    ('Catan',                        'Jeu',       'Extension marins incluse'),
    ('Escabeau 5 marches',           'Bricolage', NULL),
    ('Dune',                         'Livre',     'Édition poche'),
    ('Appareil à raclette',          'Cuisine',   '8 poêlons');

INSERT INTO loans (item_id, friend_id, loaned_at, due_at, returned_at, notes) VALUES
    -- en retard
    (1, 2, CURDATE() - INTERVAL 30 DAY, CURDATE() - INTERVAL 9 DAY,  NULL, 'Pour monter une cuisine'),
    (3, 1, CURDATE() - INTERVAL 20 DAY, CURDATE() - INTERVAL 2 DAY,  NULL, NULL),
    -- en cours
    (2, 3, CURDATE() - INTERVAL 5 DAY,  CURDATE() + INTERVAL 25 DAY, NULL, NULL),
    (4, 4, CURDATE() - INTERVAL 2 DAY,  NULL,                        NULL, 'Pas de date de retour fixée'),
    -- rendus
    (5, 2, CURDATE() - INTERVAL 60 DAY, CURDATE() - INTERVAL 50 DAY, CURDATE() - INTERVAL 52 DAY, NULL),
    (7, 1, CURDATE() - INTERVAL 45 DAY, CURDATE() - INTERVAL 40 DAY, CURDATE() - INTERVAL 38 DAY, NULL),
    (6, 2, CURDATE() - INTERVAL 90 DAY, CURDATE() - INTERVAL 60 DAY, CURDATE() - INTERVAL 55 DAY, NULL);
