-- ============================================================
-- Education Resources Manager — Sample Data
--
-- 10 sample resources with realistic educational content in Spanish.
-- Topics: comunicación, liderazgo, inteligencia emocional, bienestar.
--
-- USAGE:
--   Replace {prefix} with your WordPress table prefix (default: wp_).
--   Run AFTER activating the plugin (tables must already exist).
--   Safe to re-run: INSERT IGNORE skips duplicate post slugs via
--   the UNIQUE KEY on post_name if the rows already exist.
--
-- Post IDs start at 9001 to avoid collisions with real content.
-- Adjust post_author (1) to a valid user ID in your installation.
-- ============================================================

-- ── Step 1: Insert WordPress posts ────────────────────────────────────────────

INSERT INTO `{prefix}posts`
    (ID, post_author, post_date, post_date_gmt,
     post_content, post_title, post_excerpt,
     post_status, comment_status, ping_status,
     post_name, post_type,
     post_modified, post_modified_gmt,
     to_ping, pinged, post_content_filtered,
     guid, post_mime_type, comment_count, post_parent, menu_order)
VALUES

-- 1. Comunicación No Violenta
(9001, 1, '2025-09-01 09:00:00', '2025-09-01 09:00:00',
 'Este curso introduce los cuatro componentes de la Comunicación No Violenta (CNV) desarrollada por Marshall Rosenberg: observación, sentimiento, necesidad y petición. A través de ejercicios prácticos aprenderás a expresarte con empatía y a escuchar activamente a los demás sin juicios ni críticas.',
 'Introducción a la Comunicación No Violenta',
 'Aprende los fundamentos de la CNV para mejorar tus relaciones personales y profesionales mediante la empatía y la escucha activa.',
 'publish', 'closed', 'closed',
 'introduccion-comunicacion-no-violenta', 'erm_resource',
 '2025-09-01 09:00:00', '2025-09-01 09:00:00',
 '', '', '', '', '', 0, 0, 0),

-- 2. Inteligencia Emocional en el Trabajo
(9002, 1, '2025-09-05 10:00:00', '2025-09-05 10:00:00',
 'La inteligencia emocional (IE) es la capacidad de reconocer, comprender y gestionar nuestras propias emociones y las de los demás. En este video aprenderás las cinco dimensiones del modelo de Daniel Goleman: autoconciencia, autorregulación, motivación, empatía y habilidades sociales, aplicadas al entorno laboral.',
 'Inteligencia Emocional en el Entorno Laboral',
 'Descubre cómo aplicar las cinco dimensiones de la inteligencia emocional para mejorar tu rendimiento y tus relaciones en el trabajo.',
 'publish', 'closed', 'closed',
 'inteligencia-emocional-entorno-laboral', 'erm_resource',
 '2025-09-05 10:00:00', '2025-09-05 10:00:00',
 '', '', '', '', '', 0, 0, 0),

-- 3. Guía de Mindfulness para Principiantes
(9003, 1, '2025-09-10 11:00:00', '2025-09-10 11:00:00',
 'Esta guía en formato eBook te acompaña paso a paso en tu práctica de mindfulness. Incluye meditaciones guiadas de 5 a 20 minutos, técnicas de respiración consciente, ejercicios de body scan y consejos para integrar la atención plena en tu rutina diaria sin necesidad de experiencia previa.',
 'Guía Práctica de Mindfulness para Principiantes',
 'Una guía completa con meditaciones, técnicas de respiración y ejercicios de atención plena diseñada para quienes se inician en la práctica.',
 'publish', 'closed', 'closed',
 'guia-practica-mindfulness-principiantes', 'erm_resource',
 '2025-09-10 11:00:00', '2025-09-10 11:00:00',
 '', '', '', '', '', 0, 0, 0),

-- 4. Liderazgo Auténtico
(9004, 1, '2025-09-15 09:30:00', '2025-09-15 09:30:00',
 'El liderazgo auténtico se basa en la coherencia entre los valores personales y las acciones diarias. Este curso avanzado explora los modelos de liderazgo transformacional y servant leadership, con estudios de caso reales, dinámicas de autoconocimiento y herramientas para construir equipos de alto rendimiento desde la autenticidad.',
 'Liderazgo Auténtico: Lidera desde tus Valores',
 'Curso avanzado para líderes que quieren alinear sus valores personales con su estilo de liderazgo y construir equipos cohesionados.',
 'publish', 'closed', 'closed',
 'liderazgo-autentico-lidera-desde-valores', 'erm_resource',
 '2025-09-15 09:30:00', '2025-09-15 09:30:00',
 '', '', '', '', '', 0, 0, 0),

-- 5. Gestión del Estrés y Resiliencia
(9005, 1, '2025-09-20 10:00:00', '2025-09-20 10:00:00',
 'El estrés crónico afecta la salud física y mental, y reduce la productividad. Este tutorial en video presenta técnicas basadas en evidencia para gestionar el estrés: técnica STOP, respiración 4-7-8, reencuadre cognitivo y construcción de resiliencia. Incluye un plan de acción personalizable de 21 días.',
 'Gestión del Estrés y Construcción de Resiliencia',
 'Tutorial práctico con técnicas basadas en evidencia para reducir el estrés y fortalecer tu resiliencia en el día a día.',
 'publish', 'closed', 'closed',
 'gestion-estres-construccion-resiliencia', 'erm_resource',
 '2025-09-20 10:00:00', '2025-09-20 10:00:00',
 '', '', '', '', '', 0, 0, 0),

-- 6. Escucha Activa y Empatía
(9006, 1, '2025-09-25 11:30:00', '2025-09-25 11:30:00',
 'La escucha activa es mucho más que oír palabras: implica comprender el mensaje completo, incluyendo el componente emocional. Este podcast de seis episodios explora las barreras de la escucha, la empatía como habilidad entrenable, y ejercicios para practicar conversaciones difíciles con presencia plena.',
 'Escucha Activa y Empatía: El Arte de Conectar',
 'Serie de podcast sobre cómo desarrollar la escucha activa y la empatía para conectar de forma genuina con las personas a tu alrededor.',
 'publish', 'closed', 'closed',
 'escucha-activa-empatia-arte-conectar', 'erm_resource',
 '2025-09-25 11:30:00', '2025-09-25 11:30:00',
 '', '', '', '', '', 0, 0, 0),

-- 7. Autoconocimiento y Valores Personales
(9007, 1, '2025-10-01 09:00:00', '2025-10-01 09:00:00',
 'Conocerse a uno mismo es la base de cualquier proceso de crecimiento personal. Este artículo profundiza en herramientas de autoconocimiento como la Rueda de la Vida, el inventario de valores de Schwartz, el modelo DISC y el eneagrama, con guías de aplicación práctica y ejercicios de reflexión.',
 'Autoconocimiento: Herramientas para Descubrirte',
 'Artículo completo con las principales herramientas de autoconocimiento —Rueda de la Vida, DISC, eneagrama— y cómo aplicarlas en tu desarrollo personal.',
 'publish', 'closed', 'closed',
 'autoconocimiento-herramientas-descubrirte', 'erm_resource',
 '2025-10-01 09:00:00', '2025-10-01 09:00:00',
 '', '', '', '', '', 0, 0, 0),

-- 8. Comunicación Asertiva en Equipos
(9008, 1, '2025-10-08 10:00:00', '2025-10-08 10:00:00',
 'La asertividad es la habilidad de expresar opiniones, necesidades y límites de manera clara y respetuosa. Este curso intermedio incluye módulos sobre los estilos de comunicación (pasivo, agresivo, pasivo-agresivo y asertivo), técnicas como el disco rayado y el banco de niebla, y simulaciones de situaciones reales en equipos de trabajo.',
 'Comunicación Asertiva en Equipos de Trabajo',
 'Aprende a expresar tus ideas y límites con claridad y respeto. Curso con simulaciones prácticas para equipos que quieren mejorar su dinámica comunicativa.',
 'publish', 'closed', 'closed',
 'comunicacion-asertiva-equipos-trabajo', 'erm_resource',
 '2025-10-08 10:00:00', '2025-10-08 10:00:00',
 '', '', '', '', '', 0, 0, 0),

-- 9. Bienestar Integral: Cuerpo, Mente y Emoción
(9009, 1, '2025-10-15 11:00:00', '2025-10-15 11:00:00',
 'El bienestar integral abarca las dimensiones física, mental, emocional y social de la persona. Este eBook avanzado reúne investigaciones actuales sobre neurociencia del bienestar, el rol del sueño, la nutrición y el movimiento en la salud emocional, y propone un modelo práctico de autocuidado sostenible para profesionales.',
 'Bienestar Integral: Cuerpo, Mente y Emoción',
 'eBook avanzado que integra neurociencia, psicología positiva y hábitos saludables en un modelo práctico de bienestar sostenible para profesionales.',
 'publish', 'closed', 'closed',
 'bienestar-integral-cuerpo-mente-emocion', 'erm_resource',
 '2025-10-15 11:00:00', '2025-10-15 11:00:00',
 '', '', '', '', '', 0, 0, 0),

-- 10. Resolución de Conflictos con Mediación
(9010, 1, '2025-10-22 09:30:00', '2025-10-22 09:30:00',
 'Los conflictos son inevitables en cualquier organización; lo que marca la diferencia es cómo se gestionan. Este curso intermedio enseña el modelo de mediación colaborativa en cinco etapas: apertura, narración, identificación de intereses, generación de opciones y acuerdo. Incluye casos prácticos y role-plays descargables.',
 'Resolución de Conflictos mediante Mediación Colaborativa',
 'Curso práctico con el modelo de mediación en cinco etapas para transformar conflictos en oportunidades de crecimiento dentro de equipos y organizaciones.',
 'publish', 'closed', 'closed',
 'resolucion-conflictos-mediacion-colaborativa', 'erm_resource',
 '2025-10-22 09:30:00', '2025-10-22 09:30:00',
 '', '', '', '', '', 0, 0, 0);


-- ── Step 2: Insert resource metadata ──────────────────────────────────────────

INSERT INTO `{prefix}erm_resource_meta`
    (post_id, resource_url, resource_type, difficulty_level,
     duration_minutes, download_count, is_featured, meta_json,
     created_at, updated_at)
VALUES

-- 1. Comunicación No Violenta — course, beginner, featured
(9001,
 'https://global-authenticity.com/recursos/comunicacion-no-violenta',
 'course', 'beginner',
 90, 312, 1,
 '{"author":"Marshall Rosenberg","language":"es","certificate":true}',
 '2025-09-01 09:00:00', '2025-09-01 09:00:00'),

-- 2. Inteligencia Emocional — video, intermediate
(9002,
 'https://global-authenticity.com/recursos/inteligencia-emocional-trabajo',
 'video', 'intermediate',
 45, 178, 0,
 '{"author":"Daniel Goleman","language":"es","subtitles":["es","en"]}',
 '2025-09-05 10:00:00', '2025-09-05 10:00:00'),

-- 3. Mindfulness — ebook, beginner, featured
(9003,
 'https://global-authenticity.com/recursos/guia-mindfulness-principiantes.pdf',
 'ebook', 'beginner',
 60, 541, 1,
 '{"pages":72,"format":"PDF","language":"es","printable":true}',
 '2025-09-10 11:00:00', '2025-09-10 11:00:00'),

-- 4. Liderazgo Auténtico — course, advanced, featured
(9004,
 'https://global-authenticity.com/recursos/liderazgo-autentico',
 'course', 'advanced',
 240, 89, 1,
 '{"modules":8,"author":"Global Authenticity","language":"es","certificate":true}',
 '2025-09-15 09:30:00', '2025-09-15 09:30:00'),

-- 5. Gestión del Estrés — tutorial (video), intermediate
(9005,
 'https://global-authenticity.com/recursos/gestion-estres-resiliencia',
 'tutorial', 'intermediate',
 35, 203, 0,
 '{"includes_workbook":true,"language":"es","plan_days":21}',
 '2025-09-20 10:00:00', '2025-09-20 10:00:00'),

-- 6. Escucha Activa — podcast, beginner
(9006,
 'https://global-authenticity.com/recursos/podcast-escucha-activa',
 'podcast', 'beginner',
 120, 97, 0,
 '{"episodes":6,"avg_duration_min":20,"language":"es","rss":"https://global-authenticity.com/feed/podcast"}',
 '2025-09-25 11:30:00', '2025-09-25 11:30:00'),

-- 7. Autoconocimiento — article, beginner
(9007,
 'https://global-authenticity.com/recursos/autoconocimiento-herramientas',
 'article', 'beginner',
 20, 445, 0,
 '{"reading_level":"general","language":"es","tools":["Rueda de la Vida","DISC","Eneagrama","Schwartz"]}',
 '2025-10-01 09:00:00', '2025-10-01 09:00:00'),

-- 8. Comunicación Asertiva — course, intermediate
(9008,
 'https://global-authenticity.com/recursos/comunicacion-asertiva-equipos',
 'course', 'intermediate',
 150, 134, 0,
 '{"modules":5,"language":"es","includes_roleplays":true,"certificate":false}',
 '2025-10-08 10:00:00', '2025-10-08 10:00:00'),

-- 9. Bienestar Integral — ebook, advanced
(9009,
 'https://global-authenticity.com/recursos/bienestar-integral.pdf',
 'ebook', 'advanced',
 180, 66, 0,
 '{"pages":148,"format":"PDF","language":"es","references":true,"printable":false}',
 '2025-10-15 11:00:00', '2025-10-15 11:00:00'),

-- 10. Resolución de Conflictos — course, intermediate, featured
(9010,
 'https://global-authenticity.com/recursos/resolucion-conflictos-mediacion',
 'course', 'intermediate',
 120, 258, 1,
 '{"modules":5,"language":"es","includes_roleplays":true,"certificate":true}',
 '2025-10-22 09:30:00', '2025-10-22 09:30:00');


-- ── Step 3: Assign taxonomy terms (optional) ──────────────────────────────────
--
-- Run these only if the erm_category terms already exist in your installation.
-- Replace term_taxonomy_id values with real IDs from your wp_term_taxonomy table.
--
-- Example (uncomment and adjust IDs as needed):
--
-- INSERT INTO `{prefix}term_relationships` (object_id, term_taxonomy_id) VALUES
--   (9001, 1),   -- Comunicación
--   (9002, 2),   -- Inteligencia Emocional
--   (9003, 3),   -- Mindfulness
--   (9004, 4),   -- Liderazgo
--   (9005, 5),   -- Bienestar
--   (9006, 1),   -- Comunicación
--   (9007, 6),   -- Autoconocimiento
--   (9008, 1),   -- Comunicación
--   (9009, 5),   -- Bienestar
--   (9010, 1);   -- Comunicación
